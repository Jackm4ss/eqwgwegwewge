@extends('layouts.app', ['title' => 'Register | Songkran Festival'])

@section('content')
<div class="card">
    <h1 class="title">Register Now</h1>
    <p class="subtitle">Join Songkran Festival with your verified account.</p>
    <form id="registerForm" class="grid">
        @csrf
        <div><input name="full_name" placeholder="Full Name"></div>
        <div><input name="identity_number" placeholder="Identity Number / Passport ID"></div>
        <div><input type="email" name="email" placeholder="Email"></div>
        <div><input name="phone_number" placeholder="Phone Number"></div>
        <div>
            <select name="gender"><option value="">Select Gender</option><option value="male">Male</option><option value="female">Female</option><option value="other">Other</option></select>
        </div>
        <div><input name="country" placeholder="Country"></div>
        <div class="full"><textarea name="address" placeholder="Address"></textarea></div>
        <div><input type="date" name="birth_date"></div>
        <div><input type="password" name="password" placeholder="Password (min 8)"></div>
        <div class="full"><input type="password" name="password_confirmation" placeholder="Confirm Password"></div>
        <div class="full"><label><input type="checkbox" name="agree_terms" value="1"> I agree to terms and privacy policy.</label></div>
        <input type="hidden" name="g-recaptcha-response" id="captchaToken" value="dev-captcha-token">
        <div class="full"><button type="submit">Create Account</button></div>
    </form>
    <div id="formMessage" class="muted" style="margin-top:12px"></div>
</div>
<script>
const form = document.getElementById('registerForm');
form.addEventListener('submit', async (e) => {
    e.preventDefault();
    document.querySelectorAll('.error').forEach(e => e.remove());
    const formData = new FormData(form);
    const payload = Object.fromEntries(formData.entries());
    payload.agree_terms = formData.get('agree_terms') ? '1' : '';

    const response = await fetch('/api/register', {
        method: 'POST',
        headers: {'Content-Type': 'application/json','X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept':'application/json'},
        body: JSON.stringify(payload)
    });
    const data = await response.json();
    if (!response.ok) {
        if (data.errors) {
            Object.entries(data.errors).forEach(([field,messages]) => {
                const input = document.querySelector(`[name="${field}"]`);
                if (input) {
                    const err = document.createElement('div'); err.className='error'; err.innerText = messages[0]; input.parentElement.appendChild(err);
                }
            });
        }
        document.getElementById('formMessage').innerText = data.message || 'Registration failed';
        return;
    }
    window.location.href = data.redirect;
});
</script>
@endsection
