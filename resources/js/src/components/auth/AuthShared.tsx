import { useEffect, type MouseEventHandler, type ReactNode } from 'react';
import { AlertCircle } from 'lucide-react';
import { motion } from 'motion/react';

import { cn } from '../../lib/utils';
import { WaterAnimation } from './WaterAnimation';

type AuthPageShellProps = {
  children: ReactNode;
  skipHref?: string;
  skipLabel?: string;
  onPageClick?: MouseEventHandler<HTMLDivElement>;
  onCanvasReady?: (addRipple: (x: number, y: number) => void) => void;
  contentClassName?: string;
  backgroundImageUrl?: string;
  fixedTheme?: 'light' | 'dark';
};

type AuthCardFrameProps = {
  children: ReactNode;
  className?: string;
};

type AuthCardHeaderProps = {
  title: ReactNode;
  description?: ReactNode;
  note?: ReactNode;
  eyebrow?: ReactNode;
  topSlot?: ReactNode;
  className?: string;
};

type AuthInlineErrorProps = {
  message?: string;
  id?: string;
  className?: string;
};

type AuthSectionHeadingProps = {
  eyebrow?: ReactNode;
  title: ReactNode;
  description?: ReactNode;
  className?: string;
};

type AuthCodeBadgeProps = {
  code?: string | null;
  label?: string;
  className?: string;
};

const AUTH_INPUT_BASE =
  'w-full rounded-xl border-2 bg-white px-4 text-base text-slate-800 placeholder-slate-400 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-1';

