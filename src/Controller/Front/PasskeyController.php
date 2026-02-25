<?php

namespace App\Controller\Front;

use App\Entity\PasskeyCredential;
use App\Entity\User;
use App\Service\PasskeySessionStorage;
use Doctrine\ORM\EntityManagerInterface;
use lbuchs\WebAuthn\Binary\ByteBuffer;
use lbuchs\WebAuthn\WebAuthn;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;
use App\Security\AppAuthenticator;

#[Route('/passkey')]
class PasskeyController extends AbstractController
{
    /**
     * Phone: page opened when user scans QR code (run WebAuthn getAssertion here).
     */
    #[Route('/connect', name: 'passkey_connect', methods: ['GET'])]
    public function connect(Request $request): Response
    {
        $sessionId = (string) $request->query->get('session', '');
        return $this->render('passkey/connect.html.twig', [
            'sessionId' => $sessionId,
            'baseUrl' => $this->getQrUrl($request),
        ]);
    }
    public function __construct(
        private PasskeySessionStorage $sessionStorage,
        private EntityManagerInterface $entityManager,
        private UserAuthenticatorInterface $userAuthenticator,
        private AppAuthenticator $appAuthenticator
    ) {
    }

    /**
     * Browser: start Face ID login → get session ID and QR URL.
     */
    #[Route('/login/start', name: 'passkey_login_start', methods: ['POST'])]
    public function startLogin(Request $request): JsonResponse
    {
        $rpId = $this->getRpId($request);
        $sessionId = bin2hex(random_bytes(16));

        \lbuchs\WebAuthn\Binary\ByteBuffer::$useBase64UrlEncoding = true;
        $webAuthn = new WebAuthn('FitSense', $rpId, ['none', 'packed', 'apple', 'android-key', 'fido-u2f'], true);

        $getArgs = $webAuthn->getGetArgs([], 120, true, true, true, true, true, 'required');
        $challenge = $webAuthn->getChallenge();
        $challengeBase64 = base64_encode($challenge->getBinaryString());
        $optionsJson = json_encode($getArgs);

        $this->sessionStorage->createSession($sessionId, $challengeBase64, $optionsJson);

        $qrUrl = $this->getQrUrl($request) . $this->generateUrl('passkey_connect', ['session' => $sessionId]);

        return new JsonResponse([
            'sessionId' => $sessionId,
            'qrUrl' => $qrUrl,
        ]);
    }

    /**
     * Phone: get WebAuthn getAssertion options for this session.
     */
    #[Route('/connect/options', name: 'passkey_connect_options', methods: ['GET'])]
    public function connectOptions(Request $request): JsonResponse
    {
        $sessionId = (string) $request->query->get('session', '');
        if ($sessionId === '') {
            return new JsonResponse(['error' => 'Missing session'], Response::HTTP_BAD_REQUEST);
        }

        $optionsJson = $this->sessionStorage->getOptionsJson($sessionId);
        if ($optionsJson === null) {
            return new JsonResponse(['error' => 'Session expired or invalid'], Response::HTTP_GONE);
        }

        return new JsonResponse(json_decode($optionsJson, true));
    }

