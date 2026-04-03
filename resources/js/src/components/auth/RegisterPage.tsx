import { useState, useCallback, useRef, useEffect } from 'react';
import { Controller, useForm } from 'react-hook-form';
import { motion, AnimatePresence } from 'motion/react';
import { Toaster, toast } from 'sonner';
import {
  User, Mail, Phone, Globe, MapPin, IdCard,
  Calendar, Music2, ChevronDown, CheckCircle2,
  AlertCircle, Loader2,
  Droplets, Star, Waves, Sparkles, X, Lock
} from 'lucide-react';
import {
  AuthCardFrame,
  AuthCardHeader,
  AuthInlineError,
  LotusIcon,
  AuthPageShell,
  authInputClass,
  authSelectClass,
} from './AuthShared';
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
  SelectSeparator,
  SelectTrigger,
  SelectValue,
} from '../ui/Select';
import {
  captureTrafficAttribution,
  type TrafficAttributionPayload,
} from '@/lib/trafficAttribution';
import { getSpaUrl } from '@/lib/spaRouting';
import {
  OTHER_PHONE_OPTIONS as OTHER_PHONE_COUNTRY_CODES,
  OTHER_SORTED_COUNTRIES,
  PHONE_DIAL_CODES,
  PHONE_OPTIONS as PHONE_COUNTRY_CODES,
  PRIORITY_PHONE_OPTIONS as PRIORITY_PHONE_COUNTRY_CODES,
  PRIORITY_SORTED_COUNTRIES,
  SORTED_COUNTRIES,
} from '@/lib/countryCatalog';

interface FormData {
  full_name: string;
  email: string;
  phone_country_code: string;
  phone_national_number: string;
  country: string;
  identity_type: 'national_id' | 'passport' | '';
  identity_number: string;
  recaptcha_token: string;
  agreeTerms: boolean;
}

type DeliveryStatus = 'sent' | 'already_sent' | 'queued' | 'failed';

interface RegisterResponse {
  message?: string;
  status?: 'ticket_ready' | 'ticket_ready_email_pending';
  delivery_status?: DeliveryStatus;
  email_sent?: boolean;
  ticket_url?: string;
  ticket_qr_url?: string;
  ticket_code?: string;
  entry_code_display?: string;
  errors?: Record<string, string[]>;
}

interface RegisteredTicketPreviewData {
  fullName: string;
  identityNumber: string;
}

const FORM_FIELDS: Array<keyof FormData> = [
  'full_name',
  'email',
  'phone_country_code',
  'phone_national_number',
  'country',
  'identity_type',
  'identity_number',
  'recaptcha_token',
  'agreeTerms',
];

type GrecaptchaExecuteParameters = {
  action: string;
};

type GrecaptchaInstance = {
  ready: (callback: () => void) => void;
  execute: (siteKey: string, parameters: GrecaptchaExecuteParameters) => Promise<string>;
};

declare global {
  interface Window {
    grecaptcha?: GrecaptchaInstance;
  }
}

const MONTHS = [
  'January', 'February', 'March', 'April', 'May', 'June',
  'July', 'August', 'September', 'October', 'November', 'December'
];

const IDENTITY_TYPES = [
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
] as const;

const TICKET_BACKGROUND_URL = '/images/BACKGROUND.jpg';
const SONGKRAN_LOGO_URL = '/images/Songkran%20logo.png';
const SPONSORS_FOOTER_URL = '/images/email-sponsors-footer.png';
const MAPS_LOCATION_URL = 'https://maps.app.goo.gl/UEPceTqzjesMy1ze8?g_st=iw';
const ENABLE_LEGACY_SUCCESS_SCREEN = true;
const TICKET_HEADER_FONT_FAMILY = '"Tilt Warp", sans-serif';
const PUBLIC_HOME_URL = getSpaUrl('publicHome', '/');

function MapsPinIcon() {
  return (
    <svg
      className="h-[30px] w-[30px] shrink-0 drop-shadow-[0_1px_2px_rgba(0,0,0,0.2)]"
      viewBox="0 0 24 24"
      aria-hidden="true"
    >
      <path
        fill="#4285F4"
        d="M12 2C8.13 2 5 5.13 5 9c0 4.91 5.37 11.62 6.08 12.49a1.18 1.18 0 0 0 1.84 0C13.63 20.62 19 13.91 19 9c0-3.87-3.13-7-7-7Z"
      />
      <path
        fill="#34A853"
        d="M12 2a6.96 6.96 0 0 0-5.17 2.29l4.24 4.24A2.5 2.5 0 0 1 14.5 12l4.21 4.21C18.9 13.91 19 11.15 19 9c0-3.87-3.13-7-7-7Z"
      />
      <path
        fill="#FBBC04"
        d="M7.04 4.06A6.97 6.97 0 0 0 5 9c0 4.91 5.37 11.62 6.08 12.49.49.61 1.27.61 1.84 0 .29-.36 1.42-1.78 2.63-3.63L7.04 9.35A2.49 2.49 0 0 1 7.04 4.06Z"
      />
      <circle cx="12" cy="9" r="3.2" fill="#EA4335" />
    </svg>
  );
}

