import {
  useRef,
  useCallback,
  type CSSProperties,
  type ReactNode,
  type MouseEvent,
} from "react";
import { motion, useInView } from "motion/react";
import { Stack } from "../ui/Stack";
import { WaterAnimation } from "../auth/WaterAnimation";
import { SECTION_BACKGROUND } from "./sectionContrastTheme";

const images = [
  { id: 1, src: "/images/foto-3.png" },
  { id: 2, src: "/images/foto-4.png" },
  { id: 3, src: "/images/foto-5.png" },
];

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

const TILT: CSSProperties = {
  fontFamily: "'Tilt Warp', sans-serif",
};

const TITLE_HIGHLIGHT_TEXT_STYLE: CSSProperties = {
  color: "#0284C7",
  textShadow: "2px 3px 0 rgba(31, 52, 71, 0.24), 0 3px 8px rgba(31, 52, 71, 0.10)",
};

const STAT_NUMBER_STYLE: CSSProperties = {
  color: "#349CD2 ",
  textShadow: "1px 2px 0 rgba(31, 52, 71, 0.18), 0 2px 6px rgba(31, 52, 71, 0.08)",
};

const STAT_LABEL_STYLE: CSSProperties = {
  color: "#349CD2",
  textShadow: "1px 2px 0 rgba(31, 52, 71, 0.18), 0 2px 6px rgba(31, 52, 71, 0.08)",
};

const PANEL_STYLE: CSSProperties = {
  background:
    "linear-gradient(rgba(255,255,255,0.12) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,0.12) 1px, transparent 1px), rgba(186, 230, 253, 0.78)",
  backgroundSize: "26px 26px, 26px 26px, auto",
  border: "1px solid rgba(255,255,255,0.45)",
  boxShadow: "0 18px 60px rgba(0,0,0,0.10)",
  backdropFilter: "blur(12px)",
  WebkitBackdropFilter: "blur(12px)",
};

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

