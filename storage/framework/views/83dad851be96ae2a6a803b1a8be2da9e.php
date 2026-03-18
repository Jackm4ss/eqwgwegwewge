

<?php $__env->startSection('content'); ?>
<div class="card auth-card">
    <h1 class="title">Forgot Password</h1>
    <p class="subtitle">Enter your email to receive a password reset link.</p>

    <form id="forgotPasswordForm">
        <?php echo csrf_field(); ?>
        <div>
            <input type="email" name="email" placeholder="Enter your email" required>
        </div>

        <div style="margin-top: 14px;">
            <button type="submit">Send Reset Link</button>
        </div>
    </form>

    <p class="muted" style="margin-top:16px; text-align:center;">
        <a href="<?php echo e(route('login')); ?>">Back to Login</a>
    </p>

    <p id="forgotPasswordMessage" class="muted" style="margin-top:10px;"></p>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', ['title' => 'Forgot Password | Songkran Festival'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\IQBAL\event-system\resources\views/auth/forgot-password.blade.php ENDPATH**/ ?>