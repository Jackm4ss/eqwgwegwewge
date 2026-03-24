import { useState, useCallback, useRef, useEffect } from 'react';
import { Controller, useForm } from 'react-hook-form';
import { motion, AnimatePresence } from 'motion/react';
import { Toaster, toast } from 'sonner';
import {
  User, Mail, Phone, Globe, MapPin, IdCard,
  Calendar, Music2, ChevronDown, CheckCircle2,
  AlertCircle, ArrowRight, ArrowLeft, Loader2,
  Droplets, Star, Waves, Sparkles, X, Lock
} from 'lucide-react';
import { Link } from 'react-router';
import { WaterAnimation } from './WaterAnimation';
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
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '../ui/Select';

interface FormData {
  full_name: string;
  email: string;
  phone_country_code: string;
  phone_national_number: string;
  country: string;
  identity_type: 'national_id' | 'passport' | '';
  identity_number: string;
  agreeTerms: boolean;
}

const MONTHS = [
  'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
  'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
];

const STEPS = [
  { id: 1, label: 'Data Pribadi', icon: User },
  { id: 2, label: 'Konfirmasi', icon: CheckCircle2 },
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

const IDENTITY_TYPES = [
  {
    value: 'national_id',
    label: 'IC / National ID',
    description: 'MyKad untuk Malaysia, atau identitas nasional / resident ID resmi untuk negara lain.',
  },
  {
    value: 'passport',
    label: 'Passport',
    description: 'Gunakan nomor passport yang masih berlaku sesuai dokumen perjalanan Anda.',
  },
] as const;

const SORTED_COUNTRIES = [...COUNTRIES].sort((left, right) => left.name.localeCompare(right.name));

const PHONE_COUNTRY_CODES = SORTED_COUNTRIES.map((country) => ({
  country: country.code,
  countryName: country.name,
  dialCode: PHONE_DIAL_CODES[country.code],
  flagClassName: `fi fi-${country.code.toLowerCase()}`,
  label: `${country.name} (${PHONE_DIAL_CODES[country.code]})`,
}));

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

type SweetAlertResult = {
  isConfirmed?: boolean;
  isDismissed?: boolean;
};

type SweetAlertInstance = {
  fire: (options: Record<string, unknown>) => Promise<SweetAlertResult>;
};

let sweetAlertLoader: Promise<SweetAlertInstance> | null = null;

function escapeHtml(value: string) {
  return value
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');
}

function buildRegistrationReviewHtml(items: Array<{ label: string; value: string }>) {
  return `
    <div class="registration-review-swal__lead">
      Pastikan seluruh data berikut sudah benar sebelum registrasi dikirim. Data ini akan digunakan untuk verifikasi peserta dan penerbitan tiket.
    </div>
    <div class="registration-review-swal__grid">
      ${items.map((item) => `
        <div class="registration-review-swal__item">
          <span class="registration-review-swal__label">${escapeHtml(item.label)}</span>
          <strong class="registration-review-swal__value">${escapeHtml(item.value || 'Belum diisi')}</strong>
        </div>
      `).join('')}
    </div>
    <p class="registration-review-swal__footnote">
      Jika ada data yang belum sesuai, pilih "Periksa lagi" untuk kembali ke formulir dan lakukan perbaikan.
    </p>
  `;
}

function ensureSweetAlertStyles() {
  const styleId = 'swal2-vuexy-style';
  const customStyleId = 'swal2-registration-review-style';

  if (document.getElementById(styleId)) {
    if (document.getElementById(customStyleId)) {
      return;
    }
  } else {
    const link = document.createElement('link');
    link.id = styleId;
    link.rel = 'stylesheet';
    link.href = '/assets-vuexy/vendor/libs/sweetalert2/sweetalert2.css';
    document.head.appendChild(link);
  }

  if (document.getElementById(customStyleId)) {
    return;
  }

  const style = document.createElement('style');
  style.id = customStyleId;
  style.textContent = `
    .registration-review-swal {
      width: min(680px, calc(100vw - 2rem)) !important;
      padding: 0 !important;
      overflow: hidden !important;
      border-radius: 28px !important;
      border: 1px solid rgba(186, 230, 253, 0.95) !important;
      background: linear-gradient(180deg, #ffffff 0%, #f8fcff 100%) !important;
      box-shadow: 0 28px 80px rgba(12, 74, 110, 0.28) !important;
    }

    .registration-review-swal__title {
      margin: 0 !important;
      padding: 1.75rem 1.75rem 0.25rem !important;
      color: #0f172a !important;
      font-family: "Kanit", sans-serif !important;
      font-size: 1.9rem !important;
      font-weight: 700 !important;
      line-height: 1.15 !important;
      text-align: left !important;
    }

    .registration-review-swal__html {
      margin: 0 !important;
      padding: 0 1.75rem !important;
      text-align: left !important;
    }

    .registration-review-swal__lead {
      margin: 0 0 1rem;
      padding: 1rem 1.1rem;
      border-radius: 1rem;
      border: 1px solid rgba(186, 230, 253, 0.85);
      background: linear-gradient(135deg, rgba(224, 242, 254, 0.95), rgba(240, 249, 255, 0.96));
      color: #334155;
      font-size: 0.93rem;
      line-height: 1.7;
    }

    .registration-review-swal__grid {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 0.85rem;
    }

    .registration-review-swal__item {
      padding: 0.95rem 1rem;
      border-radius: 1rem;
      border: 1px solid rgba(186, 230, 253, 0.9);
      background: #ffffff;
      box-shadow: 0 10px 24px rgba(14, 116, 144, 0.08);
    }

    .registration-review-swal__label {
      display: block;
      margin-bottom: 0.4rem;
      color: #64748b;
      font-size: 0.68rem;
      font-weight: 700;
      letter-spacing: 0.08em;
      text-transform: uppercase;
    }

    .registration-review-swal__value {
      display: block;
      color: #0f172a;
      font-size: 0.97rem;
      font-weight: 700;
      line-height: 1.6;
      word-break: break-word;
    }

    .registration-review-swal__footnote {
      margin: 1rem 0 0;
      padding-top: 1rem;
      border-top: 1px solid rgba(226, 232, 240, 0.9);
      color: #64748b;
      font-size: 0.78rem;
      line-height: 1.7;
    }

    .registration-review-swal__actions {
      margin: 1.25rem 0 0 !important;
      padding: 1.25rem 1.75rem 1.75rem !important;
      border-top: 1px solid rgba(226, 232, 240, 0.9);
      gap: 0.75rem !important;
      justify-content: flex-end !important;
    }

    .registration-review-swal__confirm,
    .registration-review-swal__cancel {
      margin: 0 !important;
      padding: 0.85rem 1.25rem !important;
      border-radius: 0.95rem !important;
      font-size: 0.95rem !important;
      font-weight: 700 !important;
      outline: none !important;
      transition: transform 0.18s ease, filter 0.18s ease, background-color 0.18s ease !important;
    }

    .registration-review-swal__confirm {
      border: 1px solid transparent !important;
      background: linear-gradient(135deg, #0284C7, #0EA5E9) !important;
      color: #ffffff !important;
      box-shadow: 0 14px 30px rgba(2, 132, 199, 0.22) !important;
    }

    .registration-review-swal__confirm:hover {
      transform: translateY(-1px);
      filter: brightness(1.03);
    }

    .registration-review-swal__cancel {
      border: 1px solid rgba(125, 211, 252, 0.9) !important;
      background: #ffffff !important;
      color: #0369a1 !important;
    }

    .registration-review-swal__cancel:hover {
      background: #f0f9ff !important;
    }

    .registration-review-swal__confirm:focus-visible,
    .registration-review-swal__cancel:focus-visible,
    .registration-review-swal .swal2-close:focus-visible {
      box-shadow: 0 0 0 4px rgba(14, 165, 233, 0.18) !important;
    }

    .registration-review-swal .swal2-close {
      top: 1rem !important;
      right: 1rem !important;
      color: #64748b !important;
      font-size: 1.6rem !important;
      transition: background-color 0.18s ease, color 0.18s ease !important;
    }

    .registration-review-swal .swal2-close:hover {
      background: rgba(226, 232, 240, 0.7) !important;
      color: #0f172a !important;
    }

    @media (max-width: 640px) {
      .registration-review-swal {
        width: calc(100vw - 1rem) !important;
        border-radius: 24px !important;
      }

      .registration-review-swal__title {
        padding: 1.35rem 1.1rem 0.25rem !important;
        font-size: 1.5rem !important;
      }

      .registration-review-swal__html {
        padding: 0 1.1rem !important;
      }

      .registration-review-swal__grid {
        grid-template-columns: 1fr;
      }

      .registration-review-swal__actions {
        padding: 1rem 1.1rem 1.1rem !important;
        flex-direction: column-reverse !important;
      }

      .registration-review-swal__confirm,
      .registration-review-swal__cancel {
        width: 100% !important;
      }
    }
  `;

  document.head.appendChild(style);
}

function ensureSweetAlert(): Promise<SweetAlertInstance> {
  const globalWindow = window as Window & { Swal?: SweetAlertInstance };

  if (globalWindow.Swal) {
    ensureSweetAlertStyles();
    return Promise.resolve(globalWindow.Swal);
  }

  if (sweetAlertLoader) {
    return sweetAlertLoader;
  }

  const loader = new Promise<SweetAlertInstance>((resolve, reject) => {
    ensureSweetAlertStyles();

    const existingScript = document.getElementById('swal2-vuexy-script') as HTMLScriptElement | null;

    if (existingScript) {
      existingScript.addEventListener('load', () => {
        if (globalWindow.Swal) {
          resolve(globalWindow.Swal);
          return;
        }

        reject(new Error('SweetAlert gagal dimuat.'));
      }, { once: true });
      existingScript.addEventListener('error', () => reject(new Error('SweetAlert gagal dimuat.')), { once: true });
      return;
    }

    const script = document.createElement('script');
    script.id = 'swal2-vuexy-script';
    script.src = '/assets-vuexy/vendor/libs/sweetalert2/sweetalert2.js';
    script.async = true;
    script.onload = () => {
      if (globalWindow.Swal) {
        resolve(globalWindow.Swal);
        return;
      }

      reject(new Error('SweetAlert gagal dimuat.'));
    };
    script.onerror = () => reject(new Error('SweetAlert gagal dimuat.'));
    document.body.appendChild(script);
  }).finally(() => {
    sweetAlertLoader = null;
  });

  sweetAlertLoader = loader;

  return loader;
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
    <svg viewBox="0 0 80 20" className="w-full mt-2" aria-hidden="true">
      <defs>
        <linearGradient id="diamondGrad" x1="0%" y1="0%" x2="100%" y2="0%">
          <stop offset="0%" stopColor="#7DD3FC" stopOpacity="0" />
          <stop offset="20%" stopColor="#BAE6FD" />
          <stop offset="50%" stopColor="#FFFFFF" />
          <stop offset="80%" stopColor="#BAE6FD" />
          <stop offset="100%" stopColor="#7DD3FC" stopOpacity="0" />
        </linearGradient>
      </defs>
      {[0, 1, 2, 3, 4].map(i => (
        <polygon
          key={i}
          points={`${8 + i * 16},2 ${12 + i * 16},10 ${8 + i * 16},18 ${4 + i * 16},10`}
          fill="url(#diamondGrad)"
          fillOpacity={0.8}
          className="drop-shadow-[0_0_3px_rgba(255,255,255,0.4)]"
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
      className="mt-1.5 text-red-600 text-xs flex items-center gap-1 font-medium"
    >
      <AlertCircle className="w-3.5 h-3.5 flex-shrink-0" aria-hidden="true" />
      {message}
    </motion.p>
  );
}

const slideVariants = {
  enter: (dir: number) => ({
    x: dir > 0 ? 70 : -70,
    opacity: 0,
  }),
  center: {
    x: 0,
    opacity: 1,
    transition: { duration: 0.38, ease: [0.25, 0.46, 0.45, 0.94] as const },
  },
  exit: (dir: number) => ({
    x: dir > 0 ? -70 : 70,
    opacity: 0,
    transition: { duration: 0.28, ease: [0.55, 0, 1, 0.45] as const },
  }),
};

type LegalDialogType = 'terms' | 'privacy';

const LEGAL_DIALOG_CONTENT: Record<LegalDialogType, {
  title: string;
  description: string;
  paragraphs: string[];
}> = {
  terms: {
    title: 'Syarat & Ketentuan',
    description: 'Konten ini masih berupa dummy text untuk kebutuhan review UI dan akan diganti dengan naskah final.',
    paragraphs: [
      'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Integer posuere erat a ante venenatis dapibus posuere velit aliquet. Vestibulum id ligula porta felis euismod semper, sed posuere consectetur est at lobortis.',
      'Praesent commodo cursus magna, vel scelerisque nisl consectetur et. Donec id elit non mi porta gravida at eget metus. Cras mattis consectetur purus sit amet fermentum, sed posuere consectetur est at lobortis.',
      'Aenean lacinia bibendum nulla sed consectetur. Curabitur blandit tempus porttitor. Nulla vitae elit libero, a pharetra augue. Maecenas faucibus mollis interdum, sed posuere consectetur est at lobortis.',
    ],
  },
  privacy: {
    title: 'Kebijakan Privasi',
    description: 'Konten ini masih berupa dummy text untuk kebutuhan review UI dan akan diganti dengan naskah final.',
    paragraphs: [
      'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed posuere consectetur est at lobortis. Maecenas sed diam eget risus varius blandit sit amet non magna, id elit non mi porta gravida at eget metus.',
      'Donec ullamcorper nulla non metus auctor fringilla. Nulla vitae elit libero, a pharetra augue. Integer posuere erat a ante venenatis dapibus posuere velit aliquet, sed posuere consectetur est at lobortis.',
      'Morbi leo risus, porta ac consectetur ac, vestibulum at eros. Cras justo odio, dapibus ac facilisis in, egestas eget quam. Etiam porta sem malesuada magna mollis euismod, sed posuere consectetur est at lobortis.',
    ],
  },
};

export function RegisterPage() {
  const [currentStep, setCurrentStep] = useState(1);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [isSuccess, setIsSuccess] = useState(false);
  const [canSubmitConfirmation, setCanSubmitConfirmation] = useState(false);
  const [legalDialog, setLegalDialog] = useState<LegalDialogType | null>(null);
  const [registeredEmail, setRegisteredEmail] = useState('');
  const [timeLeft, setTimeLeft] = useState({ Hari: '00', Jam: '00', Menit: '00', Detik: '00' });
  const [direction, setDirection] = useState(1);
  const addRippleRef = useRef<((x: number, y: number) => void) | null>(null);
  const previousCountryRef = useRef('');

  const {
    control,
    register,
    handleSubmit,
    trigger,
    getValues,
    setValue,
    watch,
    reset,
    formState: { errors, dirtyFields },
  } = useForm<FormData>({
    mode: 'onTouched',
    defaultValues: {
      full_name: '',
      email: '',
      phone_country_code: '',
      phone_national_number: '',
      country: '',
      identity_type: '',
      identity_number: '',
      agreeTerms: false,
    },
  });

  const handleCanvasReady = useCallback((fn: (x: number, y: number) => void) => {
    addRippleRef.current = fn;
  }, []);

  const handlePageClick = useCallback((e: React.MouseEvent) => {
    if (addRippleRef.current) {
      addRippleRef.current(e.clientX, e.clientY);
    }
  }, []);

  useEffect(() => {
    const target = new Date('2026-04-09T00:00:00').getTime();
    
    // Initial calculation to prevent 1-second delay
    const calculateTimeLeft = () => {
      const now = new Date().getTime();
      const difference = target - now;
      if (difference <= 0) return { Hari: '00', Jam: '00', Menit: '00', Detik: '00' };

      const d = Math.floor(difference / (1000 * 60 * 60 * 24));
      const h = Math.floor((difference % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
      const m = Math.floor((difference % (1000 * 60 * 60)) / (1000 * 60));
      const s = Math.floor((difference % (1000 * 60)) / 1000);

      return {
        Hari: d.toString().padStart(2, '0'),
        Jam: h.toString().padStart(2, '0'),
        Menit: m.toString().padStart(2, '0'),
        Detik: s.toString().padStart(2, '0'),
      };
    };

    setTimeLeft(calculateTimeLeft());

    const interval = setInterval(() => {
      const remaining = calculateTimeLeft();
      setTimeLeft(remaining);
      if (remaining.Hari === '00' && remaining.Jam === '00' && remaining.Menit === '00' && remaining.Detik === '00') {
        clearInterval(interval);
      }
    }, 1000);

    return () => clearInterval(interval);
  }, []);

  useEffect(() => {
    if (currentStep !== 2) {
      setCanSubmitConfirmation(false);
      return;
    }

    const frame = window.requestAnimationFrame(() => {
      setCanSubmitConfirmation(true);
    });

    return () => window.cancelAnimationFrame(frame);
  }, [currentStep]);

  const countryVal = watch('country');
  const phoneCountryCodeVal = watch('phone_country_code');
  const phoneNationalNumberVal = watch('phone_national_number');
  const identityTypeVal = watch('identity_type');

  useEffect(() => {
    if (dirtyFields.phone_country_code) {
      return;
    }

    const suggestedDialCode = PHONE_COUNTRY_CODES.find(option => option.country === countryVal)?.dialCode ?? '';

    if (suggestedDialCode !== '') {
      setValue('phone_country_code', suggestedDialCode, {
        shouldDirty: false,
        shouldTouch: false,
        shouldValidate: false,
      });
    }
  }, [countryVal, dirtyFields.phone_country_code, setValue]);

  useEffect(() => {
    const previousCountry = previousCountryRef.current;
    previousCountryRef.current = countryVal;

    if (countryVal === '') {
      return;
    }

    if (countryVal !== 'MY') {
      if (identityTypeVal !== 'passport') {
        setValue('identity_type', 'passport', {
          shouldDirty: true,
          shouldTouch: true,
          shouldValidate: true,
        });
      }

      if (identityTypeVal === 'national_id') {
        setValue('identity_number', '', {
          shouldDirty: true,
          shouldTouch: false,
          shouldValidate: false,
        });
      }

      return;
    }

    if (previousCountry !== '' && previousCountry !== 'MY') {
      setValue('identity_type', '', {
        shouldDirty: true,
        shouldTouch: true,
        shouldValidate: true,
      });

      setValue('identity_number', '', {
        shouldDirty: true,
        shouldTouch: false,
        shouldValidate: false,
      });
    }
  }, [countryVal, identityTypeVal, setValue]);

  const handleResetForm = () => {
    setIsSuccess(false);
    setRegisteredEmail('');
    setCanSubmitConfirmation(false);
    setCurrentStep(1);
    setDirection(-1);
    reset(); // Clear form values
  };

  const handleNext = async () => {
    const fieldsMap: Record<number, (keyof FormData)[]> = {
      1: ['full_name', 'email', 'phone_country_code', 'phone_national_number', 'country', 'identity_type', 'identity_number'],
    };
    const isValid = await trigger(fieldsMap[currentStep]);
    if (isValid) {
      setCanSubmitConfirmation(false);
      setDirection(1);
      setCurrentStep(s => s + 1);
    }
  };

  const handlePrev = () => {
    setCanSubmitConfirmation(false);
    setDirection(-1);
    setCurrentStep(s => s - 1);
  };

  const onSubmit = async (data: FormData) => {
    try {
      const phoneNumber = buildPhoneNumber(data.phone_country_code, data.phone_national_number);
      const selectedCountry = SORTED_COUNTRIES.find(country => country.code === data.country)?.name ?? data.country;
      const selectedIdentityLabel = data.identity_type === 'national_id'
        ? data.country === 'MY'
          ? 'IC Malaysia (MyKad)'
          : 'National ID / Resident ID'
        : 'Passport';
      const selectedIdentityNumberLabel = data.country !== 'MY'
        ? 'Nomor Passport'
        : data.identity_type === 'national_id'
          ? 'Nomor IC Malaysia (MyKad)'
          : 'Nomor Passport';
      const Swal = await ensureSweetAlert();
      const confirmation = await Swal.fire({
        title: 'Periksa kembali data Anda',
        buttonsStyling: false,
        showCloseButton: true,
        backdrop: 'rgba(12, 74, 110, 0.55)',
        html: buildRegistrationReviewHtml([
          { label: 'Nama Lengkap', value: data.full_name },
          { label: 'Email', value: data.email },
          { label: 'Nomor HP', value: phoneNumber },
          { label: 'Negara', value: selectedCountry },
          { label: 'Jenis Dokumen', value: selectedIdentityLabel },
          { label: selectedIdentityNumberLabel, value: data.identity_number },
        ]),
        customClass: {
          popup: 'registration-review-swal',
          title: 'registration-review-swal__title',
          htmlContainer: 'registration-review-swal__html',
          actions: 'registration-review-swal__actions',
          confirmButton: 'registration-review-swal__confirm',
          cancelButton: 'registration-review-swal__cancel',
        },
        showCancelButton: true,
        focusCancel: true,
        confirmButtonText: 'Ya, data sudah benar',
        cancelButtonText: 'Periksa lagi',
      });

      if (!confirmation.isConfirmed) {
        return;
      }

      setIsSubmitting(true);

      const csrfToken = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content;
      const payload = {
        ...data,
        phone_country_code: normalizePhoneCountryCode(data.phone_country_code),
        phone_national_number: normalizePhoneNationalNumber(data.phone_national_number),
        phone_number: phoneNumber,
      };
      
      const response = await fetch('/api/register', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {})
        },
        body: JSON.stringify(payload),
      });

      const result = await response.json();

      if (!response.ok) {
        if (result.errors) {
          Object.keys(result.errors).forEach((key) => {
             toast.error(result.errors[key][0]);
          });
        } else {
          throw new Error(result.message || 'Registration failed');
        }
        return;
      }

      setRegisteredEmail(data.email);
      setIsSuccess(true);
      toast.success(result.message || 'Link verifikasi berhasil dikirim ke email Anda.');
    } catch (error: any) {
      toast.error(error.message || 'Terjadi kesalahan saat mendaftar. Silakan coba lagi.');
    } finally {
      setIsSubmitting(false);
    }
  };

  const inputBase =
    'w-full px-4 py-3 pl-11 rounded-xl border-2 transition-all duration-200 bg-white text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-offset-1';

  const inputClass = (field: keyof FormData) =>
    `${inputBase} ${errors[field]
      ? 'border-red-400 focus:border-red-500 focus:ring-red-300'
      : 'border-sky-200 focus:border-sky-500 focus:ring-sky-300 hover:border-sky-300'
    }`;

  const phoneSelectClass = `min-h-[50px] rounded-xl border-2 bg-white px-4 text-base font-medium text-slate-800 data-[size=default]:h-[50px] ${
    errors.phone_country_code
      ? 'border-red-400 focus:border-red-500 focus:ring-red-300'
      : 'border-sky-200 focus:border-sky-500 focus:ring-sky-300 hover:border-sky-300'
  }`;

  const phoneNumberInputClass = `${inputBase} min-h-[50px] px-4 text-base ${
    errors.phone_national_number
      ? 'border-red-400 focus:border-red-500 focus:ring-red-300'
      : 'border-sky-200 focus:border-sky-500 focus:ring-sky-300 hover:border-sky-300'
  }`;

  const countrySelectClass = `min-h-[50px] rounded-xl border-2 bg-white px-4 text-base font-medium text-slate-800 data-[size=default]:h-[50px] ${
    errors.country
      ? 'border-red-400 focus:border-red-500 focus:ring-red-300'
      : 'border-sky-200 focus:border-sky-500 focus:ring-sky-300 hover:border-sky-300'
  }`;

  const selectedCountryOption = SORTED_COUNTRIES.find(c => c.code === countryVal);
  const countryLabel = selectedCountryOption?.name ?? '';
  const isMalaysianRegistrant = countryVal === 'MY';
  const isForeignRegistrant = countryVal !== '' && countryVal !== 'MY';
  const availableIdentityTypes = isMalaysianRegistrant
    ? IDENTITY_TYPES
    : IDENTITY_TYPES.filter(option => option.value === 'passport');
  const identityTypeLabel = identityTypeVal === 'national_id'
    ? isMalaysianRegistrant
      ? 'IC Malaysia (MyKad)'
      : 'National ID / Resident ID'
    : identityTypeVal === 'passport'
      ? 'Passport'
      : 'Jenis Dokumen';
  const identityNumberLabel = isForeignRegistrant
    ? 'Nomor Passport'
    : identityTypeVal === 'national_id'
    ? isMalaysianRegistrant
      ? 'Nomor IC Malaysia (MyKad)'
      : 'Nomor National ID / Resident ID'
    : 'Nomor Passport';
  const identityNumberPlaceholder = isForeignRegistrant
    ? 'Contoh: A1234567'
    : identityTypeVal === 'national_id'
    ? isMalaysianRegistrant
      ? 'Contoh: 901231101234'
      : 'Masukkan nomor identitas resmi Anda'
    : 'Contoh: A1234567';
  const identityHelperText = isForeignRegistrant
    ? 'Untuk pendaftar luar Malaysia, gunakan Passport Only sebagai identitas utama.'
    : identityTypeVal === 'national_id'
    ? isMalaysianRegistrant
      ? 'Untuk warga negara atau penduduk tetap Malaysia, gunakan nomor IC / MyKad.'
      : 'Gunakan nomor identitas nasional atau resident ID yang resmi dan masih berlaku.'
    : identityTypeVal === 'passport'
      ? 'Gunakan nomor passport yang masih berlaku dan sesuai dokumen perjalanan Anda.'
      : isMalaysianRegistrant
        ? 'Untuk Malaysia, Anda bisa pilih IC Malaysia (MyKad) atau Passport.'
        : 'Pilih jenis dokumen yang akan digunakan untuk registrasi.';
  const phoneCountryOption = PHONE_COUNTRY_CODES.find(option => option.dialCode === phoneCountryCodeVal);
  const phonePreview = phoneCountryCodeVal && phoneNationalNumberVal
    ? `${normalizePhoneCountryCode(phoneCountryCodeVal)} ${normalizePhoneNationalNumber(phoneNationalNumberVal)}`
    : '';
  const activeLegalDialog = legalDialog ? LEGAL_DIALOG_CONTENT[legalDialog] : null;
  const phoneNationalNumberField = register('phone_national_number', {
    required: 'Nomor HP wajib diisi',
    setValueAs: (value: string) => normalizePhoneNationalNumber(value),
    pattern: { value: /^\d{4,20}$/, message: 'Nomor HP tidak valid' },
    onChange: (event) => {
      event.target.value = normalizePhoneNationalNumber(event.target.value);
    },
  });

  return (
    <div
      className="min-h-screen relative overflow-x-hidden"
      style={{ background: 'linear-gradient(145deg, #0C4A6E 0%, #0369A1 30%, #0284C7 60%, #0EA5E9 100%)' }}
      onClick={handlePageClick}
    >
      <a
        href="#main-form"
        className="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 focus:z-50 focus:px-4 focus:py-2 focus:bg-white focus:text-sky-800 focus:rounded-lg focus:shadow-lg focus:font-semibold"
      >
        Lewati ke formulir pendaftaran
      </a>

      <Toaster position="top-center" richColors />
      <WaterAnimation onCanvasReady={handleCanvasReady} />

      <div className="fixed inset-0 pointer-events-none overflow-hidden" aria-hidden="true" style={{ zIndex: 1 }}>
        <div className="absolute -top-32 -right-32 w-[500px] h-[500px] rounded-full opacity-10" style={{ background: 'radial-gradient(circle, #BAE6FD, transparent)' }} />
        <div className="absolute -bottom-40 -left-40 w-[600px] h-[600px] rounded-full opacity-10" style={{ background: 'radial-gradient(circle, #E0F2FE, transparent)' }} />
        <div className="absolute top-4 right-4 w-64 h-64 text-sky-200 opacity-[0.08]">
          <LotusIcon className="w-full h-full" />
        </div>
        <div className="absolute bottom-4 left-4 w-48 h-48 text-sky-200 opacity-[0.08] rotate-180">
          <LotusIcon className="w-full h-full" />
        </div>
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

      <div className="relative flex flex-col lg:flex-row min-h-screen" style={{ zIndex: 2 }}>

        {/* LEFT PANEL */}
        <motion.aside
          initial={{ opacity: 0, x: -50 }}
          animate={{ opacity: 1, x: 0 }}
          transition={{ duration: 0.9, ease: [0.25, 0.46, 0.45, 0.94] as const }}
          className="hidden lg:flex lg:w-[44%] flex-col justify-center items-center px-10 py-12 text-white"
          aria-label="Informasi Festival Songkran"
        >
          <motion.div
            initial={{ opacity: 0, y: -20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ delay: 0.2 }}
            className="inline-flex items-center gap-2 bg-gradient-to-r from-sky-400/10 via-white/10 to-sky-400/10 backdrop-blur-md border border-sky-300/30 px-5 py-2.5 rounded-full mb-8 shadow-[0_0_15px_rgba(186,230,253,0.1)]"
          >
            <Droplets className="w-4 h-4 text-sky-300" aria-hidden="true" />
            <span className="text-white text-xs tracking-[0.2em] uppercase font-semibold">
              Malaysia • 9–19 April 2026
            </span>
            <Droplets className="w-4 h-4 text-sky-300" aria-hidden="true" />
          </motion.div>

          <motion.div
            initial={{ scale: 0.7, opacity: 0 }}
            animate={{ scale: 1, opacity: 1 }}
            transition={{ delay: 0.3, duration: 0.7, type: 'spring', damping: 12 }}
            className="relative mb-6"
          >
            <motion.div
              animate={{ y: [0, -8, 0] }}
              transition={{ duration: 4, repeat: Infinity, ease: 'easeInOut' }}
              className="w-36 h-36 text-sky-200"
            >
              <LotusIcon className="w-full h-full drop-shadow-lg" />
            </motion.div>
            <div className="absolute inset-0 rounded-full blur-2xl opacity-30" style={{ background: 'radial-gradient(circle, #BAE6FD, transparent)' }} />
          </motion.div>

          <motion.div
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ delay: 0.4 }}
            className="text-center mb-3"
          >
            <h1 className="text-[4rem] leading-none font-black tracking-tight text-transparent bg-clip-text bg-gradient-to-b from-white via-white to-sky-200" style={{ fontFamily: '"Kanit", sans-serif', filter: 'drop-shadow(0 4px 12px rgba(0,0,0,0.25))' }}>
              SONGKRAN
            </h1>
            <p className="text-xl tracking-[0.4em] text-sky-100 mt-1 font-bold" style={{ fontFamily: '"Kanit", sans-serif' }}>
              MUSIC <span className="text-transparent bg-clip-text bg-gradient-to-r from-sky-300 to-cyan-300">FESTIVAL</span>
            </p>
          </motion.div>

          <div className="flex items-center gap-3 mb-8 w-full max-w-xs" aria-hidden="true">
            <div className="flex-1 h-px bg-white/20" />
            <Sparkles className="w-4 h-4 text-sky-300" />
            <div className="flex-1 h-px bg-white/20" />
          </div>

          <motion.div
            initial={{ opacity: 0, scale: 0.95 }}
            animate={{ opacity: 1, scale: 1 }}
            transition={{ delay: 0.5, duration: 0.5 }}
            className="w-full max-w-sm bg-white/5 backdrop-blur-md border border-white/20 rounded-[2rem] p-5 mb-8 shadow-2xl"
          >
            <p className="text-sky-200 text-xs uppercase tracking-widest mb-3 font-medium text-center">
              Menghitung Mundur
            </p>
            <div className="grid grid-cols-4 gap-2" aria-label="Sisa waktu hingga festival">
              {Object.entries(timeLeft).map(([lbl, val]) => (
                <div key={lbl} className="text-center">
                  <motion.div
                    animate={{ scale: [1, 1.05, 1] }}
                    transition={{ duration: 1.5, repeat: Infinity }}
                    className="bg-white/10 border border-white/15 rounded-xl py-2.5 mb-1 shadow-inner"
                  >
                    <span
                      className="text-white font-black text-2xl leading-none"
                      style={{ fontFamily: '"Kanit", sans-serif', fontVariantNumeric: 'tabular-nums' }}
                    >
                      {val}
                    </span>
                  </motion.div>
                  <span className="text-sky-300 text-[10px] font-medium tracking-wide">{lbl}</span>
                </div>
              ))}
            </div>
          </motion.div>

          <motion.ul
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
            transition={{ delay: 0.6 }}
            className="space-y-3 w-full max-w-sm list-none p-0"
            aria-label="Keunggulan Festival"
          >
            {[
              { icon: Music2, text: 'Akses Line up Artis Eksklusif untuk Menyaksikan', iconColor: 'text-sky-300', iconBg: 'bg-sky-500/20' },
              { icon: Waves, text: 'Jadilah Bagian dari Sejarah Perayaan Air Terbesar di Asia Tenggara', iconColor: 'text-cyan-300', iconBg: 'bg-cyan-500/20' },
              { icon: Star, text: 'Nikmati Pengalaman Budaya Malaysia yang Paling Autentik & Spektakuler', iconColor: 'text-yellow-200', iconBg: 'bg-yellow-500/20' },
              { icon: Droplets, text: 'Mulai Awal Baru yang Berkah dengan Ritual Pembersihan Jiwa Songkran', iconColor: 'text-blue-300', iconBg: 'bg-blue-500/20' },
            ].map((item, i) => (
              <div key={i} style={{ perspective: 1000 }}>
                <motion.li
                  initial={{ opacity: 0, x: -24, rotateX: 0, rotateY: 0 }}
                  animate={{
                    opacity: 1,
                    x: 0,
                    scale: [1, 1.05, 1],
                    rotateX: [0, 5, 0],
                    rotateY: [0, -5, 0],
                    backgroundColor: ['rgba(255, 255, 255, 0.05)', 'rgba(234, 179, 8, 0.15)', 'rgba(255, 255, 255, 0.05)'],
                    borderColor: ['rgba(255, 255, 255, 0.15)', 'rgba(250, 204, 21, 0.4)', 'rgba(255, 255, 255, 0.15)'],
                  }}
                  transition={{
                    opacity: { delay: 0.65 + i * 0.1, duration: 0.5 },
                    x: { delay: 0.65 + i * 0.1, duration: 0.5 },
                    scale: { delay: 2 + i * 1.5, duration: 1.5, repeat: Infinity, repeatDelay: 4.5 },
                    rotateX: { delay: 2 + i * 1.5, duration: 1.5, repeat: Infinity, repeatDelay: 4.5 },
                    rotateY: { delay: 2 + i * 1.5, duration: 1.5, repeat: Infinity, repeatDelay: 4.5 },
                    backgroundColor: { delay: 2 + i * 1.5, duration: 1.5, repeat: Infinity, repeatDelay: 4.5 },
                    borderColor: { delay: 2 + i * 1.5, duration: 1.5, repeat: Infinity, repeatDelay: 4.5 }
                  }}
                  whileHover={{
                    scale: 1.05,
                    x: 4,
                    rotateX: 8,
                    rotateY: -8,
                    backgroundColor: 'rgba(234, 179, 8, 0.15)',
                    borderColor: 'rgba(250, 204, 21, 0.4)',
                    transition: { duration: 0.3 }
                  }}
                  style={{ backgroundColor: 'rgba(255, 255, 255, 0.05)', borderColor: 'rgba(255, 255, 255, 0.15)', transformStyle: 'preserve-3d' }}
                  className="flex items-center gap-3 backdrop-blur-sm border rounded-2xl px-4 py-3 shadow-[0_10px_30px_rgba(0,0,0,0.15)]"
                >
                  <div className={`w-9 h-9 ${item.iconBg} rounded-xl flex items-center justify-center flex-shrink-0 shadow-[inset_0_2px_4px_rgba(255,255,255,0.2)] border border-white/10`} style={{ transform: 'translateZ(20px)' }}>
                    <item.icon className={`w-4 h-4 ${item.iconColor}`} aria-hidden="true" />
                  </div>
                  <span className="text-sky-50 text-sm leading-snug font-medium" style={{ transform: 'translateZ(10px)' }}>{item.text}</span>
                </motion.li>
              </div>
            ))}
          </motion.ul>
        </motion.aside>

        {/* RIGHT PANEL – FORM */}
        <motion.main
          id="main-form"
          initial={{ opacity: 0, x: 50 }}
          animate={{ opacity: 1, x: 0 }}
          transition={{ duration: 0.9, ease: [0.25, 0.46, 0.45, 0.94] }}
          className="flex-1 flex flex-col items-center justify-center px-4 py-10 lg:px-8"
          tabIndex={-1}
        >
          {/* Mobile header */}
          <div className="lg:hidden text-center mb-6 text-white">
            <div className="w-20 h-20 mx-auto text-sky-200 mb-3">
              <LotusIcon className="w-full h-full" />
            </div>
            <h1 className="text-4xl font-black tracking-tight" style={{ fontFamily: '"Kanit", sans-serif' }}>
              SONGKRAN
            </h1>
            <p className="text-sky-200 text-sm tracking-[0.3em] mt-1">MUSIC FESTIVAL 2026</p>
          </div>

          {/* Form card */}
          <div
            className="w-full max-w-[520px] rounded-[2rem] overflow-hidden border border-white/20"
            style={{ boxShadow: '0 30px 80px rgba(0,0,0,0.35), 0 0 0 1px rgba(255,255,255,0.08)' }}
          >
            {/* Card header */}
            <div
              className="relative px-7 pt-7 pb-6 overflow-hidden"
              style={{ background: 'linear-gradient(135deg, #0369A1, #0284C7, #0EA5E9)' }}
            >
              <div className="absolute -top-10 -right-10 w-40 h-40 rounded-full border border-white/10 pointer-events-none" aria-hidden="true" />
              <div className="absolute -top-6 -right-6 w-28 h-28 rounded-full border border-white/10 pointer-events-none" aria-hidden="true" />

              <div className="relative">
                <h2 className="text-white text-2xl font-bold" style={{ fontFamily: '"Kanit", sans-serif' }}>
                  Daftar Sekarang
                </h2>
                <p className="text-sky-100 text-sm mt-0.5">Bergabunglah bersama ribuan peserta Songkran 2026</p>

                <nav className="flex items-center mt-6" aria-label="Langkah pendaftaran">
                  <ol className="flex items-center w-full list-none p-0 m-0">
                    {STEPS.map((step, idx) => (
                      <li
                        key={step.id}
                        className={`flex items-center ${idx < STEPS.length - 1 ? 'flex-1' : ''}`}
                        aria-current={currentStep === step.id ? 'step' : undefined}
                      >
                        <div className="flex flex-col items-center">
                          <motion.div
                            animate={{
                              backgroundColor: currentStep >= step.id ? '#ffffff' : 'rgba(255,255,255,0.2)',
                              scale: currentStep === step.id ? 1.12 : 1,
                            }}
                            transition={{ duration: 0.3 }}
                            className="w-9 h-9 rounded-full flex items-center justify-center border-2 border-white/40"
                            aria-label={`${step.label} ${currentStep > step.id ? '(selesai)' : currentStep === step.id ? '(aktif)' : '(belum)'}`}
                          >
                            {currentStep > step.id ? (
                              <CheckCircle2 className="w-5 h-5 text-sky-600" aria-hidden="true" />
                            ) : (
                              <step.icon
                                className={`w-4 h-4 ${currentStep >= step.id ? 'text-sky-600' : 'text-white/60'}`}
                                aria-hidden="true"
                              />
                            )}
                          </motion.div>
                          <span
                            className={`text-[10px] mt-1 font-medium whitespace-nowrap ${currentStep >= step.id ? 'text-white' : 'text-white/45'
                              }`}
                          >
                            {step.label}
                          </span>
                        </div>
                        {idx < STEPS.length - 1 && (
                          <div className="flex-1 px-2 mb-5" aria-hidden="true">
                            <div className="relative h-0.5 bg-white/20 rounded-full overflow-hidden">
                              <motion.div
                                animate={{ width: currentStep > step.id ? '100%' : '0%' }}
                                transition={{ duration: 0.45, ease: 'easeOut' }}
                                className="absolute inset-y-0 left-0 bg-white rounded-full"
                              />
                            </div>
                          </div>
                        )}
                      </li>
                    ))}
                  </ol>
                </nav>
              </div>
            </div>

            {/* Form body */}
            <div className="bg-white">
              <form
                onSubmit={handleSubmit(onSubmit)}
                noValidate
                aria-label="Formulir Pendaftaran Songkran Music Festival"
              >
                <div className="px-7 py-6 relative overflow-hidden" style={{ minHeight: 360 }}>
                  <AnimatePresence mode="wait" custom={direction}>

                    {/* STEP 1: DATA PRIBADI */}
                    {currentStep === 1 && (
                      <motion.div
                        key="step1"
                        custom={direction}
                        variants={slideVariants}
                        initial="enter"
                        animate="center"
                        exit="exit"
                        className="space-y-5"
                      >
                        <div>
                          <label htmlFor="full_name" className="block text-slate-700 text-sm font-semibold mb-1.5">
                            Nama Lengkap <span className="text-red-500" aria-hidden="true">*</span>
                          </label>
                          <div className="relative">
                            <User className="absolute left-3.5 top-1/2 -translate-y-1/2 text-sky-400 pointer-events-none" aria-hidden="true" style={{ width: 18, height: 18 }} />
                            <input
                              id="full_name"
                              type="text"
                              autoComplete="name"
                              placeholder="Masukkan nama lengkap Anda"
                              className={inputClass('full_name')}
                              {...register('full_name', {
                                required: 'Nama lengkap wajib diisi',
                                minLength: { value: 2, message: 'Nama minimal 2 karakter' },
                                pattern: { value: /^[a-zA-Z\s.''-]+$/, message: 'Nama hanya boleh berisi huruf dan spasi' },
                              })}
                            />
                          </div>
                          <AnimatePresence>
                            <FieldError id="err-full_name" message={errors.full_name?.message} />
                          </AnimatePresence>
                        </div>

                        <div>
                          <label htmlFor="email" className="block text-slate-700 text-sm font-semibold mb-1.5">
                            Email <span className="text-red-500" aria-hidden="true">*</span>
                          </label>
                          <div className="relative">
                            <Mail className="absolute left-3.5 top-1/2 -translate-y-1/2 text-sky-400 pointer-events-none" aria-hidden="true" style={{ width: 18, height: 18 }} />
                            <input
                              id="email"
                              type="email"
                              autoComplete="email"
                              placeholder="nama@email.com"
                              className={inputClass('email')}
                              {...register('email', {
                                required: 'Email wajib diisi',
                                pattern: { value: /^[^\s@]+@[^\s@]+\.[^\s@]+$/, message: 'Format email tidak valid' },
                              })}
                            />
                          </div>
                          <AnimatePresence>
                            <FieldError id="err-email" message={errors.email?.message} />
                          </AnimatePresence>
                        </div>

                        <div>
                          <label htmlFor="phone_country_code" className="block text-slate-700 text-sm font-semibold mb-1.5">
                            Nomor HP <span className="text-red-500" aria-hidden="true">*</span>
                          </label>
                          <div className="grid grid-cols-1 gap-3 sm:grid-cols-[180px_minmax(0,1fr)]">
                            <Controller
                              control={control}
                              name="phone_country_code"
                              rules={{
                                required: 'Kode negara wajib dipilih',
                                validate: (value) => /^\+\d{1,4}$/.test(normalizePhoneCountryCode(value)) || 'Kode negara tidak valid',
                              }}
                              render={({ field }) => (
                                <Select
                                  value={field.value}
                                  onValueChange={(value) => field.onChange(normalizePhoneCountryCode(value))}
                                >
                                  <SelectTrigger
                                    id="phone_country_code"
                                    className={phoneSelectClass}
                                    aria-invalid={errors.phone_country_code ? 'true' : 'false'}
                                  >
                                    {phoneCountryOption ? (
                                      <span className="flex items-center gap-2.5 truncate">
                                        <span
                                          className={`${phoneCountryOption.flagClassName} h-4 w-[22px] rounded-[2px] shadow-sm`}
                                          aria-hidden="true"
                                        />
                                        <span className="truncate text-base font-medium">{phoneCountryOption.dialCode}</span>
                                      </span>
                                    ) : (
                                      <SelectValue placeholder="Kode" />
                                    )}
                                  </SelectTrigger>
                                  <SelectContent className="rounded-xl border-sky-100">
                                    {PHONE_COUNTRY_CODES.map(option => (
                                      <SelectItem key={`${option.country}-${option.dialCode}`} value={option.dialCode}>
                                        <span className="flex items-center gap-2.5">
                                          <span
                                            className={`${option.flagClassName} h-4 w-[22px] rounded-[2px] shadow-sm`}
                                            aria-hidden="true"
                                          />
                                          <span>{option.countryName}</span>
                                          <span className="text-slate-500">{option.dialCode}</span>
                                        </span>
                                      </SelectItem>
                                    ))}
                                  </SelectContent>
                                </Select>
                              )}
                            />
                            <input
                              id="phone_national_number"
                              type="tel"
                              autoComplete="tel-national"
                              placeholder="822123450"
                              inputMode="numeric"
                              pattern="[0-9]*"
                              maxLength={20}
                              className={phoneNumberInputClass}
                              {...phoneNationalNumberField}
                            />
                          </div>
                          <p className="mt-1.5 text-xs leading-relaxed text-slate-500">
                            Pilih kode negara, lalu isi nomor tanpa mengulang kode negara. Contoh: +62 822123450.
                          </p>
                          <AnimatePresence>
                            <FieldError id="err-phone_country_code" message={errors.phone_country_code?.message} />
                          </AnimatePresence>
                          <AnimatePresence>
                            <FieldError id="err-phone_national_number" message={errors.phone_national_number?.message} />
                          </AnimatePresence>
                        </div>

                        <div>
                          <label htmlFor="country" className="block text-slate-700 text-sm font-semibold mb-1.5">
                            Negara <span className="text-red-500" aria-hidden="true">*</span>
                          </label>
                          <Controller
                            control={control}
                            name="country"
                            rules={{ required: 'Negara wajib dipilih' }}
                            render={({ field }) => (
                              <Select value={field.value} onValueChange={field.onChange}>
                                <SelectTrigger
                                  id="country"
                                  className={countrySelectClass}
                                  aria-invalid={errors.country ? 'true' : 'false'}
                                >
                                  {selectedCountryOption ? (
                                    <span className="flex items-center gap-2.5 truncate">
                                      <span
                                        className={`fi fi-${selectedCountryOption.code.toLowerCase()} h-4 w-[22px] rounded-[2px] shadow-sm`}
                                        aria-hidden="true"
                                      />
                                      <span className="truncate text-base font-medium">{selectedCountryOption.name}</span>
                                    </span>
                                  ) : (
                                    <span className="flex items-center gap-2.5 text-slate-400">
                                      <Globe className="h-4 w-4 text-sky-400" aria-hidden="true" />
                                      <SelectValue placeholder="Pilih negara Anda" />
                                    </span>
                                  )}
                                </SelectTrigger>
                                <SelectContent className="rounded-xl border-sky-100">
                                  {SORTED_COUNTRIES.map(c => (
                                    <SelectItem key={c.code} value={c.code}>
                                      <span className="flex items-center gap-2.5">
                                        <span
                                          className={`fi fi-${c.code.toLowerCase()} h-4 w-[22px] rounded-[2px] shadow-sm`}
                                          aria-hidden="true"
                                        />
                                        <span>{c.name}</span>
                                      </span>
                                    </SelectItem>
                                  ))}
                                </SelectContent>
                              </Select>
                            )}
                          />
                          <AnimatePresence>
                            <FieldError id="err-country" message={errors.country?.message} />
                          </AnimatePresence>
                        </div>

                        <div>
                          <label htmlFor="identity_type" className="block text-slate-700 text-sm font-semibold mb-1.5">
                            Jenis Dokumen <span className="text-red-500" aria-hidden="true">*</span>
                          </label>
                          <div className="relative">
                            <IdCard className="absolute left-3.5 top-1/2 -translate-y-1/2 text-sky-400 pointer-events-none" aria-hidden="true" style={{ width: 18, height: 18 }} />
                            <select
                              id="identity_type"
                              className={`${inputClass('identity_type')} appearance-none cursor-pointer`}
                              {...register('identity_type', { required: 'Jenis dokumen wajib dipilih' })}
                            >
                              <option value="">Pilih jenis dokumen</option>
                              {availableIdentityTypes.map(option => (
                                <option key={option.value} value={option.value}>
                                  {option.label}
                                </option>
                              ))}
                            </select>
                            <ChevronDown className="absolute right-3.5 top-1/2 -translate-y-1/2 text-sky-400 pointer-events-none" aria-hidden="true" style={{ width: 18, height: 18 }} />
                          </div>
                          <p className="mt-1.5 text-[11px] leading-relaxed text-slate-500">
                            {identityHelperText}
                          </p>
                          <AnimatePresence>
                            <FieldError id="err-identity_type" message={errors.identity_type?.message} />
                          </AnimatePresence>
                        </div>

                        <div>
                          <label htmlFor="identity_number" className="block text-slate-700 text-sm font-semibold mb-1">
                            {identityNumberLabel} <span className="text-red-500" aria-hidden="true">*</span>
                          </label>
                          <div className="relative">
                            <IdCard className="absolute left-3.5 top-1/2 -translate-y-1/2 text-sky-400 pointer-events-none" aria-hidden="true" style={{ width: 18, height: 18 }} />
                            <input
                              id="identity_number"
                              type="text"
                              placeholder={identityNumberPlaceholder}
                              className={inputClass('identity_number')}
                              {...register('identity_number', {
                                required: 'Nomor dokumen wajib diisi',
                                minLength: { value: 6, message: 'Minimal 6 karakter' },
                              })}
                            />
                          </div>
                          <AnimatePresence>
                            <FieldError id="err-identity_number" message={errors.identity_number?.message} />
                          </AnimatePresence>
                        </div>
                      </motion.div>
                    )}

                    {/* STEP 2: KONFIRMASI */}
                    {currentStep === 2 && (
                      <motion.div
                        key="step2"
                        custom={direction}
                        variants={slideVariants}
                        initial="enter"
                        animate="center"
                        exit="exit"
                        className="space-y-5"
                      >
                        <div className="bg-gradient-to-br from-sky-50 to-cyan-50 rounded-2xl p-5 border border-sky-100 shadow-sm">
                          <h3 className="text-sky-900 text-lg font-bold mb-4 flex items-center gap-2" style={{ fontFamily: '"Kanit", sans-serif' }}>
                            <CheckCircle2 className="w-5 h-5 text-sky-600" aria-hidden="true" />
                            Ringkasan Data
                          </h3>
                          <div className="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3">
                            {[
                              { label: 'Nama Lengkap', value: getValues('full_name'), icon: User },
                              { label: 'Email', value: getValues('email'), icon: Mail },
                              { label: 'Jenis Dokumen', value: identityTypeLabel, icon: IdCard },
                              { label: identityNumberLabel, value: getValues('identity_number'), icon: IdCard },
                              { label: 'Nomor HP', value: phonePreview, icon: Phone },
                              { label: 'Negara', value: countryLabel, icon: Globe },
                            ].map(d => (
                              <div key={d.label} className="flex flex-col gap-0.5 border-b border-sky-100/50 pb-2 last:border-0 last:pb-0">
                                <dt className="text-slate-400 text-[10px] uppercase tracking-wider font-semibold flex items-center gap-1.5">
                                  <d.icon className="w-3 h-3 text-sky-400" />
                                  {d.label}
                                </dt>
                                <dd className="font-bold text-slate-800 text-sm truncate">{d.value}</dd>
                              </div>
                            ))}
                          </div>
                        </div>

                        <div className="flex items-start gap-3">
                          <input
                            id="agreeTerms"
                            type="checkbox"
                            className="mt-0.5 rounded border-2 border-sky-300 text-sky-600 cursor-pointer focus:ring-2 focus:ring-sky-500 focus:ring-offset-1 flex-shrink-0"
                            style={{ width: 18, height: 18 }}
                            {...register('agreeTerms', { required: 'Anda harus menyetujui syarat dan ketentuan' })}
                          />
                          <div className="text-slate-600 text-xs leading-relaxed">
                            <label htmlFor="agreeTerms" className="cursor-pointer">
                              Saya menyetujui
                            </label>{' '}
                            <button
                              type="button"
                              onClick={() => setLegalDialog('terms')}
                              className="font-semibold text-sky-600 underline underline-offset-2 transition-colors hover:text-sky-700 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2 focus:ring-offset-white rounded-sm"
                            >
                              Syarat &amp; Ketentuan
                            </button>{' '}
                            serta{' '}
                            <button
                              type="button"
                              onClick={() => setLegalDialog('privacy')}
                              className="font-semibold text-sky-600 underline underline-offset-2 transition-colors hover:text-sky-700 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2 focus:ring-offset-white rounded-sm"
                            >
                              Kebijakan Privasi
                            </button>
                            .
                          </div>
                        </div>
                        <AnimatePresence>
                          <FieldError id="err-terms" message={errors.agreeTerms?.message} />
                        </AnimatePresence>
                      </motion.div>
                    )}
                  </AnimatePresence>
                </div>

                {/* Navigation footer */}
                <div className="px-7 py-4 bg-slate-50 border-t border-slate-100 flex items-center justify-between gap-3">
                  <span className="text-slate-400 text-xs" aria-live="polite" aria-atomic="true">
                    Langkah <strong className="text-slate-600">{currentStep}</strong> dari 2
                  </span>
                  <div className="flex items-center gap-3">
                    {currentStep > 1 && (
                      <motion.button
                        type="button"
                        onClick={handlePrev}
                        whileHover={{ scale: 1.02 }}
                        whileTap={{ scale: 0.97 }}
                        className="flex items-center gap-1.5 px-4 py-2.5 rounded-xl border-2 border-sky-200 text-sky-700 text-sm font-semibold hover:bg-sky-50 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-1 transition-colors"
                      >
                        <ArrowLeft className="w-4 h-4" aria-hidden="true" />
                        Kembali
                      </motion.button>
                    )}
                    {currentStep < 2 ? (
                      <motion.button
                        type="button"
                        onClick={handleNext}
                        whileHover={{ scale: 1.02 }}
                        whileTap={{ scale: 0.97 }}
                        className="flex items-center gap-1.5 px-6 py-2.5 rounded-xl text-white text-sm font-semibold focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-1 transition-all"
                        style={{ background: 'linear-gradient(135deg, #0284C7, #0EA5E9)', boxShadow: '0 4px 14px rgba(2,132,199,0.35)' }}
                      >
                        Selanjutnya
                        <ArrowRight className="w-4 h-4" aria-hidden="true" />
                      </motion.button>
                    ) : (
                      <motion.button
                        type="submit"
                        disabled={isSubmitting || !canSubmitConfirmation}
                        whileHover={{ scale: isSubmitting || !canSubmitConfirmation ? 1 : 1.02 }}
                        whileTap={{ scale: isSubmitting || !canSubmitConfirmation ? 1 : 0.97 }}
                        className="flex items-center gap-2 px-6 py-2.5 rounded-xl text-white text-sm font-semibold focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-1 transition-all disabled:opacity-70 disabled:cursor-not-allowed"
                        style={{ background: 'linear-gradient(135deg, #0284C7, #0EA5E9)', boxShadow: '0 4px 14px rgba(2,132,199,0.35)' }}
                        aria-busy={isSubmitting}
                        aria-disabled={isSubmitting || !canSubmitConfirmation}
                      >
                        {isSubmitting ? (
                          <>
                            <Loader2 className="w-4 h-4 animate-spin" aria-hidden="true" />
                            <span>Mendaftar...</span>
                          </>
                        ) : (
                          <>
                            <span>Daftar Sekarang</span>
                            <Droplets className="w-4 h-4" aria-hidden="true" />
                          </>
                        )}
                      </motion.button>
                    )}
                  </div>
                </div>
              </form>
            </div>
          </div>

          {/* Login link */}
          {/* <p className="text-center mt-5 text-sky-200 text-sm">
            Sudah punya akun?{' '}
            <Link
              to="/"
              className="text-white underline font-semibold hover:text-sky-100 focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-1 focus:ring-offset-transparent rounded"
            >
              Masuk di sini
            </Link>
          </p> */}


        </motion.main>
      </div>

      <Dialog open={legalDialog !== null} onOpenChange={(open) => !open && setLegalDialog(null)}>
        {activeLegalDialog ? (
          <DialogContent showCloseButton={false} className="max-w-2xl gap-0 overflow-hidden rounded-[1.75rem] border-sky-100 p-0 shadow-2xl">
            <div className="bg-gradient-to-r from-sky-700 to-cyan-500 px-6 py-5 text-white">
              <DialogHeader className="text-left">
                <DialogTitle className="text-xl font-bold tracking-tight" style={{ fontFamily: '"Kanit", sans-serif' }}>
                  {activeLegalDialog.title}
                </DialogTitle>
                <DialogDescription className="text-sm text-sky-50/90">
                  {activeLegalDialog.description}
                </DialogDescription>
              </DialogHeader>
            </div>

            <div className="max-h-[60vh] overflow-y-auto px-6 py-5">
              <div className="flex flex-col gap-4 text-sm leading-7 text-slate-600">
                {activeLegalDialog.paragraphs.map((paragraph, index) => (
                  <p key={`${legalDialog}-${index}`}>{paragraph}</p>
                ))}
              </div>
            </div>

              <DialogFooter className="border-t border-slate-100 px-6 py-4">
              <DialogClose asChild>
                <Button type="button" variant="outline">
                  Tutup
                </Button>
              </DialogClose>
            </DialogFooter>
          </DialogContent>
        ) : null}
      </Dialog>

      {/* SUCCESS OVERLAY */}
      <AnimatePresence>
        {isSuccess && (
          <motion.div
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
            className="fixed inset-0 flex items-center justify-center p-4 md:p-6"
            style={{ zIndex: 100, backgroundColor: 'rgba(12,74,110,0.98)', backdropFilter: 'blur(20px)' }}
            role="dialog"
            aria-modal="true"
            aria-labelledby="success-title"
          >
            <motion.div
              initial={{ scale: 0.9, opacity: 0, y: 20 }}
              animate={{ scale: 1, opacity: 1, y: 0 }}
              className="relative w-full max-w-lg max-h-[90vh] overflow-y-auto bg-white/10 border border-white/20 p-6 md:p-10 rounded-[2.5rem] shadow-2xl backdrop-blur-md scrollbar-hide text-center"
            >
              {/* CLOSE BUTTON */}
              <button
                onClick={handleResetForm}
                className="absolute top-5 right-5 p-2 text-sky-200 hover:text-white hover:bg-white/10 rounded-full transition-colors z-10"
                aria-label="Tutup"
              >
                <X className="w-6 h-6" />
              </button>

              {/* ICON */}
              <motion.div
                animate={{ rotate: [0, 5, -5, 0], scale: [1, 1.05, 1] }}
                transition={{ duration: 4, repeat: Infinity, ease: "easeInOut" }}
                className="w-20 h-20 md:w-28 md:h-28 mx-auto mb-6 text-sky-300"
              >
                <LotusIcon className="w-full h-full drop-shadow-[0_0_15px_rgba(125,211,252,0.4)]" />
              </motion.div>

              <div className="space-y-6">
                <div>
                  <CheckCircle2 className="w-10 h-10 md:w-14 md:h-14 text-emerald-400 mx-auto mb-4" />
                  <h2 id="success-title" className="text-white text-2xl md:text-4xl font-black leading-tight tracking-tight mb-3" style={{ fontFamily: '"Kanit", sans-serif' }}>
                    Registrasi Berhasil! 🎉 <br className="hidden sm:block" /> Cek Email Verifikasi
                  </h2>
                  <p className="text-sky-200 text-base md:text-lg mb-1">
                    Kami sudah mengirim link verifikasi ke
                  </p>
                  <p className="text-sky-100 font-bold text-xl md:text-2xl" style={{ fontFamily: '"Kanit", sans-serif' }}>
                    {registeredEmail || 'email Anda'}
                  </p>
                </div>

                <p className="text-sky-300 text-xs md:text-sm leading-relaxed max-w-sm mx-auto opacity-90">
                  Buka email tersebut, klik link verifikasi, lalu akun Anda akan aktif dan QR code tiket akan langsung tersedia.
                </p>

                <div className="flex flex-wrap gap-2 justify-center">
                  {[
                    { text: '📅 9–19 April 2026', color: 'from-sky-500/20 to-sky-400/10' },
                    { text: '📍 Malaysia', color: 'from-cyan-500/20 to-cyan-400/10' },
                    { text: '🎵 50+ Artis', color: 'from-indigo-500/20 to-indigo-400/10' }
                  ].map((item, idx) => (
                    <span key={idx} className={`bg-gradient-to-br ${item.color} border border-white/10 text-sky-100 text-[10px] md:text-xs px-4 py-2 rounded-full font-semibold tracking-wide backdrop-blur-sm`}>
                      {item.text}
                    </span>
                  ))}
                </div>

                <motion.button
                  whileHover={{ scale: 1.02 }}
                  whileTap={{ scale: 0.98 }}
                  onClick={handleResetForm}
                  className="w-full py-4 rounded-2xl bg-gradient-to-r from-sky-400 to-cyan-400 text-sky-950 font-black text-sm md:text-base uppercase tracking-widest hover:shadow-[0_0_20px_rgba(56,189,248,0.4)] transition-all shadow-lg"
                  style={{ fontFamily: '"Kanit", sans-serif' }}
                >
                  Daftarkan Peserta Lain
                </motion.button>
              </div>
            </motion.div>
          </motion.div>
        )}
      </AnimatePresence>
    </div>
  );
}
