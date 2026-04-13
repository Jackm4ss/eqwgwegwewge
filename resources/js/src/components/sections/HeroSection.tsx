import { useEffect, useState } from "react";
import { motion, useMotionValue, useSpring } from "motion/react";
import { ArrowDown, ArrowRight, ShieldAlert } from "lucide-react";
import { buildRegisterUrl } from "@/lib/trafficAttribution";
import { LazyImage } from "../ui/LazyImage";
import { getSpaPaths } from "@/lib/spaRouting";

const TARGET = new Date("2026-04-09T12:00:00+08:00");

function useCountdown() {
  const calc = () => {
    const diff = TARGET.getTime() - Date.now();

    if (diff <= 0) {
      return { days: 0, hours: 0, minutes: 0, seconds: 0, live: true };
    }

    return {
      days: Math.floor(diff / 86400000),
      hours: Math.floor((diff % 86400000) / 3600000),
      minutes: Math.floor((diff % 3600000) / 60000),
      seconds: Math.floor((diff % 60000) / 1000),
      live: false,
    };
  };

  const [time, setTime] = useState(calc);

  useEffect(() => {
    const intervalId = setInterval(() => setTime(calc()), 1000);
    return () => clearInterval(intervalId);
  }, []);

  return time;
}

function ScribbleUnderline({ color = "#2FA7D8", width = 160 }: { color?: string; width?: number }) {
  return (
    <svg viewBox={`0 0 ${width} 12`} fill="none" style={{ width, height: 12, display: "block" }}>
      <motion.path
        d={`M2 8 Q${width * 0.15} 3 ${width * 0.3} 8 Q${width * 0.45} 13 ${width * 0.6} 7 Q${width * 0.75} 2 ${width * 0.9} 7 Q${width * 0.97} 10 ${width - 2} 6`}
        stroke={color}
        strokeWidth="2.5"
        strokeLinecap="round"
        fill="none"
        initial={{ pathLength: 0, opacity: 0 }}
        animate={{ pathLength: 1, opacity: 1 }}
        transition={{ delay: 1.5, duration: 1.2, ease: "easeOut" }}
      />
    </svg>
  );
}

const SYNE: React.CSSProperties = { fontFamily: "'Tilt Warp', sans-serif" };
const SG: React.CSSProperties = { fontFamily: "'Tilt Warp', sans-serif" };
const TW: React.CSSProperties = { fontFamily: "'Tilt Warp', system-ui, sans-serif" };
const TILT: React.CSSProperties = { fontFamily: "'Tilt Warp', sans-serif" };
const HELP_DESK_CARD_BACKGROUND =
  "linear-gradient(145deg, rgba(240, 249, 255, 0.92), rgba(224, 242, 254, 0.82))";
const HELP_DESK_CARD_BORDER = "rgba(14, 116, 144, 0.18)";
const HELP_DESK_CARD_SHADOW = "0 20px 44px rgba(15,23,42,0.14)";
const HELP_DESK_BADGE_BACKGROUND = "rgba(255, 255, 255, 0.66)";
const HELP_DESK_BADGE_BORDER = "1px solid rgba(8, 47, 73, 0.12)";
const HELP_DESK_BADGE_TEXT = "rgba(15, 23, 42, 0.72)";
const HELP_DESK_ACCENT = "#389DD2";
const HELP_DESK_ICON = "#0f766e";

