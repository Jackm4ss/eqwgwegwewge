<?php

namespace Tests\Feature;

use App\Contracts\UserRepositoryInterface;
use App\Services\Tickets\TicketQrCodeService;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Http;
use Tests\Fakes\InMemoryUserRepository;
use Tests\TestCase;

class ForgotQrLookupTest extends TestCase
{
    private InMemoryUserRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ThrottleRequests::class);
        config([
            'services.recaptcha.enabled' => false,
            'services.recaptcha.site_key' => null,
            'services.recaptcha.secret_key' => null,
        ]);

        $this->repository = new InMemoryUserRepository();
        $this->app->instance(UserRepositoryInterface::class, $this->repository);
    }

    public function test_forgot_qr_page_loads_the_spa_shell(): void
    {
        $this->get('/forgot-qr')
            ->assertOk()
            ->assertViewIs('welcome');
    }

    public function test_lookup_by_email_returns_participant_and_signed_ticket_url(): void
    {
        [$user, $ticket] = $this->seedParticipant();

        $this->postJson('/api/forgot-qr/lookup', [
            'search_type' => 'email',
            'email' => $user['email'],
        ])->assertOk()
            ->assertJson([
                'found' => true,
                'participant' => [
                    'full_name' => $user['full_name'],
                    'email' => $user['email'],
                    'phone_country_code' => '+62',
                    'phone_national_number' => '8123456789',
                    'phone_number' => '+628123456789',
                    'country' => 'ID',
                    'identity_type' => 'passport',
                    'identity_number' => 'A1234567',
                    'account_status' => 'active',
                    'verification_status' => 'verified',
                    'ticket_code' => $ticket['ticket_code'],
                    'entry_code_display' => $ticket['entry_code_display'],
                ],
                'ticket_code' => $ticket['ticket_code'],
                'entry_code_display' => $ticket['entry_code_display'],
            ])
            ->assertJsonPath('ticket_url', app(TicketQrCodeService::class)->signedTicketUrl($ticket['ticket_id']));
    }

    public function test_lookup_by_phone_requires_exact_normalized_match(): void
    {
        [$user, $ticket] = $this->seedParticipant();

        $this->postJson('/api/forgot-qr/lookup', [
            'search_type' => 'phone',
            'phone_country_code' => '+62',
            'phone_national_number' => '08123456789',
        ])->assertOk()
            ->assertJson([
                'found' => true,
                'participant' => [
                    'email' => $user['email'],
                    'ticket_code' => $ticket['ticket_code'],
                ],
            ]);

        $this->postJson('/api/forgot-qr/lookup', [
            'search_type' => 'phone',
            'phone_country_code' => '+62',
            'phone_national_number' => '8123456780',
        ])->assertOk()
            ->assertJson([
                'found' => false,
                'message' => 'Data peserta tidak ditemukan.',
            ]);
    }

    public function test_lookup_by_passport_succeeds_with_country_and_identity_number(): void
    {
        [$user, $ticket] = $this->seedParticipant();

        $this->postJson('/api/forgot-qr/lookup', [
            'search_type' => 'passport',
            'country' => 'id',
            'identity_number' => strtolower($user['identity_number']),
        ])->assertOk()
            ->assertJson([
                'found' => true,
                'participant' => [
                    'country' => 'ID',
                    'identity_type' => 'passport',
                    'identity_number' => 'A1234567',
                    'ticket_code' => $ticket['ticket_code'],
                ],
            ]);
    }

    public function test_lookup_by_ic_forces_malaysia_and_succeeds(): void
    {
        [, $ticket] = $this->seedParticipant([
            'country' => 'MY',
            'identity_country' => 'MY',
            'identity_type' => 'national_id',
            'identity_number' => '010203100011',
            'email' => 'mykad@example.test',
            'phone_country_code' => '+60',
            'phone_national_number' => '123456789',
            'phone_number' => '+60123456789',
        ]);

        $this->postJson('/api/forgot-qr/lookup', [
            'search_type' => 'ic',
            'country' => 'ID',
            'identity_number' => '010203100011',
        ])->assertOk()
            ->assertJson([
                'found' => true,
                'participant' => [
                    'country' => 'MY',
                    'identity_type' => 'national_id',
                    'identity_number' => '010203100011',
                    'ticket_code' => $ticket['ticket_code'],
                ],
            ]);
    }

    public function test_lookup_returns_validation_errors_for_invalid_mode_specific_payloads(): void
    {
        $this->postJson('/api/forgot-qr/lookup', [
            'search_type' => 'phone',
            'phone_country_code' => '+62',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['phone_national_number']);

        $this->postJson('/api/forgot-qr/lookup', [
            'search_type' => 'passport',
            'country' => 'ID',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['identity_number']);
    }

    public function test_lookup_requires_recaptcha_when_enabled(): void
    {
        config([
            'services.recaptcha.enabled' => true,
            'services.recaptcha.secret_key' => 'test-secret',
            'services.recaptcha.verify_url' => 'https://www.google.com/recaptcha/api/siteverify',
        ]);

        Http::fake();

        $this->postJson('/api/forgot-qr/lookup', [
            'search_type' => 'email',
            'email' => 'test@example.com',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['recaptcha_token']);

        Http::assertNothingSent();
    }

    public function test_lookup_returns_not_found_when_participant_has_no_ticket(): void
    {
        $user = $this->repository->create([
            'full_name' => 'No Ticket',
            'email' => 'noticket@example.test',
            'phone_country_code' => '+62',
            'phone_national_number' => '8111111111',
            'phone_number' => '+628111111111',
            'country' => 'ID',
            'identity_country' => 'ID',
            'identity_type' => 'passport',
            'identity_number' => 'NT123456',
            'account_status' => 'active',
            'verification_status' => 'verified',
            'email_verified_at' => now()->toISOString(),
            'ticket_id' => null,
            'ticket_ready_email_sent_at' => null,
            'agreed_terms_at' => now()->toISOString(),
            'registered_ip' => '127.0.0.1',
        ]);

        $this->postJson('/api/forgot-qr/lookup', [
            'search_type' => 'email',
            'email' => $user['email'],
        ])->assertOk()
            ->assertJson([
                'found' => false,
                'message' => 'Data peserta tidak ditemukan.',
            ]);
    }

    public function test_lookup_endpoint_is_rate_limited(): void
    {
        $this->withMiddleware(ThrottleRequests::class);
        $this->seedParticipant();

        for ($attempt = 1; $attempt <= 3; $attempt++) {
            $this->postJson('/api/forgot-qr/lookup', [
                'search_type' => 'email',
                'email' => 'test@example.com',
            ])->assertOk();
        }

        $this->postJson('/api/forgot-qr/lookup', [
            'search_type' => 'email',
            'email' => 'test@example.com',
        ])->assertStatus(429);
    }

    /**
     * @return array{0: array, 1: array}
     */
    private function seedParticipant(array $overrides = []): array
    {
        $user = $this->repository->create(array_merge([
            'full_name' => 'Test User',
            'email' => 'test@example.com',
            'phone_country_code' => '+62',
            'phone_national_number' => '8123456789',
            'phone_number' => '+628123456789',
            'country' => 'ID',
            'identity_country' => 'ID',
            'identity_type' => 'passport',
            'identity_number' => 'A1234567',
            'account_status' => 'active',
            'verification_status' => 'verified',
            'email_verified_at' => now()->toISOString(),
            'ticket_id' => null,
            'ticket_ready_email_sent_at' => null,
            'agreed_terms_at' => now()->toISOString(),
            'registered_ip' => '127.0.0.1',
        ], $overrides));

        $result = $this->repository->activateAndIssueTicket(
            (string) $user['user_id'],
            app(TicketQrCodeService::class)->makeTicketAttributes((string) $user['user_id']),
        );

        return [$result['user'], $result['ticket']];
    }
}
