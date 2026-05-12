<?php

namespace App\Controller\Coach;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Route('/coach')]
#[IsGranted('ROLE_COACH')]
class PidevAssetsController extends AbstractController
{
    private string $assetsDir;

    public function __construct()
    {
        $this->assetsDir = 'C:\\xampp2\\htdocs\\pidevassets';
    }

    /**
     * List all images in pidevassets directory, with optional search filter.
     */
    #[Route('/pidevassets/list', name: 'coach_pidevassets_list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $q = strtolower(trim((string) $request->query->get('q', '')));
        $dir = $this->assetsDir;

        if (!is_dir($dir)) {
            return $this->json(['error' => 'Assets directory not found: ' . $dir], 404);
        }

        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg'];
        $images = [];

        $files = scandir($dir);
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') continue;
            
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            if (!in_array($ext, $allowedExtensions)) continue;

            // Apply search filter
            if ($q !== '' && strpos(strtolower($file), $q) === false) continue;

            $images[] = [
                'name' => $file,
                'url'  => $this->generateUrl('coach_pidevassets_serve', ['filename' => $file], UrlGeneratorInterface::ABSOLUTE_URL),
            ];
        }

        // Sort alphabetically
        usort($images, fn($a, $b) => strcasecmp($a['name'], $b['name']));

        return $this->json($images);
    }

    /**
     * Upload a new image to the pidevassets directory.
     */
    #[Route('/pidevassets/upload', name: 'coach_pidevassets_upload', methods: ['POST'])]
    public function upload(Request $request): JsonResponse
    {
        $dir = $this->assetsDir;

        if (!is_dir($dir)) {
            return $this->json(['error' => 'Assets directory not found'], 404);
        }

        $file = $request->files->get('file');
        if (!$file) {
            return $this->json(['error' => 'No file uploaded'], 400);
        }

        $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/bmp', 'image/svg+xml'];
        if (!in_array($file->getMimeType(), $allowedMimes)) {
            return $this->json(['error' => 'Invalid file type. Only images are allowed.'], 400);
        }

        $originalName = $file->getClientOriginalName();
        $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $originalName);

        // Avoid overwriting — add unique suffix if file exists
        $finalName = $safeName;
        if (file_exists($dir . DIRECTORY_SEPARATOR . $safeName)) {
            $name = pathinfo($safeName, PATHINFO_FILENAME);
            $ext  = pathinfo($safeName, PATHINFO_EXTENSION);
            $finalName = $name . '_' . uniqid() . '.' . $ext;
        }

        $file->move($dir, $finalName);

        return $this->json([
            'success' => true,
            'name'    => $finalName,
            'url'     => $this->generateUrl('coach_pidevassets_serve', ['filename' => $finalName], UrlGeneratorInterface::ABSOLUTE_URL),
        ]);
    }

    /**
     * Serve an image file from the pidevassets directory.
     */
    #[Route('/pidevassets/image/{filename}', name: 'coach_pidevassets_serve', methods: ['GET'])]
    public function serve(string $filename): Response
    {
        $dir = $this->assetsDir;
        // Sanitize: prevent directory traversal
        $filename = basename($filename);
        $filePath = $dir . DIRECTORY_SEPARATOR . $filename;

        if (!file_exists($filePath) || !is_file($filePath)) {
            throw $this->createNotFoundException('Image not found');
        }

        $response = new BinaryFileResponse($filePath);
        $response->headers->set('Cache-Control', 'public, max-age=3600');

        return $response;
    }
}
