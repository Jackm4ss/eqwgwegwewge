import { useEffect, useRef, useState } from 'react';
import { AnimatePresence, motion } from 'motion/react';
import { CheckCircle2, ChevronDown, Loader2, Lock, Mail, Phone, UserRound } from 'lucide-react';
import { Controller, useForm } from 'react-hook-form';
import { toast } from 'sonner';

import {
  AuthCardFrame,
  AuthCardHeader,
  AuthInlineError,
  AuthPageShell,
  authInputClass,
  authPrimaryButtonClass,
  authSelectClass,
} from './AuthShared';
import { getSpaUrl } from '../../lib/spaRouting';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectSeparator,
  SelectTrigger,
  SelectValue,
} from '../ui/Select';

type ReportType = 'incident_security' | 'lost_item' | 'lost_locker_card' | 'medical_attention' | 'others';

type FormData = {
  report_type: ReportType;
  name: string;
  phone_country_code: string;
  phone_national_number: string;
  identity_number: string;
  email: string;
  incident_date: string;
  incident_time: string;
  chronology: string;
  staff_name: string;
  recaptcha_token: string;
};

type SubmitResponse = {
  message?: string;
  reference?: string;
  recipient?: string;
};

const SONGKRAN_LOGO_URL = '/images/Songkran%20logo.png';

const REPORT_OPTIONS: Array<{
  value: ReportType;
  title: string;
}> = [
  { value: 'incident_security', title: 'Incident / Security' },
  { value: 'lost_item', title: 'Lost Item' },
  { value: 'lost_locker_card', title: 'Lost Locker Card' },
  { value: 'medical_attention', title: 'Medical Attention' },
  { value: 'others', title: 'Others' },
];

const PRIORITY_COUNTRIES = ['MY', 'TH', 'SG', 'ID', 'BN', 'MM', 'VN'] as const;

const COUNTRIES = [
  { code: 'AU', name: 'Australia' },
  { code: 'BN', name: 'Brunei' },
  { code: 'KH', name: 'Cambodia' },
  { code: 'CN', name: 'China' },
  { code: 'FR', name: 'France' },
  { code: 'DE', name: 'Germany' },
  { code: 'HK', name: 'Hong Kong' },
  { code: 'IN', name: 'India' },
  { code: 'ID', name: 'Indonesia' },
  { code: 'JP', name: 'Japan' },
  { code: 'LA', name: 'Laos' },
  { code: 'MY', name: 'Malaysia' },
  { code: 'MM', name: 'Myanmar' },
  { code: 'NL', name: 'Netherlands' },
  { code: 'NZ', name: 'New Zealand' },
  { code: 'PH', name: 'Philippines' },
  { code: 'SG', name: 'Singapore' },
  { code: 'KR', name: 'South Korea' },
  { code: 'TH', name: 'Thailand' },
  { code: 'AE', name: 'UAE' },
  { code: 'GB', name: 'United Kingdom' },
  { code: 'US', name: 'United States' },
  { code: 'VN', name: 'Vietnam' },
];

const PHONE_DIAL_CODES: Record<string, string> = {
  AU: '+61',
  BN: '+673',
  KH: '+855',
  CN: '+86',
  FR: '+33',
  DE: '+49',
  HK: '+852',
  IN: '+91',
  ID: '+62',
  JP: '+81',
  LA: '+856',
  MY: '+60',
  MM: '+95',
  NL: '+31',
  NZ: '+64',
  PH: '+63',
  SG: '+65',
  KR: '+82',
  TH: '+66',
  AE: '+971',
  GB: '+44',
  US: '+1',
  VN: '+84',
};

