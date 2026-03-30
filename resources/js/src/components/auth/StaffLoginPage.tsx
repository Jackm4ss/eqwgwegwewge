import { useCallback, useRef, useState, type MouseEvent } from 'react';
import { useForm } from 'react-hook-form';
import { AnimatePresence, motion } from 'motion/react';
import { ArrowUpRight, CheckCircle2, Eye, EyeOff, Loader2, Lock, Mail, MapPin, ScanLine, ShieldCheck } from 'lucide-react';
import { toast } from 'sonner';

import {
  AuthCardFrame,
  AuthCardHeader,
  AuthInlineError,
  AuthPageShell,
  authInputClass,
  authPrimaryButtonClass,
} from './AuthShared';
import { getSpaUrl } from '../../lib/spaRouting';
import { cn } from '../../lib/utils';

type StaffLoginFormData = {
  email: string;
  password: string;
  scanner_post: string;
  remember: boolean;
};

type StaffLoginResponse = {
  message?: string;
  redirect?: string;
};

const SONGKRAN_LOGO_URL = '/images/Songkran%20logo.png';
const STAFF_LOGIN_SUBMIT_URL = getSpaUrl('staffLoginSubmit', '/staff/login');
const STAFF_HOME_URL = getSpaUrl('staffHome', '/staff');

function csrfToken() {
  return (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement | null)?.content ?? '';
}

function staffScannerPosts() {
  if (typeof document === 'undefined') {
    return ['Gate A'];
  }

  const content = (document.querySelector('meta[name="staff-scanner-posts"]') as HTMLMetaElement | null)?.content;

  if (!content) {
    return ['Gate A'];
  }

  try {
    const parsed = JSON.parse(content);
    const posts = Array.isArray(parsed)
      ? parsed.filter((item): item is string => typeof item === 'string' && item.trim().length > 0).map((item) => item.trim())
      : [];

    return posts.length > 0 ? posts : ['Gate A'];
  } catch {
    return ['Gate A'];
  }
}

