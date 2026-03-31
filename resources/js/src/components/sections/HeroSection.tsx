import { useEffect, useState } from "react";
import { motion, useMotionValue, useSpring } from "motion/react";
import { ArrowDown } from "lucide-react";
import { buildRegisterUrl } from "@/lib/trafficAttribution";
import { LazyImage } from "../ui/LazyImage";

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

  return (
    <section
      id="home-hero-section"
      className="relative min-h-screen overflow-hidden"
      style={{ background: "transparent" }}
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

      <motion.div className="relative z-10 flex min-h-screen flex-col items-center justify-start pt-16 md:justify-center md:py-10 lg:py-14">
        <div className="flex w-full flex-col items-center gap-3 px-4 md:gap-5">
          <div
            className="relative mt-1 mb-2 flex items-start justify-center md:mt-4 md:mb-5 md:items-center lg:mt-6 lg:mb-6"
            style={{ width: "100vw", height: "clamp(130px, 22vh, 320px)" }}
          >
            <div
              className="absolute left-1/2 top-0 -translate-x-1/2"
              style={{
                width: "calc(100vw + 10rem)",
                height: "clamp(160px, 26vh, 350px)",
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
                height: "clamp(130px, 24vh, 280px)",
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
              height: "clamp(180px, 32vh, 400px)",
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
              wrapperClassName="w-full max-w-[820px]"
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
          className="relative z-20 mt-6 px-4 md:mb-8 md:px-8 lg:px-14"
        >
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
        </motion.div>
      </motion.div>

      <motion.button
        type="button"
        onClick={goToNextSection}
        initial={{ opacity: 0, x: 12 }}
        animate={{ opacity: 1, x: 0 }}
        transition={{ delay: 1.1, duration: 0.45, ease: "easeOut" }}
        className="absolute bottom-28 right-4 z-30 flex h-10 w-10 items-center justify-center rounded-full bg-black/45 text-white shadow-[0_8px_22px_rgba(0,0,0,0.22)] backdrop-blur-sm transition hover:scale-105 hover:bg-black/55 active:scale-95 md:hidden"
        aria-label="Scroll to next section"
      >
        <motion.div
        animate={{ y: [0, 6, 0] }}
        transition={{ repeat: Infinity, duration: 1.2 }}
      >
        <ArrowDown className="h-5 w-5" />
      </motion.div>
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