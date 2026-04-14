import { useEffect, useMemo, useState, useTransition, type ButtonHTMLAttributes } from 'react';
import { AnimatePresence, motion } from 'motion/react';
import {
  AlertCircle,
  ArrowLeft,
  CalendarDays,
  ChevronLeft,
  ChevronRight,
  Loader2,
  MapPin,
  Phone,
  SearchX,
  ShieldCheck,
} from 'lucide-react';

import {
  AuthCardFrame,
  AuthCardHeader,
  AuthPageShell,
  AuthSectionHeading,
  authPrimaryButtonClass,
} from '../auth/AuthShared';
import { LazyImage } from '../ui/LazyImage';
import { cn } from '../../lib/utils';
import { getSpaPaths, getSpaUrl } from '../../lib/spaRouting';

type FoundItem = {
  id: number;
  title: string;
  description: string;
  description_excerpt: string;
  location_found: string;
  found_date: string;
  found_date_label: string;
  contact_info: string;
  status: string;
  status_label: string;
  image_url: string | null;
  thumbnail_url: string | null;
};

type FoundItemsResponse = {
  data: FoundItem[];
  meta: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
  };
  message?: string;
};

const FOUND_ITEMS_API_URL = getSpaUrl('foundItemsApi', '/api/found-items');
const PUBLIC_HOME_URL = getSpaUrl('publicHome', '/');
const REPORT_URL = getSpaPaths('report')[0] ?? '/report';
const PAGE_SIZE = 12;
const SONGKRAN_LOGO_URL = '/images/Songkran%20logo.png';

function resolveInitialPage() {
  if (typeof window === 'undefined') {
    return 1;
  }

  const page = Number(new URLSearchParams(window.location.search).get('page'));

  return Number.isInteger(page) && page > 0 ? page : 1;
}

function buildPageWindow(currentPage: number, lastPage: number) {
  const start = Math.max(1, currentPage - 2);
  const end = Math.min(lastPage, start + 4);
  const adjustedStart = Math.max(1, end - 4);

  return Array.from({ length: Math.max(0, end - adjustedStart + 1) }, (_, index) => adjustedStart + index);
}

function StatCard({
  eyebrow,
  value,
  description,
}: {
  eyebrow: string;
  value: string;
  description: string;
}) {
  return (
    <div className="rounded-[1.4rem] border border-sky-100 bg-white/90 p-4 shadow-[0_12px_28px_rgba(14,165,233,0.08)]">
      <p className="text-[10px] font-bold uppercase tracking-[0.28em] text-sky-700/80">{eyebrow}</p>
      <p
        className="mt-2 text-3xl font-black tracking-tight text-slate-950"
        style={{ fontFamily: '"Kanit", sans-serif' }}
      >
        {value}
      </p>
      <p className="mt-1 text-sm leading-relaxed text-slate-600">{description}</p>
    </div>
  );
}

function MetaRow({
  icon: Icon,
  label,
  value,
}: {
  icon: typeof MapPin;
  label: string;
  value: string;
}) {
  return (
    <div className="rounded-2xl border border-sky-100 bg-white/90 px-4 py-3 shadow-[0_8px_20px_rgba(14,165,233,0.06)]">
      <div className="flex items-start gap-3">
        <div className="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-2xl bg-sky-100 text-sky-700">
          <Icon className="h-[18px] w-[18px]" />
        </div>
        <div className="min-w-0">
          <p className="text-[10px] font-bold uppercase tracking-[0.24em] text-sky-700/80">{label}</p>
          <p className="mt-1 whitespace-pre-line text-sm leading-relaxed text-slate-700">
            {value}
          </p>
        </div>
      </div>
    </div>
  );
}

function PaginationButton({
  active = false,
  children,
  className,
  ...props
}: ButtonHTMLAttributes<HTMLButtonElement> & {
  active?: boolean;
}) {
  return (
    <button
      {...props}
      className={cn(
        'inline-flex min-h-11 min-w-11 items-center justify-center rounded-xl border px-4 py-2 text-sm font-semibold transition-all disabled:cursor-not-allowed disabled:opacity-50',
        active
          ? 'border-sky-500 bg-gradient-to-r from-sky-600 to-cyan-500 text-white shadow-[0_8px_20px_rgba(14,165,233,0.25)]'
          : 'border-sky-100 bg-white text-slate-700 hover:border-sky-300 hover:bg-sky-50',
        className,
      )}
    >
      {children}
    </button>
  );
}

