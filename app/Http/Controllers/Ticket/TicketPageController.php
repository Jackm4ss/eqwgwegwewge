<?php

namespace App\Http\Controllers\Ticket;

use App\Contracts\UserRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Services\Tickets\TicketQrCodeService;

class TicketPageController extends Controller
{
    private const COUNTRY_NAMES = [
        'AU' => 'Australia',
        'BN' => 'Brunei',
        'KH' => 'Cambodia',
        'CN' => 'China',
        'FR' => 'France',
        'DE' => 'Germany',
        'HK' => 'Hong Kong',
        'IN' => 'India',
        'ID' => 'Indonesia',
        'JP' => 'Japan',
        'LA' => 'Laos',
        'MY' => 'Malaysia',
        'MM' => 'Myanmar',
        'NL' => 'Netherlands',
        'NZ' => 'New Zealand',
        'PH' => 'Philippines',
        'SG' => 'Singapore',
        'KR' => 'South Korea',
        'TH' => 'Thailand',
        'AE' => 'UAE',
        'GB' => 'United Kingdom',
        'US' => 'United States',
        'VN' => 'Vietnam',
    ];

    public function __invoke(
        string $ticketId,
        UserRepositoryInterface $users,
        TicketQrCodeService $ticketQrCodeService,
    ) {
        $ticket = $users->findTicketById($ticketId);
        abort_if(! $ticket, 404);

        $user = $users->findById((string) $ticket['user_id']);
        abort_if(! $user, 404);

        $qrSvg = $ticketQrCodeService->renderSvg(
            $ticketQrCodeService->payloadForTicket($ticket),
            320,
        );

        $countryCode = strtoupper((string) ($user['country'] ?? ''));
        $countryName = self::COUNTRY_NAMES[$countryCode] ?? ($countryCode !== '' ? $countryCode : '-');
        $countryFlagUrl = isset(self::COUNTRY_NAMES[$countryCode])
            ? asset('assets-vuexy/vendor/fonts/flags/4x3/'.strtolower($countryCode).'.svg')
            : null;

        return view('tickets.show', [
            'ticket' => $ticket,
            'user' => $user,
            'qrSvg' => $qrSvg,
            'qrDownloadUrl' => $ticketQrCodeService->signedTicketDownloadUrl($ticketId),
            'countryName' => $countryName,
            'countryFlagUrl' => $countryFlagUrl,
        ]);
    }

    public function download(
        string $ticketId,
        UserRepositoryInterface $users,
        TicketQrCodeService $ticketQrCodeService,
    ) {
        $ticket = $users->findTicketById($ticketId);
        abort_if(! $ticket, 404);

        $downloadCard = $ticketQrCodeService->renderDownloadCardJpeg(
            $ticketQrCodeService->payloadForTicket($ticket),
            (string) ($ticket['entry_code_display'] ?? ''),
        );

        $filename = 'songkran-ticket-'.strtolower((string) ($ticket['ticket_code'] ?? $ticketId)).'.jpg';

        return response($downloadCard, 200, [
            'Content-Type' => 'image/jpeg',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    public function qr(
        string $ticketId,
        UserRepositoryInterface $users,
        TicketQrCodeService $ticketQrCodeService,
    ) {
        $ticket = $users->findTicketById($ticketId);
        abort_if(! $ticket, 404);

        $qrPng = $ticketQrCodeService->renderPngBinary(
            $ticketQrCodeService->payloadForTicket($ticket),
            320,
        );

        $filename = 'songkran-ticket-'.strtolower((string) ($ticket['ticket_code'] ?? $ticketId)).'.png';

        return response($qrPng, 200, [
            'Content-Type' => 'image/png',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
            'Cache-Control' => 'private, no-store, max-age=0',
        ]);
    }
}