function TicketPreviewCard({
  fullName,
  identityNumber,
  qrUrl,
  ticketCode,
  entryCodeDisplay,
  ticketUrl,
}: RegisteredTicketPreviewData & {
  qrUrl: string;
  ticketCode: string;
  entryCodeDisplay: string;
  ticketUrl: string;
}) {
  const identityDisplay = identityNumber.trim() || '-';
  const hasTicketLink = ticketUrl.trim() !== '';
  const hasQrUrl = qrUrl.trim() !== '';

  return (
    <div
      className="mx-auto flex w-full max-w-[600px] flex-col overflow-hidden rounded-[28px] text-center text-white shadow-[0_30px_70px_rgba(4,88,120,0.28)]"
      style={{
        backgroundImage: `url(${TICKET_BACKGROUND_URL})`,
        backgroundSize: '100% calc(100% + 2cm)',
        backgroundPosition: 'center bottom',
        backgroundRepeat: 'no-repeat',
        backgroundColor: '#038cb2',
      }}
    >
      <div className="px-5 pt-5">
        <img
          src={SONGKRAN_LOGO_URL}
          alt="Songkran Festival Logo"
          className="mx-auto mb-0 w-full max-w-[500px] drop-shadow-[0_10px_20px_rgba(0,150,200,0.4)]"
          decoding="async"
        />

        <div
          className="-mt-[15px] tracking-[0.5px] [text-shadow:1px_1px_4px_rgba(0,0,0,0.4)]"
          style={{ fontFamily: TICKET_HEADER_FONT_FAMILY }}
        >
          <p className="mb-1 text-2xl font-extrabold md:text-[1.5rem]">12PM-12AM</p>
          <p className="mb-1 text-[2.2rem] font-black leading-none tracking-[1px] md:text-[3rem]">9-19 APRIL</p>
          <p className="my-[10px] text-[0.95rem] font-extrabold md:text-[1.1rem]">@GF FORECOURT OUTDOOR CARPARK, 1 UTAMA</p>
          <p className="mt-[5px] text-[0.9rem] font-bold uppercase">MALAYSIA'S PREMIER SONGKRAN FESTIVAL</p>
        </div>

        <div className="mx-auto mt-[25px] flex w-full max-w-[280px] flex-col items-center justify-center gap-[14px] overflow-hidden rounded-[12px] px-[14px] pb-[18px] pt-[14px]">
          <div className="flex h-[160px] w-[160px] items-center justify-center">
            {hasQrUrl ? (
              <img
                src={qrUrl}
                alt="Registered participant QR code"
                className="block h-[160px] w-[160px] object-contain"
              />
            ) : (
              <div className="px-5 text-center text-sm font-extrabold uppercase tracking-[0.18em] text-sky-700">
                {ticketCode.trim() !== '' ? ticketCode : 'Ticket QR'}
              </div>
            )}
          </div>

          {/* Temporary: hide Entry Code block in ticket preview. */}
        </div>

        <div className="my-5 mb-6 text-white">
          {hasTicketLink ? (
            <a
              href={ticketUrl}
              target="_blank"
              rel="noreferrer"
              className="inline-flex min-h-12 items-center justify-center rounded-full bg-white px-[22px] text-[0.95rem] font-extrabold text-sky-700 no-underline shadow-[0_12px_24px_rgba(2,132,199,0.24)] transition-transform duration-200 hover:-translate-y-px hover:bg-sky-50 hover:text-sky-800"
            >
              Open Ticket in Browser
            </a>
          ) : (
            <span className="inline-flex min-h-12 items-center justify-center rounded-full bg-white/90 px-[22px] text-[0.95rem] font-extrabold text-sky-700 shadow-[0_12px_24px_rgba(2,132,199,0.18)]">
              Ticket Link Unavailable
            </span>
          )}
        </div>

        <div className="mx-auto mt-[30px] min-h-20 w-fit min-w-[260px] max-w-[85%] rounded-[20px] border border-white/25 bg-white/40 px-[35px] py-[30px] text-center text-black shadow-[0_4px_15px_rgba(0,0,0,0.05)] backdrop-blur-[8px]">
          <div className="mb-[5px] break-words text-[1.4rem] font-extrabold uppercase tracking-[0.5px]">
            {fullName.trim() || 'GUEST'}
          </div>
          <div className="break-words text-[1.4rem] font-semibold opacity-90">{identityDisplay}</div>
        </div>
      </div>

      <div className="mx-auto my-[22px] mb-5 text-[0.82rem] font-extrabold uppercase tracking-[0.04em] [text-shadow:1px_1px_3px_rgba(0,0,0,0.45)]">
        Ticket valid from 9-19 April 2026
      </div>

      <div className="px-5 pb-7 pt-[10px]">
        <div className="mb-[30px] [text-shadow:1px_1px_3px_rgba(0,0,0,0.5)]">
          <div className="mb-3 text-[1.3rem] font-extrabold md:text-[1.6rem]">Thank you for your registration.</div>
          <div className="mx-auto max-w-[520px] text-[0.85rem] font-bold leading-[1.5] md:text-[0.95rem]">
            Please present your QR code and registered valid ID / passport at the gate.
          </div>
          <a
            href={MAPS_LOCATION_URL}
            target="_blank"
            rel="noopener noreferrer"
            className="mt-4 inline-flex items-center justify-center gap-2.5 text-[1.25rem] font-extrabold text-white no-underline [text-shadow:1px_1px_3px_rgba(0,0,0,0.45)] md:text-[1.6rem]"
          >
            <MapsPinIcon />
            <span>Maps to Location</span>
          </a>
        </div>

        <div className="px-[18px]">
          <div className="rounded-[28px] border border-white/20 bg-[linear-gradient(180deg,rgba(214,236,244,0.98)_0%,rgba(206,230,241,0.94)_100%)] p-[14px] shadow-[0_12px_24px_rgba(0,0,0,0.1)]">
            <img
              src={SPONSORS_FOOTER_URL}
              alt="Songkran Festival organiser, venue sponsor, sponsors, and media partners"
              className="block h-auto w-full rounded-[20px]"
              decoding="async"
            />
          </div>
        </div>
      </div>
    </div>
  );
}

function normalizePhoneCountryCode(value: string) {
  const digits = value.replace(/\D/g, '');

  return digits ? `+${digits}` : '';
}

function normalizePhoneNationalNumber(value: string) {
  return value.replace(/\D/g, '').replace(/^0+/, '');
}

function normalizeIdentityNumber(value: string, identityType: FormData['identity_type']) {
  if (identityType === 'national_id') {
    return value.replace(/\D/g, '').slice(0, 12);
  }

  return value.replace(/[^a-zA-Z0-9]/g, '').slice(0, 10);
}

function validateIdentityNumber(value: string, identityType: FormData['identity_type']) {
  if (value === '') {
    return 'Document number is required.';
  }

  if (identityType === 'national_id') {
    if (!/^\d+$/.test(value)) {
      return 'Malaysia IC must contain digits only.';
    }

    if (value.length > 12) {
      return 'Malaysia IC must be at most 12 digits.';
    }

    return true;
  }

  if (identityType === 'passport') {
    if (!/^[a-zA-Z0-9]+$/.test(value)) {
      return 'Passport number must be alphanumeric only.';
    }

    if (value.length > 10) {
      return 'Passport number must be at most 10 characters.';
    }

    return true;
  }

  return 'Document type is required.';
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
let recaptchaLoader: Promise<GrecaptchaInstance | null> | null = null;

function isFormField(value: string): value is keyof FormData {
  return FORM_FIELDS.includes(value as keyof FormData);
}

function getMetaContent(name: string) {
  if (typeof document === 'undefined') {
    return '';
  }

  return (document.querySelector(`meta[name="${name}"]`) as HTMLMetaElement | null)?.content?.trim() ?? '';
}

function ensureRecaptcha(siteKey: string) {
  if (typeof window === 'undefined' || typeof document === 'undefined') {
    return Promise.resolve<GrecaptchaInstance | null>(null);
  }

  if (siteKey.trim() === '') {
    return Promise.resolve<GrecaptchaInstance | null>(null);
  }

  if (window.grecaptcha) {
    return new Promise<GrecaptchaInstance | null>((resolve) => {
      window.grecaptcha?.ready(() => resolve(window.grecaptcha ?? null));
    });
  }

  if (recaptchaLoader) {
    return recaptchaLoader;
  }

  const loader = new Promise<GrecaptchaInstance | null>((resolve) => {
    const resolveWhenReady = () => {
      if (!window.grecaptcha) {
        return false;
      }

      window.grecaptcha.ready(() => resolve(window.grecaptcha ?? null));

      return true;
    };

    const existingScript = document.getElementById('google-recaptcha-api') as HTMLScriptElement | null;
    if (existingScript) {
      if (resolveWhenReady()) {
        return;
      }

      const handleLoad = () => {
        if (!resolveWhenReady()) {
          resolve(null);
        }
      };

      const handleError = () => resolve(null);

      existingScript.addEventListener('load', handleLoad, { once: true });
      existingScript.addEventListener('error', handleError, { once: true });

      return;
    }

    const script = document.createElement('script');
    script.id = 'google-recaptcha-api';
    script.src = `https://www.google.com/recaptcha/api.js?render=${encodeURIComponent(siteKey)}&hl=en`;
    script.async = true;
    script.defer = true;
    script.onload = () => {
      if (!resolveWhenReady()) {
        resolve(null);
      }
    };
    script.onerror = () => resolve(null);
    document.head.appendChild(script);
  });

  recaptchaLoader = loader.then((grecaptcha) => {
    if (!grecaptcha) {
      recaptchaLoader = null;
    }

    return grecaptcha;
  });

  return recaptchaLoader;
}

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
      Please make sure all details below are correct before submitting your registration. This information will be used for attendee verification and ticket issuance.
    </div>
    <div class="registration-review-swal__grid">
      ${items.map((item) => `
        <div class="registration-review-swal__item">
          <span class="registration-review-swal__label">${escapeHtml(item.label)}</span>
          <strong class="registration-review-swal__value">${escapeHtml(item.value || 'Not provided')}</strong>
        </div>
      `).join('')}
    </div>
    <p class="registration-review-swal__footnote">
      If anything is incorrect, choose "Review again" to return to the form and make changes.
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

        reject(new Error('Failed to load SweetAlert.'));
      }, { once: true });
      existingScript.addEventListener('error', () => reject(new Error('Failed to load SweetAlert.')), { once: true });
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

      reject(new Error('Failed to load SweetAlert.'));
    };
    script.onerror = () => reject(new Error('Failed to load SweetAlert.'));
    document.body.appendChild(script);
  }).finally(() => {
    sweetAlertLoader = null;
  });

  sweetAlertLoader = loader;

  return loader;
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

