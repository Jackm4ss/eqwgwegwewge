import { useRef, useCallback } from "react";
import { motion, useInView, useScroll, useTransform } from "motion/react";
import aboutImg from "@/assets/images/about-songkran.jpg";
import img2 from "@/assets/images/crowd.png";
import img3 from "@/assets/images/stage.png";
import { Stack } from "../ui/Stack";
import { WaterAnimation } from "../auth/WaterAnimation";

const images = [
  { id: 1, src: aboutImg },
  { id: 2, src: img2 },
  { id: 3, src: img3 },
];

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

const SYNE: React.CSSProperties = { fontFamily: "'Syne', sans-serif" };
const SG: React.CSSProperties = { fontFamily: "'Space Grotesk', sans-serif" };

const IMG = aboutImg;

function RevealText({ children, delay = 0 }: { children: React.ReactNode; delay?: number }) {
  const ref = useRef(null);
  const inView = useInView(ref, { once: true, amount: 0.5 });
  return (
    <div ref={ref} style={{ overflow: "hidden" }}>
      <motion.div
        initial={{ y: "100%", opacity: 0 }}
        animate={inView ? { y: 0, opacity: 1 } : {}}
        transition={{ duration: 0.9, ease: [0.22, 1, 0.36, 1], delay }}
      >
        {children}
      </motion.div>
    </div>
  );
}

// Hand-drawn circle SVG
function HandCircle() {
  return (
    <svg viewBox="0 0 100 48" fill="none" style={{ position: "absolute", top: -10, left: -14, width: 128, height: 60, pointerEvents: "none" }}>
      <motion.ellipse
        cx="50" cy="24" rx="46" ry="20"
        stroke="#67E8F9" strokeWidth="1.5" strokeLinecap="round" fill="none"
        style={{ strokeDasharray: "none" }}
        initial={{ pathLength: 0, opacity: 0 }}
        whileInView={{ pathLength: 1, opacity: 1 }}
        viewport={{ once: true }}
        transition={{ duration: 1.5, ease: "easeOut", delay: 0.5 }}
      />
    </svg>
  );
}

// Scribble arrow
function ScribbleArrow() {
  return (
    <svg viewBox="0 0 60 30" fill="none" style={{ width: 60, height: 30 }}>
      <motion.path
        d="M2 15 Q15 8 30 15 Q42 20 55 12 M48 8 L57 13 L50 20"
        stroke="#67E8F9" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round" fill="none"
        initial={{ pathLength: 0, opacity: 0 }}
        whileInView={{ pathLength: 1, opacity: 1 }}
        viewport={{ once: true }}
        transition={{ duration: 1, ease: "easeOut", delay: 0.8 }}
      />
    </svg>
  );
}

const stats = [
  { num: "11", label: "Days of Celebration", unit: "" },
  { num: "12", label: "Hours Daily", unit: "HRS" },
  { num: "5", label: "Key Activities", unit: "+" },
];

const lines = [
  "We bring Thailand's iconic water",
  "festival to Malaysia — celebrating",
  "culture, tradition & togetherness.",
];

