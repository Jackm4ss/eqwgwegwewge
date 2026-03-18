@extends('layouts.app', ['title' => 'Check Your Email | Songkran Festival'])

@section('content')
<div class="card" style="max-width:560px">
    <div class="success-icon">📧</div>

    <h1 class="title">Check Your Email</h1>

    <p class="subtitle">
        We have sent a password reset link to <strong>{{ $maskedEmail ?? 'your email' }}</strong>.
    </p>

    <p class="muted">
        Please check Inbox, Spam, or Junk folder. You can resend the reset link if needed.
    </p>

    <p class="muted" id="cooldown">
        You can request a new reset link in 60 seconds.
    </p>

    <button id="resendBtn" disabled>Resend Reset Email</button>

    <p id="msg" class="muted form-message"></p>
</div>

<script>
let timeLeft = 60;
const btn = document.getElementById('resendBtn');
const cooldownText = document.getElementById('cooldown');
const msg = document.getElementById('msg');

const interval = setInterval(() => {
    timeLeft--;
    cooldownText.innerText = `You can request a new reset link in ${timeLeft} seconds.`;

    if (timeLeft <= 0) {
        clearInterval(interval);
        cooldownText.innerText = "You can now resend the reset link.";
        btn.disabled = false;
    }
}, 1000);

btn.addEventListener('click', function () {
    btn.disabled = true;
    msg.innerText = "Sending...";

    fetch("{{ route('password.resend') }}", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": "{{ csrf_token() }}"
        },
        body: JSON.stringify({
            email: "{{ $email ?? '' }}"
        })
    })
    .then(res => res.json())
    .then(data => {
        msg.innerText = "Reset link sent again!";
        timeLeft = 60;
        btn.disabled = true;

        const restart = setInterval(() => {
            timeLeft--;
            cooldownText.innerText = `You can request a new reset link in ${timeLeft} seconds.`;

            if (timeLeft <= 0) {
                clearInterval(restart);
                cooldownText.innerText = "You can now resend the reset link.";
                btn.disabled = false;
            }
        }, 1000);
    })
    .catch(() => {
        msg.innerText = "Failed to resend email.";
        btn.disabled = false;
    });
});
</script>
@endsection