export function FoundItemsPage() {
  const [page, setPage] = useState(resolveInitialPage);
  const [response, setResponse] = useState<FoundItemsResponse>({
    data: [],
    meta: {
      current_page: 1,
      last_page: 1,
      per_page: PAGE_SIZE,
      total: 0,
    },
  });
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [isPending, startTransition] = useTransition();

  useEffect(() => {
    const controller = new AbortController();

    setLoading(true);
    setError(null);

    const params = new URLSearchParams({
      page: String(page),
      per_page: String(PAGE_SIZE),
    });

    fetch(`${FOUND_ITEMS_API_URL}?${params.toString()}`, {
      method: 'GET',
      signal: controller.signal,
      headers: {
        Accept: 'application/json',
      },
    })
      .then(async (fetchResponse) => {
        const payload = (await fetchResponse.json()) as FoundItemsResponse;

        if (!fetchResponse.ok) {
          throw new Error(payload.message || 'Unable to load found items right now.');
        }

        setResponse(payload);
      })
      .catch((fetchError: unknown) => {
        if ((fetchError as Error).name === 'AbortError') {
          return;
        }

        setError((fetchError as Error).message || 'Unable to load found items right now.');
      })
      .finally(() => {
        if (!controller.signal.aborted) {
          setLoading(false);
        }
      });

    return () => controller.abort();
  }, [page]);

  useEffect(() => {
    if (typeof window === 'undefined') {
      return;
    }

    const url = new URL(window.location.href);

    if (page <= 1) {
      url.searchParams.delete('page');
    } else {
      url.searchParams.set('page', String(page));
    }

    window.history.replaceState({}, '', url);
  }, [page]);

  const pageWindow = useMemo(
    () => buildPageWindow(response.meta.current_page, response.meta.last_page),
    [response.meta.current_page, response.meta.last_page],
  );

  const changePage = (nextPage: number) => {
    if (nextPage < 1 || nextPage > response.meta.last_page || nextPage === page) {
      return;
    }

    startTransition(() => {
      setPage(nextPage);
    });

    window.scrollTo({
      top: 0,
      behavior: 'smooth',
    });
  };

  return (
    <AuthPageShell
      skipHref="#found-items-content"
      skipLabel="Skip to found items content"
      backgroundImageUrl="/images/BACKGROUND.jpg"
    >
      <motion.main
        id="found-items-content"
        initial={{ opacity: 0, y: 18 }}
        animate={{ opacity: 1, y: 0 }}
        transition={{ duration: 0.7, ease: [0.25, 0.46, 0.45, 0.94] }}
        className="relative px-4 py-10 lg:px-8"
        style={{ zIndex: 2 }}
      >
        <div className="mx-auto w-full max-w-[1240px]">
          <AuthCardFrame>
            <AuthCardHeader
              eyebrow="Public Lost & Found"
              title="Found Items"
              description="Browse belongings that have been posted by the official help desk team. Only items that are still marked as available are shown here."
              topSlot={(
                <div className="flex flex-wrap items-center justify-between gap-4">
                  <a
                    href={PUBLIC_HOME_URL}
                    className="inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-4 py-2 text-sm font-semibold text-white shadow-[0_14px_45px_rgba(12,74,110,0.22)] backdrop-blur-sm transition hover:bg-white/16"
                  >
                    <ArrowLeft className="h-4 w-4" />
                    Back to Home
                  </a>

                  <div className="rounded-[1.35rem] border border-white/15 bg-white/10 px-4 py-3 shadow-[0_14px_45px_rgba(12,74,110,0.22)] backdrop-blur-sm">
                    <img src={SONGKRAN_LOGO_URL} alt="Songkran Festival 2026 logo" className="h-12 w-auto sm:h-14" />
                  </div>
                </div>
              )}
            />

            <div className="bg-white">
              <section className="border-b border-slate-100 bg-slate-50/85 px-7 py-6">
                <div className="space-y-4">
                  <div className="max-w-sm">
                    <StatCard
                      eyebrow="Visible Now"
                      value={String(response.meta.total)}
                      description="Available posts currently visible to the public."
                    />
                  </div>

                  <div className="flex flex-wrap items-center gap-3">
                    <a
                      href={REPORT_URL}
                      className={authPrimaryButtonClass('rounded-xl px-5 py-3 text-sm normal-case tracking-normal shadow-[0_4px_14px_rgba(2,132,199,0.35)]')}
                    >
                      Open Help Desk
                    </a>
                    <a
                      href={PUBLIC_HOME_URL}
                      className="inline-flex items-center justify-center rounded-xl border border-sky-100 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:border-sky-300 hover:bg-sky-50"
                    >
                      Return to Homepage
                    </a>
                  </div>
                </div>
              </section>

              <div className="space-y-6 px-7 py-6">
                <AnimatePresence>
                  {error ? (
                    <motion.div
                      initial={{ opacity: 0, y: 8 }}
                      animate={{ opacity: 1, y: 0 }}
                      exit={{ opacity: 0, y: -8 }}
                      className="rounded-[1.6rem] border border-rose-200 bg-white/96 px-5 py-4 text-slate-700 shadow-[0_18px_45px_rgba(12,74,110,0.12)] backdrop-blur-sm"
                    >
                      <div className="flex items-start gap-3">
                        <AlertCircle className="mt-0.5 h-5 w-5 shrink-0 text-rose-500" />
                        <div>
                          <p className="text-sm font-bold text-slate-900">Unable to load found items</p>
                          <p className="mt-1 text-sm leading-relaxed text-slate-600">{error}</p>
                        </div>
                      </div>
                    </motion.div>
                  ) : null}
                </AnimatePresence>

                {loading ? (
                  <section className="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
                    {Array.from({ length: PAGE_SIZE }).map((_, index) => (
                      <div
                        key={`loading-${index}`}
                        className="overflow-hidden rounded-[1.7rem] border border-sky-100 bg-gradient-to-b from-white via-white to-sky-50/70 shadow-[0_18px_45px_rgba(12,74,110,0.09)]"
                      >
                        <div className="landing-skeleton h-56 rounded-none" />
                        <div className="space-y-3 p-5">
                          <div className="landing-skeleton h-5 w-3/4 rounded-full" />
                          <div className="landing-skeleton h-4 w-full rounded-full" />
                          <div className="landing-skeleton h-4 w-5/6 rounded-full" />
                          <div className="landing-skeleton h-16 w-full rounded-2xl" />
                        </div>
                      </div>
                    ))}
                  </section>
                ) : response.data.length === 0 ? (
                  <div className="rounded-[1.8rem] border border-sky-100 bg-gradient-to-br from-white via-sky-50/70 to-cyan-50/80 px-6 py-12 text-center shadow-[0_20px_50px_rgba(12,74,110,0.12)]">
                    <div className="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-sky-100 text-sky-700">
                      <SearchX className="h-8 w-8" />
                    </div>
                    <h2
                      className="text-2xl font-black tracking-tight text-slate-950"
                      style={{ fontFamily: '"Kanit", sans-serif' }}
                    >
                      No available items right now
                    </h2>
                    <p className="mx-auto mt-3 max-w-2xl text-sm leading-relaxed text-slate-600">
                      The help desk team has not posted any available found items yet, or everything has already been claimed. If you still need help, open the help desk form and submit a lost item report.
                    </p>
                    <div className="mt-6 flex flex-wrap items-center justify-center gap-3">
                      <a
                        href={REPORT_URL}
                        className={authPrimaryButtonClass('rounded-xl px-5 py-3 text-sm normal-case tracking-normal shadow-[0_4px_14px_rgba(2,132,199,0.35)]')}
                      >
                        Submit a Lost Item Report
                      </a>
                      <a
                        href={PUBLIC_HOME_URL}
                        className="inline-flex items-center justify-center rounded-xl border border-sky-100 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:border-sky-300 hover:bg-sky-50"
                      >
                        Back to Home
                      </a>
                    </div>
                  </div>
                ) : (
                  <>
                    <div className="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                      <AuthSectionHeading
                        eyebrow="Available Catalogue"
                        title="Review every card carefully before contacting the team"
                        description="Each card mirrors the same calm service-first style as the forgot QR flow, but optimized for browsing instead of form entry."
                      />

                      <div className="inline-flex items-center gap-2 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                        <ShieldCheck className="h-4 w-4 shrink-0" />
                        <span>Only available items are visible here.</span>
                      </div>
                    </div>

                    <section className="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
                      {response.data.map((item, index) => (
                        <motion.article
                          key={item.id}
                          initial={{ opacity: 0, y: 14 }}
                          animate={{ opacity: 1, y: 0 }}
                          transition={{ duration: 0.35, delay: Math.min(index * 0.04, 0.2) }}
                          whileHover={{ y: -4 }}
                          className="group flex h-full flex-col overflow-hidden rounded-[1.7rem] border border-sky-100 bg-gradient-to-b from-white via-white to-sky-50/75 shadow-[0_18px_45px_rgba(12,74,110,0.1)]"
                        >
                          <div className="relative overflow-hidden bg-slate-100">
                            <LazyImage
                              src={item.thumbnail_url || item.image_url || undefined}
                              alt={item.title}
                              wrapperClassName="block aspect-[4/3]"
                              className="h-full w-full object-cover transition-transform duration-700 group-hover:scale-105"
                              skeletonClassName="rounded-none"
                            />

                            <div className="absolute inset-x-0 top-0 flex items-center justify-between gap-3 p-4">
                              <span className="inline-flex rounded-full border border-white/20 bg-white/90 px-3 py-1 text-[10px] font-bold uppercase tracking-[0.22em] text-emerald-700 shadow-sm">
                                {item.status_label}
                              </span>
                              <span className="rounded-full border border-white/20 bg-slate-950/70 px-3 py-1 text-[10px] font-bold uppercase tracking-[0.22em] text-white backdrop-blur-sm">
                                Item #{item.id}
                              </span>
                            </div>
                          </div>

                          <div className="flex flex-1 flex-col gap-4 p-5">
                            <div className="space-y-2">
                              <h2
                                className="text-[1.35rem] font-black leading-tight text-slate-950"
                                style={{ fontFamily: '"Kanit", sans-serif' }}
                              >
                                {item.title}
                              </h2>
                              <p className="text-sm leading-relaxed text-slate-600">{item.description_excerpt}</p>
                            </div>

                            <div className="grid gap-3">
                              <MetaRow icon={MapPin} label="Location Found" value={item.location_found} />
                              <MetaRow icon={CalendarDays} label="Found Date" value={item.found_date_label} />
                              <MetaRow icon={Phone} label="WhatsApp / Phone Number" value={item.contact_info} />
                            </div>
                          </div>
                        </motion.article>
                      ))}
                    </section>
                  </>
                )}
              </div>

              {response.meta.last_page > 1 && !loading && response.data.length > 0 ? (
                <div className="flex flex-col gap-4 border-t border-slate-100 bg-slate-50 px-7 py-5 lg:flex-row lg:items-center lg:justify-between">
                  <div className="flex items-center gap-2 text-sm text-slate-600">
                    <span>
                      Page {response.meta.current_page} of {response.meta.last_page}
                    </span>
                    {isPending ? (
                      <>
                        <span className="text-slate-300">•</span>
                        <Loader2 className="h-4 w-4 animate-spin text-sky-500" />
                        <span>Updating</span>
                      </>
                    ) : null}
                  </div>

                  <div className="flex flex-wrap items-center gap-2">
                    <PaginationButton
                      type="button"
                      onClick={() => changePage(response.meta.current_page - 1)}
                      disabled={response.meta.current_page <= 1 || isPending}
                      className="gap-2"
                    >
                      <ChevronLeft className="h-4 w-4" />
                      Previous
                    </PaginationButton>

                    {pageWindow.map((pageNumber) => (
                      <PaginationButton
                        key={pageNumber}
                        type="button"
                        onClick={() => changePage(pageNumber)}
                        disabled={isPending}
                        active={pageNumber === response.meta.current_page}
                        aria-current={pageNumber === response.meta.current_page ? 'page' : undefined}
                        className="px-0"
                      >
                        {pageNumber}
                      </PaginationButton>
                    ))}

                    <PaginationButton
                      type="button"
                      onClick={() => changePage(response.meta.current_page + 1)}
                      disabled={response.meta.current_page >= response.meta.last_page || isPending}
                      className="gap-2"
                    >
                      Next
                      <ChevronRight className="h-4 w-4" />
                    </PaginationButton>
                  </div>
                </div>
              ) : null}
            </div>
          </AuthCardFrame>
        </div>
      </motion.main>
    </AuthPageShell>
  );
}
