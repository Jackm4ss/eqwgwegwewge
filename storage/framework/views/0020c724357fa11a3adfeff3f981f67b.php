

<?php $__env->startSection('content'); ?>
<div class="card auth-card" style="max-width:560px">
    <div class="success-icon">📧</div>
    <h1 class="title">Check Your Email</h1>
    <p class="subtitle">We have sent a password reset link to your email.</p>
    <p class="muted">Please check your inbox or spam folder, then continue after verifying.</p>

    <div style="margin-top:18px;">
        <button type="button" onclick="window.location.href='<?php echo e(route('reset.sukses')); ?>'">
            I Have Verified
        </button>
    </div>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', ['title' => 'Check Your Email | Songkran Festival'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\IQBAL\event-system\resources\views/reset/reset-verify.blade.php ENDPATH**/ ?>