export function LotusIcon({ className }: { className?: string }) {
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

export function AuthPageShell({
  children,
  skipHref,
  skipLabel,
  onPageClick,
  onCanvasReady,
  contentClassName,
  backgroundImageUrl,
  fixedTheme,
}: AuthPageShellProps) {
  const enforcedColorScheme = fixedTheme === 'light' ? 'only light' : fixedTheme;
  const pageBackgroundStyle = backgroundImageUrl
    ? {
        backgroundImage: `url(${backgroundImageUrl})`,
        backgroundSize: 'cover',
        backgroundPosition: 'center',
        backgroundRepeat: 'no-repeat',
        backgroundColor: '#0369A1',
      }
    : {
        background: 'linear-gradient(145deg, #0C4A6E 0%, #0369A1 30%, #0284C7 60%, #0EA5E9 100%)',
      };

  useEffect(() => {
    if (!fixedTheme) {
      return undefined;
    }

    const html = document.documentElement;
    const body = document.body;
    const appRoot = document.getElementById('root');
    const previous = {
      htmlColorScheme: html.style.colorScheme,
      bodyColorScheme: body.style.colorScheme,
      appRootColorScheme: appRoot?.style.colorScheme ?? '',
      htmlHadDark: html.classList.contains('dark'),
      bodyHadDark: body.classList.contains('dark'),
      appRootHadDark: appRoot?.classList.contains('dark') ?? false,
    };

    const syncMetaTag = (name: string, content: string) => {
      let meta = document.querySelector(`meta[name="${name}"]`) as HTMLMetaElement | null;
      const existed = Boolean(meta);
      const previousContent = meta?.content ?? '';

      if (!meta) {
        meta = document.createElement('meta');
        meta.name = name;
        document.head.appendChild(meta);
      }

      meta.content = content;

      return () => {
        if (!meta) {
          return;
        }

        if (existed) {
          meta.content = previousContent;
          return;
        }

        meta.remove();
      };
    };

    const themeColor = fixedTheme === 'light' ? '#E0F7FF' : '#0F172A';
    const restoreColorSchemeMeta = syncMetaTag('color-scheme', fixedTheme);
    const restoreSupportedColorSchemesMeta = syncMetaTag('supported-color-schemes', fixedTheme);
    const restoreThemeColorMeta = syncMetaTag('theme-color', themeColor);

    html.classList.remove('dark');
    body.classList.remove('dark');
    appRoot?.classList.remove('dark');

    html.style.colorScheme = enforcedColorScheme;
    body.style.colorScheme = enforcedColorScheme;

    if (appRoot) {
      appRoot.style.colorScheme = enforcedColorScheme;
    }

    return () => {
      html.style.colorScheme = previous.htmlColorScheme;
      body.style.colorScheme = previous.bodyColorScheme;

      if (appRoot) {
        appRoot.style.colorScheme = previous.appRootColorScheme;
      }

      if (previous.htmlHadDark) {
        html.classList.add('dark');
      }
      if (previous.bodyHadDark) {
        body.classList.add('dark');
      }
      if (appRoot && previous.appRootHadDark) {
        appRoot.classList.add('dark');
      }

      restoreColorSchemeMeta();
      restoreSupportedColorSchemesMeta();
      restoreThemeColorMeta();
    };
  }, [enforcedColorScheme, fixedTheme]);

  return (
    <div
      className="relative min-h-screen overflow-x-hidden"
      style={{ ...pageBackgroundStyle, colorScheme: enforcedColorScheme }}
      onClick={onPageClick}
    >
      {skipHref && skipLabel ? (
        <a
          href={skipHref}
          className="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-50 focus:rounded-lg focus:bg-white focus:px-4 focus:py-2 focus:font-semibold focus:text-sky-800 focus:shadow-lg"
        >
          {skipLabel}
        </a>
      ) : null}

      <WaterAnimation onCanvasReady={onCanvasReady} />

      <div className="pointer-events-none fixed inset-0 overflow-hidden" aria-hidden="true" style={{ zIndex: 1 }}>
        <div
          className="absolute -left-32 -top-32 h-[500px] w-[500px] rounded-full opacity-10"
          style={{ background: 'radial-gradient(circle, #BAE6FD, transparent)' }}
        />
        <div
          className="absolute -bottom-40 -right-40 h-[600px] w-[600px] rounded-full opacity-10"
          style={{ background: 'radial-gradient(circle, #E0F2FE, transparent)' }}
        />
        <div className="absolute left-4 top-4 h-56 w-56 text-sky-200 opacity-[0.08]">
          <LotusIcon className="h-full w-full" />
        </div>
        <div className="absolute bottom-4 right-4 h-44 w-44 rotate-180 text-sky-200 opacity-[0.08]">
          <LotusIcon className="h-full w-full" />
        </div>

        {[0, 1, 2, 3, 4, 5].map((index) => (
          <motion.div
            key={index}
            className="absolute h-2 w-2 rounded-full bg-sky-300/30"
            style={{
              left: `${15 + index * 14}%`,
              top: `${18 + (index % 3) * 24}%`,
            }}
            animate={{
              y: [0, -20, 0],
              opacity: [0.2, 0.5, 0.2],
              scale: [1, 1.3, 1],
            }}
            transition={{
              duration: 3 + index * 0.5,
              repeat: Infinity,
              ease: 'easeInOut',
              delay: index * 0.4,
            }}
          />
        ))}

        <svg className="absolute bottom-0 left-0 w-full" viewBox="0 0 1440 100" preserveAspectRatio="none">
          <motion.path
            d="M0,50 C360,100 720,0 1080,50 C1260,75 1350,25 1440,50 L1440,100 L0,100 Z"
            fill="rgba(255,255,255,0.04)"
            animate={{
              y: [0, -6, 0],
              opacity: [1, 0.8, 1],
            }}
            transition={{ duration: 7, repeat: Infinity, ease: 'easeInOut' }}
          />
        </svg>
      </div>

      <div className={cn('relative min-h-screen', contentClassName)} style={{ zIndex: 2 }}>
        {children}
      </div>
    </div>
  );
}

export function AuthCardFrame({ children, className }: AuthCardFrameProps) {
  return (
    <div
      className={cn(
        'overflow-hidden rounded-[2rem] border border-white/20 bg-white/95 shadow-[0_30px_80px_rgba(0,0,0,0.35),0_0_0_1px_rgba(255,255,255,0.08)] backdrop-blur-sm',
        className,
      )}
    >
      {children}
    </div>
  );
}

export function AuthCardHeader({
  title,
  description,
  note,
  eyebrow,
  topSlot,
  className,
}: AuthCardHeaderProps) {
  return (
    <div
      className={cn(
        'relative overflow-hidden bg-gradient-to-br from-sky-800 via-sky-600 to-cyan-400 px-7 pb-6 pt-7 text-white',
        className,
      )}
    >
      <div className="absolute -right-10 -top-10 h-40 w-40 rounded-full border border-white/10" aria-hidden="true" />
      <div className="absolute -right-6 -top-6 h-28 w-28 rounded-full border border-white/10" aria-hidden="true" />
      <div className="absolute -bottom-10 -left-10 h-32 w-32 rounded-full border border-white/10" aria-hidden="true" />

      <div className="relative">
        {topSlot ? <div className="mb-5">{topSlot}</div> : null}
        {eyebrow ? (
          <p className="text-[11px] font-bold uppercase tracking-[0.28em] text-sky-50/90">{eyebrow}</p>
        ) : null}
        <h1 className="mt-2 text-3xl font-black tracking-tight" style={{ fontFamily: '"Kanit", sans-serif' }}>
          {title}
        </h1>
        {description ? <p className="mt-2 text-sm leading-relaxed text-sky-100">{description}</p> : null}
        {note ? <p className="mt-4 text-xs leading-relaxed text-sky-50/90">{note}</p> : null}
      </div>
    </div>
  );
}

export function AuthInlineError({ message, id, className }: AuthInlineErrorProps) {
  if (!message) {
    return null;
  }

  return (
    <motion.p
      id={id}
      role="alert"
      initial={{ opacity: 0, y: -4 }}
      animate={{ opacity: 1, y: 0 }}
      exit={{ opacity: 0, y: -4 }}
      transition={{ duration: 0.2 }}
      className={cn('mt-1.5 flex items-center gap-1 text-xs font-medium text-red-600', className)}
    >
      <AlertCircle className="h-3.5 w-3.5 flex-shrink-0" aria-hidden="true" />
      {message}
    </motion.p>
  );
}

export function AuthSectionHeading({ eyebrow, title, description, className }: AuthSectionHeadingProps) {
  return (
    <div className={cn('space-y-2', className)}>
      {eyebrow ? (
        <p className="text-[11px] font-bold uppercase tracking-[0.28em] text-sky-600">{eyebrow}</p>
      ) : null}
      <h2 className="text-2xl font-black tracking-tight text-slate-950" style={{ fontFamily: '"Kanit", sans-serif' }}>
        {title}
      </h2>
      {description ? <p className="text-sm leading-relaxed text-slate-600">{description}</p> : null}
    </div>
  );
}

export function AuthCodeBadge({ code, label = 'Entry Code', className }: AuthCodeBadgeProps) {
  if (!code) {
    return null;
  }

  return (
    <div
      className={cn(
        'inline-flex min-w-[180px] flex-col items-center gap-2 rounded-[1.4rem] border border-cyan-100 bg-gradient-to-br from-white via-sky-50 to-cyan-50 px-4 py-3 text-center shadow-[0_12px_28px_rgba(14,165,233,0.12)]',
        className,
      )}
    >
      <span className="text-[10px] font-bold uppercase tracking-[0.3em] text-cyan-700/80">{label}</span>
      <span className="text-base font-black tracking-[0.32em] text-sky-950">{code}</span>
    </div>
  );
}

export function authInputClass(
  hasError: boolean,
  options?: {
    withIcon?: boolean;
    minHeightClassName?: string;
    extraClassName?: string;
  },
) {
  return cn(
    AUTH_INPUT_BASE,
    options?.withIcon === false ? null : 'pl-11',
    options?.minHeightClassName ?? 'min-h-[50px] py-3',
    hasError
      ? 'border-red-400 focus:border-red-500 focus:ring-red-300'
      : 'border-sky-200 focus:border-sky-500 focus:ring-sky-300 hover:border-sky-300',
    options?.extraClassName,
  );
}

export function authSelectClass(hasError: boolean, extraClassName?: string) {
  return cn(
    'min-h-[50px] rounded-xl border-2 bg-white px-4 text-base font-medium text-slate-800 data-[size=default]:h-[50px]',
    hasError
      ? 'border-red-400 focus:border-red-500 focus:ring-red-300'
      : 'border-sky-200 focus:border-sky-500 focus:ring-sky-300 hover:border-sky-300',
    extraClassName,
  );
}

export const authLockedFieldClass =
  'flex min-h-[50px] items-center justify-between rounded-xl border-2 border-sky-100 bg-slate-50 px-4 text-base font-medium text-slate-700 shadow-sm';

export function authPrimaryButtonClass(extraClassName?: string) {
  return cn(
    'flex items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-sky-600 to-cyan-500 px-6 py-3 text-sm font-black uppercase tracking-[0.12em] text-white shadow-[0_16px_40px_rgba(14,165,233,0.28)] transition-all hover:from-sky-500 hover:to-cyan-400 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-70',
    extraClassName,
  );
}
