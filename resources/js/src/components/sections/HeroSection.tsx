import { useRef, useEffect, useState } from "react";
import { motion, useScroll, useTransform, useMotionValue, useSpring } from "motion/react";
import { UnderwaterBackground } from "../ui/UnderwaterBackground";

const BG_VIDEO = "https://video.wixstatic.com/video/c338c6_cbac5475bb7e41d3a5e45bdac6812b3f/720p/mp4/file.mp4";

// Set TARGET to the event start date
const TARGET = new Date("2026-04-09T12:00:00+08:00");
function useCountdown() {
  const calc = () => {
    const d = TARGET.getTime() - Date.now();
    if (d <= 0) return { days: 0, hours: 0, minutes: 0, seconds: 0, live: true };
    return {
      days: Math.floor(d / 86400000),
      hours: Math.floor((d % 86400000) / 3600000),
      minutes: Math.floor((d % 3600000) / 60000),
      seconds: Math.floor((d % 60000) / 1000),
      live: false,
    };
  };
  const [t, set] = useState(calc);
  useEffect(() => { const id = setInterval(() => set(calc()), 1000); return () => clearInterval(id); }, []);
  return t;
}

// Hand-drawn SVG scribble underline
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


const SYNE: React.CSSProperties = { fontFamily: "'Syne', sans-serif" };
const SG: React.CSSProperties = { fontFamily: "'Space Grotesk', sans-serif" };

