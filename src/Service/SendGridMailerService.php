<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class SendGridMailerService
{
    private string $apiKey;
    private string $fromEmail;
    private string $fromName;

    public function __construct(
        private HttpClientInterface $httpClient
    ) {
        $this->apiKey = $_ENV['SENDGRID_API_KEY'] ?? '';
        $this->fromEmail = $_ENV['SENDGRID_FROM_EMAIL'] ?? '';
        $this->fromName = $_ENV['SENDGRID_FROM_NAME'] ?? 'FitSense';
    }

    public function sendPasswordResetEmail(string $toEmail, string $toName, string $resetUrl, string $expiresIn): bool
    {
        try {
            $htmlContent = $this->getEmailTemplate($toName, $resetUrl, $expiresIn);
            
            $payload = [
                'personalizations' => [
                    [
                        'to' => [
                            [
                                'email' => $toEmail,
                                'name' => $toName,
                            ],
                        ],
                        'subject' => 'Reset Your FitSense Password',
                    ],
                ],
                'from' => [
                    'email' => $this->fromEmail,
                    'name' => $this->fromName,
                ],
                'content' => [
                    [
                        'type' => 'text/html',
                        'value' => $htmlContent,
                    ],
                ],
            ];
            
            $response = $this->httpClient->request('POST', 'https://api.sendgrid.com/v3/mail/send', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => $payload,
            ]);

            $statusCode = $response->getStatusCode();

            if ($statusCode !== 202) {
                error_log("SendGrid returned status " . $statusCode . ": " . $response->getContent(false));
                return false;
            }

            return true;
        } catch (\Exception $e) {
            error_log('SendGrid error: ' . $e->getMessage());
            return false;
        }
    }

    public function sendEmail(string $toEmail, string $subject, string $htmlContent): bool
    {
        try {
            $payload = [
                'personalizations' => [
                    [
                        'to' => [
                            ['email' => $toEmail],
                        ],
                        'subject' => $subject,
                    ],
                ],
                'from' => [
                    'email' => $this->fromEmail,
                    'name' => $this->fromName,
                ],
                'content' => [
                    [
                        'type' => 'text/html',
                        'value' => $htmlContent,
                    ],
                ],
            ];
            
            $response = $this->httpClient->request('POST', 'https://api.sendgrid.com/v3/mail/send', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => $payload,
            ]);

            return $response->getStatusCode() === 202;
        } catch (\Exception $e) {
            error_log('SendGrid error: ' . $e->getMessage());
            return false;
        }
    }

    private function getEmailTemplate(string $userName, string $resetUrl, string $expiresIn): string
    {
        return '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f4f4f4;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f4f4f4; padding: 40px 20px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 8px;">
                    <tr>
                        <td style="padding: 40px; text-align: center; background-color: #8B5CF6; border-radius: 8px 8px 0 0;">
                            <h1 style="margin: 0; font-size: 28px; color: #ffffff;">FitSense</h1>
                            <p style="margin: 10px 0 0; color: #ffffff; font-size: 14px;">Password Reset Request</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 40px;">
                            <p style="margin: 0 0 20px; color: #333333; font-size: 16px;">Hello ' . htmlspecialchars($userName) . ',</p>
                            <p style="margin: 0 0 20px; color: #666666; font-size: 14px;">We received a request to reset your password. Click the button below to reset it:</p>
                            <table width="100%" cellpadding="0" cellspacing="0" style="margin: 30px 0;">
                                <tr>
                                    <td align="center">
                                        <a href="' . htmlspecialchars($resetUrl) . '" style="display: inline-block; padding: 14px 30px; background-color: #8B5CF6; color: #ffffff; text-decoration: none; border-radius: 6px; font-weight: bold; font-size: 16px;">Reset Password</a>
                                    </td>
                                </tr>
                            </table>
                            <p style="margin: 20px 0; padding: 15px; background-color: #fff3cd; border: 1px solid #ffc107; border-radius: 6px; color: #856404; font-size: 13px;">This link expires in ' . htmlspecialchars($expiresIn) . '</p>
                            <p style="margin: 20px 0 0; color: #999999; font-size: 13px;">If you did not request this password reset, please ignore this email.</p>
                            <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #eeeeee;">
                                <p style="margin: 0 0 10px; color: #999999; font-size: 12px;">Or copy and paste this link into your browser:</p>
                                <p style="margin: 0; word-break: break-all; color: #8B5CF6; font-size: 12px;">' . htmlspecialchars($resetUrl) . '</p>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td style="background-color: #f8f9fa; padding: 20px; text-align: center; border-radius: 0 0 8px 8px;">
                            <p style="margin: 0; color: #999999; font-size: 12px;">© 2026 FitSense. All rights reserved.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>';
    }
}
