@extends('layouts.app', ['title' => 'Registration Success'])

@section('content')
<div class="card" style="max-width:560px">
    <div class="success-icon">✓</div>
    <h1 class="title">Registration Successful</h1>
    <p class="subtitle">We have sent a verification email to <strong>{{ $maskedEmail }}</strong>.</p>
    <p class="muted">Please check Inbox, Spam, and Junk folders. You can request a new verification email if it hasn't arrived.</p>
    <p class="muted" id="cooldown">You can request a new verification link in 60 seconds.</p>
    <button id="resendBtn" disabled>Resend Verification Email</button>
    <p id="msg" class="muted" style="margin-top:10px"></p>
</div>
<script>
let seconds = 60;
const btn = document.getElementById('resendBtn');
const cooldown = document.getElementById('cooldown');
const timer = setInterval(()=>{seconds--;cooldown.innerText=`You can request a new verification link in ${seconds} seconds.`;if(seconds<=0){clearInterval(timer);btn.disabled=false;cooldown.innerText='You can request a new verification link now.';}},1000);
btn.addEventListener('click', async ()=>{
    const res = await fetch('/api/email/resend-verification',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content,'Accept':'application/json'},body:JSON.stringify({email:'{{ $email }}'})});
    const data = await res.json();
    document.getElementById('msg').innerText = data.message || 'Request sent';
    btn.disabled=true;seconds=60;
});
</script>
@endsection