export function HeroSection() {
  const ref = useRef<HTMLElement>(null);
  const { scrollYProgress } = useScroll({ target: ref, offset: ["start start", "end start"] });
  const bgY = useTransform(scrollYProgress, [0, 1], ["0%", "28%"]);
  const contentY = useTransform(scrollYProgress, [0, 1], ["0%", "18%"]);
  const fade = useTransform(scrollYProgress, [0, 0.7], [1, 0]);

  const mouseX = useMotionValue(0);
  const mouseY = useMotionValue(0);
  const px = useSpring(mouseX, { stiffness: 80, damping: 30 });
  const py = useSpring(mouseY, { stiffness: 80, damping: 30 });
  const px2 = useSpring(useMotionValue(0), { stiffness: 50, damping: 25 });
  const py2 = useSpring(useMotionValue(0), { stiffness: 50, damping: 25 });

  const { days, hours, minutes, seconds, live } = useCountdown();

  const handleMouse = (e: React.MouseEvent) => {
    const cx = (e.clientX / window.innerWidth - 0.5) * 40;
    const cy = (e.clientY / window.innerHeight - 0.5) * 40;
    mouseX.set(cx);
    mouseY.set(cy);
  };

  return (
<section
  ref={ref}
  className="relative h-screen overflow-hidden"
  style={{ background: "#0a0f14" }}
  onMouseMove={handleMouse}
>
  {/* BG IMAGE */}
  <motion.div className="absolute inset-0" style={{ y: bgY }}>
    <img
      src="/images/BACKGROUND.jpg"
      className="w-full h-full object-cover"
      style={{
        opacity: 1,
        filter: "brightness(1.05) contrast(1.05)"
      }}
    />

    <div
      style={{
        position: "absolute",
        inset: 0,
        background:
          "linear-gradient(180deg, rgba(7,8,16,0.42) 0%, rgba(7,8,16,0.18) 38%, rgba(7,8,16,0.28) 68%, rgba(7,8,16,0.46) 100%)"
      }}
    />

    {/* OPTIONAL: glow tipis biar ga flat */}
    <div
      style={{
        position: "absolute",
        inset: 0,
        background:
          "radial-gradient(ellipse at 60% 40%, rgba(24,199,204,0.04) 0%, transparent 85%)"
      }}
    />
  </motion.div>

  {/* ❌ MATIIN UNDERWATER (INI PENYEBAB UTAMA GELAP/KABUT) */}
  {/* <UnderwaterBackground /> */}

  {/* PARALLAX GLOW SUPER HALUS */}
  <motion.div
    className="absolute inset-0 pointer-events-none"
    style={{ x: px2, y: py2 }}
  >
    <div
      className="absolute top-[25%] right-[15%] w-20 h-20 rounded-full"
      style={{
        background:
          "radial-gradient(circle, rgba(24,199,204,0.08) 0%, transparent 80%)"
      }}
    />

    <div
      className="absolute bottom-[25%] left-[10%] w-24 h-24 rounded-full"
      style={{
        background:
          "radial-gradient(circle, rgba(47,167,216,0.06) 0%, transparent 80%)"
      }}
    />
  </motion.div>


      {/* Content */}
      <motion.div
        className="absolute inset-0 flex flex-col justify-center"
        style={{ y: contentY, opacity: fade }}
      >
        {/* Top badge (pill shape, in flow to prevent overlap) */}
        <motion.div
          className="absolute top-24 md:top-28 lg:top-32 left-0 right-0 px-4 md:px-8 lg:px-14 flex items-center z-10"
          initial={{ opacity: 0, y: -20 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ delay: 0.6, duration: 0.8, type: "spring", stiffness: 100 }}
        >
          <div className="inline-flex items-center gap-2 md:gap-3 px-3 py-1.5 md:px-5 md:py-2.5 rounded-full border border-[#2FA7D8]/30 bg-[#2FA7D8]/10 backdrop-blur-md shadow-[0_4px_20px_rgba(47,167,216,0.15)] relative overflow-hidden group">
            <div className="absolute inset-0 bg-gradient-to-b from-[#070810]/45 via-[#070810]/18 to-[#070810]/35" />
            {/* Subtle glass reflection */}
            <div className="absolute inset-0 bg-gradient-to-r from-white/0 via-white/5 to-white/0 translate-x-[-100%] group-hover:translate-x-[100%] transition-transform duration-1000 ease-in-out" />
            <div className="relative z-10 flex items-center gap-2">
              <img src="https://flagcdn.com/th.svg" alt="Thailand" className="w-3 md:w-4 h-auto rounded-[2px]" />
              <span className="text-[0.55rem] md:text-[0.7rem]" style={{ ...SG, letterSpacing: "0.15em", color: "#EDE8DC", textTransform: "uppercase", fontWeight: 600 }}>
                Thailand × Malaysia
              </span>
              <img src="https://flagcdn.com/my.svg" alt="Malaysia" className="w-3 md:w-4 h-auto rounded-[2px]" />
            </div>
          </div>
        </motion.div>

        {/* SONGKRAN + FESTIVAL IMAGE */}
        <div className="w-full flex flex-col items-center justify-center px-4 md:px-8 lg:px-14 gap-2">

          {/* SONGKRAN */}
          <motion.img
            src="/images/Songkran.png"
            className="w-full max-w-[900px] md:max-w-[1000px] lg:max-w-[1100px]"
            style={{
              x: px,
              y: py,
              filter: "drop-shadow(0 0 20px rgba(47,167,216,0.5))"
            }}
            initial={{ opacity: 0, y: 80 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 1 }}
          />

          {/* FESTIVAL */}
          <motion.img
            src="/images/Festival.png"
            className="w-full max-w-[900px] md:max-w-[1000px] lg:max-w-[1100px]"
            style={{
              x: px2,
              y: py2,
              filter: "drop-shadow(0 0 20px rgba(47,167,216,0.5))"
            }}
            initial={{ opacity: 0, y: 80 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 1, delay: 0.2 }}
          />

          {/* 2026 */}
          <motion.div
            className="flex flex-col items-center mt-3"
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ delay: 0.8 }}
          >
            <span
              style={{
                ...SYNE,
                fontWeight: 800,
                fontSize: "clamp(1.5rem, 4vw, 2.2rem)",
                color: "#2FA7D8",
              }}
            >
              2026
            </span>

            <div style={{ width: "100px" }}>
              <ScribbleUnderline width={100} />
            </div>
          </motion.div>

        </div>

        {/* Bottom bar */}
        <motion.div
          className="px-4 md:px-8 lg:px-14 mt-6 md:mt-8 flex flex-wrap items-center gap-4 md:gap-8"
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ delay: 1.1, duration: 0.8 }}
        >
          {[
            { label: "APR 9–19, 2026" },
            { label: "12PM – 12AM DAILY" },
            { label: "ONE UTAMA, MALAYSIA" },
            { label: "FREE ENTRY", highlight: true },
          ].map(({ label, highlight }) => (
            <span
              key={label}
              className="inline-flex items-center rounded-full px-3 py-1.5 md:px-4 md:py-2"
              style={{
                ...SG,
                fontSize: "0.75rem",
                letterSpacing: "0.12em",
                color: highlight ? "#66D2FF" : "#F7F3EC",
                fontWeight: highlight ? 700 : 500,
                background: highlight ? "rgba(7,8,16,0.72)" : "rgba(7,8,16,0.78)",
                border: highlight ? "1px solid rgba(47,167,216,0.38)" : "1px solid rgba(255,255,255,0.08)",
                backdropFilter: "blur(10px)",
                WebkitBackdropFilter: "blur(10px)",
                textShadow: "0 1px 8px rgba(7,8,16,0.9)",
              }}
            >
              {label}
            </span>
          ))}
        </motion.div>

        {/* CTA Register Button */}
        <motion.div
          initial={{ opacity: 0, scale: 0.9 }}
          animate={{ opacity: 1, scale: 1 }}
          transition={{ delay: 1.2, duration: 0.5, type: "spring" }}
          className="px-4 md:px-8 lg:px-14 mt-6 md:mt-8 relative z-20"
        >
          <button
            onClick={() => window.location.href = '/register'}
            className="group relative overflow-hidden rounded-full px-8 py-3.5 md:px-10 md:py-4 transition-all hover:scale-105 active:scale-95"
            style={{ background: "#2FA7D8", boxShadow: "0 0 20px rgba(244,160,51,0.3)" }}
          >
            <div className="absolute inset-0 bg-white/20 translate-y-full group-hover:translate-y-0 transition-transform duration-300 ease-out" />
            <span style={{ ...SG, fontWeight: 700, fontSize: "0.9rem", letterSpacing: "0.1em", color: "#050508", position: "relative", zIndex: 10 }}>
              REGISTER NOW
            </span>
          </button>
        </motion.div>
      </motion.div>

      {/* Countdown / Happening Now — bottom right */}
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
              <span style={{
                ...SYNE,
                fontWeight: 700,
                fontSize: "0.85rem",
                color: "#2FA7D8",
                letterSpacing: "0.15em",
              }}>
                HAPPENING NOW
              </span>
            </motion.div>
            <span style={{ ...SG, fontSize: "0.58rem", letterSpacing: "0.2em", color: "rgba(237,232,220,0.85)", textTransform: "uppercase" }}>
              APR 9–19 · ONE UTAMA
            </span>
          </motion.div>
        ) : (
          <>
            <div style={{ ...SG, fontSize: "0.6rem", letterSpacing: "0.3em", color: "rgba(237,232,220,0.85)", textTransform: "uppercase", textAlign: "right", marginBottom: "10px" }}>
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
                  <div style={{ ...SYNE, fontWeight: 700, fontSize: "clamp(1.8rem,3.5vw,2.8rem)", color: "#ffffffff", lineHeight: 1 }}>
                    {String(v).padStart(2, "0")}
                  </div>
                  <div style={{ ...SG, fontSize: "0.55rem", letterSpacing: "0.2em", color: "rgba(237,232,220,0.85)", marginTop: 4 }}>
                    {l}
                  </div>
                </div>
              ))}
            </div>
          </>
        )}
      </motion.div>

      {/* Scroll indicator — bottom left */}
    </section>
  );
}
