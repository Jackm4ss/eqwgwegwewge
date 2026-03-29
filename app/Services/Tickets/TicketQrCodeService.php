<?php

namespace App\Services\Tickets;

use BaconQrCode\Renderer\GDLibRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use RuntimeException;

class TicketQrCodeService
{
    public function makeTicketAttributes(string $userId): array
    {
        $now = now()->toISOString();
        $entryCode = $this->generateEntryCode();

        return [
            'ticket_id' => (string) Str::ulid(),
            'user_id' => $userId,
            'event_code' => (string) config('event.code', 'SONGKRAN2026'),
            'ticket_code' => strtoupper((string) Str::ulid()),
            'entry_code' => $entryCode,
            'entry_code_display' => $this->formatEntryCodeDisplay($entryCode),
            'status' => 'active',
            'qr_version' => 'v1',
            'activated_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }

    public function regenerateTicketAttributes(array $ticket): array
    {
        $version = (string) ($ticket['qr_version'] ?? 'v1');
        $numericVersion = (int) preg_replace('/\D+/', '', $version);
        $nextVersion = $numericVersion > 0 ? $numericVersion + 1 : 2;
        $now = now()->toISOString();
        $entryCode = $this->generateEntryCode();

        return array_merge($ticket, [
            'ticket_code' => strtoupper((string) Str::ulid()),
            'entry_code' => $entryCode,
            'entry_code_display' => $this->formatEntryCodeDisplay($entryCode),
            'qr_version' => 'v'.$nextVersion,
            'status' => 'active',
            'attendance_status' => 'not_checked_in',
            'checked_in_at' => null,
            'last_scanned_at' => null,
            'regenerated_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function resetAttendanceAttributes(array $ticket): array
    {
        $now = now()->toISOString();

        return array_merge($ticket, [
            'status' => 'active',
            'attendance_status' => 'not_checked_in',
            'checked_in_at' => null,
            'last_scanned_at' => null,
            'qr_reset_at' => $now,
            'qr_reset_count' => ((int) ($ticket['qr_reset_count'] ?? 0)) + 1,
            'updated_at' => $now,
        ]);
    }

    public function payloadForTicket(array $ticket): string
    {
        return $this->payloadForTicketCode((string) $ticket['ticket_code']);
    }

    public function payloadForTicketCode(string $ticketCode): string
    {
        return sprintf(
            'esf1:%s:%s',
            $ticketCode,
            $this->signTicketCode($ticketCode),
        );
    }

    public function renderSvg(string $payload, int $size = 320): string
    {
        $renderer = new ImageRenderer(
            new RendererStyle($size, 2),
            new SvgImageBackEnd
        );

        return (new Writer($renderer))->writeString($payload);
    }

    public function renderPngBinary(string $payload, int $size = 320): string
    {
        $renderer = new GDLibRenderer($size, 2, 'png');

        return (new Writer($renderer))->writeString($payload);
    }

    public function renderJpegBinary(string $payload, int $size = 320, int $quality = 90): string
    {
        $renderer = new GDLibRenderer($size, 2, 'jpeg', $quality);

        return (new Writer($renderer))->writeString($payload);
    }

    public function renderDownloadCardJpeg(
        string $payload,
        string $entryCodeDisplay = '',
        int $qrSize = 320,
        int $quality = 90,
    ): string {
        $qrPng = $this->renderPngBinary($payload, $qrSize);
        $qrImage = imagecreatefromstring($qrPng);

        if (! is_resource($qrImage) && ! $qrImage instanceof \GdImage) {
            throw new RuntimeException('Unable to generate QR image for download card.');
        }

        $canvasWidth = 520;
        $canvasHeight = trim($entryCodeDisplay) === '' ? 440 : 620;
        $canvas = imagecreatetruecolor($canvasWidth, $canvasHeight);

        if (! is_resource($canvas) && ! $canvas instanceof \GdImage) {
            imagedestroy($qrImage);

            throw new RuntimeException('Unable to create download card canvas.');
        }

        $white = imagecolorallocate($canvas, 255, 255, 255);
        $headingColor = imagecolorallocate($canvas, 82, 82, 82);
        $textColor = imagecolorallocate($canvas, 17, 17, 17);
        $mutedColor = imagecolorallocate($canvas, 64, 64, 64);
        $dividerColor = imagecolorallocate($canvas, 217, 217, 217);

        imagefill($canvas, 0, 0, $white);

        $qrX = (int) floor(($canvasWidth - imagesx($qrImage)) / 2);
        $qrY = 36;
        imagecopy(
            $canvas,
            $qrImage,
            $qrX,
            $qrY,
            0,
            0,
            imagesx($qrImage),
            imagesy($qrImage),
        );

        $fontPath = $this->resolveDownloadCardFontPath();

        if (trim($entryCodeDisplay) !== '') {
            imageline($canvas, 46, 388, $canvasWidth - 46, 388, $dividerColor);

            if ($fontPath !== null) {
                $this->drawCenteredTtfText($canvas, 12, 426, 'ENTRY CODE', $headingColor, $fontPath);
                $this->drawCenteredTtfText($canvas, 18, 462, strtoupper($entryCodeDisplay), $textColor, $fontPath);

                $helpLines = $this->wrapTtfText(
                    'Use this code for manual lookup if your QR cannot be scanned at the gate.',
                    12,
                    $fontPath,
                    $canvasWidth - 84,
                );

                $helpY = 500;

                foreach ($helpLines as $line) {
                    $this->drawCenteredTtfText($canvas, 12, $helpY, $line, $mutedColor, $fontPath);
                    $helpY += 22;
                }
            } else {
                $this->drawCenteredText($canvas, 3, 410, 'ENTRY CODE', $headingColor);
                $this->drawCenteredText($canvas, 5, 440, strtoupper($entryCodeDisplay), $textColor);

                $helpLines = $this->wrapCardText(
                    'Use this code for manual lookup if your QR cannot be scanned at the gate.',
                    3,
                    $canvasWidth - 80,
                );

                $helpY = 490;

                foreach ($helpLines as $line) {
                    $this->drawCenteredText($canvas, 3, $helpY, $line, $mutedColor, bold: true);
                    $helpY += 24;
                }
            }
        }

        ob_start();
        imagejpeg($canvas, null, $quality);
        $binary = (string) ob_get_clean();

        imagedestroy($qrImage);
        imagedestroy($canvas);

        return $binary;
    }

    public function signedTicketUrl(string $ticketId): string
    {
        return URL::signedRoute('ticket.show', ['ticketId' => $ticketId]);
    }

    public function signedTicketDownloadUrl(string $ticketId): string
    {
        return URL::signedRoute('ticket.download', ['ticketId' => $ticketId]);
    }

    public function signedTicketQrUrl(string $ticketId): string
    {
        return URL::signedRoute('ticket.qr', ['ticketId' => $ticketId]);
    }

    public function signTicketCode(string $ticketCode): string
    {
        $key = $this->resolveAppKey();
        $signature = hash_hmac('sha256', $ticketCode, $key, true);

        return rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');
    }

    private function resolveAppKey(): string
    {
        $appKey = (string) config('app.key');

        if ($appKey === '') {
            throw new RuntimeException('APP_KEY is not configured.');
        }

        if (str_starts_with($appKey, 'base64:')) {
            $decoded = base64_decode(substr($appKey, 7), true);

            if ($decoded === false) {
                throw new RuntimeException('APP_KEY base64 payload is invalid.');
            }

            return $decoded;
        }

        return $appKey;
    }

    private function generateEntryCode(): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $code = '';

        for ($index = 0; $index < 8; $index++) {
            $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }

        return $code;
    }

    private function formatEntryCodeDisplay(string $entryCode): string
    {
        $normalized = strtoupper(preg_replace('/[^A-Z0-9]/', '', $entryCode) ?? '');

        return substr($normalized, 0, 4).'-'.substr($normalized, 4, 4);
    }

    private function drawCenteredText(
        \GdImage $image,
        int $font,
        int $y,
        string $text,
        int $color,
        bool $bold = false,
    ): void
    {
        $text = trim($text);

        if ($text === '') {
            return;
        }

        $x = (int) floor((imagesx($image) - (imagefontwidth($font) * strlen($text))) / 2);
        $x = max(0, $x);

        if (! $bold) {
            imagestring($image, $font, $x, $y, $text, $color);

            return;
        }

        foreach ([[0, 0], [1, 0], [0, 1], [1, 1]] as [$offsetX, $offsetY]) {
            imagestring($image, $font, $x + $offsetX, $y + $offsetY, $text, $color);
        }
    }

    private function drawCenteredTtfText(
        \GdImage $image,
        float $size,
        int $baselineY,
        string $text,
        int $color,
        string $fontPath,
        bool $bold = false,
    ): void
    {
        $text = trim($text);

        if ($text === '' || ! function_exists('imagettftext')) {
            return;
        }

        $box = imagettfbbox($size, 0, $fontPath, $text);

        if ($box === false) {
            return;
        }

        $textWidth = (int) abs($box[4] - $box[0]);
        $x = max(0, (int) floor((imagesx($image) - $textWidth) / 2));

        if (! $bold) {
            imagettftext($image, $size, 0, $x, $baselineY, $color, $fontPath, $text);

            return;
        }

        foreach ([[0, 0], [1, 0], [0, 1], [1, 1]] as [$offsetX, $offsetY]) {
            imagettftext($image, $size, 0, $x + $offsetX, $baselineY + $offsetY, $color, $fontPath, $text);
        }
    }

    private function wrapCardText(string $text, int $font, int $maxWidth): array
    {
        $maxCharacters = max(1, (int) floor($maxWidth / imagefontwidth($font)));

        return preg_split('/\r\n|\r|\n/', wordwrap($text, $maxCharacters, PHP_EOL, true)) ?: [$text];
    }

    private function wrapTtfText(string $text, float $size, string $fontPath, int $maxWidth): array
    {
        $words = preg_split('/\s+/', trim($text)) ?: [];

        if ($words === []) {
            return [''];
        }

        $lines = [];
        $currentLine = array_shift($words);

        foreach ($words as $word) {
            $candidate = $currentLine.' '.$word;
            $box = imagettfbbox($size, 0, $fontPath, $candidate);
            $candidateWidth = $box === false ? PHP_INT_MAX : (int) abs($box[4] - $box[0]);

            if ($candidateWidth <= $maxWidth) {
                $currentLine = $candidate;
                continue;
            }

            $lines[] = $currentLine;
            $currentLine = $word;
        }

        $lines[] = $currentLine;

        return $lines;
    }

    private function resolveDownloadCardFontPath(): ?string
    {
        if (! function_exists('imagettftext') || ! function_exists('imagettfbbox')) {
            return null;
        }

        $configuredPath = trim((string) env('TICKET_DOWNLOAD_FONT_PATH', ''));
        $candidates = array_filter([
            $configuredPath,
            storage_path('app/fonts/TiltWarp-Regular-VariableFont_XROT,YROT.ttf'),
            'C:\\Windows\\Fonts\\TiltWarp-Regular-VariableFont_XROT,YROT.ttf',
        ]);

        foreach ($candidates as $candidate) {
            $resolvedPath = $this->resolveReadableFontPath($candidate);

            if ($resolvedPath !== null) {
                return $resolvedPath;
            }
        }

        return null;
    }

    private function resolveReadableFontPath(string $candidate): ?string
    {
        $candidate = trim($candidate);

        if ($candidate === '') {
            return null;
        }

        if (is_file($candidate) && is_readable($candidate)) {
            return $candidate;
        }

        $projectRelativePath = base_path($candidate);

        if (is_file($projectRelativePath) && is_readable($projectRelativePath)) {
            return $projectRelativePath;
        }

        return null;
    }
}
