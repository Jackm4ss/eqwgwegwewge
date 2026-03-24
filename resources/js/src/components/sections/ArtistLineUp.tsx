import { useRef, useState, useEffect, useCallback } from "react";
import { motion, useScroll, useTransform, useMotionValue, useSpring } from "motion/react";
import { Mic2, Disc3, Music4, ChevronLeft, ChevronRight } from "lucide-react";

import lineup1 from "@/assets/images/lineup_1.png";
import lineup2 from "@/assets/images/lineup_2.png";
import lineup3 from "@/assets/images/lineup_3.png";
import lineup4 from "@/assets/images/lineup_4.png";
import lineup5 from "@/assets/images/lineup_5.png";

const SYNE: React.CSSProperties = { fontFamily: "'Syne', sans-serif" };
const SG: React.CSSProperties = { fontFamily: "'Space Grotesk', sans-serif" };

import { WaterAnimation } from "../auth/WaterAnimation";

function LotusIcon({ className }: { className?: string }) {
  return (
    <svg viewBox="0 0 120 120" className={className} aria-hidden="true" fill="currentColor">
      <ellipse cx="60" cy="90" rx="8" ry="5" opacity="0.9" />
      <path d="M60 90 C60 90 38 68 38 46 C38 28 48 16 60 16 C72 16 82 28 82 46 C82 68 60 90 60 90Z" opacity="0.75" />
      <path d="M60 90 C60 90 22 72 16 50 C12 32 22 18 34 20 C46 22 60 90 60 90Z" opacity="0.6" />
      <path d="M60 90 C60 90 98 72 104 50 C108 32 98 18 86 20 C74 22 60 90 60 90Z" opacity="0.6" />
      <path d="M60 90 C60 90 12 55 18 34 C22 18 38 12 48 22 C58 32 60 90 60 90Z" opacity="0.45" />
      <path d="M60 90 C60 90 108 55 102 34 C98 18 82 12 72 22 C62 32 60 90 60 90Z" opacity="0.45" />
    </svg>
  );
}

type ArtistType = "DJ" | "LIVE" | "SPECIAL";

interface Artist {
  id: number;
  name: string;
  origin: string;
  type: ArtistType;
  day: string;
  image: string;
  headliner?: boolean;
  /** Vertical stagger for broken-grid depth */
  offsetY: number;
  /** Slight clockwise/counter-clockwise tilt */
  rotate: number;
}

const TYPE_CONFIG: Record<ArtistType, { label: string; color: string; Icon: React.ElementType }> = {
  DJ: { label: "DJ SET", color: "#00d4ff", Icon: Disc3 },
  LIVE: { label: "LIVE", color: "#2FA7D8", Icon: Mic2 },
  SPECIAL: { label: "SPECIAL GUEST", color: "#ffd740", Icon: Music4 },
};

const artists: Artist[] = [
  { id: 1, name: "DJ Nakorn", origin: "Bangkok, TH", type: "DJ", day: "Apr 9", image: lineup1, headliner: true, offsetY: 40, rotate: -2 },
  { id: 2, name: "Aria Siam", origin: "Chiang Mai, TH", type: "LIVE", day: "Apr 11", image: lineup2, headliner: true, offsetY: 0, rotate: 1.5 },
  { id: 3, name: "Ray Kasem", origin: "Kuala Lumpur, MY", type: "LIVE", day: "Apr 13", image: lineup3, offsetY: 60, rotate: -1.5 },
  { id: 4, name: "DJ Supanat", origin: "Phuket, TH", type: "DJ", day: "Apr 15", image: lineup4, offsetY: 20, rotate: 2 },
  { id: 5, name: "K-Force", origin: "Seoul, KR", type: "SPECIAL", day: "Apr 19", image: lineup5, headliner: true, offsetY: 35, rotate: -1.2 },
];

