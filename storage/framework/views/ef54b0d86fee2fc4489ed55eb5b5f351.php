

<?php $__env->startSection('content'); ?>
<div class="card">
    <h1 class="title">Register Now</h1>
    <p class="subtitle">Join Songkran Festival with your verified account.</p>

    <form id="registerForm" class="grid">
        <?php echo csrf_field(); ?>

        <div>
            <input name="full_name" placeholder="Full Name" required>
        </div>

        <div>
            <input name="identity_number" placeholder="Identity Number / Passport ID" required>
        </div>

        <div>
            <input type="email" name="email" placeholder="Email" required>
        </div>

        <div>
            <input name="phone_number" placeholder="Phone Number" required>
        </div>

        <div>
            <select name="gender" required>
                <option value="">Select Gender</option>
                <option value="male">Male</option>
                <option value="female">Female</option>
                <option value="other">Other</option>
            </select>
        </div>

        <div>
            <input name="country" placeholder="Country" required>
        </div>

        <div class="full">
            <textarea name="address" placeholder="Address" required></textarea>
        </div>

        <div>
            <input type="date" name="birth_date" required>
        </div>

        <div>
            <input type="password" name="password" placeholder="Password (min 8)" required>
        </div>

        <div class="full">
            <input type="password" name="password_confirmation" placeholder="Confirm Password" required>
        </div>

        <div class="full checkbox">
            <label>
                <input type="checkbox" name="agree_terms" value="1">
                I agree to terms and privacy policy.
            </label>
        </div>

        <input type="hidden" name="g-recaptcha-response" id="captchaToken" value="dev-captcha-token">

        <div class="full">
            <button type="submit">Create Account</button>
        </div>
    </form>

    <div id="formMessage" class="muted form-message"></div>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', ['title' => 'Register | Songkran Festival'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\IQBAL\event-system\resources\views/auth/register.blade.php ENDPATH**/ ?>