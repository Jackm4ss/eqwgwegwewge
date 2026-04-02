import dayjs, { type Dayjs } from 'dayjs';
import { LocalizationProvider, TimePicker } from '@mui/x-date-pickers';
import { AdapterDayjs } from '@mui/x-date-pickers/AdapterDayjs';
import { renderTimeViewClock } from '@mui/x-date-pickers/timeViewRenderers';
import { useEffect, useRef, useState } from 'react';
import { AnimatePresence, motion } from 'motion/react';
import { CheckCircle2, ChevronDown, IdCard, Loader2, Lock, Mail, Phone, UserRound } from 'lucide-react';
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
import {
  Dialog,
  DialogClose,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '../ui/Dialog';
import { Button } from '../ui/Button';
import {
  OTHER_PHONE_OPTIONS,
  PHONE_DIAL_CODES,
  PHONE_OPTIONS,
  PRIORITY_PHONE_OPTIONS,
} from '@/lib/countryCatalog';

type ReportType = 'incident_security' | 'lost_item' | 'lost_locker_card' | 'medical_attention' | 'others';
type IdentityType = 'national_id' | 'passport' | '';

type FormData = {
  report_type: ReportType;
  name: string;
  phone_country_code: string;
  phone_national_number: string;
  identity_type: IdentityType;
  identity_number: string;
  email: string;
  incident_day: string;
  incident_month: string;
  incident_year: string;
  incident_date: string;
  incident_time: string;
  chronology: string;
  staff_name: string;
  recaptcha_token: string;
};

type SubmitResponse = {
  message?: string;
  reference?: string;
};

type SubmittedState = {
  reference: string;
  message: string;
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

const IDENTITY_TYPES: Array<{
  value: Exclude<IdentityType, ''>;
  label: string;
  description: string;
}> = [
  {
    value: 'national_id',
    label: 'Malaysia IC (MyKad)',
    description: 'Use your Malaysia IC / MyKad exactly as it appears on your card.',
  },
  {
    value: 'passport',
    label: 'Passport',
    description: 'Use a valid passport number that matches your travel document.',
  },
];

const INCIDENT_MONTH_OPTIONS = [
  { value: '04', label: 'April' },
] as const;

const INCIDENT_DAY_OPTIONS = Array.from({ length: 31 }, (_, index) => {
  const value = String(index + 1).padStart(2, '0');

  return {
    value,
    label: value,
  };
});

const INCIDENT_YEAR_OPTIONS = ['2026'] as const;

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

function buildIncidentDate(day: string, month: string, year: string) {
  if (day === '' || month === '' || year === '') {
    return null;
  }

  const dayNumber = Number(day);
  const monthNumber = Number(month);
  const yearNumber = Number(year);

  if (!Number.isInteger(dayNumber) || !Number.isInteger(monthNumber) || !Number.isInteger(yearNumber)) {
    return null;
  }

  const parsedDate = new Date(yearNumber, monthNumber - 1, dayNumber);

  if (
    parsedDate.getFullYear() !== yearNumber
    || parsedDate.getMonth() !== monthNumber - 1
    || parsedDate.getDate() !== dayNumber
  ) {
    return null;
  }

  return `${year}-${month}-${day}`;
}

function parseIncidentTimeValue(value: string): Dayjs | null {
  if (!/^\d{2}:\d{2}$/.test(value)) {
    return null;
  }

  const parsed = dayjs(`2026-04-01T${value}`);

  return parsed.isValid() ? parsed : null;
}

export function ReportPage() {
  const recaptchaEnabled = getMetaContent('recaptcha-enabled') === '1';
  const recaptchaSiteKey = getMetaContent('recaptcha-site-key');
  const requestIp = getMetaContent('request-ip');
  const addRippleRef = useRef<((x: number, y: number) => void) | null>(null);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [submitted, setSubmitted] = useState<SubmittedState | null>(null);
  const [isRecaptchaReady, setIsRecaptchaReady] = useState(!recaptchaEnabled);
  const [isIncidentTimePickerOpen, setIsIncidentTimePickerOpen] = useState(false);

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
      identity_type: '',
      identity_number: '',
      email: '',
      incident_day: '',
      incident_month: '04',
      incident_year: '2026',
      incident_date: '',
      incident_time: '',
      chronology: '',
      staff_name: '',
      recaptcha_token: '',
    },
  });

  const selectedReportType = watch('report_type');
  const selectedIdentityType = watch('identity_type');
  const selectedPhoneCountryCode = watch('phone_country_code');
  const selectedIncidentDay = watch('incident_day');
  const selectedIncidentMonth = watch('incident_month');
  const selectedIncidentYear = watch('incident_year');
  const selectedPhoneOption = PHONE_OPTIONS.find((item) => item.dialCode === selectedPhoneCountryCode);
  const inputClass = (field: keyof FormData, withIcon = true) => authInputClass(Boolean(errors[field]), { withIcon });
  const selectClass = (field: keyof FormData, extraClassName?: string) => authSelectClass(Boolean(errors[field]), extraClassName);
  const incidentDateSelectClass = authSelectClass(Boolean(errors.incident_date), 'px-3');
  const identityNumberLabel = selectedIdentityType === 'national_id'
    ? 'Malaysia IC (MyKad) Number'
    : selectedIdentityType === 'passport'
      ? 'Passport Number'
      : 'Document Number';
  const identityNumberPlaceholder = selectedIdentityType === 'national_id'
    ? 'Example: 901231101234'
    : selectedIdentityType === 'passport'
      ? 'Example: A1234567'
      : 'Select document type first';
  const identityNumberHelperText = selectedIdentityType === 'national_id'
    ? 'For Malaysian citizens or permanent residents, use your IC / MyKad number.'
    : selectedIdentityType === 'passport'
      ? 'Use a valid passport number that matches your travel document.'
      : 'Choose whether this report uses Malaysia IC (MyKad) or Passport first.';

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

  useEffect(() => {
    const normalizedIncidentDate = buildIncidentDate(
      selectedIncidentDay,
      selectedIncidentMonth,
      selectedIncidentYear,
    );

    setValue('incident_date', normalizedIncidentDate ?? '', {
      shouldDirty: normalizedIncidentDate !== null,
      shouldValidate: false,
    });

    if (normalizedIncidentDate) {
      clearErrors('incident_date');
    }
  }, [clearErrors, selectedIncidentDay, selectedIncidentMonth, selectedIncidentYear, setValue]);

  const executeRecaptchaToken = async () => {
    if (!recaptchaEnabled) {
      setValue('recaptcha_token', '', { shouldValidate: false });
      return '';
    }

    if (!recaptchaSiteKey) {
      toast.error('reCAPTCHA is not configured. Please contact the administrator.');
      setError('recaptcha_token', {
        type: 'manual',
        message: 'reCAPTCHA is not configured. Please contact the administrator.',
      });
      return null;
    }

    clearErrors('recaptcha_token');

    const grecaptcha = await ensureRecaptcha(recaptchaSiteKey);
    if (!grecaptcha) {
      setIsRecaptchaReady(false);
      toast.error('Failed to load reCAPTCHA. Please try again.');
      setError('recaptcha_token', {
        type: 'manual',
        message: 'Failed to load reCAPTCHA. Please try again.',
      });
      return null;
    }

    setIsRecaptchaReady(true);

    try {
      const token = (await grecaptcha.execute(recaptchaSiteKey, { action: 'public_report_submit' })).trim();

      if (token === '') {
        toast.error('Failed to verify reCAPTCHA. Please try again.');
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
      toast.error('Failed to verify reCAPTCHA. Please try again.');
      setError('recaptcha_token', {
        type: 'manual',
        message: 'Failed to verify reCAPTCHA. Please try again.',
      });
      return null;
    }
  };

  const onSubmit = async (data: FormData) => {
    clearErrors();
    setIsSubmitting(true);
    setSubmitted(null);

    try {
      const normalizedIncidentDate = buildIncidentDate(
        data.incident_day,
        data.incident_month,
        data.incident_year,
      );

      if (!normalizedIncidentDate) {
        setError('incident_date', {
          type: 'manual',
          message: 'Please select date, month, and year.',
        });
        return;
      }

      const recaptchaToken = await executeRecaptchaToken();

      if (recaptchaEnabled && (!recaptchaToken || !recaptchaToken.trim())) {
        return;
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
          identity_type: data.identity_type,
          identity_number: data.identity_number.trim().toUpperCase(),
          email: data.email.trim().toLowerCase(),
          incident_date: normalizedIncidentDate,
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

          const primaryError = json.errors.recaptcha_token?.[0]
            ?? json.errors.incident_date?.[0]
            ?? Object.values(json.errors)[0]?.[0]
            ?? 'Please review the highlighted fields.';

          toast.error(primaryError);
          return;
        }

        throw new Error(json.message || 'Unable to submit your report right now.');
      }

      const reference = json.reference?.trim() ?? '';
      const successMessage = json.message?.trim() || 'Your report has been submitted successfully.';

      setSubmitted({
        reference,
        message: successMessage,
      });
      toast.success(successMessage, {
        description: reference ? `Reference number: ${reference}` : undefined,
      });
    } catch (error) {
      toast.error(error instanceof Error ? error.message : 'Unable to submit your report right now.');
    } finally {
      setValue('recaptcha_token', '', { shouldValidate: false });
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
        className="relative flex min-h-screen items-center justify-center px-4 py-10"
      >
        <div className="w-full">
          <AuthCardFrame className="relative mx-auto w-full max-w-[520px]">
            <AuthCardHeader
              className="text-center"
              eyebrow="Help Desk"
              title="Public Report"
              description="Submit incident, lost item, medical, or other assistance requests directly from this page."
              note="Every submitted report is stored in the help desk dashboard for review."
              topSlot={(
                <div className="flex items-center justify-center gap-4">
                  <div className="rounded-[1.35rem] border border-white/15 bg-white/10 px-4 py-3 shadow-[0_14px_45px_rgba(12,74,110,0.22)] backdrop-blur-sm">
                    <img src={SONGKRAN_LOGO_URL} alt="Songkran Festival 2026 logo" className="h-12 w-auto" />
                  </div>
                </div>
              )}
            />

            <div className="bg-white">
              <form onSubmit={handleSubmit(onSubmit)} noValidate>
                <div className="space-y-5 px-7 py-6">
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

                  <div className="grid gap-5">
                    <div>
                      <label htmlFor="name" className="mb-1.5 block text-sm font-semibold text-slate-700">
                        Full Name <span className="text-red-500">*</span>
                      </label>
                      <div className="relative">
                        <UserRound className="pointer-events-none absolute left-3.5 top-1/2 h-[18px] w-[18px] -translate-y-1/2 text-sky-400" aria-hidden="true" />
                        <input
                          id="name"
                          type="text"
                          autoComplete="name"
                          placeholder="Enter your full name"
                          className={inputClass('name')}
                          {...register('name', {
                            required: 'Full name is required.',
                            maxLength: { value: 120, message: 'Name is too long.' },
                          })}
                        />
                      </div>
                      <AnimatePresence><AuthInlineError message={errors.name?.message} /></AnimatePresence>
                    </div>

                    <div>
                      <label htmlFor="email" className="mb-1.5 block text-sm font-semibold text-slate-700">
                        Email <span className="text-red-500">*</span>
                      </label>
                      <div className="relative">
                        <Mail className="pointer-events-none absolute left-3.5 top-1/2 h-[18px] w-[18px] -translate-y-1/2 text-sky-400" aria-hidden="true" />
                        <input
                          id="email"
                          type="email"
                          autoComplete="email"
                          placeholder="Your email"
                          className={inputClass('email')}
                          {...register('email', {
                            required: 'Email is required.',
                            setValueAs: (value: string) => value.trim().toLowerCase(),
                            pattern: { value: /^[^\s@]+@[^\s@]+\.[^\s@]+$/, message: 'Invalid email address.' },
                            maxLength: { value: 120, message: 'Email is too long.' },
                          })}
                        />
                      </div>
                      <AnimatePresence><AuthInlineError message={errors.email?.message} /></AnimatePresence>
                    </div>

                    <div>
                      <label htmlFor="phone_country_code" className="mb-1.5 block text-sm font-semibold text-slate-700">
                        Phone Number <span className="text-red-500">*</span>
                      </label>
                      <div className="grid grid-cols-[116px_minmax(0,1fr)] gap-2">
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
                            placeholder="123456789"
                            className={inputClass('phone_national_number', true)}
                            {...register('phone_national_number', {
                              required: 'Phone number is required.',
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

                  <div>
                    <label htmlFor="identity_type" className="mb-1.5 block text-sm font-semibold text-slate-700">
                      Document Type <span className="text-red-500">*</span>
                    </label>
                    <div className="relative">
                      <IdCard className="pointer-events-none absolute left-3.5 top-1/2 h-[18px] w-[18px] -translate-y-1/2 text-sky-400" aria-hidden="true" />
                      <select
                        id="identity_type"
                        className={authInputClass(Boolean(errors.identity_type), {
                          withIcon: true,
                          extraClassName: 'appearance-none cursor-pointer pr-12',
                        })}
                        value={selectedIdentityType}
                        {...register('identity_type', { required: 'Document type is required.' })}
                        onChange={(event) => {
                          const nextValue = event.target.value as IdentityType;
                          setValue('identity_type', nextValue, { shouldValidate: true });
                          setValue('identity_number', '', { shouldValidate: false });
                          clearErrors(['identity_type', 'identity_number']);
                        }}
                      >
                        <option value="">Select a document type</option>
                        {IDENTITY_TYPES.map((option) => (
                          <option key={option.value} value={option.value}>
                            {option.label}
                          </option>
                        ))}
                      </select>
                      <ChevronDown className="pointer-events-none absolute right-4 top-1/2 h-5 w-5 -translate-y-1/2 text-sky-500" aria-hidden="true" />
                    </div>
                    <p className="mt-1.5 text-[11px] leading-relaxed text-slate-500">
                      {IDENTITY_TYPES.find((option) => option.value === selectedIdentityType)?.description
                        ?? 'Choose whether this report uses Malaysia IC (MyKad) or Passport.'}
                    </p>
                    <AnimatePresence><AuthInlineError message={errors.identity_type?.message} /></AnimatePresence>
                  </div>

                  <div>
                    <label htmlFor="identity_number" className="mb-1.5 block text-sm font-semibold text-slate-700">
                      {identityNumberLabel} <span className="text-red-500">*</span>
                    </label>
                    <div className="relative">
                      <IdCard className="pointer-events-none absolute left-3.5 top-1/2 h-[18px] w-[18px] -translate-y-1/2 text-sky-400" aria-hidden="true" />
                      <input
                        id="identity_number"
                        type="text"
                        autoComplete="off"
                        placeholder={identityNumberPlaceholder}
                        disabled={selectedIdentityType === ''}
                        className={inputClass('identity_number')}
                        {...register('identity_number', {
                          required: selectedIdentityType === 'passport'
                            ? 'Passport Number is required.'
                            : selectedIdentityType === 'national_id'
                              ? 'Malaysia IC (MyKad) Number is required.'
                              : 'Document number is required.',
                          setValueAs: (value: string) => value.trim().toUpperCase(),
                          maxLength: { value: 80, message: `${identityNumberLabel} is too long.` },
                        })}
                      />
                    </div>
                    <p className="mt-1.5 text-[11px] leading-relaxed text-slate-500">
                      {identityNumberHelperText}
                    </p>
                    <AnimatePresence><AuthInlineError message={errors.identity_number?.message} /></AnimatePresence>
                  </div>

                  <div className="grid gap-5">
                    <div>
                      <label className="mb-1.5 block text-sm font-semibold text-slate-700">
                        Incident Date <span className="text-red-500">*</span>
                      </label>
                      <div className="grid grid-cols-3 gap-2">
                        <Controller
                          control={control}
                          name="incident_day"
                          render={({ field }) => (
                            <Select value={field.value} onValueChange={field.onChange}>
                              <SelectTrigger id="incident_day" className={incidentDateSelectClass}>
                                <SelectValue placeholder="Date" />
                              </SelectTrigger>
                              <SelectContent className="rounded-xl border-sky-100">
                                {INCIDENT_DAY_OPTIONS.map((option) => (
                                  <SelectItem key={option.value} value={option.value}>
                                    {option.label}
                                  </SelectItem>
                                ))}
                              </SelectContent>
                            </Select>
                          )}
                        />
                        <Controller
                          control={control}
                          name="incident_month"
                          render={({ field }) => (
                            <Select value={field.value} onValueChange={field.onChange} disabled>
                              <SelectTrigger id="incident_month" className={incidentDateSelectClass}>
                                <SelectValue placeholder="Month" />
                              </SelectTrigger>
                              <SelectContent className="rounded-xl border-sky-100">
                                {INCIDENT_MONTH_OPTIONS.map((option) => (
                                  <SelectItem key={option.value} value={option.value}>
                                    {option.label}
                                  </SelectItem>
                                ))}
                              </SelectContent>
                            </Select>
                          )}
                        />
                        <Controller
                          control={control}
                          name="incident_year"
                          render={({ field }) => (
                            <Select value={field.value} onValueChange={field.onChange} disabled>
                              <SelectTrigger id="incident_year" className={incidentDateSelectClass}>
                                <SelectValue placeholder="Year" />
                              </SelectTrigger>
                              <SelectContent className="rounded-xl border-sky-100">
                                {INCIDENT_YEAR_OPTIONS.map((option) => (
                                  <SelectItem key={option} value={option}>
                                    {option}
                                  </SelectItem>
                                ))}
                              </SelectContent>
                            </Select>
                          )}
                        />
                      </div>
                      <input
                        type="hidden"
                        {...register('incident_date', {
                          validate: () => buildIncidentDate(
                            selectedIncidentDay,
                            selectedIncidentMonth,
                            selectedIncidentYear,
                          ) !== null || 'Please select date, month, and year.',
                        })}
                      />
                      <p className="mt-1.5 text-[11px] leading-relaxed text-slate-500">
                        Select the date. Month is fixed to April and year is fixed to 2026.
                      </p>
                      <AnimatePresence><AuthInlineError message={errors.incident_date?.message} /></AnimatePresence>
                    </div>

                    <div>
                      <label htmlFor="incident_time" className="mb-1.5 block text-sm font-semibold text-slate-700">
                        Incident Time <span className="text-red-500">*</span>
                      </label>
                      <Controller
                        control={control}
                        name="incident_time"
                        rules={{
                          required: 'Incident time is required.',
                        }}
                        render={({ field }) => (
                          <LocalizationProvider dateAdapter={AdapterDayjs}>
                            <TimePicker
                              open={isIncidentTimePickerOpen}
                              value={parseIncidentTimeValue(field.value)}
                              onOpen={() => setIsIncidentTimePickerOpen(true)}
                              onChange={(nextValue) => {
                                if (nextValue && nextValue.isValid()) {
                                  field.onChange(nextValue.format('HH:mm'));
                                  clearErrors('incident_time');
                                  return;
                                }

                                field.onChange('');
                              }}
                              onClose={() => {
                                setIsIncidentTimePickerOpen(false);
                                field.onBlur();
                              }}
                              views={['hours', 'minutes']}
                              openTo="hours"
                              ampm
                              ampmInClock
                              minutesStep={1}
                              format="hh:mm A"
                              viewRenderers={{
                                hours: renderTimeViewClock,
                                minutes: renderTimeViewClock,
                              }}
                              slotProps={{
                                textField: {
                                  id: 'incident_time',
                                  fullWidth: true,
                                  placeholder: 'Select incident time',
                                  onBlur: () => field.onBlur(),
                                  onClick: () => setIsIncidentTimePickerOpen(true),
                                  onKeyDown: (event) => {
                                    if (event.key === 'Enter' || event.key === ' ' || event.key === 'ArrowDown') {
                                      event.preventDefault();
                                      setIsIncidentTimePickerOpen(true);
                                    }
                                  },
                                  error: Boolean(errors.incident_time),
                                  sx: {
                                    '& .MuiOutlinedInput-root': {
                                      minHeight: 50,
                                      borderRadius: '0.75rem',
                                      backgroundColor: '#ffffff',
                                      fontSize: '1rem',
                                      paddingRight: '0.2rem',
                                      '& fieldset': {
                                        borderWidth: 2,
                                        borderColor: errors.incident_time ? '#f87171' : '#bae6fd',
                                      },
                                      '&:hover fieldset': {
                                        borderColor: errors.incident_time ? '#ef4444' : '#7dd3fc',
                                      },
                                      '&.Mui-focused fieldset': {
                                        borderColor: errors.incident_time ? '#ef4444' : '#0ea5e9',
                                      },
                                    },
                                    '& .MuiInputBase-input': {
                                      padding: '13px 16px',
                                      color: '#1e293b',
                                    },
                                    '& .MuiInputAdornment-root .MuiIconButton-root': {
                                      color: '#0ea5e9',
                                    },
                                  },
                                },
                                actionBar: {
                                  actions: ['clear', 'cancel', 'accept'],
                                },
                                desktopPaper: {
                                  sx: {
                                    borderRadius: '1.25rem',
                                    border: '1px solid #e0f2fe',
                                    boxShadow: '0 18px 50px rgba(2, 132, 199, 0.16)',
                                  },
                                },
                                mobilePaper: {
                                  sx: {
                                    borderRadius: '1.5rem',
                                  },
                                },
                                layout: {
                                  sx: {
                                    '& .MuiPickersToolbar-root': {
                                      backgroundColor: '#f0f9ff',
                                    },
                                    '& .MuiTimeClock-root': {
                                      backgroundColor: '#ffffff',
                                    },
                                  },
                                },
                              }}
                            />
                          </LocalizationProvider>
                        )}
                      />
                      <p className="mt-1.5 text-[11px] leading-relaxed text-slate-500">
                        Use the clock picker to select the incident hour and minute.
                      </p>
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

                <div className="flex flex-col gap-3 border-t border-slate-100 bg-slate-50 px-7 py-4 sm:flex-row sm:items-center sm:justify-between">
                  <div className="inline-flex w-fit items-center gap-3 rounded-full border border-sky-200 bg-white px-3 py-2 shadow-[0_10px_25px_rgba(14,165,233,0.08)]">
                    <span className="rounded-full bg-sky-50 px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.18em] text-sky-700">
                      Your IP
                    </span>
                    <span className="text-sm font-semibold text-slate-800">{requestIp || '-'}</span>
                  </div>
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

          <Dialog open={submitted !== null} onOpenChange={(open) => !open && setSubmitted(null)}>
            {submitted ? (
              <DialogContent showCloseButton={false} className="max-w-xl gap-0 overflow-hidden rounded-[1.75rem] border-sky-100 p-0 shadow-2xl">
                <div className="bg-gradient-to-r from-sky-700 to-cyan-500 px-6 py-5 text-white">
                  <DialogHeader className="text-left">
                    <div className="mb-3 inline-flex h-12 w-12 items-center justify-center rounded-[1rem] border border-white/25 bg-white/12 text-white shadow-[inset_0_1px_0_rgba(255,255,255,0.2)]">
                      <CheckCircle2 className="h-6 w-6" aria-hidden="true" />
                    </div>
                    <DialogTitle id="report-success-title" className="text-xl font-bold tracking-tight" style={{ fontFamily: '"Kanit", sans-serif' }}>
                      Report Received
                    </DialogTitle>
                    <DialogDescription id="report-success-description" className="text-sm text-sky-50/90">
                      {submitted.message}
                    </DialogDescription>
                  </DialogHeader>
                </div>

                <div className="space-y-4 px-6 py-5">
                  <div className="rounded-[1.35rem] border border-sky-100 bg-sky-50/80 px-4 py-4 shadow-[0_10px_24px_rgba(14,116,144,0.08)]">
                    <div className="flex items-start justify-between gap-3">
                      <div>
                        <p className="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">
                          Reference Number
                        </p>
                        <p className="mt-2 text-2xl font-black tracking-[-0.03em] text-sky-800">
                          {submitted.reference || 'Pending'}
                        </p>
                      </div>
                      <span className="rounded-full bg-white px-3 py-1 text-[11px] font-semibold text-sky-700 shadow-sm">
                        Keep this for tracking
                      </span>
                    </div>
                  </div>

                  <div className="rounded-[1.2rem] border border-slate-100 bg-white px-4 py-4 text-sm leading-7 text-slate-600">
                    <p className="font-semibold text-slate-900">What happens next</p>
                    <p className="mt-1">
                      We have safely recorded your report. Please keep the reference number above because it will be used for tracking your report.
                    </p>
                    <p className="mt-2">
                      Our help desk team will review your report from the admin dashboard. Any follow-up or action will depend on the urgency and nature of the case.
                    </p>
                  </div>
                </div>

                <DialogFooter className="border-t border-slate-100 px-6 py-4">
                  <DialogClose asChild>
                    <Button type="button" className="bg-sky-600 text-white hover:bg-sky-700">
                      Done
                    </Button>
                  </DialogClose>
                </DialogFooter>
              </DialogContent>
            ) : null}
          </Dialog>
        </div>
      </motion.main>
    </AuthPageShell>
  );
}
