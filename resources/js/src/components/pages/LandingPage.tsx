import { Suspense, lazy, useEffect, useRef, useState } from "react";
import { motion, useScroll } from "motion/react";
import { GrainOverlay } from "../ui/GrainOverlay";
import { Navbar } from "../sections/Navbar";
import { HeroSection } from "../sections/HeroSection";
import { SectionDivider } from "../ui/SectionDivider";
import { LazyImage } from "../ui/LazyImage";

const AboutFestival = lazy(() =>
  import("../sections/AboutFestival").then((module) => ({ default: module.AboutFestival }))
);
const EventDetails = lazy(() =>
  import("../sections/EventDetails").then((module) => ({ default: module.EventDetails }))
);
const FestivalActivities = lazy(() =>
  import("../sections/FestivalActivities").then((module) => ({
    default: module.FestivalActivities,
  }))
);
const EventSchedule = lazy(() =>
  import("../sections/EventSchedule").then((module) => ({ default: module.EventSchedule }))
);
const DontsSection = lazy(() =>
  import("../sections/DontsSection").then((module) => ({ default: module.DontsSection }))
);
const FAQ = lazy(() => import("../sections/FAQ").then((module) => ({ default: module.FAQ })));
const SponsorSection = lazy(() =>
  import("../sections/SponsorSection").then((module) => ({ default: module.SponsorSection }))
);
const Footer = lazy(() =>
  import("../sections/Footer").then((module) => ({ default: module.Footer }))
);

// Komponen Global Scroll Indicator (Panah Bawah -> Mentok Bawah jadi Panah Atas)
function ScrollIndicator() {
  const { scrollYProgress } = useScroll();
  const [isAtBottom, setIsAtBottom] = useState(false);

  useEffect(() => {
    return scrollYProgress.on("change", (latest) => {
      // Jika nyaris mentok bawah, ubah jadi mode "Back to top"
      setIsAtBottom(latest > 0.95);
    });
  }, [scrollYProgress]);

  return (
    <motion.div
      initial={{ opacity: 0 }}
      animate={{ opacity: 1 }}
      className={`fixed bottom-6 right-6 md:bottom-8 md:right-10 z-[150] flex flex-col items-center transition-all duration-300 ${
        isAtBottom ? "pointer-events-auto cursor-pointer" : "pointer-events-none"
      }`}
      onClick={() => isAtBottom && window.scrollTo({ top: 0, behavior: "smooth" })}
    >
      {/* Dark pill backing to ensure WCAG AAA high contrast */}
      <div className="bg-[#050508]/90 backdrop-blur-md border border-white/20 w-10 h-10 rounded-full shadow-[0_8px_16px_rgba(0,0,0,0.6)] flex items-center justify-center hover:scale-110 active:scale-95 transition-transform">
        {/* Animated Arrow (Chevron) - Rotates based on position */}
        <motion.svg
          width="20"
          height="20"
          viewBox="0 0 24 24"
          fill="none"
          stroke="#3FD7F5"
          strokeWidth="3"
          strokeLinecap="round"
          strokeLinejoin="round"
          animate={{
            y: isAtBottom ? [3, -3, 3] : [-3, 3, -3],
            opacity: [0.6, 1, 0.6],
            rotate: isAtBottom ? 180 : 0,
          }}
          transition={{
            y: { duration: 1.5, repeat: Infinity, ease: "easeInOut" },
            opacity: { duration: 1.5, repeat: Infinity, ease: "easeInOut" },
            rotate: { duration: 0.4, ease: "backOut" }
          }}
        >
          <polyline points="6 9 12 15 18 9"></polyline>
        </motion.svg>
      </div>
    </motion.div>
  );
}

function SectionSkeleton({ heightClassName = "min-h-[320px]" }: { heightClassName?: string }) {
  return (
    <div className={`px-6 py-12 md:px-12 lg:px-16 ${heightClassName}`}>
      <div className="mx-auto flex max-w-7xl flex-col gap-4 rounded-[32px] border border-white/20 bg-white/18 p-6 shadow-[0_18px_42px_rgba(0,0,0,0.08)] backdrop-blur-xl md:p-10">
        <div className="landing-skeleton h-4 w-28 rounded-full" />
        <div className="landing-skeleton h-12 w-3/5 rounded-3xl" />
        <div className="landing-skeleton h-12 w-2/5 rounded-3xl" />
        <div className="landing-skeleton h-56 w-full rounded-[28px]" />
      </div>
    </div>
  );
}

function LandingPageLoader({ ready }: { ready: boolean }) {
  return (
    <div
      className={`landing-page-loader pointer-events-none fixed inset-0 z-[320] ${
        ready ? "is-ready" : ""
      }`}
      aria-hidden="true"
    >
      <div
        className="absolute inset-0"
        style={{
          background:
            "radial-gradient(circle at 20% 15%, rgba(255,255,255,0.68) 0%, rgba(255,255,255,0.26) 24%, transparent 45%), linear-gradient(180deg, rgba(186,230,253,0.72) 0%, rgba(240,249,255,0.88) 100%)",
        }}
      />

      <div className="relative flex h-full items-center justify-center px-6">
        <div className="w-full max-w-[460px] rounded-[36px] border border-white/40 bg-white/28 p-6 shadow-[0_24px_60px_rgba(0,0,0,0.12)] backdrop-blur-xl md:p-8">
          <div className="mb-4 flex items-center gap-3">
            <div className="landing-skeleton h-3 w-16 rounded-full" />
            <div className="landing-skeleton h-3 w-28 rounded-full" />
          </div>
          <div className="space-y-3">
            <div className="landing-skeleton h-14 w-full rounded-[24px]" />
            <div className="landing-skeleton h-52 w-full rounded-[32px]" />
            <div className="landing-skeleton h-4 w-4/5 rounded-full" />
            <div className="landing-skeleton h-4 w-3/5 rounded-full" />
            <div className="landing-skeleton h-12 w-48 rounded-full" />
          </div>
        </div>
      </div>
    </div>
  );
}