/* ─── Single Artist Card ───────────────────────────────── */
function ArtistCard({ artist, isActive }: { artist: Artist; isActive: boolean }) {
  const [hovered, setHovered] = useState(false);
  const cfg = TYPE_CONFIG[artist.type];
  const Icon = cfg.Icon;

  return (
    <div
      onMouseEnter={() => setHovered(true)}
      onMouseLeave={() => setHovered(false)}
      style={{
        width: "100%", // Fill the 3D slot width
        transition: "transform 0.4s ease, z-index 0s",
        zIndex: hovered || isActive ? 20 : 5,
        cursor: "pointer",
        perspective: "1000px",
      }}
    >
      <motion.div
        animate={{
          scale: isActive ? 1.05 : hovered ? 1.02 : 1,
          boxShadow: isActive
            ? `0 0 40px ${cfg.color}40, 0 20px 40px rgba(0,0,0,0.6)`
            : hovered
              ? `0 0 30px ${cfg.color}20, 0 12px 30px rgba(0,0,0,0.5)`
              : "0 4px 24px rgba(0,0,0,0.4)",
        }}
        transition={{ duration: 0.8, ease: [0.22, 1, 0.36, 1] }}
        className="relative overflow-hidden"
        style={{
          borderRadius: 20,
          border: isActive ? `1.5px solid ${cfg.color}80` : "1.5px solid rgba(255,255,255,0.05)",
          background: "#080812",
          // Flickering fixes
          WebkitBackfaceVisibility: "hidden",
          backfaceVisibility: "hidden",
          transformStyle: "preserve-3d",
          willChange: "transform, scale",
        }}
      >
        {/* Portrait Image */}
        <div className="relative overflow-hidden" style={{ height: "clamp(280px, 45vh, 460px)" }}>
          <motion.img
            src={artist.image}
            alt={artist.name}
            draggable={false}
            className="w-full h-full object-cover object-top"
            animate={{ scale: hovered || isActive ? 1.06 : 1 }}
            transition={{ duration: 0.6, ease: "easeOut" }}
          />
          {/* Gradient */}
          <div className="absolute inset-0" style={{ background: "linear-gradient(to top, rgba(5,5,8,1) 0%, rgba(5,5,8,0.3) 50%, rgba(5,5,8,0.05) 100%)" }} />

          {/* Day pill */}
          <div className="absolute top-3 left-3 flex items-center gap-1 px-2.5 py-1 rounded-full" style={{ background: "rgba(8,51,68,0.85)", border: "1px solid rgba(255,255,255,0.1)" }}>
            <div className="w-1.5 h-1.5 rounded-full" style={{ backgroundColor: cfg.color }} />
            <span style={{ ...SG, fontSize: "0.62rem", color: "#FFFFFF", fontWeight: 700, letterSpacing: "0.05em" }}>{artist.day}</span>
          </div>
          {/* Ghost number */}
          <div className="absolute bottom-16 left-3 pointer-events-none" style={{ ...SYNE, fontSize: "clamp(3rem,6vw,5rem)", fontWeight: 900, color: "rgba(255,255,255,0.06)", lineHeight: 1, letterSpacing: "-0.04em" }}>
            {String(artist.id).padStart(2, "0")}
          </div>
        </div>

        {/* Info */}
        <div className="px-4 py-3">
          <div className="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md mb-2" style={{ background: "rgba(255,255,255,0.05)", border: "1px solid rgba(255,255,255,0.1)" }}>
            <Icon size={10} color={cfg.color} />
            <span style={{ ...SG, fontSize: "0.58rem", color: "#FFFFFF", fontWeight: 700, letterSpacing: "0.12em" }}>{cfg.label}</span>
          </div>
          <motion.h3
            animate={{ color: isActive || hovered ? cfg.color : "#FFFFFF" }}
            transition={{ duration: 0.3 }}
            style={{ ...SYNE, fontWeight: 800, fontSize: "clamp(1.1rem, 2vw, 1.6rem)", lineHeight: 1.05, letterSpacing: "-0.02em" }}
          >
            {artist.name}
          </motion.h3>
          <p className="mt-0.5" style={{ ...SG, fontSize: "0.72rem", color: "rgba(255,255,255,0.5)" }}>{artist.origin}</p>
        </div>

        {/* Bottom glow */}
        <motion.div className="absolute bottom-0 left-0 right-0 h-[2px]"
          animate={{ opacity: isActive || hovered ? 1 : 0, scaleX: isActive || hovered ? 1 : 0 }}
          transition={{ duration: 0.4 }}
          style={{ background: `linear-gradient(to right, transparent, ${cfg.color}, transparent)`, transformOrigin: "center" }}
        />
      </motion.div>
    </div>
  );
}