type LegalDialogType = 'terms' | 'privacy';

const LEGAL_DIALOG_CONTENT: Record<LegalDialogType, {
  title: string;
  description: string;
  paragraphs: string[];
}> = {
  terms: {
    title: 'Event Terms & Conditions',
    description: '',
    paragraphs: [
      `1. Ticket and Entry Requirements
Age Restriction: Minor under 13 Years old need to be accompanied by adult or guardian.
Valid QR code and Original ID cards or passports must be presented at the venue.
Name Matching: QR Ticket must be registered to the attendee's full name, matching their official ID.
Re-entry on the same day does not require QR scanning provided if valid UV stamp of the day is still visible.`,
      `2. Prohibited Items
To ensure safety, the following are generally prohibited:
Weapons, sharp objects, and fireworks.
Drugs and illegal substances.
External food and drinks.`,
      `3. Safety & Behavioral Guidelines
Water Fight Safety: Do not aim high-pressure water guns at faces or eyes.
Cultural Respect: Do not splash food vendor, crew on duty, the elderly, or young children.

Liability: Organizers are not responsible for lost or stolen personal property.`,
      `4. Event & Weather Policy
Rain or Shine: Events proceed regardless of weather unless conditions are deemed dangerous, in which case the organizer may amend the event.
Changes: Organizers reserve the right to change schedules, lineups, or terms without prior notice.`,
      `5. Media Rights
By entering the event, you consent to being photographed or recorded, with the content being used for promotional purposes`,
      `6. Security & Removal Clauses
Right of Refusal: The Organiser reserves the absolute right to refuse entry or remove any visitor from the venue who fails to comply with security screenings, displays unruly behavior, or poses a safety risk to others.
Prohibited Items: Visitors are prohibited from bringing weapons, illegal substances, or hazardous materials into the venue. All bags are subject to inspection upon entry.
CCTV Monitoring: For the safety of all attendees, 24-hour video surveillance is active. Footage is handled in accordance with our Privacy Policy and may be used as evidence in the event of an incident.`,
    ],
  },
  privacy: {
    title: 'Personal Data Protection Notice (PDPA)',
    description: '',
    paragraphs: [
      `In compliance with the Personal Data Protection Act 2010 (PDPA) of Malaysia, Songkran Festival 2026 / EQ Solutions ("the Organiser") is committed to protecting your personal data.`,
      `Collection of Data: We collect your name, NRIC/Passport number, contact details, and professional information to process your registration and verify your identity for security purposes.`,
      `Purpose: Your data will be used for event administration, security screening, health and safety monitoring, and (if consented) marketing updates.`,
      `Surveillance: Please be visually informed that Closed Circuit Television (CCTV) cameras are in operation throughout the venue for crime prevention and public safety. By entering, you consent to the recording of your image.`,
      `Disclosure: We may disclose your information to our authorized security vendors, venue providers, or law enforcement agencies as required by law.`,
      `Your Rights: You have the right to access, correct, or withdraw consent for your personal data. Please contact our Data Protection Officer at rs@rsgr.net for any inquiries.`,
      `I have read and agree to the Personal Data Protection Notice and the Event Terms & Conditions.`,
    ],
  },
};

