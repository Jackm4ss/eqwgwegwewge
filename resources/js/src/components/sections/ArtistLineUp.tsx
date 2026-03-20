import { useRef, useState, useEffect, useCallback } from "react";
import { motion, useScroll, useTransform } from "motion/react";
import { Mic2, Disc3, Music4, ChevronLeft, ChevronRight } from "lucide-react";

import lineup1 from "@/assets/images/lineup_1.png";
import lineup2 from "@/assets/images/lineup_2.png";
import lineup3 from "@/assets/images/lineup_3.png";
import lineup4 from "@/assets/images/lineup_4.png";
import lineup5 from "@/assets/images/lineup_5.png";

const SYNE: React.CSSProperties = { fontFamily: "'Syne', sans-serif" };
const SG: React.CSSProperties   = { fontFamily: "'Space Grotesk', sans-serif" };

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
  DJ:      { label: "DJ SET",        color: "#00d4ff", Icon: Disc3  },
  LIVE:    { label: "LIVE",          color: "#2FA7D8", Icon: Mic2   },
  SPECIAL: { label: "SPECIAL GUEST", color: "#ffd740", Icon: Music4 },
};

const artists: Artist[] = [
  { id: 1, name: "DJ Nakorn",  origin: "Bangkok, TH",      type: "DJ",      day: "Apr 9",  image: lineup1, headliner: true,  offsetY: 40,  rotate: -2   },
  { id: 2, name: "Aria Siam",  origin: "Chiang Mai, TH",   type: "LIVE",    day: "Apr 11", image: lineup2, headliner: true,  offsetY: 0,   rotate:  1.5 },
  { id: 3, name: "Ray Kasem",  origin: "Kuala Lumpur, MY", type: "LIVE",    day: "Apr 13", image: lineup3,                   offsetY: 60,  rotate: -1.5 },
  { id: 4, name: "DJ Supanat", origin: "Phuket, TH",       type: "DJ",      day: "Apr 15", image: lineup4,                   offsetY: 20,  rotate:  2   },
  { id: 5, name: "K-Force",    origin: "Seoul, KR",        type: "SPECIAL", day: "Apr 19", image: lineup5, headliner: true,  offsetY: 35,  rotate: -1.2 },
];

/* ─── Bouncing Spotlight Orb ───────────────────────────── */
interface OrbProps { color: string; size: number; duration: number; x1: string; y1: string; x2: string; y2: string; blur: number; }
function SpotlightOrb({ color, size, duration, x1, y1, x2, y2, blur }: OrbProps) {
  return (
    <motion.div
      className="absolute pointer-events-none"
      style={{ width: size, height: size, borderRadius: "50%", background: `radial-gradient(circle at 40% 40%, ${color} 0%, transparent 70%)`, filter: `blur(${blur}px)`, left: 0, top: 0, willChange: "transform" }}
      animate={{ x: [x1, x2, x1], y: [y1, y2, y1] }}
      transition={{ duration, repeat: Infinity, ease: "linear" }}
    />
  );
}

