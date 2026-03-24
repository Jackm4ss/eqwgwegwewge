import { useState, useCallback, useRef } from 'react';
import { useForm } from 'react-hook-form';
import { motion, AnimatePresence } from 'motion/react';
import { Toaster, toast } from 'sonner';
import {
  Mail, Lock, Eye, EyeOff, LogIn, AlertCircle,
  Loader2, Droplets, ArrowRight, Sparkles, ShieldCheck,
  LayoutDashboard, QrCode, Activity, HardDrive
} from 'lucide-react';
import { WaterAnimation } from './WaterAnimation';

interface LoginFormData {
  email: string;
  password: string;
  rememberMe: boolean;
}

function LotusIcon({ className }: { className?: string }) {
  return (
    <svg viewBox="0 0 120 120" className={className} aria-hidden="true" fill="currentColor">
      <ellipse cx="60" cy="90" rx="8" ry="5" opacity="0.9" />
      <path d="M60 90 C60 90 38 68 38 46 C38 28 48 16 60 16 C72 16 82 28 82 46 C82 68 60 90 60 90Z" opacity="0.75" />
      <path d="M60 90 C60 90 22 72 16 50 C12 32 22 18 34 20 C46 22 60 90 60 90Z" opacity="0.6" />
      <path d="M60 90 C60 90 98 72 104 50 C108 32 98 18 86 20 C74 22 60 90 60 90Z" opacity="0.6" />
      <path d="M60 90 C60 90 8 80 6 56 C4 36 16 22 28 26 C42 30 60 90 60 90Z" opacity="0.4" />
      <path d="M60 90 C60 90 112 80 114 56 C116 36 104 22 92 26 C78 30 60 90 60 90Z" opacity="0.4" />
    </svg>
  );
}

function DiamondPattern() {
  return (
    <svg viewBox="0 0 80 20" className="w-full opacity-40" aria-hidden="true">
      {[0, 1, 2, 3, 4].map(i => (
        <polygon
          key={i}
          points={`${8 + i * 16},2 ${12 + i * 16},10 ${8 + i * 16},18 ${4 + i * 16},10`}
          fill="white"
          opacity="0.6"
        />
      ))}
    </svg>
  );
}

function FieldError({ id, message }: { id: string; message?: string }) {
  if (!message) return null;
  return (
    <motion.p
      id={id}
      role="alert"
      initial={{ opacity: 0, y: -4 }}
      animate={{ opacity: 1, y: 0 }}
      exit={{ opacity: 0, y: -4 }}
      transition={{ duration: 0.2 }}
      className="mt-1.5 text-red-600 text-xs flex items-center gap-1 font-medium"
    >
      <AlertCircle className="w-3.5 h-3.5 flex-shrink-0" aria-hidden="true" />
      {message}
    </motion.p>
  );
}

/* Password strength meter */
function PasswordStrength({ password }: { password: string }) {
  if (!password) return null;

  const checks = [
    password.length >= 8,
    /[A-Z]/.test(password),
    /[0-9]/.test(password),
    /[^a-zA-Z0-9]/.test(password),
  ];
  const score = checks.filter(Boolean).length;
  const labels = ['', 'Weak', 'Fair', 'Strong', 'Very Strong'];
  const colors = ['', 'bg-red-400', 'bg-orange-400', 'bg-yellow-400', 'bg-emerald-400'];
  const textColors = ['', 'text-red-500', 'text-orange-500', 'text-yellow-600', 'text-emerald-600'];

  return (
    <motion.div
      initial={{ opacity: 0, height: 0 }}
      animate={{ opacity: 1, height: 'auto' }}
      exit={{ opacity: 0, height: 0 }}
      className="mt-2"
      aria-live="polite"
    >
      <div className="flex gap-1 mb-1" role="img" aria-label={`Password strength: ${labels[score]}`}>
        {[1, 2, 3, 4].map(i => (
          <motion.div
            key={i}
            className={`h-1 flex-1 rounded-full transition-colors duration-300 ${i <= score ? colors[score] : 'bg-slate-200'}`}
            initial={{ scaleX: 0 }}
            animate={{ scaleX: 1 }}
            transition={{ duration: 0.3, delay: i * 0.06 }}
            style={{ transformOrigin: 'left' }}
          />
        ))}
      </div>
      <p className={`text-[11px] font-medium ${textColors[score]}`}>{labels[score]}</p>
    </motion.div>
  );
}