export function StaffLoginPage() {
  const addRippleRef = useRef<((x: number, y: number) => void) | null>(null);
  const [showPassword, setShowPassword] = useState(false);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [formError, setFormError] = useState('');
  const scannerPosts = staffScannerPosts();

  const {
    register,
    handleSubmit,
    watch,
    setValue,
    formState: { errors },
  } = useForm<StaffLoginFormData>({
    mode: 'onTouched',
    defaultValues: {
      email: '',
      password: '',
      scanner_post: scannerPosts[0] ?? '',
      remember: true,
    },
  });
  const selectedScannerPost = watch('scanner_post') || scannerPosts[0] || '';

  const handleCanvasReady = useCallback((fn: (x: number, y: number) => void) => {
    addRippleRef.current = fn;
  }, []);

  const handlePageClick = useCallback((event: MouseEvent<HTMLDivElement>) => {
    addRippleRef.current?.(event.clientX, event.clientY);
  }, []);

  const onSubmit = async (data: StaffLoginFormData) => {
    setIsSubmitting(true);
    setFormError('');

    try {
      const response = await fetch(STAFF_LOGIN_SUBMIT_URL, {
        method: 'POST',
        headers: {
          Accept: 'application/json',
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrfToken(),
        },
        body: JSON.stringify({
          email: data.email.trim(),
          password: data.password,
          scanner_post: data.scanner_post,
          remember: data.remember,
        }),
      });

      const result = (await response.json()) as StaffLoginResponse;

      if (!response.ok) {
        const message = result.message || 'Scanner credentials were rejected.';
        setFormError(message);
        toast.error(message);
        return;
      }

      toast.success('Scanner access granted.');
      window.location.href = result.redirect || STAFF_HOME_URL;
    } catch {
      const message = 'Unable to reach the staff portal right now.';
      setFormError(message);
      toast.error(message);
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <AuthPageShell
      skipHref="#staff-login-form"
      skipLabel="Skip to staff login form"
      onCanvasReady={handleCanvasReady}
      onPageClick={handlePageClick}
      backgroundImageUrl="/images/BACKGROUND.jpg"
    >
      <motion.main
        id="staff-login-form"
        initial={{ opacity: 0, y: 18 }}
        animate={{ opacity: 1, y: 0 }}
        transition={{ duration: 0.7, ease: [0.25, 0.46, 0.45, 0.94] }}
        className="relative flex min-h-screen items-center justify-center px-4 py-10 lg:px-8"
      >
        <AuthCardFrame className="w-full max-w-[500px]">
          <AuthCardHeader
            eyebrow="Scanner Portal"
            title="Staff Login"
            description="Sign in to the scanner dashboard, choose the active gate, and continue with QR scanning and fallback entry codes in a ready-to-use session."
            note="The selected gate is assigned during sign-in, and the scanner session will follow it immediately."
            topSlot={(
              <div className="flex flex-wrap items-center gap-4">
                <div className="rounded-[1.35rem] border border-white/15 bg-white/10 px-4 py-3 shadow-[0_14px_45px_rgba(12,74,110,0.22)] backdrop-blur-sm">
                  <img src={SONGKRAN_LOGO_URL} alt="Songkran Festival 2026 logo" className="h-12 w-auto sm:h-14" />
                </div>
                <div className="inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-4 py-2 text-[11px] font-bold uppercase tracking-[0.28em] text-sky-50">
                  <ScanLine className="h-3.5 w-3.5" aria-hidden="true" />
                  Gate Scanner
                </div>
              </div>
            )}
          />

          <div className="bg-white px-8 py-7">
            <form onSubmit={handleSubmit(onSubmit)} noValidate className="space-y-5">
              <div>
                <label htmlFor="staff-email" className="mb-1.5 block text-sm font-semibold text-slate-700">
                  Staff Email <span className="text-red-500">*</span>
                </label>
                <div className="relative">
                  <Mail className="pointer-events-none absolute left-3.5 top-1/2 h-[18px] w-[18px] -translate-y-1/2 text-sky-400" aria-hidden="true" />
                  <input
                    id="staff-email"
                    type="email"
                    autoComplete="email"
                    placeholder="name1@example.com"
                    className={authInputClass(Boolean(errors.email))}
                    {...register('email', {
                      required: 'Staff email is required.',
                      pattern: {
                        value: /^[^\s@]+@[^\s@]+\.[^\s@]+$/,
                        message: 'Use a valid email address.',
                      },
                    })}
                  />
                </div>
                <AnimatePresence>
                  <AuthInlineError message={errors.email?.message} />
                </AnimatePresence>
              </div>

              <div>
                <label htmlFor="staff-password" className="mb-1.5 block text-sm font-semibold text-slate-700">
                  Password <span className="text-red-500">*</span>
                </label>
                <div className="relative">
                  <Lock className="pointer-events-none absolute left-3.5 top-1/2 h-[18px] w-[18px] -translate-y-1/2 text-sky-400" aria-hidden="true" />
                  <input
                    id="staff-password"
                    type={showPassword ? 'text' : 'password'}
                    autoComplete="current-password"
                    placeholder="Enter your scanner password"
                    className={authInputClass(Boolean(errors.password), {
                      extraClassName: 'pr-11',
                    })}
                    {...register('password', {
                      required: 'Password is required.',
                      minLength: {
                        value: 6,
                        message: 'Password must be at least 6 characters.',
                      },
                    })}
                  />
                  <button
                    type="button"
                    onClick={() => setShowPassword((current) => !current)}
                    className="absolute right-3.5 top-1/2 -translate-y-1/2 rounded-md p-0.5 text-sky-400 transition-colors hover:text-sky-600 focus:outline-none focus:ring-2 focus:ring-sky-400 focus:ring-offset-1"
                    aria-label={showPassword ? 'Hide password' : 'Show password'}
                  >
                    {showPassword ? <EyeOff className="h-[18px] w-[18px]" aria-hidden="true" /> : <Eye className="h-[18px] w-[18px]" aria-hidden="true" />}
                  </button>
                </div>
                <AnimatePresence>
                  <AuthInlineError message={errors.password?.message} />
                </AnimatePresence>
              </div>

              <fieldset className="rounded-[1.7rem] border border-sky-100 bg-gradient-to-br from-sky-50 via-white to-cyan-50 p-4 shadow-[0_18px_40px_rgba(14,165,233,0.08)]">
                <div className="flex flex-wrap items-start justify-between gap-3">
                  <div className="space-y-1">
                    <div className="inline-flex items-center gap-2 rounded-full border border-sky-200 bg-white/90 px-3 py-1.5 text-[10px] font-bold uppercase tracking-[0.26em] text-sky-700 shadow-sm">
                      <MapPin className="h-3.5 w-3.5" aria-hidden="true" />
                      Scanner Gate
                    </div>
                    <div>
                      <p className="text-sm font-semibold text-slate-800">
                        Choose the active gate <span className="text-red-500">*</span>
                      </p>
                      <p className="text-xs leading-relaxed text-slate-500">
                        This gate will be used for the entire scanner session right after sign-in succeeds.
                      </p>
                    </div>
                  </div>
                  <div className="rounded-full border border-sky-100 bg-white/85 px-3 py-1.5 text-[11px] font-semibold text-sky-700 shadow-sm">
                    {scannerPosts.length} post{scannerPosts.length > 1 ? 's' : ''}
                  </div>
                </div>

                <input
                  type="hidden"
                  {...register('scanner_post', {
                    required: 'Choose the gate for this scanner.',
                  })}
                />

                <div className="mt-4 grid gap-3 sm:grid-cols-2" role="radiogroup" aria-label="Scanner gate">
                  {scannerPosts.map((post, index) => {
                    const isSelected = selectedScannerPost === post;

                    return (
                      <motion.button
                        key={post}
                        type="button"
                        role="radio"
                        aria-checked={isSelected}
                        whileHover={{ y: isSubmitting ? 0 : -2 }}
                        whileTap={{ scale: isSubmitting ? 1 : 0.995 }}
                        onClick={() => {
                          setValue('scanner_post', post, {
                            shouldDirty: true,
                            shouldTouch: true,
                            shouldValidate: true,
                          });
                        }}
                        className={cn(
                          'group relative overflow-hidden rounded-[1.45rem] border px-4 py-4 text-left transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-sky-400 focus:ring-offset-2',
                          isSelected
                            ? 'border-sky-500 bg-sky-950 text-white shadow-[0_20px_45px_rgba(2,132,199,0.28)]'
                            : 'border-sky-100 bg-white/95 text-slate-800 shadow-[0_14px_30px_rgba(15,23,42,0.06)] hover:border-sky-300 hover:bg-white',
                        )}
                      >
                        <div
                          className={cn(
                            'absolute inset-x-0 top-0 h-1.5',
                            isSelected
                              ? 'bg-gradient-to-r from-cyan-300 via-sky-200 to-white/80'
                              : 'bg-gradient-to-r from-sky-200 via-cyan-100 to-transparent',
                          )}
                          aria-hidden="true"
                        />

                        <div className="flex items-start justify-between gap-3">
                          <div className="space-y-2">
                            <div
                              className={cn(
                                'inline-flex items-center rounded-full px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.24em]',
                                isSelected ? 'bg-white/14 text-sky-100' : 'bg-sky-100 text-sky-700',
                              )}
                            >
                              Post {String(index + 1).padStart(2, '0')}
                            </div>
                            <div>
                              <p className="text-base font-black tracking-tight" style={{ fontFamily: '"Kanit", sans-serif' }}>
                                {post}
                              </p>
                              <p className={cn('mt-1 text-xs leading-relaxed', isSelected ? 'text-sky-100/85' : 'text-slate-500')}>
                                {isSelected
                                  ? 'This scanner session will open directly into this gate.'
                                  : 'Tap to assign this device to the gate before scanning starts.'}
                              </p>
                            </div>
                          </div>

                          <div
                            className={cn(
                              'flex h-10 w-10 items-center justify-center rounded-2xl border transition-colors',
                              isSelected
                                ? 'border-white/20 bg-white/12 text-cyan-100'
                                : 'border-sky-100 bg-sky-50 text-sky-600 group-hover:border-sky-200 group-hover:bg-sky-100',
                            )}
                            aria-hidden="true"
                          >
                            {isSelected ? <CheckCircle2 className="h-5 w-5" /> : <ArrowUpRight className="h-4.5 w-4.5" />}
                          </div>
                        </div>
                      </motion.button>
                    );
                  })}
                </div>

                <div className="mt-4 rounded-[1.35rem] border border-sky-100 bg-white/90 px-4 py-3 shadow-sm">
                  <div className="flex flex-wrap items-center gap-3">
                    <div className="flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-sky-600 to-cyan-500 text-white shadow-[0_12px_28px_rgba(14,165,233,0.24)]">
                      <ScanLine className="h-5 w-5" aria-hidden="true" />
                    </div>
                    <div className="min-w-0 flex-1">
                      <p className="text-[10px] font-bold uppercase tracking-[0.26em] text-sky-600">Selected Gate</p>
                      <p className="truncate text-base font-black tracking-tight text-slate-900" style={{ fontFamily: '"Kanit", sans-serif' }}>
                        {selectedScannerPost}
                      </p>
                      <p className="mt-0.5 text-xs text-slate-500">
                        The operator will be taken straight to the scanner dashboard with this gate as the active context.
                      </p>
                    </div>
                  </div>
                </div>

                <AnimatePresence>
                  <AuthInlineError message={errors.scanner_post?.message} className="mt-3" />
                </AnimatePresence>
              </fieldset>

              <label className="flex items-center gap-3 rounded-2xl border border-sky-100 bg-sky-50/70 px-4 py-3 text-sm text-slate-700">
                <input
                  type="checkbox"
                  className="h-4.5 w-4.5 rounded border-2 border-sky-300 text-sky-600 focus:ring-2 focus:ring-sky-500 focus:ring-offset-1"
                  {...register('remember')}
                />
                <span className="font-medium">Keep this scanner signed in on this device</span>
              </label>

              <AnimatePresence>
                {formError ? (
                  <motion.div
                    initial={{ opacity: 0, y: -6 }}
                    animate={{ opacity: 1, y: 0 }}
                    exit={{ opacity: 0, y: -6 }}
                    className="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"
                    role="alert"
                  >
                    {formError}
                  </motion.div>
                ) : null}
              </AnimatePresence>

              <motion.button
                type="submit"
                disabled={isSubmitting}
                whileHover={{ scale: isSubmitting ? 1 : 1.01 }}
                whileTap={{ scale: isSubmitting ? 1 : 0.99 }}
                className={authPrimaryButtonClass('w-full text-base')}
                aria-busy={isSubmitting}
                aria-disabled={isSubmitting}
              >
                {isSubmitting ? (
                  <>
                    <Loader2 className="h-4.5 w-4.5 animate-spin" aria-hidden="true" />
                    <span>Signing In...</span>
                  </>
                ) : (
                  <>
                    <ShieldCheck className="h-4.5 w-4.5" aria-hidden="true" />
                    <span>Open Scanner Dashboard</span>
                  </>
                )}
              </motion.button>
            </form>
          </div>
        </AuthCardFrame>
      </motion.main>
    </AuthPageShell>
  );
}