/* ─── Single Artist Card ───────────────────────────────── */
function ArtistCard({ artist, isActive }: { artist: Artist; isActive: boolean }) {
  const [hovered, setHovered] = useState(false);
  const cfg = TYPE_CONFIG[artist.type];
  const Icon = cfg.Icon;

  return (
    <div
      data-card
      onMouseEnter={() => setHovered(true)}
      onMouseLeave={() => setHovered(false)}
      style={{
        // Responsive card width via CSS clamp
        width: "clamp(220px, 58vw, 320px)",
        flexShrink: 0,
        // Broken-grid: small tilt + vertical stagger (scaled down for responsiveness)
        transform: `translateY(${artist.offsetY * 0.5}px) rotate(${artist.rotate}deg)`,
        transition: "transform 0.4s ease, z-index 0s",
        zIndex: hovered || isActive ? 20 : 5,
        cursor: "pointer",
        // Negative margin for overlap
        marginRight: "clamp(-24px, -2.5vw, -12px)",
        scrollSnapAlign: "start",
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
          <div className="absolute top-3 left-3 flex items-center gap-1 px-2.5 py-1 rounded-full" style={{ background: "rgba(5,5,8,0.8)", border: "1px solid rgba(255,255,255,0.1)" }}>
            <div className="w-1.5 h-1.5 rounded-full" style={{ backgroundColor: cfg.color }} />
            <span style={{ ...SG, fontSize: "0.62rem", color: "rgba(237,232,220,0.75)", fontWeight: 600, letterSpacing: "0.05em" }}>{artist.day}</span>
          </div>
          {/* Ghost number */}
          <div className="absolute bottom-16 left-3 pointer-events-none" style={{ ...SYNE, fontSize: "clamp(3rem,6vw,5rem)", fontWeight: 900, color: "rgba(237,232,220,0.04)", lineHeight: 1, letterSpacing: "-0.04em" }}>
            {String(artist.id).padStart(2, "0")}
          </div>
        </div>

        {/* Info */}
        <div className="px-4 py-3">
          <div className="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md mb-2" style={{ background: `${cfg.color}18`, border: `1px solid ${cfg.color}30` }}>
            <Icon size={10} color={cfg.color} />
            <span style={{ ...SG, fontSize: "0.58rem", color: cfg.color, fontWeight: 700, letterSpacing: "0.12em" }}>{cfg.label}</span>
          </div>
          <motion.h3
            animate={{ color: isActive || hovered ? cfg.color : "#EDE8DC" }}
            transition={{ duration: 0.3 }}
            style={{ ...SYNE, fontWeight: 800, fontSize: "clamp(1.1rem, 2vw, 1.6rem)", lineHeight: 1.05, letterSpacing: "-0.02em" }}
          >
            {artist.name}
          </motion.h3>
          <p className="mt-0.5" style={{ ...SG, fontSize: "0.72rem", color: "rgba(237,232,220,0.3)" }}>{artist.origin}</p>
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
      setCurrent(prev => (prev + 1) % artists.length);
    }, 2500);
    return () => clearInterval(id);
  }, [paused]);

  // ── Calculate X-Offset ──
  // Card width is clamp(220px, 58vw, 320px)
  // Margin-right is clamp(-24px, -2.5vw, -12px)
  const getCardWidth = () => {
    const vw = windowWidth;
    let w = Math.max(220, Math.min(vw * 0.58, 320));
    let m = Math.max(-24, Math.min(vw * -0.025, -12));
    return w + m; 
  };

  const cardFullWidth = getCardWidth();
  // We want the 'current' card to be dead-center.
  // The 'x' offset for the track should be: (center_of_screen) - (center_of_current_card) - (offset_to_card_i)
  const trackX = (windowWidth / 2) - (cardFullWidth / 2) - (current * cardFullWidth);

  const prev = () => setCurrent(prev => (prev - 1 + artists.length) % artists.length);
  const next = () => setCurrent(prev => (prev + 1) % artists.length);

  return (
    <section
      id="lineup"
      ref={sectionRef}
      className="relative w-full py-24 md:py-36 overflow-hidden"
      style={{ background: "#070810", minHeight: "60vh" }}
    >
      {/* ── Blend top/bottom ── */}
      <div className="absolute top-0 left-0 right-0 pointer-events-none z-10" style={{ height: 100, background: "linear-gradient(to bottom, #070810 0%, transparent 100%)" }} />
      <div className="absolute bottom-0 left-0 right-0 pointer-events-none z-10" style={{ height: 100, background: "linear-gradient(to top, #070810 0%, transparent 100%)" }} />

      {/* ── Spotlight orbs ── */}
      <motion.div className="absolute inset-0 pointer-events-none" style={{ y: bgY }}>
        <SpotlightOrb color="rgba(47,167,216,0.18)"  size={650} blur={90} duration={12} x1="-10%"  y1="0%"  x2="40%"  y2="30%" />
        <SpotlightOrb color="rgba(156,108,255,0.12)" size={550} blur={90} duration={18} x1="50%"  y1="40%" x2="0%"   y2="10%"  />
      </motion.div>

      {/* ── Section Header ── */}
      <div className="relative z-10 max-w-7xl mx-auto px-6 md:px-12 lg:px-16 mb-12 md:mb-16">
        <div className="grid lg:grid-cols-2 gap-8 items-end">
          <div>
            <motion.div className="flex items-center gap-3 mb-6" initial={{ opacity: 0 }} whileInView={{ opacity: 1 }} viewport={{ once: true }}>
              <div className="w-8 h-px bg-[#2FA7D8]" />
              <span style={{ ...SG, fontSize: "0.7rem", letterSpacing: "0.25em", color: "#2FA7D8", textTransform: "uppercase" }}>Performing Artists</span>
            </motion.div>
            <div className="overflow-hidden">
              <motion.h2 initial={{ y: "100%", opacity: 0 }} whileInView={{ y: 0, opacity: 1 }} viewport={{ once: true }} transition={{ duration: 1, ease: [0.22, 1, 0.36, 1] }}
                style={{ ...SYNE, fontWeight: 800, fontSize: "clamp(2.4rem,5.5vw,5rem)", color: "#EDE8DC", lineHeight: 1, letterSpacing: "-0.03em" }}>
                The Stage
              </motion.h2>
            </div>
            <div className="overflow-hidden">
              <motion.h2 initial={{ y: "100%", opacity: 0 }} whileInView={{ y: 0, opacity: 1 }} viewport={{ once: true }} transition={{ duration: 1, ease: [0.22, 1, 0.36, 1], delay: 0.08 }}
                style={{ ...SYNE, fontWeight: 800, fontSize: "clamp(2.4rem,5.5vw,5rem)", color: "transparent", WebkitTextStroke: "1.5px rgba(237,232,220,0.22)", lineHeight: 1, letterSpacing: "-0.03em" }}>
                Line Up
              </motion.h2>
            </div>
          </div>
          <motion.div initial={{ opacity: 0, y: 20 }} whileInView={{ opacity: 1, y: 0 }} viewport={{ once: true }} transition={{ delay: 0.3 }}>
            <p style={{ ...SG, fontSize: "0.9rem", color: "rgba(237,232,220,0.35)", lineHeight: 1.8 }}>
              From global icons to local legends, experience the ultimate Songkran soundtrack across 11 pulse-pounding nights.
            </p>
          </motion.div>
        </div>
      </div>

      {/* ── Motion Track Carousel ── */}
      <div
        className="relative z-20 cursor-grab active:cursor-grabbing"
        onMouseEnter={() => setPaused(true)}
        onMouseLeave={() => setPaused(false)}
      >
        <motion.div
          drag="x"
          dragConstraints={{
            left: (windowWidth / 2) - (cardFullWidth / 2) - ((artists.length - 1) * cardFullWidth),
            right: (windowWidth / 2) - (cardFullWidth / 2),
          }}
          onDragStart={() => setPaused(true)}
          onDragEnd={(_, info) => {
            const offset = info.offset.x;
            const velocity = info.velocity.x;
            
            // Snap to next/prev if drag is significant or fast
            if (offset < -50 || velocity < -500) {
              next();
            } else if (offset > 50 || velocity > 500) {
              prev();
            } else {
              // Stay on current
              setCurrent(current);
            }
          }}
          animate={{ x: trackX }}
          transition={{ type: "spring", stiffness: 120, damping: 22, mass: 1 }}
          className="flex items-start"
          style={{
            paddingTop: 40,
            paddingBottom: 100,
            willChange: "transform",
            transformStyle: "preserve-3d",
          }}
        >
          {artists.map((artist, i) => (
            <ArtistCard key={artist.id} artist={artist} isActive={i === current} />
          ))}
        </motion.div>
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
                background: i === current ? TYPE_CONFIG[artists[i].type].color : "rgba(255,255,255,0.2)",
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
            style={{ border: "1.5px solid rgba(0,212,255,0.35)", background: "rgba(0,212,255,0.07)", color: "#00d4ff", cursor: "pointer" }}
          >
            <ChevronLeft size={18} />
          </motion.button>
          <motion.button
            whileHover={{ scale: 1.1 }} whileTap={{ scale: 0.92 }}
            onClick={next}
            className="w-10 h-10 rounded-full flex items-center justify-center transition-colors"
            style={{ border: "1.5px solid rgba(0,212,255,0.35)", background: "rgba(0,212,255,0.07)", color: "#00d4ff", cursor: "pointer" }}
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