/* ─── Main Section ─────────────────────────────────────── */
export function ArtistLineUp() {
  const sectionRef = useRef<HTMLDivElement>(null);
  const [current, setCurrent] = useState(0);
  const [paused, setPaused] = useState(false);
  const [windowWidth, setWindowWidth] = useState(typeof window !== "undefined" ? window.innerWidth : 1500);

  const { scrollYProgress } = useScroll({ target: sectionRef, offset: ["start end", "end start"] });
  const bgY = useTransform(scrollYProgress, [0, 1], ["0%", "12%"]);

  // ── 3D CoverFlow Logic ──
  // Map index to state
  const rotateToIndex = (index: number) => {
    setCurrent(index);
  };

  // ── Handle window resize for dynamic centering ──
  useEffect(() => {
    const handleResize = () => setWindowWidth(window.innerWidth);
    window.addEventListener("resize", handleResize);
    return () => window.removeEventListener("resize", handleResize);
  }, []);

  // ── Auto-advance: pauses when hovering ──
  useEffect(() => {
    if (paused) return;
    const id = setInterval(() => {
      const nextIndex = (current + 1) % artists.length;
      rotateToIndex(nextIndex);
    }, 4000);
    return () => clearInterval(id);
  }, [paused, current, artists.length]); // Added artists.length to dependencies

  const prev = () => rotateToIndex((current - 1 + artists.length) % artists.length);
  const next = () => rotateToIndex((current + 1) % artists.length);

  return (
    <section
      id="lineup"
      ref={sectionRef}
      className="relative w-full py-24 md:py-36 overflow-hidden"
      style={{ background: "#34D8F7", minHeight: "60vh" }}
    >
      {/* ── Background Elements (Synced with AboutFestival) ── */}
      <WaterAnimation />

      <div className="absolute inset-0 pointer-events-none overflow-hidden" aria-hidden="true" style={{ zIndex: 1 }}>
        <div className="absolute -top-32 -right-32 w-[500px] h-[500px] rounded-full opacity-[0.12]" style={{ background: 'radial-gradient(circle, #083344, transparent)' }} />
        <div className="absolute -bottom-40 -left-40 w-[600px] h-[600px] rounded-full opacity-[0.12]" style={{ background: 'radial-gradient(circle, #083344, transparent)' }} />
        <div className="absolute top-4 right-4 w-64 h-64 text-cyan-950 opacity-[0.12]">
          <LotusIcon className="w-full h-full" />
        </div>
        <div className="absolute bottom-4 left-4 w-48 h-48 text-cyan-950 opacity-[0.12] rotate-180">
          <LotusIcon className="w-full h-full" />
        </div>
        <svg className="absolute bottom-0 left-0 w-full" viewBox="0 0 1440 100" preserveAspectRatio="none">
          <motion.path
            d="M0,50 C360,100 720,0 1080,50 C1260,75 1350,25 1440,50 L1440,100 L0,100 Z"
            fill="rgba(8, 51, 68, 0.06)"
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

      {/* ── Blend top/bottom (Optional, adjusted for light background) ── */}
      <div className="absolute top-0 left-0 right-0 pointer-events-none z-10" style={{ height: 100, background: "linear-gradient(to bottom, #34D8F7 0%, transparent 100%)" }} />
      <div className="absolute bottom-0 left-0 right-0 pointer-events-none z-10" style={{ height: 100, background: "linear-gradient(to top, #34D8F7 0%, transparent 100%)" }} />

      {/* ── Section Header ── */}
      <div className="relative z-10 max-w-7xl mx-auto px-6 md:px-12 lg:px-16 mb-12 md:mb-16">
        <div className="grid lg:grid-cols-2 gap-8 items-end">
          <div>
            <motion.div className="flex items-center gap-3 mb-6" initial={{ opacity: 0 }} whileInView={{ opacity: 1 }} viewport={{ once: true }}>
              <div className="w-8 h-px bg-[#083344]" />
              <span style={{ ...SG, fontSize: "0.7rem", letterSpacing: "0.25em", color: "#083344", textTransform: "uppercase", fontWeight: 700 }}>Performing Artists</span>
            </motion.div>
            <div className="overflow-hidden">
              <motion.h2 initial={{ y: "100%", opacity: 0 }} whileInView={{ y: 0, opacity: 1 }} viewport={{ once: true }} transition={{ duration: 1, ease: [0.22, 1, 0.36, 1] }}
                style={{ ...SYNE, fontWeight: 800, fontSize: "clamp(2.4rem,5.5vw,5rem)", color: "#083344", lineHeight: 1, letterSpacing: "-0.03em" }}>
                The Stage
              </motion.h2>
            </div>
            <div className="overflow-hidden">
              <motion.h2 initial={{ y: "100%", opacity: 0 }} whileInView={{ y: 0, opacity: 1 }} viewport={{ once: true }} transition={{ duration: 1, ease: [0.22, 1, 0.36, 1], delay: 0.08 }}
                style={{ ...SYNE, fontWeight: 800, fontSize: "clamp(2.4rem,5.5vw,5rem)", color: "#FFFFFF", WebkitTextStroke: "2.5px #000000", lineHeight: 1, letterSpacing: "-0.03em" }}>
                Line Up
              </motion.h2>
            </div>
          </div>
          <motion.div initial={{ opacity: 0, y: 20 }} whileInView={{ opacity: 1, y: 0 }} viewport={{ once: true }} transition={{ delay: 0.3 }}>
            <p style={{ ...SG, fontSize: "0.9rem", color: "#164E63", lineHeight: 1.8, fontWeight: 500 }}>
              From global icons to local legends, experience the ultimate Songkran soundtrack across 11 pulse-pounding nights.
            </p>
          </motion.div>
        </div>
      </div>

      {/* ── 3D CoverFlow Container ── */}
      <div 
        className="relative h-[550px] md:h-[650px] w-full flex items-center justify-center py-12"
        style={{ perspective: "1200px" }}
        onMouseEnter={() => setPaused(true)}
        onMouseLeave={() => setPaused(false)}
      >
        {/* Invisible Drag Layer */}
        <motion.div
          drag="x"
          dragConstraints={{ left: 0, right: 0 }}
          dragElastic={0}
          onDragEnd={(_, info) => {
            if (info.offset.x < -50) next();
            else if (info.offset.x > 50) prev();
          }}
          className="absolute inset-0 z-50 cursor-grab active:cursor-grabbing"
        />
 
        <div className="relative w-full h-full flex items-center justify-center" style={{ transformStyle: "preserve-3d" }}>
          {artists.map((artist, i) => {
            // Calculate distance for CoverFlow math
            let distance = i - current;
            // Handle wraparound for smooth infinite flow
            if (distance > artists.length / 2) distance -= artists.length;
            if (distance < -artists.length / 2) distance += artists.length;
 
            const absDist = Math.abs(distance);
            const isActive = i === current;
 
            // CoverFlow Math
            // Center is front and center. Sides tilt and recede.
            const rotateY = distance === 0 ? 0 : distance > 0 ? -45 : 45;
            const translateZ = absDist * -180;
            const translateX = distance * (windowWidth > 768 ? 160 : 120);
            const scale = 1 - (absDist * (windowWidth > 768 ? 0.12 : 0.08));
            const opacity = 1 - (absDist * 0.25);
            const zIndex = 100 - Math.floor(absDist * 10);
 
            return (
              <motion.div
                key={artist.id}
                initial={false}
                animate={{
                  x: translateX,
                  z: translateZ,
                  rotateY: rotateY,
                  scale: scale,
                  opacity: Math.max(opacity, 0),
                }}
                transition={{
                  type: "spring",
                  stiffness: 100,
                  damping: 20,
                  mass: 1
                }}
                className="absolute"
                style={{
                  width: windowWidth > 768 ? 320 : 260,
                  zIndex: zIndex,
                  transformStyle: "preserve-3d",
                  backfaceVisibility: "hidden",
                }}
              >
                <ArtistCard artist={artist} isActive={isActive} />
              </motion.div>
            );
          })}
        </div>
      </div>

      {/* ── Controls ── */}
      <div className="relative z-10 max-w-7xl mx-auto px-6 md:px-12 lg:px-16 mt-4 flex items-center justify-between">
        {/* Dot indicators */}
        <div className="flex items-center gap-2">
          {artists.map((_, i) => (
            <button
              key={i}
              onClick={() => setCurrent(i)}
              style={{
                width: i === current ? 24 : 6,
                height: 6,
                borderRadius: 3,
                background: i === current ? "#083344" : "rgba(8, 51, 68, 0.2)",
                transition: "all 0.35s ease",
                border: "none",
                cursor: "pointer",
                padding: 0,
              }}
            />
          ))}
        </div>

        {/* Arrow buttons */}
        <div className="flex items-center gap-3">
          <motion.button
            whileHover={{ scale: 1.1 }} whileTap={{ scale: 0.92 }}
            onClick={prev}
            className="w-10 h-10 rounded-full flex items-center justify-center transition-colors"
            style={{ border: "1.5px solid rgba(8, 51, 68, 0.35)", background: "rgba(8, 51, 68, 0.07)", color: "#083344", cursor: "pointer" }}
          >
            <ChevronLeft size={18} />
          </motion.button>
          <motion.button
            whileHover={{ scale: 1.1 }} whileTap={{ scale: 0.92 }}
            onClick={next}
            className="w-10 h-10 rounded-full flex items-center justify-center transition-colors"
            style={{ border: "1.5px solid rgba(8, 51, 68, 0.35)", background: "rgba(8, 51, 68, 0.07)", color: "#083344", cursor: "pointer" }}
          >
            <ChevronRight size={18} />
          </motion.button>
        </div>
      </div>

      {/* ── Disclaimer ── */}
      {/* 
      <p className="relative z-10 text-center mt-8" style={{ ...SG, fontSize: "0.65rem", color: "rgba(237,232,220,0.15)", letterSpacing: "0.04em" }}>
        * Lineup subject to change without prior notice.
      </p>
      */}
    </section>
  );
}
