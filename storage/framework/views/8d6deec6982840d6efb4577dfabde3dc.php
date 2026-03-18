

<?php $__env->startSection('content'); ?>
<div class="card" style="max-width:560px">
    <div class="success-icon">✓</div>

    <h1 class="title">Registration Successful</h1>

    <p class="subtitle">
        We have sent a verification email to <strong><?php echo e($maskedEmail); ?></strong>.
    </p>

    <p class="muted">
        Please check Inbox, Spam, and Junk folders. You can request a new verification email if it has not arrived yet.
    </p>

    <p class="muted" id="cooldown">
        You can request a new verification link in 60 seconds.
    </p>

    <button id="resendBtn" disabled>Resend Verification Email</button>

    <p id="msg" class="muted form-message"></p>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', ['title' => 'Registration Success | Songkran Festival'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\IQBAL\event-system\resources\views/auth/register-success.blade.php ENDPATH**/ ?>