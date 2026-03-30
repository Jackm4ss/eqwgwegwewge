import { useEffect, useState } from "react";
import { motion, useMotionValue, useSpring } from "motion/react";
import { buildRegisterUrl } from "@/lib/trafficAttribution";

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


const SYNE: React.CSSProperties = { fontFamily: "'Tilt Warp', sans-serif" };
const SG: React.CSSProperties = { fontFamily: "'Tilt Warp', sans-serif" };
const TW: React.CSSProperties = { fontFamily: "'Tilt Warp', system-ui, sans-serif" };

export function HeroSection() {
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
  const goToRegister = () => {
    window.location.assign(buildRegisterUrl());
  };

  return (
    <section
      className="relative min-h-screen overflow-hidden"
      style={{ background: "transparent" }}
      onMouseMove={handleMouse}
    >
      <div className="absolute inset-0 pointer-events-none">
        <div
          style={{
            position: "absolute",
            inset: 0,
            background:
              "linear-gradient(180deg, rgba(3,12,20,0.18) 0%, rgba(3,12,20,0.08) 45%, rgba(3,12,20,0.24) 100%)",
          }}
        />

        <div
          style={{
            position: "absolute",
            inset: 0,
            background:
              "radial-gradient(ellipse at 50% 18%, rgba(255,255,255,0.08) 0%, transparent 52%), radial-gradient(ellipse at 60% 40%, rgba(24,199,204,0.04) 0%, transparent 85%)"
          }}
        />
      </div>

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


      {/* Content — full-screen centered */}
      <motion.div
        className="relative z-10 flex min-h-screen flex-col items-center justify-start pt-20 md:justify-center md:pt-0"
      >

        {/* CENTER: Songkran Logo + Islands */}
        <div className="flex flex-col items-center w-full px-4" style={{ gap: "8px" }}>

          {/* Songkran Festival Logo — dengan STEAM di belakang */}
          <div className="relative flex items-start md:items-center justify-center -mt-[2vh] md:mt-0" style={{ width: "100vw", height: "clamp(120px, 22vh, 300px)" }}>

            {/* STEAM — tepat di belakang logo, lebih besar */}
            <motion.img
              src="/images/STEAM.png"
              alt=""
              aria-hidden="true"
              style={{
                x: px,
                y: py,
                position: "absolute",
                width: "120vw",
                height: "clamp(160px, 26vh, 350px)",

                zIndex: 0,
                opacity: 0.92,
              }}
              initial={{ opacity: 0, scale: 0.95 }}
              animate={{ opacity: 0.92, scale: 1 }}
              transition={{ duration: 1.1, ease: "easeOut" }}
            />

            {/* Songkran Logo — di depan STEAM */}
            <motion.img
              src="/images/Songkran logo.png"
              alt="Songkran Festival"
              style={{
                x: px,
                y: py,
                filter: "drop-shadow(0 0 28px rgba(47,167,216,0.55))",
                position: "relative",
                width: "90vw",
                height: "clamp(130px, 24vh, 280px)",
                objectFit: "contain",
                zIndex: 1,
              }}
              initial={{ opacity: 0, y: -20 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ duration: 0.9, ease: "easeOut" }}
            />
          </div>

          {/* Islands Stage Illustration */}
          <motion.img
            src="/images/islands.png"
            alt="Festival Stage"
            style={{
              x: px2,
              y: py2,
              filter: "drop-shadow(0 0 24px rgba(47,167,216,0.35))",
              width: "100vw",
              height: "clamp(180px, 32vh, 400px)",
              objectFit: "contain",
            }}
            initial={{ opacity: 0, y: 30 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 1, delay: 0.15, ease: "easeOut" }}
          />

          {/* Event Info Block */}
          <motion.div
            className="flex w-full justify-center px-4 md:px-0"
            initial={{ opacity: 0, y: 16 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.85, delay: 0.45, ease: "easeOut" }}
          >
            <img
              src="/images/text.png"
              alt="12PM-12AM, 9-19 APRIL, @GF Forecourt Outdoor Carpark, 1 Utama, Malaysia's Premier Songkran Festival"
              className="w-full max-w-[820px]"
              style={{
                height: "auto",
                objectFit: "contain",
                filter: "drop-shadow(0 6px 20px rgba(0,0,0,0.35))",
              }}
            />
          </motion.div>

        </div>


        {/* CTA Register Button */}
        <motion.div
          initial={{ opacity: 0, scale: 0.9 }}
          animate={{ opacity: 1, scale: 1 }}
          transition={{ delay: 1.2, duration: 0.5, type: "spring" }}
          className="px-4 md:px-8 lg:px-14 mt-6 md:mb-8 relative z-20"
        >
          <button
            onClick={goToRegister}
            className="group relative overflow-hidden rounded-full px-8 py-3.5 md:px-10 md:py-4 transition-all hover:scale-105 active:scale-95"
            style={{
              background: 'linear-gradient(135deg, #0284C7, #0EA5E9)', boxShadow: '0 4px 14px rgba(2,132,199,0.35)'
            }}
          >
            {/* Hover: darken overlay */}
            <div className="absolute inset-0 bg-black/10 opacity-0 group-hover:opacity-100 transition-opacity duration-200 ease-out" />
            {/* Pressed: deeper darken */}
            <div className="absolute inset-0 bg-black/20 opacity-0 group-active:opacity-100 transition-opacity duration-100 ease-out" />
            <span style={{ ...TW, fontSize: "1rem", letterSpacing: "0.12em", color: "#ffffff", position: "relative", zIndex: 10 }}>
              REGISTER NOW
            </span>
          </button>
        </motion.div>
      </motion.div>

      {/* Countdown / Happening Now — bottom right (hidden for now) */}
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
      )}

      {/* Scroll indicator — bottom left */}
    </section>
  );
}
