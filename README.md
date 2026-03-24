# Songkran Festival - Registration Module (Laravel 11)

Full Laravel 11 project for `register.songkremfestival.my` with Firestore-first user registration and email verification flow.



If this is your first time running the project, do this first:

1. Copy `.env.example` to `.env`
2. Put your Firebase credential JSON in `storage/app/secrets/firebase-credentials.json`
3. Set your own `FIREBASE_COLLECTION_PREFIX` in `.env`
4. Run `php artisan key:generate`
5. Start Laravel and test the register page

If you are using Laragon, you can point `APP_URL` to your local vhost such as `http://event-system.test`.

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

Open `http://event-system.test/register` if you use Laragon vhosts, or align `APP_URL` with your local host.

## Environment Setup

The repository now ships multiple environment templates:

- `.env.example` for local development
- `.env.testing.example` for local testing
- `.env.ci.example` for CI
- `.env.production.example` for production reference

### Which File Should I Use?

- New local developer: use `.env.example`
- Running tests in a special environment: use `.env.testing.example`
- CI pipeline maintainer: use `.env.ci.example`
- Ops / senior developer preparing deploy: use `.env.production.example`

### The 4 Values Most Developers Need to Change

In most cases only need to update these variables in `.env`:

| Variable | What it means | Example |
| --- | --- | --- |
| `APP_URL` | your local app URL | `http://event-system.test` |
| `FIREBASE_PROJECT_ID` | shared Firebase project id | `event-songkran-festival` |
| `FIREBASE_CREDENTIALS` | where your Firebase JSON is stored | `storage/app/secrets/firebase-credentials.json` |
| `FIREBASE_COLLECTION_PREFIX` | your personal local namespace | `local_dina_` |

Recommended local pattern:

- Keep secrets only in your own `.env`
- Use a relative credential path such as `storage/app/secrets/firebase-credentials.json`
- Set `FIREBASE_COLLECTION_PREFIX=local_yourname_` so each developer gets isolated Firestore collections even when sharing one Firebase project
- Leave `MAIL_MAILER=log` by default unless you explicitly want sandbox email delivery

### Safe Defaults for Beginners

- Keep `FIREBASE_TRANSPORT=rest` for local Windows/Laragon development
- Leave all `FIREBASE_*_COLLECTION` values blank unless a senior asks you to override them
- Keep `MAIL_MAILER=log` if you only want to test the UI and backend flow
- Keep `RECAPTCHA_ENABLED=false` in local
- Do not put secrets in `.env.example`

Important Firebase variables:

- `FIREBASE_TRANSPORT=rest` for local Windows/Laragon development
- `FIREBASE_TRANSPORT=grpc` for production
- `FIREBASE_CREDENTIALS` accepts relative paths from the project root
- `FIREBASE_COLLECTION_PREFIX` is prepended automatically when collection names are left blank

### Why We Use `FIREBASE_COLLECTION_PREFIX`

If three developers use the same Firebase project and all write to the same `users` collection, local testing becomes messy very quickly.  
With prefixes, each developer gets isolated data automatically:

- `local_andi_users`
- `local_siti_users`
- `local_intern1_users`

That makes debugging safer and prevents accidental collisions.

If Firebase credentials are missing in a local environment, the app can use fallback file-based storage at:

- `storage/app/local_firestore_users.json`

Do not commit that file. It can contain full registration data and is ignored by default.

### Suggested Folder for Local Secrets

Put local secrets here:

```text
storage/app/secrets/
```

Suggested local files:

- `storage/app/secrets/firebase-credentials.json`
- `storage/app/secrets/firebase-credentials.testing.json`

These files are local-only and should never be committed.

## Production Notes

- Keep `APP_DEBUG=false`.
- Use HTTPS domain in `APP_URL`.
- Use queue workers for mail in production.
- Keep `FIREBASE_FALLBACK_LOCAL=false` outside local development.
- Encrypt `identity_number` field in next phase using app-level encryption key rotation policy.
- This repository does not ship database migrations for the shared auth tables used by password reset and login flows, so align schema provisioning outside this package before deployment.