    /**
     * Phone: verify assertion (Face ID result) and mark session as authenticated.
     */
    #[Route('/connect/verify', name: 'passkey_connect_verify', methods: ['POST'])]
    public function verifyAssertion(Request $request): JsonResponse
    {
        $sessionId = (string) $request->request->get('sessionId', $request->request->get('session', ''));
        if ($sessionId === '') {
            return new JsonResponse(['success' => false, 'error' => 'Missing session'], Response::HTTP_BAD_REQUEST);
        }

        $challengeBase64 = $this->sessionStorage->getChallenge($sessionId);
        if ($challengeBase64 === null) {
            return new JsonResponse(['success' => false, 'error' => 'Session expired'], Response::HTTP_GONE);
        }

        $id = $request->request->get('id');
        $clientDataJSON = $request->request->get('clientDataJSON');
        $authenticatorData = $request->request->get('authenticatorData');
        $signature = $request->request->get('signature');
        $userHandle = $request->request->get('userHandle');

        if (!$id || !$clientDataJSON || !$authenticatorData || !$signature) {
            return new JsonResponse(['success' => false, 'error' => 'Missing assertion data'], Response::HTTP_BAD_REQUEST);
        }

        $credential = $this->entityManager->getRepository(PasskeyCredential::class)
            ->findOneBy(['credentialId' => $id]);
        if (!$credential) {
            return new JsonResponse(['success' => false, 'error' => 'Unknown credential'], Response::HTTP_BAD_REQUEST);
        }

        \lbuchs\WebAuthn\Binary\ByteBuffer::$useBase64UrlEncoding = true;
        $webAuthn = new WebAuthn('FitSense', $this->getRpId($request), ['none', 'packed', 'apple', 'android-key', 'fido-u2f'], true);

        $clientDataJSONBin = $this->base64UrlDecode($clientDataJSON);
        $authenticatorDataBin = $this->base64UrlDecode($authenticatorData);
        $signatureBin = $this->base64UrlDecode($signature);
        $challengeBin = base64_decode($challengeBase64);
        if ($challengeBin === false) {
            $challengeBin = $this->base64UrlDecode($challengeBase64);
        }

        try {
            $webAuthn->processGet(
                $clientDataJSONBin,
                $authenticatorDataBin,
                $signatureBin,
                $credential->getCredentialPublicKey(),
                $challengeBin,
                $credential->getSignatureCounter(),
                true,
                true
            );
        } catch (\Throwable $e) {
            return new JsonResponse(['success' => false, 'error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        $newSignCount = $webAuthn->getSignatureCounter();
        if ($newSignCount !== null) {
            $credential->setSignatureCounter($newSignCount);
            $this->entityManager->flush();
        }

        $user = $credential->getUser();
        if ($user->getAccountStatus() === 'inactive') {
            $adminEmail = $this->getAdminEmail();
            return new JsonResponse([
                'success' => false,
                'error' => 'account_deactivated',
                'adminEmail' => $adminEmail,
            ], Response::HTTP_FORBIDDEN);
        }
        $this->sessionStorage->markAuthenticated($sessionId, $user->getId());

        return new JsonResponse(['success' => true, 'email' => $user->getUserIdentifier()]);
    }

    /**
     * Browser: poll login status.
     */
    #[Route('/login/status', name: 'passkey_login_status', methods: ['GET'])]
    public function loginStatus(Request $request): JsonResponse
    {
        $sessionId = (string) $request->query->get('session', '');
        if ($sessionId === '') {
            return new JsonResponse(['status' => 'expired'], Response::HTTP_BAD_REQUEST);
        }

        $data = $this->sessionStorage->getSession($sessionId);
        if ($data === null) {
            return new JsonResponse(['status' => 'expired']);
        }

        if (!empty($data['userId'])) {
            return new JsonResponse(['status' => 'authenticated', 'userId' => $data['userId']]);
        }

        return new JsonResponse(['status' => 'pending']);
    }

    /**
     * Browser: confirm login with session → log user in and redirect.
     */
    #[Route('/login/confirm', name: 'passkey_login_confirm', methods: ['GET'])]
    public function confirmLogin(Request $request): Response
    {
        $sessionId = (string) $request->query->get('session', '');
        if ($sessionId === '') {
            return $this->redirectToRoute('auth_sign_in');
        }

        $data = $this->sessionStorage->getSession($sessionId);
        if ($data === null || empty($data['userId'])) {
            return $this->redirectToRoute('auth_sign_in');
        }

        $user = $this->entityManager->getRepository(User::class)->find($data['userId']);
        if (!$user) {
            return $this->redirectToRoute('auth_sign_in');
        }
        if ($user->getAccountStatus() === 'inactive') {
            $request->getSession()->set('deactivated_admin_email', $this->getAdminEmail());
            return $this->redirectToRoute('account_deactivated');
        }

        return $this->userAuthenticator->authenticateUser($user, $this->appAuthenticator, $request);
    }

    /**
     * Registration: get create options (for phone, first-time Face ID setup).
     */
    #[Route('/register/options', name: 'passkey_register_options', methods: ['GET'])]
    public function registerOptions(Request $request): JsonResponse
    {
        $email = (string) $request->query->get('email', '');
        if ($email === '') {
            return new JsonResponse(['error' => 'Missing email'], Response::HTTP_BAD_REQUEST);
        }

        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
        if (!$user) {
            return new JsonResponse(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        $rpId = $this->getRpId($request);
        error_log('Register options - RP ID: ' . $rpId . ', Host: ' . $request->getHost() . ', Origin: ' . $request->headers->get('origin', 'N/A'));
        \lbuchs\WebAuthn\Binary\ByteBuffer::$useBase64UrlEncoding = true;
        $webAuthn = new WebAuthn('FitSense', $rpId, ['none', 'packed', 'apple', 'android-key', 'fido-u2f'], true);

        $existingIds = [];
        foreach ($user->getPasskeyCredentials() as $cred) {
            $existingIds[] = ByteBuffer::fromBase64Url($cred->getCredentialId());
        }

        $createArgs = $webAuthn->getCreateArgs(
            (string) $user->getId(),
            $user->getUserIdentifier(),
            $user->getFirstname() . ' ' . $user->getLastname(),
            120,
            'preferred',
            'required',
            null,
            $existingIds
        );

        $challenge = $webAuthn->getChallenge();
        $request->getSession()->set('passkey_register_challenge', base64_encode($challenge->getBinaryString()));
        $request->getSession()->set('passkey_register_user_id', $user->getId());

        return new JsonResponse($createArgs);
    }

    /**
     * Registration: verify attestation and store credential.
     */
    #[Route('/register/verify', name: 'passkey_register_verify', methods: ['POST'])]
    public function registerVerify(Request $request): JsonResponse
    {
        $challengeBase64 = $request->getSession()->get('passkey_register_challenge');
        $userId = $request->getSession()->get('passkey_register_user_id');
        if (!$challengeBase64 || !$userId) {
            return new JsonResponse(['success' => false, 'error' => 'Session expired'], Response::HTTP_BAD_REQUEST);
        }

        $clientDataJSON = $request->request->get('clientDataJSON');
        $attestationObject = $request->request->get('attestationObject');
        if (!$clientDataJSON || !$attestationObject) {
            return new JsonResponse(['success' => false, 'error' => 'Missing data'], Response::HTTP_BAD_REQUEST);
        }

        $user = $this->entityManager->getRepository(User::class)->find($userId);
        if (!$user) {
            return new JsonResponse(['success' => false, 'error' => 'User not found'], Response::HTTP_BAD_REQUEST);
        }

        $clientDataJSONBin = $this->base64UrlDecode($clientDataJSON);
        $attestationObjectBin = $this->base64UrlDecode($attestationObject);
        $challengeBin = base64_decode($challengeBase64) ?: $this->base64UrlDecode($challengeBase64);

        \lbuchs\WebAuthn\Binary\ByteBuffer::$useBase64UrlEncoding = true;
        $rpId = $this->getRpId($request);
        error_log('Register verify - RP ID: ' . $rpId . ', Host: ' . $request->getHost() . ', Origin: ' . $request->headers->get('origin', 'N/A'));
        error_log('Register verify - Challenge from session: ' . ($challengeBase64 ? 'present' : 'missing'));
        error_log('Register verify - User ID from session: ' . ($userId ?? 'missing'));
        $webAuthn = new WebAuthn('FitSense', $rpId, ['none', 'packed', 'apple', 'android-key', 'fido-u2f'], true);

        try {
            $data = $webAuthn->processCreate($clientDataJSONBin, $attestationObjectBin, $challengeBin, true, true, false);
            error_log('Register verify - processCreate succeeded');
        } catch (\Throwable $e) {
            // Log detailed error for debugging
            error_log('Passkey registration error: ' . $e->getMessage());
            error_log('RP ID used: ' . $rpId);
            error_log('Request host: ' . $request->getHost());
            error_log('Request scheme: ' . $request->getScheme());
            error_log('Request origin: ' . $request->headers->get('origin', 'N/A'));
            error_log('Exception class: ' . get_class($e));
            error_log('Exception trace: ' . $e->getTraceAsString());
            
            // Return more detailed error message
            $errorMsg = $e->getMessage();
            if (str_contains($errorMsg, 'invalid origin')) {
                $errorMsg .= ' (RP ID: ' . $rpId . ', Origin: ' . ($request->headers->get('origin') ?? 'N/A') . ')';
            }
            
            return new JsonResponse(['success' => false, 'error' => $errorMsg], Response::HTTP_BAD_REQUEST);
        }

        try {
            $credIdRaw = $data->credentialId;
            // Credential ID from the library can be ByteBuffer or raw binary string - always encode as base64url for DB (UTF-8 safe)
            if ($credIdRaw instanceof ByteBuffer) {
                $credentialIdStr = $this->binaryToBase64Url($credIdRaw->getBinaryString());
            } else {
                $credentialIdStr = $this->binaryToBase64Url((string) $credIdRaw);
            }

            if (empty($credentialIdStr)) {
                throw new \RuntimeException('Credential ID is empty after processing');
            }

            $credential = new PasskeyCredential();
            $credential->setUser($user);
            $credential->setCredentialId($credentialIdStr);
            $credential->setCredentialPublicKey($data->credentialPublicKey);
            $credential->setSignatureCounter($data->signatureCounter ?? 0);
            $this->entityManager->persist($credential);
            $this->entityManager->flush();
            
            error_log('Register verify - Credential saved successfully with ID: ' . substr($credentialIdStr, 0, 20) . '...');
        } catch (\Throwable $e) {
            error_log('Error saving credential: ' . $e->getMessage());
            error_log('Exception trace: ' . $e->getTraceAsString());
            return new JsonResponse(['success' => false, 'error' => 'Failed to save credential: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $request->getSession()->remove('passkey_register_challenge');
        $request->getSession()->remove('passkey_register_user_id');

        return new JsonResponse(['success' => true]);
    }

    private function getRpId(Request $request): string
    {
        $host = $request->getHost();
        
        // For ngrok domains, use the ngrok domain as rpId
        if (str_contains($host, 'ngrok-free.app') || str_contains($host, 'ngrok-free.dev') || str_contains($host, 'ngrok.io')) {
            return $host;
        }
        
        // For localhost, keep as localhost
        if ($host === 'localhost' || $host === '127.0.0.1') {
            return 'localhost';
        }
        
        // For other domains (including IP addresses), return as-is
        return $host;
    }

    /**
     * Get the URL to use in QR codes. Replaces localhost/127.0.0.1 with network IP.
     * Supports HTTPS URLs (e.g. ngrok) via PASSKEY_QR_HOST environment variable.
     */
    private function getQrUrl(Request $request): string
    {
        // Check if a full HTTPS URL is configured (e.g. ngrok)
        $envHost = $_ENV['PASSKEY_QR_HOST'] ?? $_SERVER['PASSKEY_QR_HOST'] ?? null;
        if ($envHost) {
            // If it's a full URL (starts with http:// or https://), use it directly
            if (str_starts_with($envHost, 'http://') || str_starts_with($envHost, 'https://')) {
                return rtrim($envHost, '/');
            }
            // Otherwise treat it as hostname and use HTTPS if it's not localhost
            if ($envHost !== 'localhost' && $envHost !== '127.0.0.1') {
                return 'https://' . $envHost;
            }
        }

        $scheme = $request->getScheme();
        $host = $request->getHost();
        $port = $request->getPort();

        // If using localhost/127.0.0.1, try to get the network IP address
        if ($host === 'localhost' || $host === '127.0.0.1') {
            // Try to get local network IP
            $localIp = $this->getLocalNetworkIp();
            if ($localIp) {
                $host = $localIp;
            } else {
                // Last resort: keep localhost but warn (won't work from phone)
                $host = 'localhost';
            }
        }

        // Build URL with port if not standard
        $url = $scheme . '://' . $host;
        if (($scheme === 'http' && $port !== 80) || ($scheme === 'https' && $port !== 443)) {
            $url .= ':' . $port;
        }

        return $url;
    }

    /**
     * Try to detect the local network IP address.
     */
    private function getLocalNetworkIp(): ?string
    {
        // First check environment variable (most reliable)
        $envIp = $_ENV['PASSKEY_QR_HOST'] ?? $_SERVER['PASSKEY_QR_HOST'] ?? null;
        if ($envIp && filter_var($envIp, FILTER_VALIDATE_IP) !== false) {
            return $envIp;
        }

        // Try to detect automatically
        $commands = [
            // Windows PowerShell (more reliable)
            'powershell -Command "(Get-NetIPAddress -AddressFamily IPv4 | Where-Object {$_.IPAddress -notlike \'127.*\' -and $_.IPAddress -notlike \'169.254.*\'}).IPAddress | Select-Object -First 1"',
            // Windows ipconfig
            'ipconfig | findstr /i "IPv4"',
            // Linux/Mac
            "hostname -I 2>/dev/null | awk '{print $1}'",
            "ip route get 1 2>/dev/null | awk '{print $7; exit}'",
            "ifconfig 2>/dev/null | grep 'inet ' | grep -v '127.0.0.1' | awk '{print $2}' | head -1",
        ];

        foreach ($commands as $cmd) {
            $output = @shell_exec($cmd);
            if ($output) {
                // Extract all IPs from output
                if (preg_match_all('/(\d+\.\d+\.\d+\.\d+)/', $output, $matches)) {
                    foreach ($matches[1] as $ip) {
                        $ip = trim($ip);
                        // Skip localhost and link-local addresses
                        if ($ip !== '127.0.0.1' 
                            && !str_starts_with($ip, '169.254.')
                            && filter_var($ip, FILTER_VALIDATE_IP) !== false) {
                            return $ip;
                        }
                    }
                }
            }
        }

        return null;
    }

    private function getAdminEmail(): string
    {
        $admin = $this->entityManager->getRepository(User::class)
            ->createQueryBuilder('u')
            ->where('u.roles LIKE :role')
            ->setParameter('role', '%ROLE_ADMIN%')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
        return $admin instanceof User ? (string) $admin->getEmail() : '';
    }

    private function base64UrlDecode(string $s): string
    {
        $bin = base64_decode(strtr($s, '-_', '+/') . str_repeat('=', (4 - (strlen($s) % 4)) % 4), true);
        return $bin !== false ? $bin : '';
    }

    private function binaryToBase64Url(string $bin): string
    {
        return rtrim(strtr(base64_encode($bin), '+/', '-_'), '=');
    }
}
