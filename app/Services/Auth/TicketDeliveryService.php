<?php

namespace App\Services\Auth;

use App\Contracts\UserRepositoryInterface;
use App\Jobs\SendTicketReadyMailJob;
use App\Mail\TicketReadyMail;
use App\Services\Tickets\TicketQrCodeService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use RuntimeException;

class TicketDeliveryService
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly TicketQrCodeService $ticketQrCodeService,
    ) {
    }

    public function sendIfNeeded(string $userId, array $user, array $ticket): array
    {
        $ticketReadyEmailSentAt = $user['ticket_ready_email_sent_at'] ?? null;
        $ticketReadyEmailQueuedAt = $user['ticket_ready_email_queued_at'] ?? null;
        $ticketUrl = $this->ticketQrCodeService->signedTicketUrl((string) $ticket['ticket_id']);
        $ticketQrUrl = $this->ticketQrCodeService->signedTicketQrUrl((string) $ticket['ticket_id']);

        if (! empty($ticketReadyEmailSentAt)) {
            return $this->buildResult($user, 'already_sent', true, $ticketUrl, $ticketQrUrl);
        }

        if ($this->usesRedisQueueMode()) {
            if (! empty($ticketReadyEmailQueuedAt)) {
                return $this->buildResult($user, 'queued', true, $ticketUrl, $ticketQrUrl);
            }

            return $this->queueDelivery($userId, $user, $ticket, $ticketUrl, $ticketQrUrl);
        }

        return $this->deliverSynchronously($userId, $user, $ticket, $ticketUrl, $ticketQrUrl);
    }

    public function deliverQueuedTicketReadyEmail(string $userId): void
    {
        $user = $this->users->findById($userId);

        if ($user === null) {
            Log::warning('Queued ticket delivery skipped because user was not found.', [
                'user_id' => $userId,
            ]);

            return;
        }

        if (! empty($user['ticket_ready_email_sent_at'] ?? null)) {
            return;
        }

        $ticket = $this->users->findTicketByUserId($userId);

        if ($ticket === null) {
            Log::warning('Queued ticket delivery skipped because ticket was not found.', [
                'user_id' => $userId,
            ]);

            return;
        }

        $ticketUrl = $this->ticketQrCodeService->signedTicketUrl((string) $ticket['ticket_id']);
        $ticketQrUrl = $this->ticketQrCodeService->signedTicketQrUrl((string) $ticket['ticket_id']);

        $this->deliverSynchronously($userId, $user, $ticket, $ticketUrl, $ticketQrUrl, true);
    }

    private function queueDelivery(
        string $userId,
        array $user,
        array $ticket,
        string $ticketUrl,
        string $ticketQrUrl,
    ): array {
        $this->guardRedisQueueConfiguration();

        $queuedAt = now()->toISOString();
        $updatedUser = $this->users->update($userId, [
            'ticket_ready_email_queued_at' => $queuedAt,
            'ticket_ready_email_failed_at' => null,
            'ticket_ready_email_last_error' => null,
        ]);

        try {
            SendTicketReadyMailJob::dispatch($userId)
                ->onConnection('redis')
                ->onQueue((string) config('registration.email_queue', 'registration-emails'));
        } catch (\Throwable $throwable) {
            $this->users->update($userId, [
                'ticket_ready_email_queued_at' => null,
                'ticket_ready_email_failed_at' => now()->toISOString(),
                'ticket_ready_email_last_error' => $this->truncateErrorMessage($throwable->getMessage()),
            ]);

            Log::warning('Ticket ready email queue dispatch failed', [
                'user_id' => $userId,
                'email' => $user['email'] ?? null,
                'ticket_id' => $ticket['ticket_id'] ?? null,
                'error' => $throwable->getMessage(),
            ]);

            throw $throwable;
        }

        return $this->buildResult($updatedUser, 'queued', true, $ticketUrl, $ticketQrUrl);
    }

    private function deliverSynchronously(
        string $userId,
        array $user,
        array $ticket,
        string $ticketUrl,
        string $ticketQrUrl,
        bool $rethrowOnFailure = false,
    ): array {
        try {
            $qrPngBinary = $this->ticketQrCodeService->renderPngBinary(
                $this->ticketQrCodeService->payloadForTicket($ticket),
                240,
            );

            Mail::to($user['email'])->send(
                new TicketReadyMail($user, $ticket, $ticketUrl, $qrPngBinary)
            );
        } catch (\Throwable $throwable) {
            $failedUser = $this->users->update($userId, [
                'ticket_ready_email_failed_at' => now()->toISOString(),
                'ticket_ready_email_last_error' => $this->truncateErrorMessage($throwable->getMessage()),
            ]);

            Log::warning('Ticket ready email delivery failed', [
                'user_id' => $userId,
                'email' => $user['email'] ?? null,
                'ticket_id' => $ticket['ticket_id'] ?? null,
                'error' => $throwable->getMessage(),
            ]);

            if ($rethrowOnFailure) {
                throw $throwable;
            }

            return $this->buildResult($failedUser, 'failed', false, $ticketUrl, $ticketQrUrl);
        }

        $updatedUser = $this->users->update($userId, [
            'ticket_ready_email_sent_at' => now()->toISOString(),
            'ticket_ready_email_failed_at' => null,
            'ticket_ready_email_last_error' => null,
        ]);

        return $this->buildResult($updatedUser, 'sent', true, $ticketUrl, $ticketQrUrl);
    }

    private function buildResult(
        array $user,
        string $status,
        bool $emailSent,
        string $ticketUrl,
        string $ticketQrUrl,
    ): array {
        return [
            'user' => $user,
            'delivery' => [
                'status' => $status,
                'email_sent' => $emailSent,
                'ticket_url' => $ticketUrl,
                'ticket_qr_url' => $ticketQrUrl,
            ],
        ];
    }

    private function usesRedisQueueMode(): bool
    {
        return (string) config('registration.redis_mode', 'disabled') === 'required';
    }

    private function guardRedisQueueConfiguration(): void
    {
        if ((string) config('queue.default', 'sync') !== 'redis') {
            throw new RuntimeException('Registration Redis mode requires QUEUE_CONNECTION=redis.');
        }
    }

    private function truncateErrorMessage(string $message): string
    {
        return mb_strimwidth(trim($message), 0, 1000, '');
    }
}