function RevealText({
  children,
  delay = 0,
}: {
  children: ReactNode;
  delay?: number;
}) {
  const ref = useRef<HTMLDivElement | null>(null);
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

function ScribbleArrow() {
  return (
    <svg viewBox="0 0 60 30" fill="none" style={{ width: 60, height: 30 }}>
      <motion.path
        d="M2 15 Q15 8 30 15 Q42 20 55 12 M48 8 L57 13 L50 20"
        stroke="rgba(0,0,0,0.58)"
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

export function AboutFestival() {
  const ref = useRef<HTMLDivElement | null>(null);
  const inView = useInView(ref, { once: true, amount: 0.2 });
  const sectionRef = useRef<HTMLElement | null>(null);

  const addRippleRef = useRef<((x: number, y: number) => void) | null>(null);

  const handleCanvasReady = useCallback((fn: (x: number, y: number) => void) => {
    addRippleRef.current = fn;
  }, []);

  const handlePageClick = useCallback((e: MouseEvent<HTMLElement>) => {
    addRippleRef.current?.(e.clientX, e.clientY);
  }, []);

  return (
    <section
      id="about"
      ref={sectionRef}
      className="relative overflow-hidden py-28 md:py-40"
      style={SECTION_BACKGROUND}
      onClick={handlePageClick}
    >
      <WaterAnimation onCanvasReady={handleCanvasReady} />

      <div
        className="absolute inset-0 pointer-events-none overflow-hidden"
        aria-hidden="true"
        style={{ zIndex: 1 }}
      >
        <div
          className="absolute -top-32 -right-32 h-[500px] w-[500px] rounded-full opacity-[0.08]"
          style={{ background: "radial-gradient(circle, rgba(0,0,0,0.18), transparent)" }}
        />
        <div
          className="absolute -bottom-40 -left-40 h-[600px] w-[600px] rounded-full opacity-[0.08]"
          style={{ background: "radial-gradient(circle, rgba(0,0,0,0.18), transparent)" }}
        />
        <div className="absolute top-4 right-4 h-64 w-64 text-black opacity-[0.04]">
          <LotusIcon className="h-full w-full" />
        </div>
        <div className="absolute bottom-4 left-4 h-48 w-48 rotate-180 text-black opacity-[0.04]">
          <LotusIcon className="h-full w-full" />
        </div>

        <svg
          className="absolute bottom-0 left-0 w-full"
          viewBox="0 0 1440 100"
          preserveAspectRatio="none"
        >
          <motion.path
            d="M0,50 C360,100 720,0 1080,50 C1260,75 1350,25 1440,50 L1440,100 L0,100 Z"
            fill="rgba(0, 0, 0, 0.03)"
            animate={{
              y: [0, -6, 0],
              opacity: [1, 0.8, 1],
            }}
            transition={{ duration: 7, repeat: Infinity, ease: "easeInOut" }}
          />
        </svg>
      </div>

      <div
        className="absolute inset-0 flex items-center justify-center overflow-hidden pointer-events-none"
        style={{ zIndex: 1 }}
      >
        <span
          style={{
            ...TILT,
            fontSize: "clamp(70px,16vw,180px)",
            fontWeight: 400,
            color: "rgba(0, 0, 0, 0.035)",
            letterSpacing: "0.01em",
            userSelect: "none",
            whiteSpace: "nowrap",
          }}
        >
          FESTIVAL
        </span>
      </div>

      <div className="relative mx-auto max-w-7xl px-6 md:px-12 lg:px-16" style={{ zIndex: 2 }}>
        <div className="rounded-[32px] p-6 md:rounded-[40px] md:p-10 lg:p-14" style={PANEL_STYLE}>
          <motion.div
            initial={{ opacity: 0, x: -20 }}
            whileInView={{ opacity: 1, x: 0 }}
            viewport={{ once: true }}
            transition={{ duration: 0.7 }}
            className="mb-12 flex items-center gap-3 md:mb-16"
          >
            <div className="h-px w-8 bg-black/50" />
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

          <div className="grid items-start gap-12 lg:grid-cols-12 lg:gap-0">
            <div className="lg:col-span-7 lg:pr-16">
              <div ref={ref}>
                <div className="mb-8">
                  <RevealText delay={0}>
                    <h2
                      style={{
                        ...TILT,
                        fontSize: "clamp(2.2rem,5vw,4.6rem)",
                        lineHeight: 0.98,
                        ...TITLE_HIGHLIGHT_TEXT_STYLE,
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
                          ...TITLE_HIGHLIGHT_TEXT_STYLE,
                        }}
                      >
                        a Festival
                      </motion.h2>
                    </div>

                    <div className="hidden pb-2 md:block">
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
                          fontSize: "clamp(1.6rem,1.35vw,1.15rem)",
                          color: "#349CD2 ",
                          lineHeight: 1.5,

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
                    fontSize: "1rem",
                    color: "#349CD2 ",
                    lineHeight: 1.8,
                    maxWidth: 520,

                  }}
                >
                  More than a festival, it is a vibrant cultural celebration where communities come
                  together and every splash tells a story of joy, unity, and new beginnings.
                </motion.p>

                <div className="mt-12 flex flex-wrap gap-8 border-t border-black/10 pt-10 md:gap-12">
                  {stats.map(({ num, label, unit }, i) => (
                    <motion.div
                      key={label}
                      initial={{ opacity: 0, y: 20 }}
                      whileInView={{ opacity: 1, y: 0 }}
                      viewport={{ once: true }}
                      transition={{ delay: 0.4 + i * 0.1, duration: 0.7 }}
                    >
                      <div className="relative inline-block">
                        <span
                          style={{
                            ...TILT,
                            fontSize: "clamp(1.8rem,3.5vw,3rem)",
                            lineHeight: 1,
                            ...STAT_NUMBER_STYLE,
                          }}
                        >
                          {num}
                          <span
                            style={{
                              ...STAT_NUMBER_STYLE,
                              fontSize: "0.5em",
                            }}
                          >
                            {unit}
                          </span>
                        </span>
                      </div>

                      <p
                        style={{
                          ...TILT,
                          fontSize: "0.7rem",
                          letterSpacing: "0.04em",
                          marginTop: 8,
                          ...STAT_LABEL_STYLE,
                        }}
                      >
                        {label}
                      </p>
                    </motion.div>
                  ))}
                </div>
              </div>
            </div>

            <div className="relative lg:col-span-5 lg:pt-12">
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
                  className="absolute -bottom-6 -left-8 rounded-2xl border border-white/70 bg-white/55 px-5 py-4 shadow-[0_8px_32px_rgba(0,0,0,0.12)] backdrop-blur-xl"
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
            className="mt-20 flex items-center gap-6 overflow-hidden md:mt-24"
          >
            <div className="flex items-center gap-3">
              <img
                src="https://flagcdn.com/th.svg"
                alt="Thailand"
                loading="lazy"
                decoding="async"
                className="h-auto w-8 rounded-[2px] shadow-sm md:w-10"
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

            <div className="relative h-px flex-1 bg-black/15">
              <motion.div
                className="absolute top-1/2 h-4 w-64 -translate-y-1/2 rounded-full bg-white/35 blur-2xl"
                animate={{ left: ["0%", "100%", "0%"] }}
                transition={{ duration: 6, repeat: Infinity, ease: "linear" }}
                style={{ x: "-50%" }}
              />
              <motion.div
                className="absolute top-1/2 h-[2px] w-32 -translate-y-1/2 rounded-full bg-white/80 blur-sm"
                animate={{ left: ["0%", "100%", "0%"] }}
                transition={{ duration: 6, repeat: Infinity, ease: "linear" }}
                style={{ x: "-50%" }}
              />
              <motion.div
                className="absolute top-1/2 z-10 h-2.5 w-2.5 -translate-y-1/2 rounded-full"
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
                loading="lazy"
                decoding="async"
                className="h-auto w-8 rounded-[2px] shadow-sm md:w-10"
              />
            </div>
          </motion.div>
        </div>
      </div>
    </section>
  );
}