/* Social login button */
function SocialButton({
  icon, label, onClick,
}: {
  icon: React.ReactNode;
  label: string;
  onClick: () => void;
}) {
  return (
    <motion.button
      type="button"
      onClick={onClick}
      whileHover={{ scale: 1.02, y: -1 }}
      whileTap={{ scale: 0.97 }}
      className="flex-1 flex items-center justify-center gap-2.5 px-4 py-2.5 rounded-xl border-2 border-sky-100 bg-white text-slate-600 text-sm font-semibold hover:border-sky-300 hover:bg-sky-50 focus:outline-none focus:ring-2 focus:ring-sky-400 focus:ring-offset-1 transition-all duration-200"
      aria-label={`Sign in with ${label}`}
    >
      {icon}
      <span>{label}</span>
    </motion.button>
  );
}

export function LoginPage() {
  const [showPassword, setShowPassword] = useState(false);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [loginAttempts, setLoginAttempts] = useState(0);
  const addRippleRef = useRef<((x: number, y: number) => void) | null>(null);
  const {
    register,
    handleSubmit,
    watch,
    formState: { errors },
  } = useForm<LoginFormData>({ mode: 'onTouched' });

  const passwordValue = watch('password', '');

  const handleCanvasReady = useCallback((fn: (x: number, y: number) => void) => {
    addRippleRef.current = fn;
  }, []);

  const handlePageClick = useCallback((e: React.MouseEvent) => {
    if (addRippleRef.current) {
      addRippleRef.current(e.clientX, e.clientY);
    }
  }, []);

  const onSubmit = async (data: LoginFormData) => {
    setIsSubmitting(true);

    try {
      const response = await fetch('/login', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || ''
        },
        body: JSON.stringify({
          email: data.email,
          password: data.password,
          remember: data.rememberMe
        }),
      });

      const result = await response.json();

      if (!response.ok) {
        setLoginAttempts(a => a + 1);
        throw new Error(result.message || 'Incorrect email or password.');
      }

      toast.success('Welcome back!', {
        description: 'You have successfully signed in.',
      });

      // Redirect to home or intended page
      setTimeout(() => {
        window.location.href = result.redirect || '/';
      }, 1000);

    } catch (error: any) {
      toast.error(error.message || 'An error occurred while signing in.');
    } finally {
      setIsSubmitting(false);
    }
  };

  const handleSocialLogin = (provider: string) => {
    toast.info(`Connecting to ${provider}...`, { description: 'This feature is coming soon.' });
  };

  const inputBase =
    'w-full px-4 py-3 pl-11 rounded-xl border-2 transition-all duration-200 bg-white text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-offset-1';

  const inputClass = (hasError: boolean) =>
    `${inputBase} ${hasError
      ? 'border-red-400 focus:border-red-500 focus:ring-red-300'
      : 'border-sky-200 focus:border-sky-500 focus:ring-sky-300 hover:border-sky-300'
    }`;

  return (
    <div
      className="min-h-screen relative overflow-x-hidden"
      style={{ background: 'linear-gradient(145deg, #0C4A6E 0%, #0369A1 30%, #0284C7 60%, #0EA5E9 100%)' }}
      onClick={handlePageClick}
    >
      {/* Skip link – WCAG */}
      <a
        href="#login-form"
        className="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 focus:z-50 focus:px-4 focus:py-2 focus:bg-white focus:text-sky-800 focus:rounded-lg focus:shadow-lg focus:font-semibold"
      >
        Skip to login form
      </a>

      <Toaster position="top-center" richColors />
      <WaterAnimation onCanvasReady={handleCanvasReady} />

      {/* Decorative blobs */}
      <div className="fixed inset-0 pointer-events-none overflow-hidden" aria-hidden="true" style={{ zIndex: 1 }}>
        <div
          className="absolute -top-32 -left-32 w-[500px] h-[500px] rounded-full opacity-10"
          style={{ background: 'radial-gradient(circle, #BAE6FD, transparent)' }}
        />
        <div
          className="absolute -bottom-40 -right-40 w-[600px] h-[600px] rounded-full opacity-10"
          style={{ background: 'radial-gradient(circle, #E0F2FE, transparent)' }}
        />
        {/* Lotus corners */}
        <div className="absolute top-4 left-4 w-56 h-56 text-sky-200 opacity-[0.08]">
          <LotusIcon className="w-full h-full" />
        </div>
        <div className="absolute bottom-4 right-4 w-44 h-44 text-sky-200 opacity-[0.08] rotate-180">
          <LotusIcon className="w-full h-full" />
        </div>

        {/* Animated floating particles */}
        {[...Array(6)].map((_, i) => (
          <motion.div
            key={i}
            className="absolute w-2 h-2 rounded-full bg-sky-300/30"
            style={{
              left: `${15 + i * 14}%`,
              top: `${20 + (i % 3) * 25}%`,
            }}
            animate={{
              y: [0, -20, 0],
              opacity: [0.2, 0.5, 0.2],
              scale: [1, 1.3, 1],
            }}
            transition={{
              duration: 3 + i * 0.5,
              repeat: Infinity,
              ease: 'easeInOut',
              delay: i * 0.4,
            }}
          />
        ))}

        {/* Wave bottom */}
        <svg className="absolute bottom-0 left-0 w-full" viewBox="0 0 1440 100" preserveAspectRatio="none">
          <motion.path
            d="M0,50 C360,100 720,0 1080,50 C1260,75 1350,25 1440,50 L1440,100 L0,100 Z"
            fill="rgba(255,255,255,0.04)"
            animate={{
              d: [
                'M0,50 C360,100 720,0 1080,50 C1260,75 1350,25 1440,50 L1440,100 L0,100 Z',
                'M0,30 C360,0 720,80 1080,30 C1260,5 1350,70 1440,30 L1440,100 L0,100 Z',
                'M0,50 C360,100 720,0 1080,50 C1260,75 1350,25 1440,50 L1440,100 L0,100 Z',
              ],
            }}
            transition={{ duration: 7, repeat: Infinity, ease: 'easeInOut' }}
          />
        </svg>
      </div>

      {/* ── MAIN LAYOUT ── */}
      <div className="relative flex flex-col lg:flex-row min-h-screen items-stretch" style={{ zIndex: 2 }}>

        {/* ── LEFT PANEL – FORM ── */}
        <motion.main
          id="login-form"
          initial={{ opacity: 0, x: -50 }}
          animate={{ opacity: 1, x: 0 }}
          transition={{ duration: 0.85, ease: [0.25, 0.46, 0.45, 0.94] }}
          className="flex-1 flex flex-col items-center justify-center px-4 py-12 lg:px-10"
          tabIndex={-1}
        >
          {/* Mobile logo */}
          <div className="lg:hidden text-center mb-7 text-white">
            <motion.div
              initial={{ scale: 0.8, opacity: 0 }}
              animate={{ scale: 1, opacity: 1 }}
              transition={{ duration: 0.6, type: 'spring' }}
              className="w-20 h-20 mx-auto mb-3 text-sky-200"
            >
              <LotusIcon className="w-full h-full" />
            </motion.div>
            <h1
              className="text-4xl font-black tracking-tight"
              style={{ fontFamily: '"Kanit", sans-serif' }}
            >
              SONGKRAN
            </h1>
            <p className="text-sky-200 text-sm tracking-[0.3em] mt-1">MUSIC FESTIVAL 2026</p>
          </div>

          {/* Form card */}
          <motion.div
            initial={{ opacity: 0, y: 30 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ delay: 0.15, duration: 0.7, ease: [0.25, 0.46, 0.45, 0.94] }}
            className="w-full max-w-[470px] rounded-3xl overflow-hidden"
            style={{ boxShadow: '0 30px 80px rgba(0,0,0,0.35), 0 0 0 1px rgba(255,255,255,0.08)' }}
          >
            {/* Card header */}
            <div
              className="relative px-8 pt-8 pb-7 overflow-hidden"
              style={{ background: 'linear-gradient(135deg, #0369A1, #0284C7, #0EA5E9)' }}
            >
              {/* Decorative rings */}
              <div className="absolute -top-12 -right-12 w-44 h-44 rounded-full border border-white/10 pointer-events-none" aria-hidden="true" />
              <div className="absolute -top-7 -right-7 w-28 h-28 rounded-full border border-white/10 pointer-events-none" aria-hidden="true" />
              <div className="absolute -bottom-8 -left-8 w-36 h-36 rounded-full border border-white/8 pointer-events-none" aria-hidden="true" />

              <div className="relative">
                {/* Logo top */}
                <div className="flex items-center gap-3 mb-5">
                  <motion.div
                    animate={{ rotate: [0, 8, -8, 0] }}
                    transition={{ duration: 4, repeat: Infinity, ease: 'easeInOut' }}
                    className="w-10 h-10 text-sky-200"
                  >
                    <LotusIcon className="w-full h-full" />
                  </motion.div>
                  <div>
                    <p
                      className="text-white font-black text-xl leading-tight tracking-wide"
                      style={{ fontFamily: '"Kanit", sans-serif' }}
                    >
                      SONGKRAN
                    </p>
                    <p className="text-sky-200 text-[10px] tracking-[0.25em] leading-none">MUSIC FESTIVAL 2026</p>
                  </div>
                </div>

                <h2
                  className="text-white text-3xl font-bold leading-tight"
                  style={{ fontFamily: '"Kanit", sans-serif' }}
                >
                  Staff & Admin Portal
                </h2>
                <p className="text-sky-100 text-sm mt-1">
                  Gate Management System & Operations Dashboard
                </p>

                {/* Security badge intentionally disabled by request. */}
                {false && (
                  <div className="mt-4 inline-flex items-center gap-1.5 bg-emerald-400/15 border border-emerald-300/25 px-3 py-1.5 rounded-full">
                    <ShieldCheck className="w-3.5 h-3.5 text-emerald-300" aria-hidden="true" />
                    <span className="text-emerald-200 text-[11px] font-medium tracking-wide">
                      Secure & Encrypted Connection
                    </span>
                  </div>
                )}
              </div>
            </div>

            {/* Form body */}
            <div className="bg-white px-8 py-7">
              <form
                onSubmit={handleSubmit(onSubmit)}
                noValidate
                aria-label="Songkran Music Festival login form"
              >
                {/* Email */}
                <div className="mb-5">
                  <label htmlFor="email" className="block text-slate-700 text-sm font-semibold mb-1.5">
                    Email / Staff ID{' '}
                    <span className="text-red-500" aria-hidden="true">*</span>
                  </label>
                  <div className="relative">
                    <Mail
                      className="absolute left-3.5 top-1/2 -translate-y-1/2 text-sky-400 pointer-events-none"
                      aria-hidden="true"
                      style={{ width: 18, height: 18 }}
                    />
                    <input
                      id="email"
                      type="email"
                      autoComplete="email"
                      placeholder="nama@email.com"
                      aria-required="true"
                      aria-describedby={errors.email ? 'err-login-email' : 'email-hint'}
                      aria-invalid={!!errors.email}
                      className={inputClass(!!errors.email)}
                      {...register('email', {
                        required: 'Email is required',
                        pattern: {
                          value: /^[^\s@]+@[^\s@]+\.[^\s@]+$/,
                          message: 'Invalid email format',
                        },
                      })}
                    />
                  </div>
                  <p id="email-hint" className="sr-only">
                    Enter the email you used when registering
                  </p>
                  <AnimatePresence>
                    <FieldError id="err-login-email" message={errors.email?.message} />
                  </AnimatePresence>
                </div>

                {/* Password */}
                <div className="mb-2">
                  <div className="flex items-center justify-between mb-1.5">
                    <label htmlFor="password" className="block text-slate-700 text-sm font-semibold">
                      Password{' '}
                      <span className="text-red-500" aria-hidden="true">*</span>
                    </label>
                    {/* Forgot password link intentionally disabled by request. */}
                    {false && (
                      <a
                        href="/forgot-password"
                        className="text-sky-600 text-xs font-medium hover:text-sky-800 focus:outline-none focus:ring-1 focus:ring-sky-500 focus:ring-offset-1 rounded transition-colors"
                      >
                        Forgot password?
                      </a>
                    )}
                  </div>
                  <div className="relative">
                    <Lock
                      className="absolute left-3.5 top-1/2 -translate-y-1/2 text-sky-400 pointer-events-none"
                      aria-hidden="true"
                      style={{ width: 18, height: 18 }}
                    />
                    <input
                      id="password"
                      type={showPassword ? 'text' : 'password'}
                      autoComplete="current-password"
                      placeholder="Enter your password"
                      aria-required="true"
                      aria-describedby={errors.password ? 'err-login-password' : undefined}
                      aria-invalid={!!errors.password}
                      className={`${inputClass(!!errors.password)} pr-11`}
                      {...register('password', {
                        required: 'Password is required',
                        minLength: { value: 6, message: 'Password must be at least 6 characters' },
                      })}
                    />
                    <motion.button
                      type="button"
                      whileHover={{ scale: 1.1 }}
                      whileTap={{ scale: 0.9 }}
                      onClick={() => setShowPassword(v => !v)}
                      className="absolute right-3.5 top-1/2 -translate-y-1/2 text-sky-400 hover:text-sky-600 focus:outline-none focus:ring-2 focus:ring-sky-400 focus:ring-offset-1 rounded-md p-0.5 transition-colors"
                      aria-label={showPassword ? 'Hide password' : 'Show password'}
                      aria-pressed={showPassword}
                    >
                      {showPassword ? (
                        <EyeOff style={{ width: 18, height: 18 }} aria-hidden="true" />
                      ) : (
                        <Eye style={{ width: 18, height: 18 }} aria-hidden="true" />
                      )}
                    </motion.button>
                  </div>
                  <AnimatePresence>
                    <FieldError id="err-login-password" message={errors.password?.message} />
                  </AnimatePresence>
                  <AnimatePresence>
                    {passwordValue && <PasswordStrength password={passwordValue} />}
                  </AnimatePresence>
                </div>

                {/* Remember me */}
                <div className="flex items-center gap-2.5 mb-6 mt-4">
                  <input
                    id="rememberMe"
                    type="checkbox"
                    className="w-4.5 h-4.5 rounded border-2 border-sky-300 text-sky-600 cursor-pointer focus:ring-2 focus:ring-sky-500 focus:ring-offset-1"
                    style={{ width: 18, height: 18 }}
                    {...register('rememberMe')}
                  />
                  <label htmlFor="rememberMe" className="text-slate-600 text-sm cursor-pointer select-none">
                    Remember me
                  </label>
                </div>

                {/* Alert on failed attempt */}
                <AnimatePresence>
                  {loginAttempts > 0 && (
                    <motion.div
                      initial={{ opacity: 0, height: 0, marginBottom: 0 }}
                      animate={{ opacity: 1, height: 'auto', marginBottom: 16 }}
                      exit={{ opacity: 0, height: 0, marginBottom: 0 }}
                      role="alert"
                      aria-live="assertive"
                      className="flex items-start gap-3 bg-red-50 border border-red-200 rounded-xl px-4 py-3"
                    >
                      <AlertCircle className="w-4 h-4 text-red-500 flex-shrink-0 mt-0.5" aria-hidden="true" />
                      <p className="text-red-700 text-xs leading-relaxed">
                        <strong>Account not found.</strong> Use{' '}
                        <code className="bg-red-100 px-1 py-0.5 rounded text-red-800 font-mono">
                          demo@songkran.com
                        </code>{' '}
                        to try the demo login.
                      </p>
                    </motion.div>
                  )}
                </AnimatePresence>

                {/* Submit button */}
                <motion.button
                  type="submit"
                  disabled={isSubmitting}
                  whileHover={{ scale: isSubmitting ? 1 : 1.015, y: isSubmitting ? 0 : -1 }}
                  whileTap={{ scale: isSubmitting ? 1 : 0.97 }}
                  className="w-full flex items-center justify-center gap-2.5 py-3.5 rounded-xl text-white font-bold text-sm focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2 transition-all duration-200 disabled:opacity-70 disabled:cursor-not-allowed"
                  style={{
                    background: isSubmitting
                      ? '#94a3b8'
                      : 'linear-gradient(135deg, #0369A1, #0284C7, #0EA5E9)',
                    boxShadow: isSubmitting ? 'none' : '0 6px 20px rgba(2,132,199,0.4)',
                  }}
                  aria-busy={isSubmitting}
                >
                  {isSubmitting ? (
                    <>
                      <Loader2 className="w-4 h-4 animate-spin" aria-hidden="true" />
                      <span>Signing In...</span>
                    </>
                  ) : (
                    <>
                      <ShieldCheck className="w-4 h-4" aria-hidden="true" />
                      <span>Sign In to Dashboard</span>
                    </>
                  )}
                </motion.button>

                {/* Social login section intentionally disabled by request. */}
                {false && (
                  <>
                    <div className="flex items-center gap-3 my-5" aria-hidden="true">
                      <div className="flex-1 h-px bg-slate-200" />
                      <span className="text-slate-400 text-xs font-medium px-1">or sign in with</span>
                      <div className="flex-1 h-px bg-slate-200" />
                    </div>

                    <div className="flex gap-3" role="group" aria-label="Alternative sign-in options">
                      <SocialButton
                        label="Google"
                        onClick={() => handleSocialLogin('Google')}
                        icon={
                          <svg viewBox="0 0 24 24" style={{ width: 18, height: 18 }} aria-hidden="true">
                            <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4" />
                            <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853" />
                            <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05" />
                            <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335" />
                          </svg>
                        }
                      />
                      <SocialButton
                        label="Facebook"
                        onClick={() => handleSocialLogin('Facebook')}
                        icon={
                          <svg viewBox="0 0 24 24" style={{ width: 18, height: 18 }} aria-hidden="true" fill="#1877F2">
                            <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z" />
                          </svg>
                        }
                      />
                      <SocialButton
                        label="LINE"
                        onClick={() => handleSocialLogin('LINE')}
                        icon={
                          <svg viewBox="0 0 24 24" style={{ width: 18, height: 18 }} aria-hidden="true" fill="#00B900">
                            <path d="M19.365 9.863c.349 0 .63.285.63.631 0 .345-.281.63-.63.63H17.61v1.125h1.755c.349 0 .63.283.63.63 0 .344-.281.629-.63.629h-2.386c-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63h2.386c.346 0 .627.285.627.63 0 .349-.281.63-.63.63H17.61v1.125h1.755zm-3.855 3.016c0 .27-.174.51-.432.596-.064.021-.133.031-.199.031-.211 0-.391-.09-.51-.25l-2.443-3.317v2.94c0 .344-.279.629-.631.629-.346 0-.626-.285-.626-.629V8.108c0-.27.173-.51.43-.595.06-.023.136-.033.194-.033.195 0 .375.104.495.254l2.462 3.33V8.108c0-.345.282-.63.63-.63.345 0 .63.285.63.63v4.771zm-5.741 0c0 .344-.282.629-.631.629-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63.346 0 .628.285.628.63v4.771zm-2.466.629H4.917c-.345 0-.63-.285-.63-.629V8.108c0-.345.285-.63.63-.63.348 0 .63.285.63.63v4.141h1.756c.348 0 .629.283.629.63 0 .344-.281.629-.629.629M24 10.314C24 4.943 18.615.572 12 .572S0 4.943 0 10.314c0 4.811 4.27 8.842 10.035 9.608.391.082.923.258 1.058.59.12.301.079.766.038 1.08l-.164 1.02c-.045.301-.24 1.186 1.049.645 1.291-.539 6.916-4.078 9.436-6.975C23.176 14.393 24 12.458 24 10.314" />
                          </svg>
                        }
                      />
                    </div>
                  </>
                )}
              </form>
            </div>

            {/* Support footer intentionally disabled by request. */}
            {false && (
              <div className="bg-gradient-to-r from-sky-50 to-cyan-50 px-8 py-5 border-t border-sky-100 text-center">
                <p className="text-slate-500 text-xs flex items-center justify-center gap-2">
                  <AlertCircle className="w-3.5 h-3.5 text-sky-500" />
                  Having access issues? Contact <span className="font-bold text-sky-700">IT Support Dashboard</span>
                </p>
              </div>
            )}
          </motion.div>


        </motion.main>

        {/* ── RIGHT PANEL – INFO ── */}
        {/* Gate management promo panel intentionally disabled by request. */}
        {false && (
          <motion.aside
            initial={{ opacity: 0, x: 50 }}
            animate={{ opacity: 1, x: 0 }}
            transition={{ duration: 0.9, ease: [0.25, 0.46, 0.45, 0.94] }}
            className="hidden lg:flex lg:w-[44%] flex-col justify-center items-center px-10 py-12 text-white"
            aria-label="Songkran festival information"
          >
            {/* Badge */}
            <motion.div
              initial={{ opacity: 0, y: -20 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ delay: 0.25 }}
              className="inline-flex items-center gap-2 bg-white/10 backdrop-blur-md border border-white/20 px-5 py-2.5 rounded-full mb-8"
            >
              <Activity className="w-4 h-4 text-sky-200" aria-hidden="true" />
              <span className="text-sky-100 text-xs tracking-[0.2em] uppercase font-medium">
                GATE MANAGEMENT SYSTEM
              </span>
              <Activity className="w-4 h-4 text-sky-200" aria-hidden="true" />
            </motion.div>

            {/* Animated lotus */}
            <motion.div
              initial={{ scale: 0.7, opacity: 0 }}
              animate={{ scale: 1, opacity: 1 }}
              transition={{ delay: 0.35, duration: 0.7, type: 'spring', damping: 12 }}
              className="relative mb-6"
            >
              <motion.div
                animate={{ y: [0, -10, 0] }}
                transition={{ duration: 4.5, repeat: Infinity, ease: 'easeInOut' }}
                className="w-36 h-36 text-sky-200"
              >
                <LotusIcon className="w-full h-full drop-shadow-xl" />
              </motion.div>
              <div
                className="absolute inset-0 rounded-full blur-2xl opacity-25"
                style={{ background: 'radial-gradient(circle, #BAE6FD, transparent)' }}
                aria-hidden="true"
              />
            </motion.div>

            {/* Title */}
            <motion.div
              initial={{ opacity: 0, y: 20 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ delay: 0.45 }}
              className="text-center mb-3"
            >
              <h1
                className="text-[4rem] leading-none font-black tracking-tight"
                style={{ fontFamily: '"Kanit", sans-serif', textShadow: '0 4px 24px rgba(0,0,0,0.25)' }}
              >
                SONGKRAN
              </h1>
              <p
                className="text-xl tracking-[0.4em] text-sky-200 mt-1 font-light"
                style={{ fontFamily: '"Kanit", sans-serif' }}
              >
                MUSIC FESTIVAL
              </p>

            </motion.div>

            {/* Divider */}
            <div className="flex items-center gap-3 mb-8 w-full max-w-xs" aria-hidden="true">
              <div className="flex-1 h-px bg-white/20" />
              <Sparkles className="w-4 h-4 text-sky-300" />
              <div className="flex-1 h-px bg-white/20" />
            </div>

            {/* Countdown / info cards */}
            <motion.div
              initial={{ opacity: 0, y: 20 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ delay: 0.6 }}
              className="w-full max-w-sm space-y-3 mb-8"
            >


              {/* Staff Features Card with 3D Effect */}
              <div style={{ perspective: '1200px' }}>
                <motion.div
                  whileHover={{ rotateY: 5, rotateX: -2, y: -5, scale: 1.02 }}
                  transition={{ type: 'spring', stiffness: 300, damping: 20 }}
                  className="bg-white/10 backdrop-blur-md border border-white/20 rounded-[2rem] p-6 shadow-2xl relative overflow-hidden"
                >
                  {/* Background glow effect */}
                  <div className="absolute -top-24 -right-24 w-48 h-48 bg-sky-500/10 rounded-full blur-3xl pointer-events-none" />

                  <p className="text-sky-200 text-xs uppercase tracking-[0.2em] mb-5 font-black flex items-center gap-2.5">
                    <div className="p-1.5 bg-sky-500/20 rounded-lg">
                      <LayoutDashboard className="w-4 h-4 text-sky-300" />
                    </div>
                    Staff Operations Features
                  </p>

                  <ul className="space-y-4 list-none p-0 m-0 mb-6">
                    {[
                      { icon: QrCode, text: 'Ticket Scanning & QR Access Control', color: 'text-sky-300' },
                      { icon: Activity, text: 'Real-Time Visitor Flow Monitoring', color: 'text-cyan-300' },
                      { icon: HardDrive, text: 'Gate & Location Data Synchronization', color: 'text-blue-300' },
                      { icon: ShieldCheck, text: 'Identity Verification & Blacklist Check', color: 'text-emerald-300' },
                    ].map((item, i) => (
                      <motion.li
                        key={i}
                        initial={{ opacity: 0, x: 15 }}
                        animate={{ opacity: 1, x: 0 }}
                        transition={{ delay: 0.6 + i * 0.1 }}
                        className="text-white text-sm flex items-center gap-4 group"
                      >
                        <div className={`p-2 rounded-xl bg-white/5 border border-white/10 group-hover:bg-white/10 transition-colors ${item.color}`}>
                          <item.icon className="w-4 h-4" aria-hidden="true" />
                        </div>
                        <span className="font-medium tracking-wide">{item.text}</span>
                      </motion.li>
                    ))}
                  </ul>

                  {/* Internal Access Footer (Integrated) */}
                  <div className="pt-5 border-t border-white/20 mt-2">
                    <p className="text-sky-100 text-xs leading-relaxed uppercase tracking-wider font-semibold text-center">
                      Internal Access System Copyright 2026 SONGKRAN IT DIV.<br />
                      <span className="text-emerald-400 flex items-center justify-center gap-1.5 mt-1 text-[11px] font-bold">

                      </span>
                    </p>
                  </div>
                </motion.div>
              </div>
            </motion.div>
          </motion.aside>
        )}
      </div>
    </div>
  );
}

