

<?php $__env->startSection('content'); ?>
<div class="card auth-card" style="max-width:560px">
    <div class="success-icon">✔</div>
    <h1 class="title">Password Reset Successful</h1>
    <p class="subtitle">Your password has been updated successfully.</p>

    <div style="margin-top:18px;">
        <button type="button" onclick="window.location.href='<?php echo e(route('login')); ?>'">
            Login
        </button>
    </div>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', ['title' => 'Reset Success | Songkran Festival'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\IQBAL\event-system\resources\views/reset/reset-sukses.blade.php ENDPATH**/ ?>