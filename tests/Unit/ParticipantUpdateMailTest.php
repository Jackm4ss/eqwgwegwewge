<?php

namespace Tests\Unit;

use App\Mail\ParticipantUpdateMail;
use Tests\TestCase;

class ParticipantUpdateMailTest extends TestCase
{
    public function test_profile_update_mail_renders_change_summary_and_ticket_link(): void
    {
        $mail = new ParticipantUpdateMail(
            user: [
                'full_name' => 'Alya Putri',
                'email' => 'alya@example.test',
            ],
            ticket: [
                'ticket_id' => 'ticket-123',
                'ticket_code' => 'TICKET-123',
            ],
            updateType: 'profile_update',
            changes: [
                [
                    'label' => 'Account Status',
                    'value' => 'Active',
                ],
                [
                    'label' => 'Verification Status',
                    'value' => 'Verified',
                ],
            ],
            ticketUrl: 'https://example.test/tickets/ticket-123',
        );

        $mail->assertSeeInHtml('Your Participant Details Were Updated');
        $mail->assertSeeInHtml('Update Summary');
        $mail->assertSeeInHtml('Account Status');
        $mail->assertSeeInHtml('Verification Status');
        $mail->assertSeeInHtml('TICKET-123');
        $mail->assertSeeInHtml('Review My Ticket');
    }

    public function test_qr_regenerated_mail_renders_latest_qr_section(): void
    {
        $mail = new ParticipantUpdateMail(
            user: [
                'full_name' => 'Alya Putri',
                'email' => 'alya@example.test',
            ],
            ticket: [
                'ticket_id' => 'ticket-123',
                'ticket_code' => 'TICKET-NEW',
                'qr_version' => 'v2',
            ],
            updateType: 'qr_regenerated',
            changes: [
                [
                    'label' => 'Update Type',
                    'value' => 'A new QR pass is now active for your ticket.',
                ],
            ],
            ticketUrl: 'https://example.test/tickets/ticket-123',
            qrPngBinary: 'fake-qr-png-binary',
        );

        $mail->assertSeeInHtml('Festival Pass Refresh');
        $mail->assertSeeInHtml('Your QR Pass Was Updated');
        $mail->assertSeeInHtml('A new QR pass has been generated for your Songkran Festival ticket.');
        $mail->assertSeeInHtml('Your latest festival QR pass is ready.');
        $mail->assertSeeInHtml('Please discard any older QR image and keep only this latest version for event entry.');
        $mail->assertSeeInHtml('Open My Latest Ticket');
        $mail->assertSeeInHtml('Updated Songkran Festival ticket QR code');
        $mail->assertSeeInHtml('updated-ticket-qrcode.png');
    }

    public function test_deleted_registration_mail_renders_without_ticket_cta(): void
    {
        $mail = new ParticipantUpdateMail(
            user: [
                'full_name' => 'Alya Putri',
                'email' => 'alya@example.test',
            ],
            ticket: [
                'ticket_id' => 'ticket-123',
                'ticket_code' => 'TICKET-123',
            ],
            updateType: 'participant_deleted',
            changes: [
                [
                    'label' => 'Registration Status',
                    'value' => 'Your participant record has been removed from the Songkran Festival 2026 system.',
                ],
                [
                    'label' => 'Festival Access',
                    'value' => 'Any existing QR pass or ticket linked to this registration is no longer active.',
                ],
            ],
        );

        $mail->assertSeeInHtml('Your Registration Was Removed');
        $mail->assertSeeInHtml('Registration Status');
        $mail->assertSeeInHtml('Festival Access');
        $mail->assertSeeInHtml('TICKET-123');
        $mail->assertDontSeeInHtml('Open My Latest Ticket');
        $mail->assertDontSeeInHtml('Review My Ticket');
    }
}
