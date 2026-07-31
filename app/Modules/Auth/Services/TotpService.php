<?php

namespace App\Modules\Auth\Services;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use OTPHP\TOTP;

class TotpService
{
    public function generateSecret(int $secretBytes = 20): string
    {
        if (class_exists(TOTP::class)) {
            return TOTP::create()->getSecret();
        }

        return $this->base32Encode(random_bytes($secretBytes));
    }

    public function getIssuer(): string
    {
        return $this->envValue('security.totpIssuer', 'Portal INDE');
    }

    public function provisioningData(string $accountName, string $secret): array
    {
        $issuer = $this->getIssuer();
        $otpauthUri = $this->buildOtpAuthUri($issuer, $accountName, $secret);

        if (class_exists(TOTP::class)) {
            $totp = TOTP::create($secret);
            $totp->setLabel($accountName);
            $totp->setIssuer($issuer);
            $otpauthUri = $totp->getProvisioningUri();
        }

        return [
            'otpauthUri' => $otpauthUri,
            'qrSvg' => $this->makeQrSvg($otpauthUri),
        ];
    }

    public function verifyCode(string $secret, string $code, ?int $window = null): bool
    {
        $sanitizedCode = preg_replace('/\D+/', '', $code);

        if ($sanitizedCode === null || strlen($sanitizedCode) !== 6) {
            return false;
        }

        $configuredWindow = (int) $this->envValue('security.totpLeeway', '2');
        $windowSteps = $window ?? $configuredWindow;
        $windowSteps = max(1, min($windowSteps, 5));

        if (class_exists(TOTP::class)) {
            $totp = TOTP::create($secret);
            $period = $totp->getPeriod();
            $now = time();

            for ($offset = -$windowSteps; $offset <= $windowSteps; $offset++) {
                $candidate = $totp->at($now + ($offset * $period));

                if (hash_equals($candidate, $sanitizedCode)) {
                    return true;
                }
            }

            return false;
        }

        $timeSlice = (int) floor(time() / 30);

        for ($offset = -$windowSteps; $offset <= $windowSteps; $offset++) {
            if (hash_equals($this->calculateTotp($secret, $timeSlice + $offset), $sanitizedCode)) {
                return true;
            }
        }

        return false;
    }

    protected function makeQrSvg(string $contents): ?string
    {
        if (! class_exists(Writer::class)) {
            return null;
        }

        $renderer = new ImageRenderer(new RendererStyle(220), new SvgImageBackEnd());
        $writer = new Writer($renderer);

        return $writer->writeString($contents);
    }

    protected function envValue(string $key, string $default): string
    {
        if (function_exists('env')) {
            return (string) env($key, $default);
        }

        $value = getenv($key);

        return $value === false ? $default : (string) $value;
    }

    protected function buildOtpAuthUri(string $issuer, string $accountName, string $secret): string
    {
        return sprintf(
            'otpauth://totp/%s:%s?secret=%s&issuer=%s&algorithm=SHA1&digits=6&period=30',
            rawurlencode($issuer),
            rawurlencode($accountName),
            rawurlencode($secret),
            rawurlencode($issuer)
        );
    }

    protected function calculateTotp(string $secret, int $timeSlice): string
    {
        $binarySecret = $this->base32Decode($secret);
        $counter = pack('N*', 0) . pack('N*', $timeSlice);
        $hash = hash_hmac('sha1', $counter, $binarySecret, true);
        $offset = ord(substr($hash, -1)) & 0x0F;
        $truncatedHash = (
            ((ord($hash[$offset]) & 0x7F) << 24)
            | ((ord($hash[$offset + 1]) & 0xFF) << 16)
            | ((ord($hash[$offset + 2]) & 0xFF) << 8)
            | (ord($hash[$offset + 3]) & 0xFF)
        );

        return str_pad((string) ($truncatedHash % 1000000), 6, '0', STR_PAD_LEFT);
    }

    protected function base32Encode(string $data): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $binaryString = '';

        foreach (str_split($data) as $character) {
            $binaryString .= str_pad(decbin(ord($character)), 8, '0', STR_PAD_LEFT);
        }

        $encoded = '';

        foreach (str_split($binaryString, 5) as $chunk) {
            $chunk = str_pad($chunk, 5, '0', STR_PAD_RIGHT);
            $encoded .= $alphabet[bindec($chunk)];
        }

        return $encoded;
    }

    protected function base32Decode(string $secret): string
    {
        $alphabet = array_flip(str_split('ABCDEFGHIJKLMNOPQRSTUVWXYZ234567'));
        $sanitizedSecret = strtoupper(str_replace('=', '', $secret));
        $binaryString = '';

        foreach (str_split($sanitizedSecret) as $character) {
            if (! array_key_exists($character, $alphabet)) {
                continue;
            }

            $binaryString .= str_pad(decbin($alphabet[$character]), 5, '0', STR_PAD_LEFT);
        }

        $decoded = '';

        foreach (str_split($binaryString, 8) as $chunk) {
            if (strlen($chunk) < 8) {
                continue;
            }

            $decoded .= chr(bindec($chunk));
        }

        return $decoded;
    }
}