export function LandingPage() {
  const [pageReady, setPageReady] = useState(false);
  const [isHeroToFaqBackgroundVisible, setIsHeroToFaqBackgroundVisible] = useState(true);
  const heroToFaqRef = useRef<HTMLDivElement | null>(null);

  useEffect(() => {
    let cancelled = false;

    const markReady = () => {
      if (cancelled) {
        return;
      }

      window.requestAnimationFrame(() => {
        if (!cancelled) {
          setPageReady(true);
        }
      });
    };

    if (document.readyState === "complete") {
      markReady();
    }

    window.addEventListener("load", markReady, { once: true });
    const fallbackTimer = window.setTimeout(markReady, 1600);

    return () => {
      cancelled = true;
      window.removeEventListener("load", markReady);
      window.clearTimeout(fallbackTimer);
    };
  }, []);

  useEffect(() => {
    let frameId = 0;

    const updateBackgroundVisibility = () => {
      frameId = 0;

      const heroToFaq = heroToFaqRef.current;

      if (!heroToFaq) {
        return;
      }

      const { top, bottom } = heroToFaq.getBoundingClientRect();
      const viewportHeight = window.innerHeight;
      const isVisible = bottom > 0 && top < viewportHeight;

      setIsHeroToFaqBackgroundVisible(isVisible);
    };

    const requestUpdate = () => {
      if (frameId !== 0) {
        return;
      }

      frameId = window.requestAnimationFrame(updateBackgroundVisibility);
    };

    requestUpdate();
    window.addEventListener("scroll", requestUpdate, { passive: true });
    window.addEventListener("resize", requestUpdate);

    return () => {
      window.removeEventListener("scroll", requestUpdate);
      window.removeEventListener("resize", requestUpdate);

      if (frameId !== 0) {
        window.cancelAnimationFrame(frameId);
      }
    };
  }, []);

  return (
    <div className="min-h-screen overflow-x-hidden" style={{ fontFamily: "'Tilt Warp', sans-serif" }}>
      <LandingPageLoader ready={pageReady} />

      <div
        className={`pointer-events-none fixed inset-0 z-0 overflow-hidden transition-opacity duration-500 ${
          isHeroToFaqBackgroundVisible ? "opacity-100" : "opacity-0"
        }`}
        aria-hidden="true"
      >
        <LazyImage
          src="/images/BACKGROUND.jpg"
          alt=""
          aria-hidden="true"
          loading="eager"
          fetchPriority="high"
          wrapperClassName="h-full w-full"
          className="h-full w-full object-cover object-center"
          showSkeleton={false}
        />
      </div>

      <div
        className={`pointer-events-none fixed inset-0 z-[1] transition-opacity duration-500 ${
          isHeroToFaqBackgroundVisible ? "opacity-100" : "opacity-0"
        }`}
        aria-hidden="true"
        style={{
          background:
            "radial-gradient(circle at 22% 14%, rgba(255,255,255,0.18) 0%, transparent 34%), radial-gradient(circle at 82% 20%, rgba(83,211,244,0.08) 0%, transparent 30%), linear-gradient(180deg, rgba(255,255,255,0.08) 0%, rgba(255,255,255,0.02) 42%, rgba(255,255,255,0.10) 100%)",
        }}
      />

      <div className={`landing-page-shell relative z-10 ${pageReady ? "is-ready" : ""}`}>
        <GrainOverlay />
        <div ref={heroToFaqRef} className="relative isolate">
          <div className="relative z-10">
            <Navbar />
            <ScrollIndicator />
            <HeroSection />
            <SectionDivider />
            <Suspense fallback={<SectionSkeleton heightClassName="min-h-[520px]" />}>
              <AboutFestival />
            </Suspense>
            <SectionDivider reverse />
            <Suspense fallback={<SectionSkeleton heightClassName="min-h-[520px]" />}>
              <EventDetails />
            </Suspense>
            <SectionDivider />
            <Suspense fallback={<SectionSkeleton heightClassName="min-h-[560px]" />}>
              <FestivalActivities />
            </Suspense>
            <SectionDivider reverse />
            <Suspense fallback={<SectionSkeleton heightClassName="min-h-[420px]" />}>
              <EventSchedule />
            </Suspense>
            <Suspense fallback={<SectionSkeleton heightClassName="min-h-[420px]" />}>
              <DontsSection />
            </Suspense>
            <SectionDivider />
            <Suspense fallback={<SectionSkeleton heightClassName="min-h-[400px]" />}>
              <FAQ />
            </Suspense>
          </div>
        </div>
        <Suspense fallback={<SectionSkeleton heightClassName="min-h-[320px]" />}>
          <SponsorSection />
        </Suspense>
        <Suspense fallback={<SectionSkeleton heightClassName="min-h-[260px]" />}>
          <Footer />
        </Suspense>
      </div>
    </div>
  );
}