export function HeroSection() {
  const mouseX = useMotionValue(0);
  const mouseY = useMotionValue(0);
  const px = useSpring(mouseX, { stiffness: 80, damping: 30 });
  const py = useSpring(mouseY, { stiffness: 80, damping: 30 });
  const px2Source = useMotionValue(0);
  const py2Source = useMotionValue(0);
  const px2 = useSpring(px2Source, { stiffness: 50, damping: 25 });
  const py2 = useSpring(py2Source, { stiffness: 50, damping: 25 });
  const { days, hours, minutes, seconds, live } = useCountdown();
  const [heroViewport, setHeroViewport] = useState({ width: 0, height: 0 });
  const helpDeskUrl = getSpaPaths("report")[0] ?? "/report";

  useEffect(() => {
    const syncHeroViewport = () => {
      setHeroViewport({
        width: window.innerWidth,
        height: window.innerHeight,
      });
    };

    syncHeroViewport();
    window.addEventListener("resize", syncHeroViewport);

    return () => {
      window.removeEventListener("resize", syncHeroViewport);
    };
  }, []);

  const handleMouse = (event: React.MouseEvent) => {
    const cx = (event.clientX / window.innerWidth - 0.5) * 40;
    const cy = (event.clientY / window.innerHeight - 0.5) * 40;
    mouseX.set(cx);
    mouseY.set(cy);
    px2Source.set(cx * 0.35);
    py2Source.set(cy * 0.35);
  };

  const goToRegister = () => {
    window.location.assign(buildRegisterUrl());
  };

  const goToHelpDesk = () => {
    window.location.assign(helpDeskUrl);
  };

  const goToNextSection = () => {
    const heroSection = document.getElementById("home-hero-section");
    if (!heroSection) return;

    let nextSection = heroSection.nextElementSibling as HTMLElement | null;

    while (nextSection && nextSection.tagName.toLowerCase() !== "section") {
      nextSection = nextSection.nextElementSibling as HTMLElement | null;
    }

    if (nextSection) {
      nextSection.scrollIntoView({ behavior: "smooth", block: "start" });
      return;
    }

    window.scrollTo({
      top: window.scrollY + window.innerHeight * 0.9,
      behavior: "smooth",
    });
  };

  const heroViewportHeight = heroViewport.height > 0 ? `${heroViewport.height}px` : "100svh";
  const isCompactTabletHero =
    heroViewport.width >= 768 &&
    heroViewport.width < 1024 &&
    heroViewport.height > 0 &&
    heroViewport.height <= 980;
  const isCompactDesktopHero =
    heroViewport.width >= 1024 && heroViewport.height > 0 && heroViewport.height <= 900;
  const heroShellClassName = isCompactDesktopHero
    ? "relative z-10 flex h-full flex-col items-center justify-center py-8 lg:py-10"
    : isCompactTabletHero
      ? "relative z-10 flex h-full flex-col items-center justify-center py-7 md:py-8"
      : "relative z-10 flex h-full flex-col items-center justify-start pt-10 md:justify-center md:py-10 lg:py-14";
  const heroContentClassName = isCompactDesktopHero
    ? "flex w-full flex-col items-center gap-2 px-4 md:gap-3"
    : isCompactTabletHero
      ? "flex w-full flex-col items-center gap-2 px-4 md:gap-3"
      : "flex w-full flex-col items-center gap-3 px-4 md:gap-5";
  const logoBlockClassName = isCompactDesktopHero
    ? "relative mt-3 mb-1 flex items-center justify-center"
    : isCompactTabletHero
      ? "relative mt-2 mb-1 flex items-center justify-center md:mt-2 md:mb-3"
      : "relative mt-0 mb-2 flex items-start justify-center md:mt-4 md:mb-5 md:items-center lg:mt-6 lg:mb-6";
  const registerWrapClassName = isCompactDesktopHero
    ? "relative z-20 mt-4 px-4 pb-5 md:px-8 lg:px-14"
    : isCompactTabletHero
      ? "relative z-20 mt-4 px-4 pb-6 md:px-8"
      : "relative z-20 mt-6 px-4 md:mb-8 md:px-8 lg:px-14";
  const steamHeight = isCompactDesktopHero
    ? "clamp(145px, 22vh, 250px)"
    : isCompactTabletHero
      ? "clamp(145px, 21vh, 260px)"
      : "clamp(160px, 26vh, 350px)";
  const logoHeight = isCompactDesktopHero
    ? "clamp(118px, 18vh, 210px)"
    : isCompactTabletHero
      ? "clamp(120px, 17vh, 220px)"
      : "clamp(130px, 22vh, 320px)";
  const songkranLogoHeight = isCompactDesktopHero
    ? "clamp(118px, 20vh, 220px)"
    : isCompactTabletHero
      ? "clamp(120px, 19vh, 210px)"
      : "clamp(130px, 24vh, 280px)";
  const stageHeight = isCompactDesktopHero
    ? "clamp(165px, 27vh, 320px)"
    : isCompactTabletHero
      ? "clamp(185px, 29vh, 300px)"
      : "clamp(180px, 32vh, 400px)";
  const textWrapClassName = isCompactDesktopHero
    ? "w-full max-w-[700px]"
    : isCompactTabletHero
      ? "w-full max-w-[640px]"
      : "w-full max-w-[820px]";
  const showFloatingHelpDeskCard = heroViewport.width >= 1280;
  const showInlineHelpDeskCard = !showFloatingHelpDeskCard;

  return (
    <section
      id="home-hero-section"
      className="relative min-h-screen overflow-hidden"
      style={{
        background: "transparent",
        minHeight: heroViewportHeight,
        height: heroViewportHeight,
      }}
      onMouseMove={handleMouse}
    >
      <div className="absolute inset-0 pointer-events-none">
        <div style={{ position: "absolute", inset: 0, background: "" }} />
        <div style={{ position: "absolute", inset: 0, background: "," }} />
      </div>

      <motion.div className="absolute inset-0 pointer-events-none" style={{ x: px2, y: py2 }}>
        <div
          className="absolute top-[25%] right-[15%] h-20 w-20 rounded-full"
          style={{
            background: "radial-gradient(circle, rgba(24,199,204,0.08) 0%, transparent 80%)",
          }}
        />
        <div
          className="absolute bottom-[25%] left-[10%] h-24 w-24 rounded-full"
          style={{
            background: "radial-gradient(circle, rgba(47,167,216,0.06) 0%, transparent 80%)",
          }}
        />
      </motion.div>

      <motion.div className={heroShellClassName} style={{ minHeight: heroViewportHeight }}>
        <div className={heroContentClassName}>
          <div
            className={logoBlockClassName}
            style={{ width: "100vw", height: logoHeight }}
          >
            <div
              className="absolute left-1/2 top-0 -translate-x-1/2"
              style={{
                width: "calc(100vw + 10rem)",
                height: steamHeight,
                zIndex: 0,
                opacity: 0.92,
              }}
            >
              <motion.div
                className="h-full w-full"
                style={{ x: px, y: py }}
                initial={{ opacity: 0, scale: 0.95 }}
                animate={{ opacity: 0.92, scale: 1 }}
                transition={{ duration: 1.1, ease: "easeOut" }}
              >
                <LazyImage
                  src="/images/STEAM.png"
                  alt=""
                  aria-hidden="true"
                  loading="eager"
                  wrapperClassName="h-full w-full"
                  className="h-full w-full object-fill object-center"
                  showSkeleton={false}
                />
              </motion.div>
            </div>

            <motion.div
              style={{
                x: px,
                y: py,
                filter: "drop-shadow(0 0 28px rgba(47,167,216,0.55))",
                position: "relative",
                width: "90vw",
                height: songkranLogoHeight,
                zIndex: 1,
              }}
              initial={{ opacity: 0, y: -20 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ duration: 0.9, ease: "easeOut" }}
            >
              <LazyImage
                src="/images/Songkran logo.png"
                alt="Songkran Festival"
                loading="eager"
                fetchPriority="high"
                wrapperClassName="h-full w-full"
                className="h-full w-full object-contain"
                style={{ objectFit: "contain" }}
                showSkeleton={false}
              />
            </motion.div>
          </div>

          <motion.div
            style={{
              x: px2,
              y: py2,
              filter: "drop-shadow(0 0 24px rgba(47,167,216,0.35))",
              width: "100vw",
              height: stageHeight,
            }}
            initial={{ opacity: 0, y: 30 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 1, delay: 0.15, ease: "easeOut" }}
          >
            <LazyImage
              src="/images/islands.png"
              alt="Festival Stage"
              loading="eager"
              wrapperClassName="h-full w-full"
              className="h-full w-full object-contain"
              style={{ objectFit: "contain" }}
              showSkeleton={false}
            />
          </motion.div>

          <motion.div
            className="flex w-full justify-center px-4 md:px-0"
            initial={{ opacity: 0, y: 16 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.85, delay: 0.45, ease: "easeOut" }}
          >
            <LazyImage
              src="/images/text.png"
              alt="12PM-12AM, 9-19 APRIL, @GF Forecourt Outdoor Carpark, 1 Utama, Malaysia's Premier Songkran Festival"
              loading="eager"
              wrapperClassName={textWrapClassName}
              className="h-auto w-full"
              style={{
                objectFit: "contain",
                filter: "drop-shadow(0 6px 20px rgba(0,0,0,0.35))",
              }}
              showSkeleton={false}
            />
          </motion.div>
        </div>

        <motion.div
          initial={{ opacity: 0, scale: 0.9 }}
          animate={{ opacity: 1, scale: 1 }}
          transition={{ delay: 1.2, duration: 0.5, type: "spring" }}
          className={registerWrapClassName}
        >
          <div className="flex flex-col items-center gap-4">
            {/* Register button */}
            <button
              onClick={goToRegister}
              className="group relative overflow-hidden rounded-full px-8 py-3.5 transition-all hover:scale-105 active:scale-95 md:px-10 md:py-4"
              style={{
                background: "linear-gradient(135deg, #0284C7, #0EA5E9)",
                boxShadow: "0 4px 14px rgba(2,132,199,0.35)",
              }}
            >
              <div className="absolute inset-0 bg-black/10 opacity-0 transition-opacity duration-200 ease-out group-hover:opacity-100" />
              <div className="absolute inset-0 bg-black/20 opacity-0 transition-opacity duration-100 ease-out group-active:opacity-100" />
              <span
                style={{
                  ...TW,
                  fontSize: "1rem",
                  letterSpacing: "0.12em",
                  color: "#ffffff",
                  position: "relative",
                  zIndex: 10,
                }}
              >
                REGISTER NOW
              </span>
            </button>

            {showInlineHelpDeskCard ? (
            <button
              type="button"
              onClick={goToHelpDesk}
              aria-label="Open Help Desk"
              className="group w-full max-w-xs text-center transition-all hover:scale-[1.02] active:scale-[0.97]"
              style={{ background: "none", border: "none", padding: 0 }}
            >
              <div
                className="flex items-center gap-3 rounded-2xl border px-3.5 py-3 backdrop-blur-xl"
                style={{
                  background: HELP_DESK_CARD_BACKGROUND,
                  borderColor: HELP_DESK_CARD_BORDER,
                  boxShadow: HELP_DESK_CARD_SHADOW,
                }}
              >
                <div
                  className="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full"
                  style={{
                    background: HELP_DESK_BADGE_BACKGROUND,
                    border: HELP_DESK_BADGE_BORDER,
                  }}
                >
                  <ShieldAlert size={15} style={{ color: HELP_DESK_ICON }} />
                </div>
                <div className="flex-1 min-w-0 text-center">
                  <p
                    style={{
                      ...TILT,
                      fontSize: "0.65rem",
                      letterSpacing: "0.07em",
                      color: HELP_DESK_BADGE_TEXT,
                      marginBottom: "1px",
                    }}
                  >
                    Lost item · Report · Complaint
                  </p>
                  <p
                    style={{
                      ...TILT,
                      fontSize: "0.78rem",
                      lineHeight: 1.25,
                      color: HELP_DESK_ACCENT,
                      fontWeight: 600,
                    }}
                  >
                    Go to Help Desk
                  </p>
                </div>
                <ArrowRight
                  size={17}
                  style={{ color: HELP_DESK_ACCENT, flexShrink: 0 }}
                  className="transition-transform duration-200 group-hover:translate-x-1"
                />
              </div>
            </button>
            ) : null}

          </div>
        </motion.div>
      </motion.div>
      {showFloatingHelpDeskCard ? (
      <motion.button
        type="button"
        onClick={goToHelpDesk}
        initial={{ opacity: 0, x: -16 }}
        animate={{ opacity: 1, x: 0 }}
        transition={{ delay: 1.5, duration: 0.55, ease: [0.22, 1, 0.36, 1] }}
        className="group absolute bottom-10 left-6 z-30 text-center transition-all hover:scale-[1.03] active:scale-[0.97]"
        aria-label="Open Help Desk"
      >
        <div
          className="flex items-center gap-3 rounded-2xl border px-3.5 py-2.5 backdrop-blur-xl"
          style={{
            background: HELP_DESK_CARD_BACKGROUND,
            borderColor: HELP_DESK_CARD_BORDER,
            boxShadow: HELP_DESK_CARD_SHADOW,
            maxWidth: "260px",
          }}
        >
          <div
            className="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full"
            style={{
              background: HELP_DESK_BADGE_BACKGROUND,
              border: HELP_DESK_BADGE_BORDER,
            }}
          >
            <ShieldAlert size={15} style={{ color: HELP_DESK_ICON }} />
          </div>
          <div className="min-w-0 text-center">
            <p
              style={{
                ...TILT,
                fontSize: "0.65rem",
                letterSpacing: "0.07em",
                color: HELP_DESK_BADGE_TEXT,
                marginBottom: "1px",
                whiteSpace: "nowrap",
              }}
            >
              Lost item · Report · Complaint
            </p>
            <p
              style={{
                ...TILT,
                fontSize: "0.78rem",
                lineHeight: 1.25,
                color: HELP_DESK_ACCENT,
                fontWeight: 600,
                whiteSpace: "nowrap",
              }}
            >
              Go to Help Desk
            </p>
          </div>
          <ArrowRight
            size={17}
            style={{ color: HELP_DESK_ACCENT, flexShrink: 0 }}
            className="transition-transform duration-200 group-hover:translate-x-1"
          />
        </div>
      </motion.button>
      ) : null}

      <motion.button
        type="button"
        onClick={() => {
          document.getElementById("about")?.scrollIntoView({ behavior: "smooth" });
        }}
        initial={{ opacity: 0, y: 10 }}
        animate={{ opacity: 1, y: 0 }}
        transition={{ delay: 1.5, duration: 0.6 }}
        className="absolute bottom-10 right-6 z-30 flex flex-col items-center justify-center gap-2 lg:hidden"
        aria-label="Scroll to about section"
      >
        <div className="bg-[#050508]/90 backdrop-blur-md border border-white/20 w-11 h-11 rounded-full shadow-[0_8px_16px_rgba(0,0,0,0.4)] flex items-center justify-center hover:scale-110 active:scale-95 transition-all">
          <motion.div
            animate={{ y: [0, 4, 0] }}
            transition={{ repeat: Infinity, duration: 1.5, ease: "easeInOut" }}
          >
            <ArrowDown className="h-6 w-6 text-[#3FD7F5]" />
          </motion.div>
        </div>
      </motion.button>

      {false && (
        <motion.div
          className="absolute bottom-10 right-6 md:right-12"
          initial={{ opacity: 0 }}
          animate={{ opacity: 1 }}
          transition={{ delay: 1.4 }}
        >
          {live ? (
            <motion.div
              initial={{ opacity: 0, scale: 0.85, y: 10 }}
              animate={{ opacity: 1, scale: 1, y: 0 }}
              transition={{ duration: 0.6, ease: "backOut" }}
              style={{
                background: "rgba(7,8,16,0.65)",
                border: "1px solid rgba(47,167,216,0.25)",
                backdropFilter: "blur(12px)",
                WebkitBackdropFilter: "blur(12px)",
                borderRadius: "16px",
                padding: "12px 18px",
                display: "flex",
                flexDirection: "column",
                alignItems: "flex-end",
                gap: "6px",
                boxShadow: "0 4px 24px rgba(47,167,216,0.1), inset 0 1px 0 rgba(255,255,255,0.05)",
              }}
            >
              <motion.div
                animate={{ opacity: [1, 0.5, 1] }}
                transition={{ duration: 1.8, repeat: Infinity, ease: "easeInOut" }}
                style={{ display: "flex", alignItems: "center", gap: "8px" }}
              >
                <motion.span
                  animate={{ boxShadow: ["0 0 6px #2FA7D8", "0 0 14px #2FA7D8", "0 0 6px #2FA7D8"] }}
                  transition={{ duration: 1.8, repeat: Infinity, ease: "easeInOut" }}
                  style={{
                    width: 7,
                    height: 7,
                    borderRadius: "50%",
                    background: "#2FA7D8",
                    display: "inline-block",
                    flexShrink: 0,
                  }}
                />
                <span
                  style={{
                    ...SYNE,
                    fontWeight: 700,
                    fontSize: "0.85rem",
                    color: "#2FA7D8",
                    letterSpacing: "0.15em",
                  }}
                >
                  HAPPENING NOW
                </span>
              </motion.div>
              <span
                style={{
                  ...SG,
                  fontSize: "0.58rem",
                  letterSpacing: "0.2em",
                  color: "rgba(237,232,220,0.85)",
                  textTransform: "uppercase",
                }}
              >
                APR 9-19 · ONE UTAMA
              </span>
            </motion.div>
          ) : (
            <>
              <div
                style={{
                  ...SG,
                  fontSize: "0.6rem",
                  letterSpacing: "0.3em",
                  color: "rgba(237,232,220,0.85)",
                  textTransform: "uppercase",
                  textAlign: "right",
                  marginBottom: "10px",
                }}
              >
                UNTIL SONGKRAN
              </div>
              <div className="flex gap-4 md:gap-6">
                {[
                  { v: days, l: "DAYS" },
                  { v: hours, l: "HRS" },
                  { v: minutes, l: "MIN" },
                  { v: seconds, l: "SEC" },
                ].map(({ v, l }) => (
                  <div key={l} className="text-right">
                    <div
                      style={{
                        ...SYNE,
                        fontWeight: 700,
                        fontSize: "clamp(1.8rem,3.5vw,2.8rem)",
                        color: "#ffffffff",
                        lineHeight: 1,
                      }}
                    >
                      {String(v).padStart(2, "0")}
                    </div>
                    <div
                      style={{
                        ...SG,
                        fontSize: "0.55rem",
                        letterSpacing: "0.2em",
                        color: "rgba(237,232,220,0.85)",
                        marginTop: 4,
                      }}
                    >
                      {l}
                    </div>
                  </div>
                ))}
              </div>
            </>
          )}
        </motion.div>
      )}
    </section>
  );
}
