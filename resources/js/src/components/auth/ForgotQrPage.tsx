import { useCallback, useEffect, useRef, useState } from 'react';
import { Controller, useForm } from 'react-hook-form';
import { AnimatePresence, motion } from 'motion/react';
import { toast } from 'sonner';
import {
  AlertCircle,
  CheckCircle2,
  IdCard,
  Loader2,
  Lock,
  Mail,
  Phone,
  QrCode,
  Search,
  UserRoundSearch,
} from 'lucide-react';

import {
  AuthCardFrame,
  AuthCardHeader,
  AuthCodeBadge,
  AuthInlineError,
  AuthPageShell,
  authInputClass,
  authLockedFieldClass,
  authPrimaryButtonClass,
  authSelectClass,
} from './AuthShared';
import { Button } from '../ui/Button';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectSeparator,
  SelectTrigger,
  SelectValue,
} from '../ui/Select';

type SearchType = 'email' | 'phone' | 'passport' | 'ic';
type IdentityType = 'passport' | 'national_id';

type FormData = {
  search_type: SearchType;
  email: string;
  phone_country_code: string;
  phone_national_number: string;
  country: string;
  identity_type: IdentityType;
  identity_number: string;
  recaptcha_token: string;
};

type Participant = {
  full_name: string;
  email: string;
  phone_country_code: string;
  phone_national_number: string;
  phone_number: string;
  country: string;
  identity_type: string;
  identity_number: string;
  account_status: string;
  verification_status: string;
  ticket_code: string;
  entry_code_display: string;
};

