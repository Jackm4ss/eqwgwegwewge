

<?php $__env->startSection('content'); ?>
<div class="card" style="max-width:560px">
    <div class="success-icon">✓</div>

    <h1 class="title">Email Verified Successfully</h1>

    <p class="subtitle">
        Your account is now active<?php echo e(!empty($maskedEmail) ? ' for '.$maskedEmail : ''); ?>.
    </p>

    <div class="action-row">
        <a href="<?php echo e(route('login')); ?>">
            <button type="button">Login</button>
        </a>

        <a href="<?php echo e(env('FRONTEND_HOMEPAGE_URL', 'https://frolicking-twilight-019912.netlify.app/')); ?>">
            <button type="button" class="secondary-btn">Homepage</button>
        </a>
    </div>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', ['title' => 'Email Verified | Songkran Festival'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\IQBAL\event-system\resources\views/auth/email-verified.blade.php ENDPATH**/ ?>