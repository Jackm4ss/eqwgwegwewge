@extends('layouts.app', ['title' => 'Check Your Email | Songkran Festival'])

@section('content')
<div class="card" style="max-width:560px">
    <div class="success-icon">@</div>

    <h1 class="title">Check Your Email</h1>

    <p class="subtitle">
        We have sent a password reset link to <strong>{{ $maskedEmail ?? 'your email' }}</strong>.
    </p>

    <p class="muted">
        Please check Inbox, Spam, or Junk folder. You can resend the reset link if needed.
    </p>

    <p class="muted" id="resetCooldown">
        You can request a new reset link in 60 seconds.
    </p>

    <button id="resetResendBtn" disabled>Resend Reset Email</button>

    <p id="resetMsg" class="muted form-message"></p>
</div>

<script>
let timeLeft = 60;
const btn = document.getElementById('resetResendBtn');
const cooldownText = document.getElementById('resetCooldown');
const msg = document.getElementById('resetMsg');

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
            "Accept": "application/json",
            "X-CSRF-TOKEN": "{{ csrf_token() }}"
        },
        body: JSON.stringify({
            email: "{{ $email ?? '' }}"
        })
    })
    .then(async res => {
        const data = await res.json();
        if (!res.ok) {
            throw new Error(data.message || "Failed to resend email.");
        }

        return data;
    })
    .then(data => {
        msg.innerText = data.message || "Reset link sent again!";
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
    .catch(error => {
        msg.innerText = error.message || "Failed to resend email.";
        btn.disabled = false;
    });
});
</script>
@endsection