type LookupResponse = {
  found: boolean;
  message?: string;
  participant?: Participant;
  ticket_url?: string;
  ticket_code?: string;
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
const PRIORITY_COUNTRIES = ['MY', 'TH', 'SG', 'ID', 'BN', 'MM', 'VN'] as const;
const FORM_FIELDS: Array<keyof FormData> = [
  'search_type',
  'email',
  'phone_country_code',
  'phone_national_number',
  'country',
  'identity_type',
  'identity_number',
  'recaptcha_token',
];

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

const SEARCH_OPTIONS: Array<{ value: SearchType; title: string; desc: string; icon: typeof Mail }> = [
  { value: 'email', title: 'Email', desc: 'Use the exact email used during registration.', icon: Mail },
  { value: 'phone', title: 'Phone Number', desc: 'Use country code and the exact mobile number.', icon: Phone },
  { value: 'passport', title: 'Passport', desc: 'Match country and passport number exactly.', icon: QrCode },
  { value: 'ic', title: 'IC / MyKad', desc: 'Lookup with the registered Malaysia IC number.', icon: IdCard },
];

const SORTED_COUNTRIES = [...COUNTRIES].sort((a, b) => a.name.localeCompare(b.name));
const PHONE_OPTIONS = SORTED_COUNTRIES.map((country) => ({
  country: country.code,
  countryName: country.name,
  dialCode: PHONE_DIAL_CODES[country.code],
  flagClassName: `fi fi-${country.code.toLowerCase()}`,
}));
const PRIORITY_PHONE_OPTIONS = PRIORITY_COUNTRIES.map((code) => PHONE_OPTIONS.find((item) => item.country === code)).filter(Boolean) as typeof PHONE_OPTIONS;
const OTHER_PHONE_OPTIONS = PHONE_OPTIONS.filter((item) => !PRIORITY_COUNTRIES.includes(item.country as (typeof PRIORITY_COUNTRIES)[number]));
const PRIORITY_SORTED_COUNTRIES = PRIORITY_COUNTRIES.map((code) => SORTED_COUNTRIES.find((item) => item.code === code)).filter(Boolean) as typeof SORTED_COUNTRIES;
const OTHER_SORTED_COUNTRIES = SORTED_COUNTRIES.filter((item) => !PRIORITY_COUNTRIES.includes(item.code as (typeof PRIORITY_COUNTRIES)[number]));

let recaptchaLoader: Promise<Grecaptcha | null> | null = null;

function getMetaContent(name: string) {
  return (document.querySelector(`meta[name="${name}"]`) as HTMLMetaElement | null)?.content?.trim() ?? '';
}

function normalizePhoneCountryCode(value: string) {
  const digits = value.replace(/\D/g, '');
  return digits ? `+${digits}` : '';
}

function normalizePhoneNationalNumber(value: string) {
  return value.replace(/\D/g, '').replace(/^0+/, '');
}

function buildPhoneNumber(phoneCountryCode: string, phoneNationalNumber: string) {
  return `${normalizePhoneCountryCode(phoneCountryCode)}${normalizePhoneNationalNumber(phoneNationalNumber)}`;
}

function isFormField(value: string): value is keyof FormData {
  return FORM_FIELDS.includes(value as keyof FormData);
}

function humanizeCountry(code: string) {
  const country = COUNTRIES.find((item) => item.code === code);
  return country ? `${country.name} (${country.code})` : code || '-';
}

function humanizeIdentityType(type: string) {
  return type === 'national_id' ? 'Malaysia IC (MyKad)' : 'Passport';
}

function tone(status: string) {
  const normalized = status.trim().toLowerCase();

  if (normalized === 'active' || normalized === 'verified') {
    return 'border-emerald-200 bg-emerald-50 text-emerald-700';
  }

  if (!normalized) {
    return 'border-slate-200 bg-slate-50 text-slate-500';
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

export function ForgotQrPage() {
  const recaptchaEnabled = getMetaContent('recaptcha-enabled') === '1';
  const recaptchaSiteKey = getMetaContent('recaptcha-site-key');
  const addRippleRef = useRef<((x: number, y: number) => void) | null>(null);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [isRecaptchaReady, setIsRecaptchaReady] = useState(!recaptchaEnabled);
  const [result, setResult] = useState<{ participant: Participant; ticketUrl: string; ticketCode: string } | null>(null);
  const [notFoundMessage, setNotFoundMessage] = useState('');

  const {
    control,
    register,
    watch,
    handleSubmit,
    setValue,
    setError,
    clearErrors,
    formState: { errors },
  } = useForm<FormData>({
    mode: 'onTouched',
    defaultValues: {
      search_type: 'email',
      email: '',
      phone_country_code: PHONE_DIAL_CODES.MY,
      phone_national_number: '',
      country: 'MY',
      identity_type: 'passport',
      identity_number: '',
      recaptcha_token: '',
    },
  });

  const searchType = watch('search_type');
  const selectedCountry = watch('country');
  const selectedPhoneCountryCode = watch('phone_country_code');
  const selectedPhoneOption = PHONE_OPTIONS.find((item) => item.dialCode === selectedPhoneCountryCode);
  const selectedCountryOption = SORTED_COUNTRIES.find((item) => item.code === selectedCountry);
  const resultCountryOption = result ? SORTED_COUNTRIES.find((item) => item.code === result.participant.country) : null;

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
    if (searchType === 'passport') {
      setValue('identity_type', 'passport', { shouldValidate: false });
    }

    if (searchType === 'ic') {
      setValue('country', 'MY', { shouldValidate: false });
      setValue('identity_type', 'national_id', { shouldValidate: false });
    }
  }, [searchType, setValue]);

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
      const token = (await grecaptcha.execute(recaptchaSiteKey, { action: 'forgot_qr_lookup' })).trim();
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

  const selectSearchType = (next: SearchType) => {
    setValue('search_type', next, { shouldValidate: false });
    clearErrors();
    setResult(null);
    setNotFoundMessage('');
  };

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
        search_type: data.search_type,
        email: data.search_type === 'email' ? data.email.trim().toLowerCase() : '',
        phone_country_code: data.search_type === 'phone' ? normalizePhoneCountryCode(data.phone_country_code) : '',
        phone_national_number: data.search_type === 'phone' ? normalizePhoneNationalNumber(data.phone_national_number) : '',
        country: data.search_type === 'passport' ? data.country.trim().toUpperCase() : data.search_type === 'ic' ? 'MY' : '',
        identity_type: data.search_type === 'passport' ? 'passport' : data.search_type === 'ic' ? 'national_id' : '',
        identity_number: data.search_type === 'passport' || data.search_type === 'ic' ? data.identity_number.trim().toUpperCase() : '',
        recaptcha_token: recaptchaToken ?? '',
      };

      const response = await fetch('/api/forgot-qr/lookup', {
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

        throw new Error(json.message || 'Something went wrong while looking up your ticket.');
      }

      if (!json.found || !json.participant || !json.ticket_url || !json.ticket_code) {
        setNotFoundMessage(json.message || 'Participant data was not found.');
        return;
      }

      setResult({
        participant: json.participant,
        ticketUrl: json.ticket_url,
        ticketCode: json.ticket_code,
      });
      toast.success('Participant data found.');
    } catch (error) {
      toast.error(error instanceof Error ? error.message : 'Something went wrong while looking up your ticket.');
    } finally {
      setValue('recaptcha_token', '', { shouldValidate: false });
      setIsSubmitting(false);
    }
  };

  const inputClass = (field: keyof FormData, withIcon = true) =>
    authInputClass(Boolean(errors[field]), { withIcon });
  const selectClass = (field: keyof FormData) =>
    authSelectClass(Boolean(errors[field]));
  const lockedFieldClass = authLockedFieldClass;

  return (
    <AuthPageShell
      skipHref="#forgot-qr-form"
      skipLabel="Skip to forgot QR form"
      onCanvasReady={(fn) => {
        addRippleRef.current = fn;
      }}
      onPageClick={(event) => addRippleRef.current?.(event.clientX, event.clientY)}
      backgroundImageUrl="/images/BACKGROUND.jpg"
    >
      <motion.main
        id="forgot-qr-form"
        initial={{ opacity: 0, y: 18 }}
        animate={{ opacity: 1, y: 0 }}
        transition={{ duration: 0.7, ease: [0.25, 0.46, 0.45, 0.94] }}
        className="relative flex min-h-screen items-center justify-center px-4 py-10 lg:px-8"
        style={{ zIndex: 2 }}
      >
        <div className="w-full max-w-[560px]">
          <AuthCardFrame>
            <AuthCardHeader
              className="text-center"
              eyebrow="Ticket Recovery"
              title="Forgot QR"
              description="Find your registration using the same data you used during signup, then jump straight to your ticket."
              note="Manual lookup only. Search will run after you press the button below."
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
                    <p className="mb-3 text-sm font-semibold text-slate-700">Choose your lookup method</p>
                    <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
                      {SEARCH_OPTIONS.map((option) => {
                        const Icon = option.icon;
                        const active = searchType === option.value;

                        return (
                          <motion.button
                            key={option.value}
                            type="button"
                            whileHover={{ scale: 1.01 }}
                            whileTap={{ scale: 0.99 }}
                            onClick={() => selectSearchType(option.value)}
                            aria-pressed={active}
                            className={`rounded-2xl border px-4 py-4 text-left transition-all ${active
                              ? 'border-sky-500 bg-sky-50 shadow-[0_10px_30px_rgba(14,165,233,0.16)]'
                              : 'border-sky-100 bg-white hover:border-sky-300 hover:bg-sky-50/60'
                            }`}
                          >
                            <div className="mb-3 flex items-center gap-3">
                              <div className={`flex h-10 w-10 items-center justify-center rounded-2xl ${active ? 'bg-sky-500 text-white' : 'bg-sky-100 text-sky-700'}`}>
                                <Icon className="h-5 w-5" aria-hidden="true" />
                              </div>
                              <p className="text-sm font-bold text-slate-900">{option.title}</p>
                            </div>
                            <p className="text-xs leading-relaxed text-slate-500">{option.desc}</p>
                          </motion.button>
                        );
                      })}
                    </div>
                  </div>

                  <AnimatePresence mode="wait">
                    <motion.div
                      key={searchType}
                      initial={{ opacity: 0, y: 8 }}
                      animate={{ opacity: 1, y: 0 }}
                      exit={{ opacity: 0, y: -8 }}
                      transition={{ duration: 0.2 }}
                      className="space-y-5"
                    >
                      {searchType === 'email' && (
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
                              placeholder="Enter the registered email"
                              className={inputClass('email')}
                              {...register('email', {
                                required: searchType === 'email' ? 'Email is required.' : false,
                                pattern: { value: /^[^\s@]+@[^\s@]+\.[^\s@]+$/, message: 'Invalid email format.' },
                              })}
                            />
                          </div>
                          <AnimatePresence><AuthInlineError message={errors.email?.message} /></AnimatePresence>
                        </div>
                      )}

                      {searchType === 'phone' && (
                        <div>
                          <label htmlFor="phone_country_code" className="mb-1.5 block text-sm font-semibold text-slate-700">
                            Phone Number <span className="text-red-500">*</span>
                          </label>
                          <div className="grid grid-cols-1 gap-3 sm:grid-cols-[190px_minmax(0,1fr)]">
                            <Controller
                              control={control}
                              name="phone_country_code"
                              render={({ field }) => (
                                <Select value={field.value} onValueChange={(value) => field.onChange(normalizePhoneCountryCode(value))}>
                                  <SelectTrigger id="phone_country_code" className={selectClass('phone_country_code')} aria-invalid={errors.phone_country_code ? 'true' : 'false'}>
                                    {selectedPhoneOption ? (
                                      <span className="flex items-center gap-2.5 truncate">
                                        <span className={`${selectedPhoneOption.flagClassName} h-4 w-[22px] rounded-[2px] shadow-sm`} aria-hidden="true" />
                                        <span className="truncate text-base font-medium">{selectedPhoneOption.dialCode}</span>
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
                                inputMode="tel"
                                autoComplete="tel-national"
                                placeholder="Enter mobile number"
                                className={inputClass('phone_national_number')}
                                {...register('phone_national_number', {
                                  required: searchType === 'phone' ? 'Phone number is required.' : false,
                                  setValueAs: (value: string) => normalizePhoneNationalNumber(value),
                                  pattern: { value: /^\d{4,20}$/, message: 'Invalid phone number.' },
                                  onChange: (event) => { event.target.value = normalizePhoneNationalNumber(event.target.value); },
                                })}
                              />
                            </div>
                          </div>
                          <p className="mt-1.5 text-[11px] text-slate-500">
                            Choose the country code first, then enter the exact mobile number used during registration.
                          </p>
                          <AnimatePresence><AuthInlineError message={errors.phone_country_code?.message} /></AnimatePresence>
                          <AnimatePresence><AuthInlineError message={errors.phone_national_number?.message} /></AnimatePresence>
                        </div>
                      )}
                      {(searchType === 'passport' || searchType === 'ic') && (
                        <>
                          <div>
                            <label htmlFor="country" className="mb-1.5 block text-sm font-semibold text-slate-700">
                              Country <span className="text-red-500">*</span>
                            </label>
                            {searchType === 'passport' ? (
                              <Controller
                                control={control}
                                name="country"
                                render={({ field }) => (
                                  <Select value={field.value} onValueChange={field.onChange}>
                                    <SelectTrigger id="country" className={selectClass('country')} aria-invalid={errors.country ? 'true' : 'false'}>
                                      {selectedCountryOption ? (
                                        <span className="flex items-center gap-2.5 truncate">
                                          <span className={`fi fi-${selectedCountryOption.code.toLowerCase()} h-4 w-[22px] rounded-[2px] shadow-sm`} aria-hidden="true" />
                                          <span className="truncate">{selectedCountryOption.name}</span>
                                        </span>
                                      ) : (
                                        <SelectValue placeholder="Select country" />
                                      )}
                                    </SelectTrigger>
                                    <SelectContent className="rounded-xl border-sky-100">
                                      {PRIORITY_SORTED_COUNTRIES.map((country) => (
                                        <SelectItem key={country.code} value={country.code}>
                                          <span className="flex items-center gap-2.5">
                                            <span className={`fi fi-${country.code.toLowerCase()} h-4 w-[22px] rounded-[2px] shadow-sm`} aria-hidden="true" />
                                            <span>{country.name}</span>
                                          </span>
                                        </SelectItem>
                                      ))}
                                      {OTHER_SORTED_COUNTRIES.length > 0 && <SelectSeparator className="my-1 bg-sky-100" />}
                                      {OTHER_SORTED_COUNTRIES.map((country) => (
                                        <SelectItem key={country.code} value={country.code}>
                                          <span className="flex items-center gap-2.5">
                                            <span className={`fi fi-${country.code.toLowerCase()} h-4 w-[22px] rounded-[2px] shadow-sm`} aria-hidden="true" />
                                            <span>{country.name}</span>
                                          </span>
                                        </SelectItem>
                                      ))}
                                    </SelectContent>
                                  </Select>
                                )}
                              />
                            ) : (
                              <div className={lockedFieldClass}>
                                <span className="flex items-center gap-2.5">
                                  <span className="fi fi-my h-4 w-[22px] rounded-[2px] shadow-sm" aria-hidden="true" />
                                  <span>Malaysia</span>
                                </span>
                                <Lock className="h-4 w-4 text-slate-400" aria-hidden="true" />
                              </div>
                            )}
                            <AnimatePresence><AuthInlineError message={errors.country?.message} /></AnimatePresence>
                          </div>

                          <div>
                            <label htmlFor="identity_type" className="mb-1.5 block text-sm font-semibold text-slate-700">
                              Document Type <span className="text-red-500">*</span>
                            </label>
                            <div className={lockedFieldClass}>
                              <span className="flex items-center gap-2.5">
                                <IdCard className="h-4 w-4 text-sky-500" aria-hidden="true" />
                                <span>{searchType === 'passport' ? 'Passport' : 'Malaysia IC (MyKad)'}</span>
                              </span>
                              <Lock className="h-4 w-4 text-slate-400" aria-hidden="true" />
                            </div>
                          </div>

                          <div>
                            <label htmlFor="identity_number" className="mb-1.5 block text-sm font-semibold text-slate-700">
                              {searchType === 'passport' ? 'Passport Number' : 'Malaysia IC (MyKad) Number'} <span className="text-red-500">*</span>
                            </label>
                            <div className="relative">
                              <IdCard className="pointer-events-none absolute left-3.5 top-1/2 h-[18px] w-[18px] -translate-y-1/2 text-sky-400" aria-hidden="true" />
                              <input
                                id="identity_number"
                                type="text"
                                autoComplete="off"
                                placeholder={searchType === 'passport' ? 'Example: A1234567' : 'Example: 901231101234'}
                                className={inputClass('identity_number')}
                                {...register('identity_number', {
                                  required: searchType === 'passport' || searchType === 'ic'
                                    ? searchType === 'passport'
                                      ? 'Passport Number is required.'
                                      : 'Malaysia IC (MyKad) Number is required.'
                                    : false,
                                  minLength: { value: 6, message: 'Must be at least 6 characters.' },
                                })}
                              />
                            </div>
                            <p className="mt-1.5 text-[11px] text-slate-500">
                              Exact match only. Make sure the document number is the same as your registration record.
                            </p>
                            <AnimatePresence><AuthInlineError message={errors.identity_number?.message} /></AnimatePresence>
                          </div>
                        </>
                      )}
                    </motion.div>
                  </AnimatePresence>

                  <input type="hidden" {...register('recaptcha_token')} />
                  {recaptchaEnabled && (
                    <div className="rounded-2xl border border-sky-100 bg-white/90 p-4 shadow-sm">
                      <div className="mb-3 flex items-start gap-2">
                        <Lock className="mt-0.5 h-4 w-4 text-sky-500" aria-hidden="true" />
                        <div>
                          <p className="text-sm font-semibold text-slate-800">Background protection</p>
                          <p className="text-xs leading-relaxed text-slate-500">We use reCAPTCHA v3 in the background before every lookup request.</p>
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
                        <span>Searching...</span>
                      </>
                    ) : (
                      <>
                        <Search className="h-4 w-4" aria-hidden="true" />
                        <span>Find My Ticket</span>
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
                    <p className="text-sm font-bold text-slate-900">Participant not found</p>
                    <p className="mt-1 text-sm text-slate-600">{notFoundMessage}</p>
                    <p className="mt-1 text-sm text-slate-600">Please re-check the data and try the same value used during registration.</p>
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
                      <UserRoundSearch className="h-7 w-7" aria-hidden="true" />
                    </div>
                    <div className="min-w-0 flex-1">
                      <p className="text-xs font-bold uppercase tracking-[0.28em] text-sky-100">Participant Found</p>
                      <h2 className="mt-1 text-2xl font-black tracking-tight" style={{ fontFamily: '"Kanit", sans-serif' }}>{result.participant.full_name || 'Registered Participant'}</h2>
                    </div>
                  </div>
                </div>
                <div className="space-y-6 px-6 py-6">
                  <AuthCodeBadge code={result.participant.entry_code_display} label="Fallback Entry Code" className="w-full sm:w-auto" />
                  <div className="grid gap-4 sm:grid-cols-2">
                    <div className="rounded-2xl border border-sky-100 bg-sky-50/70 p-4"><p className="text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-500">Email</p><p className="mt-2 break-words text-sm font-semibold text-slate-900">{result.participant.email || '-'}</p></div>
                    <div className="rounded-2xl border border-sky-100 bg-sky-50/70 p-4"><p className="text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-500">Phone Number</p><div className="mt-2 flex items-center gap-2"><Phone className="h-4 w-4 text-sky-500" aria-hidden="true" /><p className="break-words text-sm font-semibold text-slate-900">{result.participant.phone_number || buildPhoneNumber(result.participant.phone_country_code, result.participant.phone_national_number) || '-'}</p></div></div>
                    <div className="rounded-2xl border border-sky-100 bg-sky-50/70 p-4"><p className="text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-500">Country</p><div className="mt-2 flex items-center gap-2">{resultCountryOption ? (<span className={`fi fi-${resultCountryOption.code.toLowerCase()} h-4 w-[22px] rounded-[2px] shadow-sm`} aria-hidden="true" />) : null}<p className="text-sm font-semibold text-slate-900">{humanizeCountry(result.participant.country)}</p></div></div>
                    <div className="rounded-2xl border border-sky-100 bg-sky-50/70 p-4"><p className="text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-500">Document Type</p><p className="mt-2 text-sm font-semibold text-slate-900">{humanizeIdentityType(result.participant.identity_type)}</p></div>
                    <div className="rounded-2xl border border-sky-100 bg-sky-50/70 p-4 sm:col-span-2"><p className="text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-500">Document Number</p><p className="mt-2 break-words text-sm font-semibold text-slate-900">{result.participant.identity_number || '-'}</p></div>
                  </div>
                  <div className="flex flex-wrap gap-3">
                    <span className={`inline-flex items-center rounded-full border px-3 py-1 text-xs font-bold uppercase tracking-[0.18em] ${tone(result.participant.account_status)}`}>Account: {result.participant.account_status || 'unknown'}</span>
                    <span className={`inline-flex items-center rounded-full border px-3 py-1 text-xs font-bold uppercase tracking-[0.18em] ${tone(result.participant.verification_status)}`}>Verification: {result.participant.verification_status || 'unknown'}</span>
                  </div>
                  <Button asChild size="lg" className={authPrimaryButtonClass('h-13 w-full text-base')}>
                    <a href={result.ticketUrl} target="_blank" rel="noreferrer"><QrCode className="h-5 w-5" aria-hidden="true" />Open My Ticket</a>
                  </Button>
                </div>
              </motion.section>
            )}
          </AnimatePresence>
        </div>
      </motion.main>
    </AuthPageShell>
  );
}