const SORTED_COUNTRIES = [...COUNTRIES].sort((a, b) => a.name.localeCompare(b.name));
const PHONE_OPTIONS = SORTED_COUNTRIES.map((country) => ({
  country: country.code,
  countryName: country.name,
  dialCode: PHONE_DIAL_CODES[country.code],
  flagClassName: `fi fi-${country.code.toLowerCase()}`,
}));
const PRIORITY_PHONE_OPTIONS = PRIORITY_COUNTRIES.map((code) => PHONE_OPTIONS.find((item) => item.country === code)).filter(Boolean) as typeof PHONE_OPTIONS;
const OTHER_PHONE_OPTIONS = PHONE_OPTIONS.filter((item) => !PRIORITY_COUNTRIES.includes(item.country as (typeof PRIORITY_COUNTRIES)[number]));

type Grecaptcha = {
  ready: (callback: () => void) => void;
  execute: (siteKey: string, params: { action: string }) => Promise<string>;
};

declare global {
  interface Window {
    grecaptcha?: Grecaptcha;
  }
}

let recaptchaLoader: Promise<Grecaptcha | null> | null = null;

function getMetaContent(name: string) {
  return (document.querySelector(`meta[name="${name}"]`) as HTMLMetaElement | null)?.content?.trim() ?? '';
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

function normalizePhoneCountryCode(value: string) {
  const digits = value.replace(/\D/g, '');
  return digits ? `+${digits}` : '';
}

function normalizePhoneNationalNumber(value: string) {
  return value.replace(/\D/g, '').replace(/^0+/, '');
}

export function ReportPage() {
  const recaptchaEnabled = getMetaContent('recaptcha-enabled') === '1';
  const recaptchaSiteKey = getMetaContent('recaptcha-site-key');
  const requestIp = getMetaContent('request-ip');
  const addRippleRef = useRef<((x: number, y: number) => void) | null>(null);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [submitted, setSubmitted] = useState<{ reference: string } | null>(null);
  const [isRecaptchaReady, setIsRecaptchaReady] = useState(!recaptchaEnabled);

  const {
    control,
    register,
    handleSubmit,
    setValue,
    watch,
    clearErrors,
    setError,
    formState: { errors },
  } = useForm<FormData>({
    mode: 'onTouched',
    defaultValues: {
      report_type: 'incident_security',
      name: '',
      phone_country_code: PHONE_DIAL_CODES.MY,
      phone_national_number: '',
      identity_number: '',
      email: '',
      incident_date: '',
      incident_time: '',
      chronology: '',
      staff_name: '',
      recaptcha_token: '',
    },
  });

  const selectedReportType = watch('report_type');
  const selectedPhoneCountryCode = watch('phone_country_code');
  const selectedPhoneOption = PHONE_OPTIONS.find((item) => item.dialCode === selectedPhoneCountryCode);
  const inputClass = (field: keyof FormData, withIcon = true) => authInputClass(Boolean(errors[field]), { withIcon });
  const selectClass = (field: keyof FormData) => authSelectClass(Boolean(errors[field]));

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

  const onSubmit = async (data: FormData) => {
    clearErrors();
    setIsSubmitting(true);
    setSubmitted(null);

    try {
      let recaptchaToken = '';

      if (recaptchaEnabled) {
        if (!recaptchaSiteKey) {
          setError('recaptcha_token', {
            type: 'manual',
            message: 'reCAPTCHA is not configured. Please contact the administrator.',
          });
          return;
        }

        const grecaptcha = await ensureRecaptcha(recaptchaSiteKey);
        if (!grecaptcha) {
          setIsRecaptchaReady(false);
          setError('recaptcha_token', {
            type: 'manual',
            message: 'Failed to load reCAPTCHA. Please try again.',
          });
          return;
        }

        setIsRecaptchaReady(true);
        recaptchaToken = (await grecaptcha.execute(recaptchaSiteKey, { action: 'public_report_submit' })).trim();

        if (!recaptchaToken) {
          setError('recaptcha_token', {
            type: 'manual',
            message: 'Failed to verify reCAPTCHA. Please try again.',
          });
          return;
        }
      }

      const csrf = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement | null)?.content ?? '';
      const response = await fetch(getSpaUrl('reportSubmitApi', '/api/report'), {
        method: 'POST',
        headers: {
          Accept: 'application/json',
          'Content-Type': 'application/json',
          ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
        },
        body: JSON.stringify({
          ...data,
          name: data.name.trim(),
          phone: `${normalizePhoneCountryCode(data.phone_country_code)}${normalizePhoneNationalNumber(data.phone_national_number)}`,
          identity_number: data.identity_number.trim(),
          email: data.email.trim().toLowerCase(),
          incident_date: data.incident_date,
          incident_time: data.incident_time,
          chronology: data.chronology.trim(),
          staff_name: data.staff_name.trim(),
          recaptcha_token: recaptchaToken,
        }),
      });

      const json = await response.json() as SubmitResponse & { errors?: Record<string, string[]> };

      if (!response.ok) {
        if (response.status === 422 && json.errors) {
          Object.entries(json.errors).forEach(([field, messages]) => {
            setError(field as keyof FormData, { type: 'server', message: messages[0] ?? 'Invalid input.' });
          });
          toast.error('Please review the highlighted fields.');
          return;
        }

        throw new Error(json.message || 'Unable to submit your report right now.');
      }

      const reference = json.reference?.trim() ?? '';
      setSubmitted({ reference });
      toast.success('Report submitted successfully.');
    } catch (error) {
      toast.error(error instanceof Error ? error.message : 'Unable to submit your report right now.');
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <AuthPageShell
      skipHref="#report-form"
      skipLabel="Skip to report form"
      onCanvasReady={(fn) => {
        addRippleRef.current = fn;
      }}
      onPageClick={(event) => addRippleRef.current?.(event.clientX, event.clientY)}
      backgroundImageUrl="/images/BACKGROUND.jpg"
    >
      <motion.main
        id="report-form"
        initial={{ opacity: 0, y: 18 }}
        animate={{ opacity: 1, y: 0 }}
        transition={{ duration: 0.7, ease: [0.25, 0.46, 0.45, 0.94] }}
        className="relative flex min-h-screen items-center justify-center px-4 py-10 lg:px-8"
      >
        <div className="w-full max-w-[760px]">
          <AuthCardFrame>
            <AuthCardHeader
              className="text-center"
              eyebrow="Help Desk"
              title="Public Report"
              description="Submit incident, lost item, medical, or other assistance requests directly from this page."
              note="Every new report will trigger an email notification to the response team."
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
                    <label htmlFor="report_type" className="mb-1.5 block text-sm font-semibold text-slate-700">
                      Type of Report <span className="text-red-500">*</span>
                    </label>
                    <div className="relative">
                      <select
                        id="report_type"
                        className={authInputClass(Boolean(errors.report_type), {
                          withIcon: false,
                          extraClassName: 'appearance-none pr-12 font-medium',
                        })}
                        value={selectedReportType}
                        {...register('report_type', { required: 'Please choose a report type.' })}
                        onChange={(event) => {
                          setValue('report_type', event.target.value as ReportType, { shouldValidate: true });
                          clearErrors('report_type');
                        }}
                      >
                        {REPORT_OPTIONS.map((option) => (
                          <option key={option.value} value={option.value}>
                            {option.title}
                          </option>
                        ))}
                      </select>
                      <ChevronDown className="pointer-events-none absolute right-4 top-1/2 h-5 w-5 -translate-y-1/2 text-sky-500" aria-hidden="true" />
                    </div>
                    <AnimatePresence><AuthInlineError message={errors.report_type?.message} /></AnimatePresence>
                  </div>

                  <div className="grid gap-5 sm:grid-cols-2">
                    <div>
                      <label htmlFor="name" className="mb-1.5 block text-sm font-semibold text-slate-700">
                        Name <span className="text-red-500">*</span>
                      </label>
                      <div className="relative">
                        <UserRound className="pointer-events-none absolute left-3.5 top-1/2 h-[18px] w-[18px] -translate-y-1/2 text-sky-400" aria-hidden="true" />
                        <input
                          id="name"
                          type="text"
                          autoComplete="name"
                          placeholder="Your full name"
                          className={inputClass('name')}
                          {...register('name', {
                            required: 'Name is required.',
                            maxLength: { value: 120, message: 'Name is too long.' },
                          })}
                        />
                      </div>
                      <AnimatePresence><AuthInlineError message={errors.name?.message} /></AnimatePresence>
                    </div>

                    <div>
                      <label htmlFor="phone_country_code" className="mb-1.5 block text-sm font-semibold text-slate-700">
                        Phone <span className="text-red-500">*</span>
                      </label>
                      <div className="grid grid-cols-[116px_minmax(0,1fr)] gap-2 sm:grid-cols-[122px_minmax(0,1fr)]">
                        <Controller
                          control={control}
                          name="phone_country_code"
                          render={({ field }) => (
                            <Select value={field.value} onValueChange={(value) => field.onChange(normalizePhoneCountryCode(value))}>
                              <SelectTrigger
                                id="phone_country_code"
                                className={selectClass('phone_country_code', 'px-3')}
                                aria-invalid={errors.phone_country_code ? 'true' : 'false'}
                              >
                                {selectedPhoneOption ? (
                                  <span className="flex items-center gap-1.5 truncate">
                                    <span className={`${selectedPhoneOption.flagClassName} h-4 w-[22px] rounded-[2px] shadow-sm`} aria-hidden="true" />
                                    <span className="truncate text-sm font-medium">{selectedPhoneOption.dialCode}</span>
                                  </span>
                                ) : (
                                  <SelectValue placeholder="Code" />
                                )}
                              </SelectTrigger>
                              <SelectContent className="rounded-xl border-sky-100">
                                {PRIORITY_PHONE_OPTIONS.map((option) => (
                                  <SelectItem key={`${option.country}-${option.dialCode}`} value={option.dialCode}>
                                    <span className="flex items-center gap-2.5">
                                      <span className={`${option.flagClassName} h-4 w-[22px] rounded-[2px] shadow-sm`} aria-hidden="true" />
                                      <span>{option.countryName}</span>
                                      <span className="text-slate-500">{option.dialCode}</span>
                                    </span>
                                  </SelectItem>
                                ))}
                                {OTHER_PHONE_OPTIONS.length > 0 && <SelectSeparator className="my-1 bg-sky-100" />}
                                {OTHER_PHONE_OPTIONS.map((option) => (
                                  <SelectItem key={`${option.country}-${option.dialCode}`} value={option.dialCode}>
                                    <span className="flex items-center gap-2.5">
                                      <span className={`${option.flagClassName} h-4 w-[22px] rounded-[2px] shadow-sm`} aria-hidden="true" />
                                      <span>{option.countryName}</span>
                                      <span className="text-slate-500">{option.dialCode}</span>
                                    </span>
                                  </SelectItem>
                                ))}
                              </SelectContent>
                            </Select>
                          )}
                        />
                        <div className="relative">
                          <Phone className="pointer-events-none absolute left-3.5 top-1/2 h-[18px] w-[18px] -translate-y-1/2 text-sky-400" aria-hidden="true" />
                          <input
                            id="phone_national_number"
                            type="text"
                            autoComplete="tel-national"
                            inputMode="tel"
                            placeholder="Phone number"
                            className={inputClass('phone_national_number', true)}
                            {...register('phone_national_number', {
                              required: 'Phone is required.',
                              maxLength: { value: 20, message: 'Phone is too long.' },
                              setValueAs: (value: string) => normalizePhoneNationalNumber(value),
                              onChange: (event) => { event.target.value = normalizePhoneNationalNumber(event.target.value); },
                            })}
                          />
                        </div>
                      </div>
                      <AnimatePresence><AuthInlineError message={errors.phone_country_code?.message} /></AnimatePresence>
                      <AnimatePresence><AuthInlineError message={errors.phone_national_number?.message} /></AnimatePresence>
                    </div>
                  </div>

                  <div className="grid gap-5 sm:grid-cols-2">
                    <div>
                      <label htmlFor="identity_number" className="mb-1.5 block text-sm font-semibold text-slate-700">
                        IC / Passport No.
                      </label>
                      <input
                        id="identity_number"
                        type="text"
                        autoComplete="off"
                        placeholder="Optional"
                        className={inputClass('identity_number', false)}
                        {...register('identity_number', {
                          maxLength: { value: 80, message: 'IC / Passport No. is too long.' },
                        })}
                      />
                      <AnimatePresence><AuthInlineError message={errors.identity_number?.message} /></AnimatePresence>
                    </div>

                    <div>
                      <label htmlFor="email" className="mb-1.5 block text-sm font-semibold text-slate-700">
                        E-mail
                      </label>
                      <div className="relative">
                        <Mail className="pointer-events-none absolute left-3.5 top-1/2 h-[18px] w-[18px] -translate-y-1/2 text-sky-400" aria-hidden="true" />
                        <input
                          id="email"
                          type="email"
                          autoComplete="email"
                          placeholder="Optional"
                          className={inputClass('email')}
                          {...register('email', {
                            pattern: { value: /^[^\s@]+@[^\s@]+\.[^\s@]+$/, message: 'Invalid email address.' },
                            maxLength: { value: 120, message: 'Email is too long.' },
                          })}
                        />
                      </div>
                      <AnimatePresence><AuthInlineError message={errors.email?.message} /></AnimatePresence>
                    </div>
                  </div>

                  <div className="grid gap-5 sm:grid-cols-2">
                    <div>
                      <label htmlFor="incident_date" className="mb-1.5 block text-sm font-semibold text-slate-700">
                        Incident Date <span className="text-red-500">*</span>
                      </label>
                      <input
                        id="incident_date"
                        type="date"
                        className={inputClass('incident_date', false)}
                        {...register('incident_date', {
                          required: 'Incident date is required.',
                        })}
                      />
                      <AnimatePresence><AuthInlineError message={errors.incident_date?.message} /></AnimatePresence>
                    </div>

                    <div>
                      <label htmlFor="incident_time" className="mb-1.5 block text-sm font-semibold text-slate-700">
                        Incident Time <span className="text-red-500">*</span>
                      </label>
                      <input
                        id="incident_time"
                        type="time"
                        className={inputClass('incident_time', false)}
                        {...register('incident_time', {
                          required: 'Incident time is required.',
                        })}
                      />
                      <AnimatePresence><AuthInlineError message={errors.incident_time?.message} /></AnimatePresence>
                    </div>
                  </div>

                  <div>
                    <label htmlFor="chronology" className="mb-1.5 block text-sm font-semibold text-slate-700">
                      Report / Chronology <span className="text-red-500">*</span>
                    </label>
                    <textarea
                      id="chronology"
                      rows={6}
                      placeholder="Explain what happened, where it happened, and any detail the team should know."
                      className={authInputClass(Boolean(errors.chronology), {
                        withIcon: false,
                        minHeightClassName: 'min-h-[160px] px-4 py-3',
                      })}
                      {...register('chronology', {
                        required: 'Report / Chronology is required.',
                        minLength: { value: 10, message: 'Please provide a bit more detail.' },
                        maxLength: { value: 4000, message: 'Report is too long.' },
                      })}
                    />
                    <p className="mt-1.5 text-[11px] text-slate-500">
                      Include the location, time, involved items, and immediate condition if available.
                    </p>
                    <AnimatePresence><AuthInlineError message={errors.chronology?.message} /></AnimatePresence>
                  </div>

                  <div>
                    <label htmlFor="staff_name" className="mb-1.5 block text-sm font-semibold text-slate-700">
                      Staff Name (if any)
                    </label>
                    <input
                      id="staff_name"
                      type="text"
                      autoComplete="off"
                      placeholder="Optional"
                      className={inputClass('staff_name', false)}
                      {...register('staff_name', {
                        maxLength: { value: 120, message: 'Staff name is too long.' },
                      })}
                    />
                    <AnimatePresence><AuthInlineError message={errors.staff_name?.message} /></AnimatePresence>
                  </div>

                  <div className="rounded-2xl border border-amber-200 bg-amber-50/90 px-4 py-4 text-sm text-slate-700 shadow-sm">
                    <p className="mb-2 text-[11px] font-semibold uppercase tracking-[0.18em] text-amber-700">Your IP</p>
                    <p className="mb-3 text-sm font-semibold text-slate-900">{requestIp || '-'}</p>
                    <p className="font-semibold text-slate-900">Disclaimer</p>
                    <p className="mt-1 leading-relaxed">
                      We are not obligated to respond to or act upon every report submitted. Our response and any subsequent action are subject to the urgency and nature of the matter.
                    </p>
                  </div>

                  <input type="hidden" {...register('recaptcha_token')} />
                  {recaptchaEnabled && (
                    <div className="rounded-2xl border border-sky-100 bg-white/90 p-4 shadow-sm">
                      <div className="mb-3 flex items-start gap-2">
                        <Lock className="mt-0.5 h-4 w-4 text-sky-500" aria-hidden="true" />
                        <div>
                          <p className="text-sm font-semibold text-slate-800">Background protection</p>
                          <p className="text-xs leading-relaxed text-slate-500">This form uses Google reCAPTCHA in the background before every report submission.</p>
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
                          <span>{isRecaptchaReady ? 'Protection ready. No checkbox is required.' : 'Preparing Google reCAPTCHA background protection...'}</span>
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
                  >
                    {isSubmitting ? (
                      <>
                        <Loader2 className="h-4 w-4 animate-spin" aria-hidden="true" />
                        <span>Submitting...</span>
                      </>
                    ) : (
                      <span>Submit Report</span>
                    )}
                  </motion.button>
                </div>
              </form>
            </div>
          </AuthCardFrame>

          <AnimatePresence>
            {submitted ? (
              <motion.div
                initial={{ opacity: 0 }}
                animate={{ opacity: 1 }}
                exit={{ opacity: 0 }}
                className="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 px-4"
              >
                <motion.div
                  initial={{ opacity: 0, y: 18, scale: 0.97 }}
                  animate={{ opacity: 1, y: 0, scale: 1 }}
                  exit={{ opacity: 0, y: 12, scale: 0.97 }}
                  transition={{ duration: 0.2 }}
                  className="w-full max-w-[420px] rounded-[1.8rem] border border-white/20 bg-white p-6 shadow-[0_30px_80px_rgba(0,0,0,0.28)]"
                >
                  <p className="text-lg font-semibold leading-snug text-slate-900">
                    Thank you we have received your report.
                  </p>
                  <p className="mt-3 text-sm leading-relaxed text-slate-600">
                    Our officer may contact you via whatsapp or e-mail in case we need more information regarding your report.
                  </p>
                  <p className="mt-3 text-sm leading-relaxed text-slate-600">
                    Kindly take note, We are not obligated to respond to or act upon every report submitted. Our response and any subsequent action are subject to the urgency and nature of the matter.
                  </p>
                  <div className="mt-5 rounded-2xl border border-sky-100 bg-sky-50 px-4 py-4">
                    <p className="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Case ID</p>
                    <p className="mt-2 text-2xl font-black tracking-tight text-sky-700">
                      {submitted.reference}
                    </p>
                  </div>
                  <div className="mt-6 flex justify-end">
                    <button
                      type="button"
                      onClick={() => setSubmitted(null)}
                      className={authPrimaryButtonClass('rounded-xl px-5 py-2.5 text-sm font-semibold normal-case tracking-normal shadow-[0_4px_14px_rgba(2,132,199,0.35)] focus:ring-offset-1')}
                    >
                      Close
                    </button>
                  </div>
                </motion.div>
              </motion.div>
            ) : null}
          </AnimatePresence>
        </div>
      </motion.main>
    </AuthPageShell>
  );
}