export function AboutFestival() {
  const ref = useRef(null);
  const inView = useInView(ref, { once: true, amount: 0.2 });
  const sectionRef = useRef(null);
  const { scrollYProgress } = useScroll({ target: sectionRef, offset: ["start end", "end start"] });
  const imgY = useTransform(scrollYProgress, [0, 1], ["-6%", "6%"]);

  const addRippleRef = useRef<((x: number, y: number) => void) | null>(null);

  const handleCanvasReady = useCallback((fn: (x: number, y: number) => void) => {
    addRippleRef.current = fn;
  }, []);

  const handlePageClick = useCallback((e: React.MouseEvent) => {
    if (addRippleRef.current) {
      addRippleRef.current(e.clientX, e.clientY);
    }
  }, []);

  return (
    <section id="about" ref={sectionRef} style={{ background: 'linear-gradient(145deg, #0C4A6E 0%, #0369A1 30%, #0284C7 60%, #0EA5E9 100%)' }} className="py-28 md:py-40 relative overflow-hidden" onClick={handlePageClick}>

      <WaterAnimation onCanvasReady={handleCanvasReady} />

      <div className="absolute inset-0 pointer-events-none overflow-hidden" aria-hidden="true" style={{ zIndex: 1 }}>
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

      {/* Background text watermark */}
      <div className="absolute inset-0 flex items-center justify-center pointer-events-none overflow-hidden" style={{ zIndex: 1 }}>
        <span style={{ ...SYNE, fontSize: "clamp(80px,18vw,200px)", fontWeight: 800, color: "rgba(255,255,255,0.04)", letterSpacing: "-0.05em", userSelect: "none", whiteSpace: "nowrap" }}>
          FESTIVAL
        </span>
      </div>

      <div className="max-w-7xl mx-auto px-6 md:px-12 lg:px-16 relative" style={{ zIndex: 2 }}>
        {/* Section label */}
        <motion.div
          initial={{ opacity: 0, x: -20 }}
          whileInView={{ opacity: 1, x: 0 }}
          viewport={{ once: true }}
          transition={{ duration: 0.7 }}
          className="flex items-center gap-3 mb-16 md:mb-18"
        >
          <div className="w-8 h-px bg-[#EDE8DC]" />
          <span style={{ ...SG, fontSize: "0.7rem", letterSpacing: "0.25em", color: "#EDE8DC", textTransform: "uppercase" }}>
            About the Festival
          </span>
        </motion.div>

        {/* ASYMMETRIC LAYOUT */}
        <div className="grid lg:grid-cols-12 gap-12 lg:gap-0 items-start">
          {/* Left — headline block (cols 1–7) */}
          <div className="lg:col-span-7 lg:pr-16">
            <div ref={ref}>
              {/* Big kinetic headline */}
              <div className="mb-8">
                <RevealText delay={0}>
                  <h2 style={{ ...SYNE, fontWeight: 800, fontSize: "clamp(2.4rem,5.5vw,5rem)", lineHeight: 1, color: "#EDE8DC", letterSpacing: "-0.03em" }}>
                    More Than
                  </h2>
                </RevealText>
                <div className="flex items-end gap-4">
                  <div style={{ overflow: "hidden" }}>
                    <motion.h2
                      initial={{ y: "105%", opacity: 0 }}
                      animate={inView ? { y: 0, opacity: 1 } : {}}
                      transition={{ duration: 0.9, ease: [0.22, 1, 0.36, 1], delay: 0.1 }}
                      style={{ ...SYNE, fontWeight: 800, fontSize: "clamp(2.4rem,5.5vw,5rem)", lineHeight: 1, color: "#67d7f9ff", letterSpacing: "-0.03em" }}
                    >
                      a Festival
                    </motion.h2>
                  </div>
                  <div className="pb-2 hidden md:block">
                    <ScribbleArrow />
                  </div>
                </div>
              </div>

              {/* Paragraph lines with stagger */}
              <div className="mb-10 space-y-1">
                {lines.map((line, i) => (
                  <RevealText key={i} delay={0.2 + i * 0.08}>
                    <p style={{ ...SG, fontSize: "clamp(1rem,1.5vw,1.2rem)", color: "rgba(255,255,255,0.9)", lineHeight: 1.6 }}>
                      {line}
                    </p>
                  </RevealText>
                ))}
              </div>

              <motion.p
                initial={{ opacity: 0 }}
                whileInView={{ opacity: 1 }}
                viewport={{ once: true }}
                transition={{ delay: 0.6, duration: 0.8 }}
                style={{ ...SG, fontSize: "0.9rem", color: "rgba(255,255,255,0.85)", lineHeight: 1.8, maxWidth: 480 }}
              >
                More than a festival, it is a vibrant cultural celebration where communities come together and every splash tells a story of joy, unity, and new beginnings — organised by <span style={{ color: "#67E8F9", fontWeight: "bold" }}>EQ Solutions</span>.
              </motion.p>

              {/* Stats row */}
              <div className="flex gap-8 md:gap-12 mt-12 pt-10 border-t border-white/20">
                {stats.map(({ num, label, unit }, i) => (
                  <motion.div
                    key={label}
                    initial={{ opacity: 0, y: 20 }}
                    whileInView={{ opacity: 1, y: 0 }}
                    viewport={{ once: true }}
                    transition={{ delay: 0.4 + i * 0.1, duration: 0.7 }}
                  >
                    <div className="relative inline-block">
                      {i === 0 && <HandCircle />}
                      <span style={{ ...SYNE, fontWeight: 800, fontSize: "clamp(2rem,4vw,3.5rem)", color: "#FFFFFF", lineHeight: 1 }}>
                        {num}
                        <span style={{ color: "#67E8F9", fontSize: "0.6em" }}>{unit}</span>
                      </span>
                    </div>
                    <p style={{ ...SG, fontSize: "0.75rem", color: "rgba(255,255,255,0.85)", letterSpacing: "0.06em", marginTop: 6, fontWeight: 500 }}>{label}</p>
                  </motion.div>
                ))}
              </div>
            </div>
          </div>

          {/* Right — image block (cols 8–12, offset, overlapping) */}
          <div className="lg:col-span-5 lg:pt-12 relative">
            <motion.div
              initial={{ opacity: 0, x: 40 }}
              whileInView={{ opacity: 1, x: 0 }}
              viewport={{ once: true }}
              transition={{ duration: 1.1, ease: [0.22, 1, 0.36, 1], delay: 0.3 }}
              className="relative"
            >
              {/* Main image */}
              <div className="relative w-full" style={{ height: "clamp(340px,50vw,520px)" }}>
                <Stack 
                  randomRotation={true} 
                  sensitivity={180} 
                  sendToBackOnClick={true} 
                  cardDimensions={{ width: "100%", height: "100%" }} 
                  cardsData={images} 
                  autoplay={true} 
                  autoplayDelay={1400} 
                />
              </div>

              {/* Floating badge — offset outside the card */}
              <motion.div
                initial={{ scale: 0.8, opacity: 0, rotate: -8 }}
                whileInView={{ scale: 1, opacity: 1, rotate: -6 }}
                viewport={{ once: true }}
                transition={{ delay: 0.7, duration: 0.6, ease: "backOut" }}
                className="absolute -bottom-6 -left-8 rounded-2xl px-5 py-4 shadow-[0_8px_32px_rgba(0,0,0,0.3)] bg-white/10 backdrop-blur-xl border-2 border-white/50"
                style={{ minWidth: 120, transform: "rotate(-6deg)" }}
              >
                <p style={{ ...SYNE, fontWeight: 800, fontSize: "2rem", color: "#FFFFFF", lineHeight: 1 }}>FREE</p>
                <p style={{ ...SG, fontSize: "0.75rem", color: "#BAE6FD", fontWeight: 700, letterSpacing: "0.1em" }}>ENTRY</p>
              </motion.div>

              {/* Tag top-right */}
              {/* 
              <motion.div
                initial={{ opacity: 0, y: -10 }}
                whileInView={{ opacity: 1, y: 0 }}
                viewport={{ once: true }}
                transition={{ delay: 0.8 }}
                className="absolute -top-5 right-4 flex items-center gap-2 rounded-full px-4 py-2 z-20"
                style={{ background: "rgba(24,199,204,0.12)", border: "1px solid rgba(24,199,204,0.25)", backdropFilter: "blur(4px)" }}
              >
                <span className="w-1.5 h-1.5 rounded-full bg-[#18C7CC] animate-pulse" />
                <span style={{ ...SG, fontSize: "0.7rem", color: "#18C7CC", letterSpacing: "0.1em" }}>APR 9–19</span>
              </motion.div>
              */}
            </motion.div>
          </div>
        </div>

        {/* Flags strip */}
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          whileInView={{ opacity: 1, y: 0 }}
          viewport={{ once: true }}
          transition={{ delay: 0.5 }}
          className="mt-32 md:mt-40 lg:mt-32 flex items-center gap-6 overflow-hidden"
        >
          <div className="flex items-center gap-3">
            <img src="https://flagcdn.com/th.svg" alt="Thailand" className="w-8 md:w-10 h-auto rounded-[2px] shadow-sm" />
            <div>
              <p style={{ ...SYNE, fontSize: "0.7rem", color: "rgba(255,255,255,0.7)", letterSpacing: "0.2em", textTransform: "uppercase", fontWeight: 600 }}>Thailand</p>
              <p style={{ ...SG, fontSize: "0.8rem", color: "rgba(255,255,255,0.9)" }}>Songkran Origin</p>
            </div>
          </div>
          <div className="flex-1 h-px bg-gradient-to-r from-white/10 via-[#67E8F9]/50 to-white/10 relative">
            <motion.div
              className="absolute top-1/2 -translate-y-1/2 w-2 h-2 rounded-full bg-[#67E8F9]"
              animate={{ left: ["0%", "100%", "0%"] }}
              transition={{ duration: 6, repeat: Infinity, ease: "linear" }}
            />
          </div>
          <div className="flex items-center gap-3">
            <div className="text-right">
              <p style={{ ...SYNE, fontSize: "0.7rem", color: "rgba(255,255,255,0.7)", letterSpacing: "0.2em", textTransform: "uppercase", fontWeight: 600 }}>Malaysia</p>
              <p style={{ ...SG, fontSize: "0.8rem", color: "rgba(255,255,255,0.9)" }}>Festival Host</p>
            </div>
            <img src="https://flagcdn.com/my.svg" alt="Malaysia" className="w-8 md:w-10 h-auto rounded-[2px] shadow-sm" />
          </div>
        </motion.div>
      </div>
    </section>
  );
}
