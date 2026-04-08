import { useCallback, useEffect, useRef, useState } from 'react';
import { useForm } from 'react-hook-form';
import { AnimatePresence, motion } from 'motion/react';
import { toast } from 'sonner';
import {
  AlertCircle,
  CheckCircle2,
  FileText,
  Loader2,
  Lock,
  Search,
} from 'lucide-react';

import {
  AuthCardFrame,
  AuthCardHeader,
  AuthCodeBadge,
  AuthInlineError,
  AuthPageShell,
  authInputClass,
  authPrimaryButtonClass,
} from './AuthShared';
import { getSpaPaths, getSpaUrl } from '../../lib/spaRouting';

type FormData = {
  reference: string;
  recaptcha_token: string;
};

type TrackedReport = {
  reference: string;
  report_type: string;
  action_status: string;
  action_status_label: string;
  status_guidance: string;
  name: string;
  email: string;
  phone: string;
  identity_type: string;
  identity_number: string;
  incident_date: string;
  incident_time: string;
  reported_at: string;
  chronology: string;
  latest_update: string;
};

type LookupResponse = {
  found: boolean;
  message?: string;
  report?: TrackedReport;
  errors?: Record<string, string[]>;
};

type Grecaptcha = {
  ready: (callback: () => void) => void;
  execute: (siteKey: string, params: { action: string }) => Promise<string>;
};

declare global {
  interface Window {
    grecaptcha?: Grecaptcha;
  }
}

const SONGKRAN_LOGO_URL = '/images/Songkran%20logo.png';
const REPORT_FORM_URL = getSpaPaths('report')[0] ?? '/report';
const FORM_FIELDS: Array<keyof FormData> = ['reference', 'recaptcha_token'];

let recaptchaLoader: Promise<Grecaptcha | null> | null = null;

function getMetaContent(name: string) {
  return (document.querySelector(`meta[name="${name}"]`) as HTMLMetaElement | null)?.content?.trim() ?? '';
}

function isFormField(value: string): value is keyof FormData {
  return FORM_FIELDS.includes(value as keyof FormData);
}

function statusTone(status: string) {
  const normalized = status.trim().toLowerCase();

  if (normalized === 'resolved') {
    return 'border-emerald-200 bg-emerald-50 text-emerald-700';
  }

  if (normalized === 'in_progress') {
    return 'border-sky-200 bg-sky-50 text-sky-700';
  }

  return 'border-amber-200 bg-amber-50 text-amber-700';
}

function ensureRecaptcha(siteKey: string) {
  if (!siteKey.trim()) {
    return Promise.resolve<Grecaptcha | null>(null);
  }

  if (window.grecaptcha) {
    return new Promise<Grecaptcha | null>((resolve) => {
      window.grecaptcha?.ready(() => resolve(window.grecaptcha ?? null));
    });
  }

  if (recaptchaLoader) {
    return recaptchaLoader;
  }

  recaptchaLoader = new Promise<Grecaptcha | null>((resolve) => {
    const ready = () => {
      if (!window.grecaptcha) {
        return false;
      }

      window.grecaptcha.ready(() => resolve(window.grecaptcha ?? null));
      return true;
    };

    const existing = document.getElementById('google-recaptcha-api') as HTMLScriptElement | null;
    if (existing) {
      if (!ready()) {
        existing.addEventListener('load', () => {
          if (!ready()) {
            resolve(null);
          }
        }, { once: true });
        existing.addEventListener('error', () => resolve(null), { once: true });
      }

      return;
    }

    const script = document.createElement('script');
    script.id = 'google-recaptcha-api';
    script.async = true;
    script.defer = true;
    script.src = `https://www.google.com/recaptcha/api.js?render=${encodeURIComponent(siteKey)}`;
    script.addEventListener('load', () => {
      if (!ready()) {
        resolve(null);
      }
    }, { once: true });
    script.addEventListener('error', () => resolve(null), { once: true });
    document.head.appendChild(script);
  }).finally(() => {
    recaptchaLoader = null;
  });

  return recaptchaLoader;
}