export function RegisterPage() {
  const recaptchaEnabled = getMetaContent('recaptcha-enabled') === '1';
  const recaptchaSiteKey = getMetaContent('recaptcha-site-key');
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [isSuccess, setIsSuccess] = useState(false);
  const [isRecaptchaReady, setIsRecaptchaReady] = useState(!recaptchaEnabled);
  const [legalDialog, setLegalDialog] = useState<LegalDialogType | null>(null);
  const [registeredEmail, setRegisteredEmail] = useState('');
  const [registrationSuccessMessage, setRegistrationSuccessMessage] = useState('');
  const [registeredTicketUrl, setRegisteredTicketUrl] = useState('');
  const [registeredTicketQrUrl, setRegisteredTicketQrUrl] = useState('');
  const [registeredTicketCode, setRegisteredTicketCode] = useState('');
  const [registeredEntryCodeDisplay, setRegisteredEntryCodeDisplay] = useState('');
  const [registeredTicketPreview, setRegisteredTicketPreview] = useState<RegisteredTicketPreviewData>({
    fullName: '',
    identityNumber: '',
  });
  const [ticketEmailSent, setTicketEmailSent] = useState(true);
  const [ticketDeliveryStatus, setTicketDeliveryStatus] = useState<DeliveryStatus>('sent');
  const [trafficAttribution, setTrafficAttribution] = useState<TrafficAttributionPayload | null>(null);
  const addRippleRef = useRef<((x: number, y: number) => void) | null>(null);
  const previousCountryRef = useRef('');

  const {
    control,
    register,
    handleSubmit,
    setValue,
    setError,
    clearErrors,
    watch,
    reset,
    formState: { errors, dirtyFields },
  } = useForm<FormData>({
    mode: 'onTouched',
    defaultValues: {
      full_name: '',
      email: '',
      phone_country_code: PHONE_DIAL_CODES.MY,
      phone_national_number: '',
      country: '',
      identity_type: '',
      identity_number: '',
      recaptcha_token: '',
      agreeTerms: false,
    },
  });

  const syncRecaptchaToken = useCallback((token: string, shouldValidate = false) => {
    setValue('recaptcha_token', token, {
      shouldDirty: token !== '',
      shouldTouch: token !== '',
      shouldValidate,
    });

    if (token !== '') {
      clearErrors('recaptcha_token');
    }
  }, [clearErrors, setValue]);

  const clearRecaptchaToken = useCallback((shouldValidate = false) => {
    setValue('recaptcha_token', '', {
      shouldDirty: false,
      shouldTouch: false,
      shouldValidate,
    });
  }, [setValue]);

  const executeRecaptchaToken = useCallback(async () => {
    if (!recaptchaEnabled) {
      return '';
    }

    if (recaptchaSiteKey === '') {
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
      setError('recaptcha_token', {
        type: 'manual',
        message: 'Failed to load reCAPTCHA. Please try again.',
      });

      return null;
    }

    setIsRecaptchaReady(true);

    try {
      const token = (await grecaptcha.execute(recaptchaSiteKey, { action: 'register' })).trim();

      if (token === '') {
        setError('recaptcha_token', {
          type: 'manual',
          message: 'Failed to verify reCAPTCHA. Please try again.',
        });

        return null;
      }

      syncRecaptchaToken(token, false);

      return token;
    } catch {
      setError('recaptcha_token', {
        type: 'manual',
        message: 'Failed to verify reCAPTCHA. Please try again.',
      });

      return null;
    }
  }, [clearErrors, recaptchaEnabled, recaptchaSiteKey, setError, setIsRecaptchaReady, syncRecaptchaToken]);

  const handleCanvasReady = useCallback((fn: (x: number, y: number) => void) => {
    addRippleRef.current = fn;
  }, []);

  const handlePageClick = useCallback((e: React.MouseEvent) => {
    if (addRippleRef.current) {
      addRippleRef.current(e.clientX, e.clientY);
    }
  }, []);

  useEffect(() => {
    if (!recaptchaEnabled) {
      setIsRecaptchaReady(true);
      return;
    }

    if (recaptchaSiteKey === '') {
      setIsRecaptchaReady(false);
      return;
    }

    let isMounted = true;
    setIsRecaptchaReady(false);

    ensureRecaptcha(recaptchaSiteKey).then((grecaptcha) => {
      if (!isMounted) {
        return;
      }

      setIsRecaptchaReady(Boolean(grecaptcha));
    });

    return () => {
      isMounted = false;
    };
  }, [recaptchaEnabled, recaptchaSiteKey]);

  useEffect(() => {
    setTrafficAttribution(captureTrafficAttribution());
  }, []);

  const countryVal = watch('country');
  const phoneCountryCodeVal = watch('phone_country_code');
  const phoneNationalNumberVal = watch('phone_national_number');
  const identityTypeVal = watch('identity_type');
  const identityNumberVal = watch('identity_number');

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

    if (countryVal === 'MY') {
      const shouldClearIdentityNumber = identityTypeVal !== 'national_id' || (previousCountry !== '' && previousCountry !== 'MY');

      if (identityTypeVal !== 'national_id') {
        setValue('identity_type', 'national_id', {
          shouldDirty: true,
          shouldTouch: true,
          shouldValidate: true,
        });
      }

      if (shouldClearIdentityNumber && identityNumberVal !== '') {
        setValue('identity_number', '', {
          shouldDirty: true,
          shouldTouch: false,
          shouldValidate: false,
        });
      }

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
  }, [countryVal, identityNumberVal, identityTypeVal, setValue]);

  const handleResetForm = () => {
    if (recaptchaEnabled) {
      clearRecaptchaToken(false);
      clearErrors('recaptcha_token');
    }

    setIsSuccess(false);
    setRegisteredEmail('');
    setRegistrationSuccessMessage('');
    setRegisteredTicketUrl('');
    setRegisteredTicketQrUrl('');
    setRegisteredTicketCode('');
    setRegisteredEntryCodeDisplay('');
    setRegisteredTicketPreview({
      fullName: '',
      identityNumber: '',
    });
    setTicketEmailSent(true);
    setTicketDeliveryStatus('sent');
    reset(); // Clear form values
  };

  const onSubmit = async (data: FormData) => {
    try {
      const phoneNumber = buildPhoneNumber(data.phone_country_code, data.phone_national_number);
      const selectedCountry = SORTED_COUNTRIES.find(country => country.code === data.country)?.name ?? data.country;
      const selectedIdentityLabel = data.identity_type === 'national_id'
        ? data.country === 'MY'
          ? 'Malaysia IC (MyKad)'
          : 'National ID / Resident ID'
        : 'Passport';
      const selectedIdentityNumberLabel = data.country !== 'MY'
        ? 'Passport Number'
        : data.identity_type === 'national_id'
          ? 'Malaysia IC (MyKad) Number'
          : 'Passport Number';
      const Swal = await ensureSweetAlert();
      const confirmation = await Swal.fire({
        title: 'Review your details',
        buttonsStyling: false,
        showCloseButton: true,
        backdrop: 'rgba(12, 74, 110, 0.55)',
        html: buildRegistrationReviewHtml([
          { label: 'Full Name', value: data.full_name },
          { label: 'Email', value: data.email },
          { label: 'Phone Number', value: phoneNumber },
          { label: 'Nationality', value: selectedCountry },
          { label: 'Document Type', value: selectedIdentityLabel },
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
        confirmButtonText: 'Yes, everything is correct',
        cancelButtonText: 'Review again',
      });

      if (!confirmation.isConfirmed) {
        return;
      }

      setIsSubmitting(true);

      const recaptchaToken = await executeRecaptchaToken();
      if (recaptchaEnabled && (!recaptchaToken || recaptchaToken.trim() === '')) {
        return;
      }

      const csrfToken = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content;
      const resolvedTrafficAttribution = trafficAttribution ?? captureTrafficAttribution();
      const payload = {
        ...data,
        recaptcha_token: recaptchaToken ?? '',
        phone_country_code: normalizePhoneCountryCode(data.phone_country_code),
        phone_national_number: normalizePhoneNationalNumber(data.phone_national_number),
        phone_number: phoneNumber,
        ...(resolvedTrafficAttribution ?? {}),
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

      const result: RegisterResponse = await response.json();

      if (!response.ok) {
        if (result.errors) {
          Object.entries(result.errors as Record<string, string[]>).forEach(([key, messages]) => {
            const message = messages[0] ?? 'Invalid input.';

            if (isFormField(key)) {
              setError(key, { type: 'server', message });
            }

            toast.error(message);
          });

          if ((result.errors as Record<string, string[]>).recaptcha_token) {
            clearRecaptchaToken(false);
          }
        } else {
          throw new Error(result.message || 'Registration failed');
        }
        return;
      }

      const ticketUrl = (result.ticket_url || '').trim();
      const deliveryStatus: DeliveryStatus = result.delivery_status
        ?? (result.email_sent === false ? 'failed' : 'sent');
      const successMessage = result.message || (
        deliveryStatus === 'queued'
          ? 'Registration completed. Your ticket is ready and the email copy is being prepared now.'
          : result.email_sent === false
            ? 'Registration completed. Your ticket is ready, but the email could not be sent right now.'
            : 'Your QR ticket has been sent to your email.'
      );

      setRegisteredEmail(data.email);
      setRegistrationSuccessMessage(successMessage);
      setRegisteredTicketUrl(ticketUrl);
      setRegisteredTicketQrUrl(result.ticket_qr_url || '');
      setRegisteredTicketCode(result.ticket_code || '');
      setRegisteredEntryCodeDisplay(result.entry_code_display || '');
      setRegisteredTicketPreview({
        fullName: data.full_name,
        identityNumber: data.identity_number,
      });
      setTicketEmailSent(result.email_sent !== false);
      setTicketDeliveryStatus(deliveryStatus);
      setIsSuccess(true);

      if (!ENABLE_LEGACY_SUCCESS_SCREEN) {
        toast.success(successMessage);
      }
    } catch (error: any) {
      toast.error(error.message || 'Something went wrong while registering. Please try again.');
    } finally {
      if (recaptchaEnabled) {
        clearRecaptchaToken(false);
      }

      setIsSubmitting(false);
    }
  };

  const inputClass = (field: keyof FormData) =>
    authInputClass(Boolean(errors[field]));

  const phoneSelectClass = authSelectClass(Boolean(errors.phone_country_code));

  const phoneNumberInputClass = authInputClass(Boolean(errors.phone_national_number), {
    withIcon: false,
    extraClassName: 'px-4',
  });

  const countrySelectClass = authSelectClass(Boolean(errors.country));

  const selectedCountryOption = SORTED_COUNTRIES.find(c => c.code === countryVal);
  const isMalaysianRegistrant = countryVal === 'MY';
  const isForeignRegistrant = countryVal !== '' && countryVal !== 'MY';
  const hasDirectTicketUrl = registeredTicketUrl.trim() !== '';
  const hasTicketQrUrl = registeredTicketQrUrl.trim() !== '';
  const isTicketEmailQueued = ticketDeliveryStatus === 'queued';
  const isTicketEmailFailed = ticketDeliveryStatus === 'failed';
  const availableIdentityTypes = isMalaysianRegistrant
    ? IDENTITY_TYPES.filter(option => option.value === 'national_id')
    : IDENTITY_TYPES.filter(option => option.value === 'passport');
  const identityNumberLabel = isForeignRegistrant
    ? 'Passport Number'
    : identityTypeVal === 'national_id'
      ? isMalaysianRegistrant
        ? 'Malaysia IC (MyKad) Number'
        : 'National ID / Resident ID Number'
      : 'Passport Number';
  const identityNumberPlaceholder = isForeignRegistrant
    ? 'Example: A1234567'
    : identityTypeVal === 'national_id'
      ? isMalaysianRegistrant
        ? 'Example: 901231101234'
        : 'Enter your official ID number'
      : 'Example: A1234567';
  const identityHelperText = isForeignRegistrant
    ? 'For registrants outside Malaysia, use Passport Only as the primary document. Maximum 10 alphanumeric characters.'
    : identityTypeVal === 'national_id'
      ? isMalaysianRegistrant
        ? 'For Malaysian citizens or permanent residents, use your IC / MyKad number with digits only, maximum 12.'
        : 'Use an official and valid national ID or resident ID number.'
      : identityTypeVal === 'passport'
        ? 'Use a valid passport number that matches your travel document. Maximum 10 alphanumeric characters.'
        : isMalaysianRegistrant
          ? 'For Malaysia, document type is fixed to Malaysia IC (MyKad).'
          : 'Select the document you will use for registration.';
  const phoneCountryOption = PHONE_COUNTRY_CODES.find(option => option.dialCode === phoneCountryCodeVal);
  const activeLegalDialog = legalDialog ? LEGAL_DIALOG_CONTENT[legalDialog] : null;
  const phoneNationalNumberField = register('phone_national_number', {
    required: 'Phone number is required.',
    setValueAs: (value: string) => normalizePhoneNationalNumber(value),
    pattern: { value: /^\d{4,20}$/, message: 'Invalid phone number.' },
    onChange: (event) => {
      event.target.value = normalizePhoneNationalNumber(event.target.value);
    },
  });
  const recaptchaTokenField = register('recaptcha_token', {
    validate: () => {
      if (!recaptchaEnabled) {
        return true;
      }

      if (recaptchaSiteKey === '') {
        return 'reCAPTCHA is not configured. Please contact the administrator.';
      }

      return true;
    },
  });
  const identityNumberField = register('identity_number', {
    required: 'Document number is required.',
    setValueAs: (value: string) => normalizeIdentityNumber(value, identityTypeVal),
    validate: (value: string) => validateIdentityNumber(value, identityTypeVal),
    onChange: (event) => {
      event.target.value = normalizeIdentityNumber(event.target.value, identityTypeVal);
    },
  });

  return (
    <AuthPageShell
      skipHref="#main-form"
      skipLabel="Skip to registration form"
      onCanvasReady={handleCanvasReady}
      onPageClick={handlePageClick}
      backgroundImageUrl="/images/BACKGROUND.jpg"
      fixedTheme="light"
    >
      <Toaster position="top-center" richColors />

      <div className="relative flex flex-col lg:flex-row min-h-screen" style={{ zIndex: 2 }}>

        {/* LEFT PANEL
        <motion.aside
          initial={{ opacity: 0, x: -50 }}
          animate={{ opacity: 1, x: 0 }}
          transition={{ duration: 0.9, ease: [0.25, 0.46, 0.45, 0.94] as const }}
          className="hidden lg:flex lg:w-[44%] flex-col justify-center items-center px-10 py-12 text-white"
          aria-label="Songkran Festival Information"
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
              Countdown
            </p>
            <div className="grid grid-cols-4 gap-2" aria-label="Time remaining until the festival">
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
            aria-label="Festival Highlights"
          >
            {[
              { icon: Music2, text: 'Get exclusive access to the artist lineup', iconColor: 'text-sky-300', iconBg: 'bg-sky-500/20' },
              { icon: Waves, text: 'Be part of Southeast Asia’s biggest water celebration', iconColor: 'text-cyan-300', iconBg: 'bg-cyan-500/20' },
              { icon: Star, text: 'Enjoy Malaysia’s most authentic and spectacular cultural experience', iconColor: 'text-yellow-200', iconBg: 'bg-yellow-500/20' },
              { icon: Droplets, text: 'Begin a blessed new chapter with Songkran’s cleansing water ritual', iconColor: 'text-blue-300', iconBg: 'bg-blue-500/20' },
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
        */}

        {/* RIGHT PANEL – FORM */}
        <motion.main
          id="main-form"
          initial={{ opacity: 0, x: 50 }}
          animate={{ opacity: 1, x: 0 }}
          transition={{ duration: 0.9, ease: [0.25, 0.46, 0.45, 0.94] }}
          className="flex-1 flex flex-col items-center justify-center px-4 py-10 lg:px-8"
          tabIndex={-1}
        >
          {/* Mobile header
          <div className="lg:hidden text-center mb-6 text-white">
            <div className="w-20 h-20 mx-auto text-sky-200 mb-3">
              <LotusIcon className="w-full h-full" />
            </div>
            <h1 className="text-4xl font-black tracking-tight" style={{ fontFamily: '"Kanit", sans-serif' }}>
              SONGKRAN
            </h1>
            <p className="text-sky-200 text-sm tracking-[0.3em] mt-1">MUSIC FESTIVAL 2026</p>
          </div>
          */}

          {/* Form card */}
          <AuthCardFrame className="relative w-full max-w-[520px]">

            {/* Back Button */}
            <a
              href={PUBLIC_HOME_URL}
              className="absolute top-4 right-4 z-10 flex items-center gap-1.5 rounded-full bg-white/90 px-3 py-1.5 text-[11px] font-semibold text-sky-700 shadow-md backdrop-blur hover:bg-white hover:text-sky-900 transition-all"
            >
              ← Home
            </a>
            <AuthCardHeader
              eyebrow="Free Registration"
              title="Register Now"
              description="Join thousands of attendees at Songkran Festival 2026."
              note="Complete all details, accept the terms, and submit your registration."
            />

            {/* Form body */}
            <div className="bg-white">
              <form
                onSubmit={handleSubmit(onSubmit)}
                noValidate
                aria-label="Songkran Festival Music Free Registration Form"
              >
                <div className="px-7 py-6 relative overflow-hidden" style={{ minHeight: 360 }}>
                  <motion.div
                    initial={{ opacity: 0, y: 8 }}
                    animate={{ opacity: 1, y: 0 }}
                    transition={{ duration: 0.25 }}
                    className="space-y-5"
                  >
                    <div>
                      <label htmlFor="full_name" className="block text-slate-700 text-sm font-semibold mb-1.5">
                        Full Name <span className="text-red-500" aria-hidden="true">*</span>
                      </label>
                      <div className="relative">
                        <User className="absolute left-3.5 top-1/2 -translate-y-1/2 text-sky-400 pointer-events-none" aria-hidden="true" style={{ width: 18, height: 18 }} />
                        <input
                          id="full_name"
                          type="text"
                          autoComplete="name"
                          placeholder="Enter your full name"
                          className={inputClass('full_name')}
                          {...register('full_name', {
                            required: 'Full name is required.',
                            minLength: { value: 2, message: 'Name must be at least 2 characters.' },
                            pattern: { value: /^[a-zA-Z\s.''-]+$/, message: 'Name may only contain letters and spaces.' },
                          })}
                        />
                      </div>
                      <AnimatePresence>
                        <AuthInlineError id="err-full_name" message={errors.full_name?.message} />
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
                          placeholder="Your email"
                          className={inputClass('email')}
                          {...register('email', {
                            required: 'Email is required.',
                            pattern: { value: /^[^\s@]+@[^\s@]+\.[^\s@]+$/, message: 'Invalid email format.' },
                          })}
                        />
                      </div>
                      <AnimatePresence>
                        <AuthInlineError id="err-email" message={errors.email?.message} />
                      </AnimatePresence>
                    </div>

                    <div>
                      <label htmlFor="phone_country_code" className="block text-slate-700 text-sm font-semibold mb-1.5">
                        Phone Number <span className="text-red-500" aria-hidden="true">*</span>
                      </label>
                      <div className="grid grid-cols-1 gap-3 sm:grid-cols-[180px_minmax(0,1fr)]">
                        <Controller
                          control={control}
                          name="phone_country_code"
                          rules={{
                            required: 'Country code is required.',
                            validate: (value) => /^\+\d{1,4}$/.test(normalizePhoneCountryCode(value)) || 'Invalid country code.',
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
                                  <SelectValue placeholder="Code" />
                                )}
                              </SelectTrigger>
                              <SelectContent className="rounded-xl border-sky-100">
                                {PRIORITY_PHONE_COUNTRY_CODES.map(option => (
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
                                {OTHER_PHONE_COUNTRY_CODES.length > 0 && (
                                  <SelectSeparator className="my-1 bg-sky-100" />
                                )}
                                {OTHER_PHONE_COUNTRY_CODES.map(option => (
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
                          placeholder="123456789"
                          inputMode="numeric"
                          pattern="[0-9]*"
                          maxLength={20}
                          className={phoneNumberInputClass}
                          {...phoneNationalNumberField}
                        />
                      </div>
                      <p className="mt-1.5 text-xs leading-relaxed text-slate-500">
                        Choose country code then enter the number.
                      </p>
                      <AnimatePresence>
                        <AuthInlineError id="err-phone_country_code" message={errors.phone_country_code?.message} />
                      </AnimatePresence>
                      <AnimatePresence>
                        <AuthInlineError id="err-phone_national_number" message={errors.phone_national_number?.message} />
                      </AnimatePresence>
                    </div>

                    <div>
                      <label htmlFor="country" className="block text-slate-700 text-sm font-semibold mb-1.5">
                        Default Nationality <span className="text-red-500" aria-hidden="true">*</span>
                      </label>
                      <Controller
                        control={control}
                        name="country"
                        rules={{ required: 'Nationality is required.' }}
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
                                  <SelectValue placeholder="Select your nationality" />
                                </span>
                              )}
                            </SelectTrigger>
                            <SelectContent className="rounded-xl border-sky-100">
                              {PRIORITY_SORTED_COUNTRIES.map(c => (
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
                              {OTHER_SORTED_COUNTRIES.length > 0 && (
                                <SelectSeparator className="my-1 bg-sky-100" />
                              )}
                              {OTHER_SORTED_COUNTRIES.map(c => (
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
                        <AuthInlineError id="err-country" message={errors.country?.message} />
                      </AnimatePresence>
                    </div>

                    <div>
                      <label htmlFor="identity_type" className="block text-slate-700 text-sm font-semibold mb-1.5">
                        Document Type <span className="text-red-500" aria-hidden="true">*</span>
                      </label>
                      <div className="relative">
                        <IdCard className="absolute left-3.5 top-1/2 -translate-y-1/2 text-sky-400 pointer-events-none" aria-hidden="true" style={{ width: 18, height: 18 }} />
                        <select
                          id="identity_type"
                          className={`${inputClass('identity_type')} appearance-none cursor-pointer`}
                          {...register('identity_type', { required: 'Document type is required.' })}
                        >
                          <option value="">Select a document type</option>
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
                        <AuthInlineError id="err-identity_type" message={errors.identity_type?.message} />
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
                          inputMode={identityTypeVal === 'national_id' ? 'numeric' : 'text'}
                          maxLength={identityTypeVal === 'national_id' ? 12 : 10}
                          spellCheck={false}
                          {...identityNumberField}
                        />
                      </div>
                      <AnimatePresence>
                        <AuthInlineError id="err-identity_number" message={errors.identity_number?.message} />
                      </AnimatePresence>
                    </div>

                    <div className="flex items-start gap-3">
                      <input
                        id="agreeTerms"
                        type="checkbox"
                        className="mt-0.5 rounded border-2 border-sky-300 text-sky-600 cursor-pointer focus:ring-2 focus:ring-sky-500 focus:ring-offset-1 flex-shrink-0"
                        style={{ width: 18, height: 18 }}
                        {...register('agreeTerms', {
                          required: 'Please accept the Personal Data Protection Notice and Event Terms & Conditions to continue.',
                        })}
                      />
                      <div className="text-slate-600 text-xs leading-relaxed">
                        <label htmlFor="agreeTerms" className="cursor-pointer">
                          I  agree to the
                        </label>{' '}
                        <button
                          type="button"
                          onClick={() => setLegalDialog('terms')}
                          className="font-semibold text-sky-600 underline underline-offset-2 transition-colors hover:text-sky-700 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2 focus:ring-offset-white rounded-sm"
                        >
                          Terms  &amp; Conditions
                        </button>{' '}
                        <label htmlFor="agreeTerms" className="cursor-pointer">
                          and
                        </label>{' '}
                        <button
                          type="button"
                          onClick={() => setLegalDialog('privacy')}
                          className="font-semibold text-sky-600 underline underline-offset-2 transition-colors hover:text-sky-700 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2 focus:ring-offset-white rounded-sm"
                        >
                          Privacy Policy
                        </button>
                        .
                      </div>
                    </div>
                    <AnimatePresence>
                      <AuthInlineError id="err-terms" message={errors.agreeTerms?.message} />
                    </AnimatePresence>

                    <input type="hidden" {...recaptchaTokenField} />
                    {recaptchaEnabled && (
                      <div className="rounded-2xl border border-sky-100 bg-white/90 p-4 shadow-sm">
                        <div className="mb-3 flex items-start gap-2">
                          <Lock className="mt-0.5 h-4 w-4 text-sky-500" aria-hidden="true" />
                          <div>
                            <p className="text-sm font-semibold text-slate-800">Background protection</p>
                            <p className="text-xs leading-relaxed text-slate-500">

                            </p>
                          </div>
                        </div>

                        {recaptchaSiteKey === '' ? (
                          <div className="rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-600">
                            reCAPTCHA is not configured. Please contact the administrator to provide the site key.
                          </div>
                        ) : (
                          <div
                            className={`flex items-center gap-2 rounded-xl border px-3 py-2 text-xs ${isRecaptchaReady
                              ? 'border-emerald-200 bg-emerald-50 text-emerald-700'
                              : 'border-sky-200 bg-sky-50 text-slate-600'
                              }`}
                          >
                            {isRecaptchaReady ? (
                              <CheckCircle2 className="h-4 w-4 text-emerald-600" aria-hidden="true" />
                            ) : (
                              <Loader2 className="h-4 w-4 animate-spin text-sky-500" aria-hidden="true" />
                            )}
                            <span>
                              {isRecaptchaReady
                                ? 'You are not a robot. No checkbox is required.'
                                : 'Preparing Google reCAPTCHA v3 background protection...'}
                            </span>
                          </div>
                        )}
                      </div>
                    )}
                    <AnimatePresence>
                      <AuthInlineError id="err-recaptcha" message={errors.recaptcha_token?.message} />
                    </AnimatePresence>
                  </motion.div>
                </div>

                {/* Navigation footer */}
                <div className="px-7 py-4 bg-slate-50 border-t border-slate-100 flex justify-end">
                  <motion.button
                    type="submit"
                    disabled={isSubmitting}
                    whileHover={{ scale: isSubmitting ? 1 : 1.02 }}
                    whileTap={{ scale: isSubmitting ? 1 : 0.97 }}
                    className="flex items-center gap-2 px-6 py-2.5 rounded-xl text-white text-sm font-semibold focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-1 transition-all disabled:opacity-70 disabled:cursor-not-allowed"
                    style={{ background: 'linear-gradient(135deg, #0284C7, #0EA5E9)', boxShadow: '0 4px 14px rgba(2,132,199,0.35)' }}
                    aria-busy={isSubmitting}
                    aria-disabled={isSubmitting}
                  >
                    {isSubmitting ? (
                      <>
                        <Loader2 className="w-4 h-4 animate-spin" aria-hidden="true" />
                        <span>Registering...</span>
                      </>
                    ) : (
                      <>
                        <span>Submit</span>

                      </>
                    )}
                  </motion.button>
                </div>
              </form>
            </div>
          </AuthCardFrame>

          {/* Login link */}
          {/* <p className="text-center mt-5 text-sky-200 text-sm">
            Already have an account?{' '}
            <Link
              to="/"
              className="text-white underline font-semibold hover:text-sky-100 focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-1 focus:ring-offset-transparent rounded"
            >
              Sign in here
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
                  <p key={`${legalDialog}-${index}`} className="whitespace-pre-wrap">{paragraph}</p>
                ))}
              </div>
            </div>

            <DialogFooter className="border-t border-slate-100 px-6 py-4">
              <DialogClose asChild>
                <Button type="button" variant="outline">
                  Close
                </Button>
              </DialogClose>
            </DialogFooter>
          </DialogContent>
        ) : null}
      </Dialog>

      {/* Success overlay kept in place for post-submit confirmation and future fallback needs. */}
      <AnimatePresence>
        {ENABLE_LEGACY_SUCCESS_SCREEN && isSuccess && (
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
              className={`relative w-full ${hasDirectTicketUrl ? 'max-w-[760px]' : 'max-w-lg'} max-h-[92vh] overflow-y-auto rounded-[2.5rem] shadow-2xl scrollbar-hide text-center ${hasDirectTicketUrl ? 'border-0 bg-transparent p-0' : 'border border-white/20 bg-white/10 p-6 md:p-10 backdrop-blur-md'}`}
            >
              {/* CLOSE BUTTON */}
              <button
                onClick={handleResetForm}
                className="absolute top-5 right-5 z-10 rounded-full border border-slate-200/90 bg-white p-2 text-slate-900 shadow-lg transition-colors hover:bg-slate-100 hover:text-slate-950 focus:outline-none focus-visible:ring-2 focus-visible:ring-sky-300 focus-visible:ring-offset-2 focus-visible:ring-offset-sky-950"
                aria-label="Close"
              >
                <X className="w-6 h-6" />
              </button>

              <h2 id="success-title" className="sr-only">
                Registered ticket preview
              </h2>

              {hasDirectTicketUrl ? (
                <div className="space-y-5">
                  <div className="rounded-[1.75rem] border border-white/20 bg-white/10 px-6 py-5 text-center shadow-[0_18px_55px_rgba(12,74,110,0.2)] backdrop-blur-md">
                    <p className="text-white text-xl md:text-2xl font-black leading-tight tracking-tight" style={{ fontFamily: '"Kanit", sans-serif' }}>
                      Your Ticket Is Ready
                    </p>
                    <p className="mt-2 text-sm md:text-base leading-relaxed text-sky-100">
                      {isTicketEmailQueued
                        ? 'This ticket is being emailed to'
                        : ticketEmailSent
                          ? 'This ticket has also been sent to your email'
                          : 'This ticket could not be emailed right now for'}
                      <span className="font-bold text-white"> {registeredEmail || 'your email'}</span>.
                      {isTicketEmailQueued
                        ? ' The email copy is being prepared now and should arrive shortly.'
                        : ticketEmailSent
                          ? ' Please check your inbox for a copy.'
                          : ' The email copy could not be sent right now, so please use this browser ticket.'}
                    </p>
                    <p className="mt-3 text-xs md:text-sm leading-relaxed text-sky-200/90">
                      {registrationSuccessMessage || (
                        isTicketEmailQueued
                          ? 'Your browser ticket is ready below while the email copy is being prepared in the background.'
                          : ticketEmailSent
                            ? 'Open the email to find your active festival pass and QR code, or use the browser ticket below anytime.'
                            : 'Your ticket is ready below. Use the browser ticket for event entry.'
                      )}
                    </p>
                  </div>

                  <TicketPreviewCard
                    fullName={registeredTicketPreview.fullName}
                    identityNumber={registeredTicketPreview.identityNumber}
                    qrUrl={registeredTicketQrUrl}
                    ticketCode={registeredTicketCode}
                    entryCodeDisplay={registeredEntryCodeDisplay}
                    ticketUrl={registeredTicketUrl}
                  />

                  <motion.button
                    whileHover={{ scale: 1.01 }}
                    whileTap={{ scale: 0.99 }}
                    onClick={handleResetForm}
                    className="w-full rounded-2xl bg-gradient-to-r from-sky-400 to-cyan-400 px-5 py-4 text-sm font-black uppercase tracking-widest text-sky-950 shadow-lg transition-all hover:shadow-[0_0_20px_rgba(56,189,248,0.4)] md:text-base"
                    style={{ fontFamily: '"Kanit", sans-serif' }}
                  >
                    Register Another Attendee
                  </motion.button>
                </div>
              ) : (
                <>
                  {/* ICON */}
                  <motion.div
                    animate={{ y: [0, -6, 0], scale: [1, 1.02, 1] }}
                    transition={{ duration: 4, repeat: Infinity, ease: "easeInOut" }}
                    className="mx-auto mb-6 flex flex-col items-center gap-4"
                  >
                    <div className="flex items-center justify-center rounded-[2rem] border border-white/15 bg-white/10 px-6 py-4 shadow-[0_18px_55px_rgba(12,74,110,0.28)] backdrop-blur-sm">
                      <img
                        src={SONGKRAN_LOGO_URL}
                        alt="Songkran Festival 2026 logo"
                        className="h-16 w-auto md:h-24"
                      />
                    </div>

                    {hasTicketQrUrl && (
                      <div className="rounded-[2rem] border border-white/15 bg-white p-3 shadow-[0_20px_60px_rgba(12,74,110,0.32)]">
                        <img
                          src={registeredTicketQrUrl}
                          alt="Registered participant QR code"
                          className="h-40 w-40 rounded-2xl object-contain md:h-52 md:w-52"
                        />
                      </div>
                    )}
                  </motion.div>

                  <div className="space-y-6">
                    <div>
                      <CheckCircle2 className="w-10 h-10 md:w-14 md:h-14 text-emerald-400 mx-auto mb-4" />
                      <h2 id="success-title" className="text-white text-2xl md:text-4xl font-black leading-tight tracking-tight mb-3" style={{ fontFamily: '"Kanit", sans-serif' }}>
                        Registration Successful!<br className="hidden sm:block" /> {isTicketEmailQueued ? 'Ticket Email Is On The Way' : ticketEmailSent ? 'Check Your Ticket Email' : 'Your Ticket Is Ready'}
                      </h2>
                      <p className="hidden text-sky-200 text-base md:text-lg mb-1">
                        We’ve sent a verification link to
                      </p>
                      <p className="text-sky-200 text-base md:text-lg mb-1">
                        {isTicketEmailQueued
                          ? 'Your QR ticket is ready and the email copy is being prepared for'
                          : ticketEmailSent
                            ? 'Your QR ticket has been sent to'
                            : 'We could not send the ticket email right now for'}
                      </p>
                      <p className="text-sky-100 font-bold text-xl md:text-2xl" style={{ fontFamily: '"Kanit", sans-serif' }}>
                        {registeredEmail || 'your email'}
                      </p>
                    </div>

                    <p className="text-sky-300 text-xs md:text-sm leading-relaxed max-w-sm mx-auto opacity-90">
                      {registrationSuccessMessage || (
                        isTicketEmailQueued
                          ? 'Open the browser ticket below right now while the email copy is still being prepared.'
                          : ticketEmailSent
                            ? hasDirectTicketUrl
                              ? 'Open the email to find your active festival pass and QR code, or use the ticket button below anytime.'
                              : 'Open the email to find your active festival pass, QR code, and direct ticket link for event entry.'
                            : 'Use the direct ticket link below to open your active festival pass immediately.'
                      )}
                    </p>

                    {registeredTicketCode && (
                      <div className="rounded-2xl border border-white/15 bg-white/10 px-5 py-4 backdrop-blur-sm">
                        <p className="text-base font-black text-white md:text-lg" style={{ fontFamily: '"Kanit", sans-serif' }}>
                          Valid from: 9-19 April 2026
                        </p>
                      </div>
                    )}

                    {hasDirectTicketUrl && (
                      <motion.a
                        whileHover={{ scale: 1.02 }}
                        whileTap={{ scale: 0.98 }}
                        href={registeredTicketUrl}
                        target="_blank"
                        rel="noreferrer"
                        className="block w-full py-4 rounded-2xl bg-white text-sky-900 font-black text-sm md:text-base uppercase tracking-widest hover:bg-sky-50 transition-all shadow-lg"
                        style={{ fontFamily: '"Kanit", sans-serif' }}
                      >
                        {isTicketEmailFailed ? 'Open My Ticket Now' : 'Open Ticket in Browser'}
                      </motion.a>
                    )}

                    <div className="hidden flex-wrap gap-2 justify-center">
                      {[
                        { text: '📅 9–19 April 2026', color: 'from-sky-500/20 to-sky-400/10' },
                        { text: '📍 Malaysia', color: 'from-cyan-500/20 to-cyan-400/10' },
                        { text: '🎵 50+ Artists', color: 'from-indigo-500/20 to-indigo-400/10' }
                      ].map((item, idx) => (
                        <span key={idx} className={`bg-gradient-to-br ${item.color} border border-white/10 text-sky-100 text-[10px] md:text-xs px-4 py-2 rounded-full font-semibold tracking-wide backdrop-blur-sm`}>
                          {item.text}
                        </span>
                      ))}
                    </div>
                    <div className="flex flex-wrap gap-2 justify-center">
                      {[
                        { text: '9-19 April 2026', color: 'from-sky-500/20 to-sky-400/10' },
                        { text: 'Malaysia', color: 'from-cyan-500/20 to-cyan-400/10' },
                        { text: '50+ Artists', color: 'from-indigo-500/20 to-indigo-400/10' }
                      ].map((item, idx) => (
                        <span key={`clean-${idx}`} className={`bg-gradient-to-br ${item.color} border border-white/10 text-sky-100 text-[10px] md:text-xs px-4 py-2 rounded-full font-semibold tracking-wide backdrop-blur-sm`}>
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
                      Register Another Attendee
                    </motion.button>
                  </div>
                </>
              )}
            </motion.div>
          </motion.div>
        )}
      </AnimatePresence>
    </AuthPageShell>
  );
}
