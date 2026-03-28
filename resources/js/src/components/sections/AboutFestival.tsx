import { useRef, useCallback } from "react";
import { motion, useInView } from "motion/react";
import { Stack } from "../ui/Stack";
import { WaterAnimation } from "../auth/WaterAnimation";

const images = [
  { id: 1, src: "/images/foto-3.png" },
  { id: 2, src: "/images/foto-4.png" },
  { id: 3, src: "/images/foto-5.png" },
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

const TILT: React.CSSProperties = {
  fontFamily: "'Tilt Warp', sans-serif",
};

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

function HandCircle() {
  return (
    <>
      {/* Temporarily disabled: viewBox="0 0 100 48" */}
      <svg
        fill="none"
        style={{ position: "absolute", top: -10, left: -14, width: 128, height: 60, pointerEvents: "none" }}
      >
        <motion.ellipse
          cx="50"
          cy="24"
          rx="46"
          ry="20"
          stroke="rgba(0,0,0,0.55)"
          strokeWidth="1.5"
          strokeLinecap="round"
          fill="none"
          style={{ strokeDasharray: "none" }}
          initial={{ pathLength: 0, opacity: 0 }}
          whileInView={{ pathLength: 1, opacity: 1 }}
          viewport={{ once: true }}
          transition={{ duration: 1.5, ease: "easeOut", delay: 0.5 }}
        />
      </svg>
    </>
  );
}

function ScribbleArrow() {
  return (
    <svg viewBox="0 0 60 30" fill="none" style={{ width: 60, height: 30 }}>
      <motion.path
        d="M2 15 Q15 8 30 15 Q42 20 55 12 M48 8 L57 13 L50 20"
        stroke="rgba(0,0,0,0.6)"
        strokeWidth="1.5"
        strokeLinecap="round"
        strokeLinejoin="round"
        fill="none"
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
    <section
      id="about"
      ref={sectionRef}
      className="py-28 md:py-40 relative overflow-hidden"
      onClick={handlePageClick}
    >
      <WaterAnimation onCanvasReady={handleCanvasReady} />

      <div
        className="absolute inset-0 pointer-events-none overflow-hidden"
        aria-hidden="true"
        style={{ zIndex: 1 }}
      >
        <div
          className="absolute -top-32 -right-32 w-[500px] h-[500px] rounded-full opacity-[0.1]"
          style={{ background: "radial-gradient(circle, rgba(0,0,0,0.18), transparent)" }}
        />
        <div
          className="absolute -bottom-40 -left-40 w-[600px] h-[600px] rounded-full opacity-[0.1]"
          style={{ background: "radial-gradient(circle, rgba(0,0,0,0.18), transparent)" }}
        />
        <div className="absolute top-4 right-4 w-64 h-64 text-black opacity-[0.05]">
          <LotusIcon className="w-full h-full" />
        </div>
        <div className="absolute bottom-4 left-4 w-48 h-48 text-black opacity-[0.05] rotate-180">
          <LotusIcon className="w-full h-full" />
        </div>
        <svg className="absolute bottom-0 left-0 w-full" viewBox="0 0 1440 100" preserveAspectRatio="none">
          <motion.path
            d="M0,50 C360,100 720,0 1080,50 C1260,75 1350,25 1440,50 L1440,100 L0,100 Z"
            fill="rgba(0, 0, 0, 0.04)"
            animate={{
              d: [
                "M0,50 C360,100 720,0 1080,50 C1260,75 1350,25 1440,50 L1440,100 L0,100 Z",
                "M0,30 C360,0 720,80 1080,30 C1260,5 1350,70 1440,30 L1440,100 L0,100 Z",
                "M0,50 C360,100 720,0 1080,50 C1260,75 1350,25 1440,50 L1440,100 L0,100 Z",
              ],
            }}
            transition={{ duration: 7, repeat: Infinity, ease: "easeInOut" }}
          />
        </svg>
      </div>

      <div
        className="absolute inset-0 flex items-center justify-center pointer-events-none overflow-hidden"
        style={{ zIndex: 1 }}
      >
        <span
          style={{
            ...TILT,
            fontSize: "clamp(70px,16vw,180px)",
            fontWeight: 400,
            color: "rgba(0, 0, 0, 0.05)",
            letterSpacing: "0.01em",
            userSelect: "none",
            whiteSpace: "nowrap",
          }}
        >
          FESTIVAL
        </span>
      </div>

      <div className="max-w-7xl mx-auto px-6 md:px-12 lg:px-16 relative" style={{ zIndex: 2 }}>
        <div
          className="rounded-[32px] md:rounded-[40px] p-6 md:p-10 lg:p-14"
          style={{
            background:
              "linear-gradient(rgba(255,255,255,0.16) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,0.16) 1px, transparent 1px), rgba(186, 230, 253, 0.72)",
            backgroundSize: "26px 26px, 26px 26px, auto",
            border: "1px solid rgba(255,255,255,0.45)",
            boxShadow: "0 18px 60px rgba(0,0,0,0.10)",
            backdropFilter: "blur(12px)",
            WebkitBackdropFilter: "blur(12px)",
          }}
        >
          <motion.div
            initial={{ opacity: 0, x: -20 }}
            whileInView={{ opacity: 1, x: 0 }}
            viewport={{ once: true }}
            transition={{ duration: 0.7 }}
            className="flex items-center gap-3 mb-12 md:mb-16"
          >
            <div className="w-8 h-px bg-black/50" />
            <span
              style={{
                ...TILT,
                fontSize: "0.72rem",
                letterSpacing: "0.16em",
                color: "#111111",
                textTransform: "uppercase",
              }}
            >
              About the Festival
            </span>
          </motion.div>

          <div className="grid lg:grid-cols-12 gap-12 lg:gap-0 items-start">
            <div className="lg:col-span-7 lg:pr-16">
              <div ref={ref}>
                <div className="mb-8">
                  <RevealText delay={0}>
                    <h2
                      style={{
                        ...TILT,
                        fontSize: "clamp(2.2rem,5vw,4.6rem)",
                        lineHeight: 0.98,
                        color: "#111111",
                      }}
                    >
                      More Than
                    </h2>
                  </RevealText>

                  <div className="flex items-end gap-4">
                    <div style={{ overflow: "hidden" }}>
                      <motion.h2
                        initial={{ y: "105%", opacity: 0 }}
                        animate={inView ? { y: 0, opacity: 1 } : {}}
                        transition={{ duration: 0.9, ease: [0.22, 1, 0.36, 1], delay: 0.1 }}
                        style={{
                          ...TILT,
                          fontSize: "clamp(2.2rem,5vw,4.6rem)",
                          lineHeight: 0.98,
                          color: "#111111",
                          textShadow: "0 1px 0 rgba(255,255,255,0.25)",
                        }}
                      >
                        a Festival
                      </motion.h2>
                    </div>

                    <div className="pb-2 hidden md:block">
                      <ScribbleArrow />
                    </div>
                  </div>
                </div>

                <div className="mb-8 space-y-1">
                  {lines.map((line, i) => (
                    <RevealText key={i} delay={0.2 + i * 0.08}>
                      <p
                        style={{
                          ...TILT,
                          fontSize: "clamp(0.95rem,1.35vw,1.15rem)",
                          color: "rgba(0,0,0,0.82)",
                          lineHeight: 1.55,
                        }}
                      >
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
                  style={{
                    ...TILT,
                    fontSize: "0.82rem",
                    color: "rgba(0,0,0,0.72)",
                    lineHeight: 1.8,
                    maxWidth: 520,
                  }}
                >
                  More than a festival, it is a vibrant cultural celebration where communities come
                  together and every splash tells a story of joy, unity, and new beginnings —
                  organised by{" "}
                  <span style={{ color: "#111111" }}>EQ Solutions</span>.
                </motion.p>

                <div className="flex gap-8 md:gap-12 mt-12 pt-10 border-t border-black/10 flex-wrap">
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
                        <span
                          style={{
                            ...TILT,
                            fontSize: "clamp(1.8rem,3.5vw,3rem)",
                            color: "#111111",
                            lineHeight: 1,
                          }}
                        >
                          {num}
                          <span style={{ color: "#111111", fontSize: "0.5em" }}>{unit}</span>
                        </span>
                      </div>

                      <p
                        style={{
                          ...TILT,
                          fontSize: "0.7rem",
                          color: "rgba(0,0,0,0.72)",
                          letterSpacing: "0.04em",
                          marginTop: 8,
                        }}
                      >
                        {label}
                      </p>
                    </motion.div>
                  ))}
                </div>
              </div>
            </div>

            <div className="lg:col-span-5 lg:pt-12 relative">
              <motion.div
                initial={{ opacity: 0, x: 40 }}
                whileInView={{ opacity: 1, x: 0 }}
                viewport={{ once: true }}
                transition={{ duration: 1.1, ease: [0.22, 1, 0.36, 1], delay: 0.3 }}
                className="relative"
              >
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

                <motion.div
                  initial={{ scale: 0.8, opacity: 0, rotate: -8 }}
                  whileInView={{ scale: 1, opacity: 1, rotate: -6 }}
                  viewport={{ once: true }}
                  transition={{ delay: 0.7, duration: 0.6, ease: "backOut" }}
                  className="absolute -bottom-6 -left-8 rounded-2xl px-5 py-4 shadow-[0_8px_32px_rgba(0,0,0,0.12)] bg-white/55 backdrop-blur-xl border border-white/70"
                  style={{ minWidth: 128, transform: "rotate(-6deg)" }}
                >
                  <p
                    style={{
                      ...TILT,
                      fontSize: "1.7rem",
                      color: "#111111",
                      lineHeight: 1,
                    }}
                  >
                    FREE
                  </p>
                  <p
                    style={{
                      ...TILT,
                      fontSize: "0.72rem",
                      color: "rgba(0,0,0,0.72)",
                      letterSpacing: "0.08em",
                      marginTop: 4,
                    }}
                  >
                    ENTRY
                  </p>
                </motion.div>
              </motion.div>
            </div>
          </div>

          <motion.div
            initial={{ opacity: 0, y: 20 }}
            whileInView={{ opacity: 1, y: 0 }}
            viewport={{ once: true }}
            transition={{ delay: 0.5 }}
            className="mt-20 md:mt-24 flex items-center gap-6 overflow-hidden"
          >
            <div className="flex items-center gap-3">
              <img
                src="https://flagcdn.com/th.svg"
                alt="Thailand"
                className="w-8 md:w-10 h-auto rounded-[2px] shadow-sm"
              />
              <div>
                <p
                  style={{
                    ...TILT,
                    fontSize: "0.68rem",
                    color: "#111111",
                    letterSpacing: "0.12em",
                    textTransform: "uppercase",
                  }}
                >
                  Thailand
                </p>
                <p
                  style={{
                    ...TILT,
                    fontSize: "0.72rem",
                    color: "rgba(0,0,0,0.72)",
                  }}
                >
                  Songkran Origin
                </p>
              </div>
            </div>

            <div className="flex-1 h-px bg-black/15 relative">
              <motion.div
                className="absolute top-1/2 -translate-y-1/2 h-4 w-64 bg-white/35 blur-2xl rounded-full"
                animate={{ left: ["0%", "100%", "0%"] }}
                transition={{ duration: 6, repeat: Infinity, ease: "linear" }}
                style={{ x: "-50%" }}
              />
              <motion.div
                className="absolute top-1/2 -translate-y-1/2 h-[2px] w-32 bg-white/80 blur-sm rounded-full"
                animate={{ left: ["0%", "100%", "0%"] }}
                transition={{ duration: 6, repeat: Infinity, ease: "linear" }}
                style={{ x: "-50%" }}
              />
              <motion.div
                className="absolute top-1/2 -translate-y-1/2 w-2.5 h-2.5 rounded-full z-10"
                style={{
                  background: "radial-gradient(circle at 30% 30%, #FFFFFF 0%, #BAE6FD 100%)",
                  boxShadow:
                    "0 0 10px 2px rgba(255, 255, 255, 0.8), 0 0 20px 4px rgba(255, 255, 255, 0.4)",
                  x: "-50%",
                }}
                animate={{ left: ["0%", "100%", "0%"] }}
                transition={{ duration: 6, repeat: Infinity, ease: "linear" }}
              />
            </div>

            <div className="flex items-center gap-3">
              <div className="text-right">
                <p
                  style={{
                    ...TILT,
                    fontSize: "0.68rem",
                    color: "#111111",
                    letterSpacing: "0.12em",
                    textTransform: "uppercase",
                  }}
                >
                  Malaysia
                </p>
                <p
                  style={{
                    ...TILT,
                    fontSize: "0.72rem",
                    color: "rgba(0,0,0,0.72)",
                  }}
                >
                  Festival Host
                </p>
              </div>
              <img
                src="https://flagcdn.com/my.svg"
                alt="Malaysia"
                className="w-8 md:w-10 h-auto rounded-[2px] shadow-sm"
              />
            </div>
          </motion.div>
        </div>
      </div>
    </section>
  );
}