export function ReportTrackingPage() {
  const recaptchaEnabled = getMetaContent('recaptcha-enabled') === '1';
  const recaptchaSiteKey = getMetaContent('recaptcha-site-key');
  const addRippleRef = useRef<((x: number, y: number) => void) | null>(null);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [isRecaptchaReady, setIsRecaptchaReady] = useState(!recaptchaEnabled);
  const [result, setResult] = useState<TrackedReport | null>(null);
  const [notFoundMessage, setNotFoundMessage] = useState('');

  const {
    register,
    handleSubmit,
    setValue,
    setError,
    clearErrors,
    formState: { errors },
  } = useForm<FormData>({
    mode: 'onTouched',
    defaultValues: {
      reference: '',
      recaptcha_token: '',
    },
  });

  useEffect(() => {
    if (!recaptchaEnabled) {
      setIsRecaptchaReady(true);
      return;
    }

    if (!recaptchaSiteKey) {
      setIsRecaptchaReady(false);
      return;
    }

    let mounted = true;
    setIsRecaptchaReady(false);

    ensureRecaptcha(recaptchaSiteKey).then((grecaptcha) => {
      if (mounted) {
        setIsRecaptchaReady(Boolean(grecaptcha));
      }
    });

    return () => {
      mounted = false;
    };
  }, [recaptchaEnabled, recaptchaSiteKey]);

  const executeRecaptcha = useCallback(async () => {
    if (!recaptchaEnabled) {
      return '';
    }

    if (!recaptchaSiteKey) {
      setError('recaptcha_token', {
        type: 'manual',
        message: 'reCAPTCHA is not configured. Please contact the administrator.',
      });
      return null;
    }

    const grecaptcha = await ensureRecaptcha(recaptchaSiteKey);
    if (!grecaptcha) {
      setIsRecaptchaReady(false);
      setError('recaptcha_token', {
        type: 'manual',
        message: 'Failed to load reCAPTCHA. Please try again.',
      });
      return null;
    }

    setIsRecaptchaReady(true);

    try {
      const token = (await grecaptcha.execute(recaptchaSiteKey, { action: 'public_report_lookup' })).trim();
      if (!token) {
        setError('recaptcha_token', {
          type: 'manual',
          message: 'Failed to verify reCAPTCHA. Please try again.',
        });
        return null;
      }

      setValue('recaptcha_token', token, { shouldValidate: false });
      clearErrors('recaptcha_token');
      return token;
    } catch {
      setError('recaptcha_token', {
        type: 'manual',
        message: 'Failed to verify reCAPTCHA. Please try again.',
      });
      return null;
    }
  }, [clearErrors, recaptchaEnabled, recaptchaSiteKey, setError, setValue]);

  const onSubmit = async (data: FormData) => {
    clearErrors();
    setResult(null);
    setNotFoundMessage('');
    setIsSubmitting(true);

    try {
      const recaptchaToken = await executeRecaptcha();
      if (recaptchaEnabled && (!recaptchaToken || !recaptchaToken.trim())) {
        return;
      }

      const csrf = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement | null)?.content ?? '';
      const payload = {
        reference: data.reference.trim().toUpperCase(),
        recaptcha_token: recaptchaToken ?? '',
      };

      const response = await fetch(getSpaUrl('reportTrackingLookupApi', '/api/report/lookup'), {
        method: 'POST',
        headers: {
          Accept: 'application/json',
          'Content-Type': 'application/json',
          ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
        },
        body: JSON.stringify(payload),
      });

      const json = await response.json() as LookupResponse;

      if (!response.ok) {
        if (response.status === 422 && json.errors) {
          Object.entries(json.errors).forEach(([field, messages]) => {
            if (isFormField(field)) {
              setError(field, { type: 'server', message: messages[0] ?? 'Invalid input.' });
            }
          });
          toast.error('Please review the highlighted fields.');
          return;
        }

        throw new Error(json.message || 'Something went wrong while checking your report.');
      }

      if (!json.found || !json.report) {
        setNotFoundMessage(json.message || 'Report data was not found.');
        return;
      }

      setResult(json.report);
      toast.success('Report found.');
    } catch (error) {
      toast.error(error instanceof Error ? error.message : 'Something went wrong while checking your report.');
    } finally {
      setValue('recaptcha_token', '', { shouldValidate: false });
      setIsSubmitting(false);
    }
  };

  const inputClass = (field: keyof FormData, withIcon = true) =>
    authInputClass(Boolean(errors[field]), { withIcon });

  return (
    <AuthPageShell
      skipHref="#report-tracking-form"
      skipLabel="Skip to report tracking form"
      onCanvasReady={(fn) => {
        addRippleRef.current = fn;
      }}
      onPageClick={(event) => addRippleRef.current?.(event.clientX, event.clientY)}
      backgroundImageUrl="/images/BACKGROUND.jpg"
    >
      <motion.main
        id="report-tracking-form"
        initial={{ opacity: 0, y: 18 }}
        animate={{ opacity: 1, y: 0 }}
        transition={{ duration: 0.7, ease: [0.25, 0.46, 0.45, 0.94] }}
        className="relative flex min-h-screen items-center justify-center px-4 py-10 lg:px-8"
        style={{ zIndex: 2 }}
      >
        <div className="w-full max-w-[560px]">
          <AuthCardFrame className="relative">
            <a
              href={REPORT_FORM_URL}
              className="absolute right-4 top-4 z-10 flex items-center gap-1.5 rounded-full bg-white/90 px-3 py-1.5 text-[11px] font-semibold text-sky-700 shadow-md backdrop-blur transition-all hover:bg-white hover:text-sky-900"
            >
              Back to Report
            </a>
            <AuthCardHeader
              className="text-center"
              eyebrow="Help Desk Tracking"
              title="Track Public Report"
              description="Check the latest handling status using your reference number only."
              note="Manual lookup only. Enter the reference number from your report receipt, then press the button below."
              topSlot={(
                <div className="flex items-center justify-center gap-4">
                  <div className="rounded-[1.35rem] border border-white/15 bg-white/10 px-4 py-3 shadow-[0_14px_45px_rgba(12,74,110,0.22)] backdrop-blur-sm">
                    <img src={SONGKRAN_LOGO_URL} alt="Songkran Festival 2026 logo" className="h-12 w-auto sm:h-14" />
                  </div>
                </div>
              )}
            />

            <div className="bg-white">
              <form onSubmit={handleSubmit(onSubmit)} noValidate>
                <div className="space-y-6 px-7 py-6">
                  <div>
                    <label htmlFor="reference" className="mb-1.5 block text-sm font-semibold text-slate-700">
                      Reference Number <span className="text-red-500">*</span>
                    </label>
                    <div className="relative">
                      <Search className="pointer-events-none absolute left-3.5 top-1/2 h-[18px] w-[18px] -translate-y-1/2 text-sky-400" aria-hidden="true" />
                      <input
                        id="reference"
                        type="text"
                        autoComplete="off"
                        placeholder="Example: LI001"
                        className={inputClass('reference')}
                        {...register('reference', {
                          required: 'Reference number is required.',
                          minLength: { value: 3, message: 'Reference number is too short.' },
                        })}
                      />
                    </div>
                    <p className="mt-1.5 text-[11px] text-slate-500">
                      Use the reference number shown after your report was submitted.
                    </p>
                    <AnimatePresence><AuthInlineError message={errors.reference?.message} /></AnimatePresence>
                  </div>

                  <input type="hidden" {...register('recaptcha_token')} />
                  {recaptchaEnabled && (
                    <div className="rounded-2xl border border-sky-100 bg-white/90 p-4 shadow-sm">
                      <div className="mb-3 flex items-start gap-2">
                        <Lock className="mt-0.5 h-4 w-4 text-sky-500" aria-hidden="true" />
                        <div>
                          <p className="text-sm font-semibold text-slate-800">Background protection</p>
                          <p className="text-xs leading-relaxed text-slate-500">We use reCAPTCHA v3 in the background before every report tracking request.</p>
                        </div>
                      </div>
                      {recaptchaSiteKey === '' ? (
                        <div className="rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-600">
                          reCAPTCHA is not configured. Please contact the administrator to provide the site key.
                        </div>
                      ) : (
                        <div className={`flex items-center gap-2 rounded-xl border px-3 py-2 text-xs ${isRecaptchaReady ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-sky-200 bg-sky-50 text-slate-600'}`}>
                          {isRecaptchaReady ? (
                            <CheckCircle2 className="h-4 w-4 text-emerald-600" aria-hidden="true" />
                          ) : (
                            <Loader2 className="h-4 w-4 animate-spin text-sky-500" aria-hidden="true" />
                          )}
                          <span>{isRecaptchaReady ? 'Protection ready. No checkbox is required.' : 'Preparing Google reCAPTCHA v3 background protection...'}</span>
                        </div>
                      )}
                      <AnimatePresence><AuthInlineError message={errors.recaptcha_token?.message} /></AnimatePresence>
                    </div>
                  )}
                </div>

                <div className="flex justify-end border-t border-slate-100 bg-slate-50 px-7 py-4">
                  <motion.button
                    type="submit"
                    disabled={isSubmitting}
                    whileHover={{ scale: isSubmitting ? 1 : 1.02 }}
                    whileTap={{ scale: isSubmitting ? 1 : 0.97 }}
                    className={authPrimaryButtonClass('rounded-xl px-6 py-2.5 text-sm font-semibold normal-case tracking-normal shadow-[0_4px_14px_rgba(2,132,199,0.35)] focus:ring-offset-1')}
                    aria-busy={isSubmitting}
                    aria-disabled={isSubmitting}
                  >
                    {isSubmitting ? (
                      <>
                        <Loader2 className="h-4 w-4 animate-spin" aria-hidden="true" />
                        <span>Checking...</span>
                      </>
                    ) : (
                      <>
                        <Search className="h-4 w-4" aria-hidden="true" />
                        <span>Track My Report</span>
                      </>
                    )}
                  </motion.button>
                </div>
              </form>
            </div>
          </AuthCardFrame>

          <AnimatePresence>
            {notFoundMessage && (
              <motion.div initial={{ opacity: 0, y: 8 }} animate={{ opacity: 1, y: 0 }} exit={{ opacity: 0, y: -8 }} className="mt-5 rounded-[1.6rem] border border-amber-200 bg-white/96 px-5 py-4 text-slate-700 shadow-[0_18px_45px_rgba(12,74,110,0.18)] backdrop-blur-sm">
                <div className="flex items-start gap-3">
                  <AlertCircle className="mt-0.5 h-5 w-5 flex-shrink-0 text-amber-500" aria-hidden="true" />
                  <div>
                    <p className="text-sm font-bold text-slate-900">Report not found</p>
                    <p className="mt-1 text-sm text-slate-600">{notFoundMessage}</p>
                    <p className="mt-1 text-sm text-slate-600">Please re-check the reference number and try again.</p>
                  </div>
                </div>
              </motion.div>
            )}
          </AnimatePresence>

          <AnimatePresence>
            {result && (
              <motion.section initial={{ opacity: 0, y: 12 }} animate={{ opacity: 1, y: 0 }} exit={{ opacity: 0, y: -12 }} transition={{ duration: 0.25 }} className="mt-5 overflow-hidden rounded-[2rem] border border-white/20 bg-white shadow-[0_30px_80px_rgba(0,0,0,0.28)]">
                <div className="relative overflow-hidden bg-gradient-to-r from-sky-700 via-sky-600 to-cyan-500 px-6 py-5 text-white">
                  <div className="absolute -right-10 -top-10 h-32 w-32 rounded-full border border-white/10" aria-hidden="true" />
                  <div className="relative flex items-start gap-4">
                    <div className="flex h-14 w-14 flex-shrink-0 items-center justify-center rounded-[1.2rem] border border-white/20 bg-white/10">
                      <FileText className="h-7 w-7" aria-hidden="true" />
                    </div>
                    <div className="min-w-0 flex-1">
                      <p className="text-xs font-bold uppercase tracking-[0.28em] text-sky-100">Report Found</p>
                      <h2 className="mt-1 text-2xl font-black tracking-tight" style={{ fontFamily: '"Kanit", sans-serif' }}>
                        {result.report_type || 'Public Report'}
                      </h2>
                      <p className="mt-2 text-sm text-sky-100">{result.status_guidance}</p>
                    </div>
                  </div>
                </div>
                <div className="space-y-6 px-6 py-6">
                  <div className="flex flex-wrap items-center justify-between gap-3">
                    <AuthCodeBadge code={result.reference} label="Reference Number" className="w-full sm:w-auto" />
                    <span className={`inline-flex items-center rounded-full border px-3 py-1 text-xs font-bold uppercase tracking-[0.18em] ${statusTone(result.action_status)}`}>
                      Status: {result.action_status_label}
                    </span>
                  </div>

                  <div className="grid gap-4 sm:grid-cols-2">
                    <div className="rounded-2xl border border-sky-100 bg-sky-50/70 p-4"><p className="text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-500">Submitted By</p><p className="mt-2 break-words text-sm font-semibold text-slate-900">{result.name || '-'}</p></div>
                    <div className="rounded-2xl border border-sky-100 bg-sky-50/70 p-4"><p className="text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-500">Report Type</p><p className="mt-2 break-words text-sm font-semibold text-slate-900">{result.report_type || '-'}</p></div>
                    <div className="rounded-2xl border border-sky-100 bg-sky-50/70 p-4"><p className="text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-500">Email</p><p className="mt-2 break-words text-sm font-semibold text-slate-900">{result.email || '-'}</p></div>
                    <div className="rounded-2xl border border-sky-100 bg-sky-50/70 p-4"><p className="text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-500">Phone Number</p><p className="mt-2 break-words text-sm font-semibold text-slate-900">{result.phone || '-'}</p></div>
                    <div className="rounded-2xl border border-sky-100 bg-sky-50/70 p-4"><p className="text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-500">Incident Date</p><p className="mt-2 text-sm font-semibold text-slate-900">{result.incident_date || '-'}</p></div>
                    <div className="rounded-2xl border border-sky-100 bg-sky-50/70 p-4"><p className="text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-500">Incident Time</p><p className="mt-2 text-sm font-semibold text-slate-900">{result.incident_time || '-'}</p></div>
                    <div className="rounded-2xl border border-sky-100 bg-sky-50/70 p-4 sm:col-span-2"><p className="text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-500">Submitted At</p><p className="mt-2 text-sm font-semibold text-slate-900">{result.reported_at || '-'}</p></div>
                  </div>

                  <div className="rounded-2xl border border-sky-100 bg-white p-4 shadow-[0_12px_28px_rgba(14,165,233,0.08)]">
                    <p className="text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-500">Report / Chronology</p>
                    <p className="mt-3 whitespace-pre-line text-sm leading-relaxed text-slate-700">{result.chronology || '-'}</p>
                  </div>

                  <div className="rounded-2xl border border-sky-100 bg-white p-4 shadow-[0_12px_28px_rgba(14,165,233,0.08)]">
                    <p className="text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-500">Latest Update From Help Desk</p>
                    <p className="mt-3 whitespace-pre-line text-sm leading-relaxed text-slate-700">
                      {result.latest_update || 'No follow-up note has been added yet. Please check again later if the status changes.'}
                    </p>
                  </div>
                </div>
              </motion.section>
            )}
          </AnimatePresence>
        </div>
      </motion.main>
    </AuthPageShell>
  );
}
