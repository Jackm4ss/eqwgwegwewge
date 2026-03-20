# Songkran Festival - Registration Module (Laravel 11)

Full Laravel 11 project for `register.songkremfestival.my` with Firestore-first user registration and email verification flow.

## Features

- Register page with premium clean Blade UI.
- Registration API with Form Request validation + sanitization.
- Firestore repository abstraction with local-only JSON fallback for development.
- Google reCAPTCHA verification service.
- Email verification via Laravel signed URLs.
- Register success page (resend only).
- Email verified success page (Login + Back to Homepage).
- Login restriction middleware for unverified users.
- Rate limiting for register and resend endpoints.
- Basic unit + feature tests.

## Required Routes

- `GET /register`
- `POST /api/register`
- `GET /register/success`
- `POST /api/email/resend-verification`
- `GET /email/verify/{id}/{hash}`
- `GET /email/verified`

## Firestore User Collection Shape

`users` collection fields:

- `user_id`
- `full_name`
- `identity_number`
- `email`
- `phone_number`
- `gender`
- `country`
- `address`
- `birth_date`
- `password_hash`
- `account_status`
- `verification_status`
- `email_verified_at`
- `captcha_passed`
- `registered_from_subdomain`
- `created_at`
- `updated_at`

## Local Setup

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan serve
```

Open `http://127.0.0.1:8000/register`.

## Environment Setup

Configure in `.env`:

- `APP_URL` to registration domain.
- Mail server credentials.
- `RECAPTCHA_ENABLED=true` + keys for production.
- `FIREBASE_PROJECT_ID` and `FIREBASE_CREDENTIALS` path to service account JSON.

If Firebase credentials are missing in a local environment, the app can use fallback file-based storage at:

- `storage/app/local_firestore_users.json`

Do not commit that file. It can contain full registration data and is ignored by default.

## Production Notes

- Keep `APP_DEBUG=false`.
- Use HTTPS domain in `APP_URL`.
- Use queue workers for mail in production.
- Keep `FIREBASE_FALLBACK_LOCAL=false` outside local development.
- Encrypt `identity_number` field in next phase using app-level encryption key rotation policy.
- This repository does not ship database migrations for the shared auth tables used by password reset and login flows, so align schema provisioning outside this package before deployment.
