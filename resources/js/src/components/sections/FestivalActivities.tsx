import { useRef, useEffect, useState, useCallback } from "react";
import { motion, useScroll, useTransform, useSpring } from "motion/react";
import { Crown, Music, Disc3, Waves, Star, Zap, Gamepad2, ShoppingBag, ChevronLeft, ChevronRight } from "lucide-react";

const BADGE_STYLES: Record<string, string> = {
  HIGHLIGHT: "bg-[#ffd740] text-[#0c0c0e]",
  DAILY: "bg-[#00e5a0] text-[#0c0c0e]",
  NIGHTLY: "bg-[#9c6cff] text-white",
  SPECIAL: "bg-[#ff9f43] text-[#0c0c0e]",
};

interface Activity {
  id: number;
  icon: React.ReactNode;
  badge: string;
  title: string;
  description: string;
  schedule: string;
  image: string;
  size?: "wide" | "normal";
}

const activities: Activity[] = [
  {
    id: 1,
    icon: <Crown size={20} />,
    badge: "HIGHLIGHT",
    title: "Opening Ceremony",
    description: "With Thai Ambassador",
    schedule: "Fri, 10th April",
    image: "https://images.unsplash.com/photo-1658051592420-8459926a5a82?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHxvcGVuaW5nJTIwY2VyZW1vbnklMjBmZXN0aXZhbCUyMGNlbGVicmF0aW9uJTIwY3Jvd2R8ZW58MXx8fHwxNzczNzA0NDc3fDA&ixlib=rb-4.1.0&q=80&w=1080&utm_source=figma&utm_medium=referral",
    size: "wide",
  },
  {
    id: 2,
    icon: <Music size={20} />,
    badge: "DAILY",
    title: "Live Band",
    description: "Every evening throughout all 11 nights",
    schedule: "Nightly, 9th–19th",
    image: "https://images.unsplash.com/photo-1757439160077-dd5d62a4d851?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHxsaXZlJTIwYmFuZCUyMGNvbmNlcnQlMjBuaWdodCUyMHN0YWdlJTIwcGVyZm9ybWFuY2V8ZW58MXx8fHwxNzczNzA0NDc4fDA&ixlib=rb-4.1.0&q=80&w=1080&utm_source=figma&utm_medium=referral",
  },
  {
    id: 3,
    icon: <Disc3 size={20} />,
    badge: "NIGHTLY",
    title: "DJ Performances",
    description: "Dance under the stars every night",
    schedule: "Every Night",
    image: "https://images.unsplash.com/photo-1758179764880-7513421d202a?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHxESiUyMHBlcmZvcm1hbmNlJTIwbmlnaHRjbHViJTIwbmVvbiUyMGxpZ2h0c3xlbnwxfHx8fDE3NzM2MjQwNjN8MA&ixlib=rb-4.1.0&q=80&w=1080&utm_source=figma&utm_medium=referral",
  },
  {
    id: 4,
    icon: <Waves size={20} />,
    badge: "DAILY",
    title: "Water Play",
    description: "Iconic Songkran splashes — joyful, symbolic, unforgettable",
    schedule: "5PM+ Daily",
    image: "https://images.unsplash.com/photo-1693670984742-c008c239daf2?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHx3YXRlciUyMHNwbGFzaCUyMGZlc3RpdmFsJTIwZnVuJTIwY3Jvd2QlMjBzdW1tZXJ8ZW58MXx8fHwxNzczNzA0NDgzfDA&ixlib=rb-4.1.0&q=80&w=1080&utm_source=figma&utm_medium=referral",
    size: "wide",
  },
  {
    id: 5,
    icon: <Star size={20} />,
    badge: "SPECIAL",
    title: "Kids Fashion Show",
    description: "Thai-inspired looks by our youngest stars",
    schedule: "11th April",
    image: "https://images.unsplash.com/photo-1607196475666-3cff9708edd5?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHxraWRzJTIwZmFzaGlvbiUyMHNob3clMjBydW53YXklMjBjaGlsZHJlbnxlbnwxfHx8fDE3NzM3MDQ0Nzl8MA&ixlib=rb-4.1.0&q=80&w=1080&utm_source=figma&utm_medium=referral",
  },
  {
    id: 6,
    icon: <Zap size={20} />,
    badge: "SPECIAL",
    title: "Guest Appearance",
    description: "A mystery guest that will light up the stage",
    schedule: "11th April (TBC)",
    image: "https://images.unsplash.com/photo-1764874299006-bf4266427ec9?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHxjZWxlYnJpdHklMjBndWVzdCUyMGFwcGVhcmFuY2UlMjBzcG90bGlnaHQlMjBzdGFnZXxlbnwxfHx8fDE3NzM3MDQ0Nzl8MA&ixlib=rb-4.1.0&q=80&w=1080&utm_source=figma&utm_medium=referral",
  },
  {
    id: 7,
    icon: <Gamepad2 size={20} />,
    badge: "DAILY",
    title: "Games & Water Sports",
    description: "Interactive competitions for all ages",
    schedule: "Daily, 9th–19th",
    image: "https://images.unsplash.com/photo-1758775632590-bfc2946a37d7?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHx3YXRlciUyMHNwb3J0cyUyMGdhbWVzJTIwY29tcGV0aXRpb24lMjBvdXRkb29yfGVufDF8fHx8MTc3MzcwNDQ3OXww&ixlib=rb-4.1.0&q=80&w=1080&utm_source=figma&utm_medium=referral",
  },
  {
    id: 8,
    icon: <ShoppingBag size={20} />,
    badge: "DAILY",
    title: "Food & Retail Bazaar",
    description: "Authentic Thai street food & curated retail",
    schedule: "All Day, Daily",
    image: "https://images.unsplash.com/photo-1677297256774-5412e81427c0?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHxmb29kJTIwYmF6YWFyJTIwc3RyZWV0JTIwZm9vZCUyMG1hcmtldCUyMGZlc3RpdmFsfGVufDF8fHx8MTc3MzcwNDQ4MHww&ixlib=rb-4.1.0&q=80&w=1080&utm_source=figma&utm_medium=referral",
    size: "wide",
  },
];

// Tilt card component
function TiltCard({ activity }: { activity: Activity }) {
  const cardRef = useRef<HTMLDivElement>(null);
  const [tilt, setTilt] = useState({ x: 0, y: 0 });
  const [isHovered, setIsHovered] = useState(false);
  const [imgLoaded, setImgLoaded] = useState(false);

  const handleMouseMove = useCallback((e: React.MouseEvent<HTMLDivElement>) => {
    const card = cardRef.current;
    if (!card) return;
    const rect = card.getBoundingClientRect();
    const x = (e.clientX - rect.left) / rect.width - 0.5;
    const y = (e.clientY - rect.top) / rect.height - 0.5;
    setTilt({ x: y * 14, y: -x * 14 });
  }, []);

  const handleMouseLeave = useCallback(() => {
    setTilt({ x: 0, y: 0 });
    setIsHovered(false);
  }, []);

  const springConfig = { stiffness: 200, damping: 20 };
  const rotateX = useSpring(tilt.x, springConfig);
  const rotateY = useSpring(tilt.y, springConfig);

  const isWide = activity.size === "wide";

  return (
    <motion.div
      ref={cardRef}
      onMouseMove={handleMouseMove}
      onMouseEnter={() => setIsHovered(true)}
      onMouseLeave={handleMouseLeave}
      style={{
        rotateX: tilt.x,
        rotateY: tilt.y,
        transformStyle: "preserve-3d",
        perspective: 800,
      }}
      whileHover={{ scale: 1.03, zIndex: 10 }}
      transition={{ duration: 0.3, ease: "easeOut" }}
      className={`relative flex-shrink-0 rounded-2xl overflow-hidden cursor-pointer group ${
        isWide ? "w-[420px] md:w-[480px]" : "w-[300px] md:w-[340px]"
      } h-[380px] select-none`}
    >
      {/* Background image */}
      <div
        className="absolute inset-0 transition-transform duration-700 ease-out"
        style={{
          transform: isHovered ? "scale(1.12)" : "scale(1.0)",
        }}
      >
        <img
          src={activity.image}
          alt={activity.title}
          onLoad={() => setImgLoaded(true)}
          className={`w-full h-full object-cover transition-all duration-500 ${
            isHovered ? "grayscale-0" : "grayscale brightness-75"
          } ${imgLoaded ? "opacity-100" : "opacity-0"}`}
        />
      </div>

      {/* Gradient overlay */}
      <div
        className={`absolute inset-0 transition-opacity duration-500 ${
          isHovered ? "opacity-60" : "opacity-80"
        }`}
        style={{
          background:
            "linear-gradient(to top, rgba(12,12,14,0.98) 0%, rgba(12,12,14,0.5) 55%, rgba(12,12,14,0.1) 100%)",
        }}
      />

      {/* Cyan glow border on hover */}
      <motion.div
        className="absolute inset-0 rounded-2xl pointer-events-none"
        animate={{
          boxShadow: isHovered
            ? "inset 0 0 0 1.5px rgba(0,212,255,0.6), 0 0 40px rgba(0,212,255,0.2)"
            : "inset 0 0 0 1px rgba(255,255,255,0.06)",
        }}
        transition={{ duration: 0.3 }}
      />

      {/* Content */}
      <div className="absolute inset-0 p-5 flex flex-col justify-between" style={{ transform: "translateZ(20px)" }}>
        {/* Top row */}
        <div className="flex items-start justify-between">
          <div
            className={`w-9 h-9 rounded-xl flex items-center justify-center transition-colors duration-300 ${
              isHovered ? "bg-[#00d4ff] text-[#0c0c0e]" : "bg-white/10 text-white/70"
            }`}
          >
            {activity.icon}
          </div>
          <span
            className={`text-[10px] tracking-widest px-2.5 py-1 rounded-full ${BADGE_STYLES[activity.badge]}`}
            style={{ fontWeight: 700, letterSpacing: "0.12em" }}
          >
            {activity.badge}
          </span>
        </div>

        {/* Bottom content */}
        <div>
          <motion.h3
            className="text-white mb-1.5"
            style={{
              fontSize: isWide ? "1.5rem" : "1.25rem",
              fontWeight: 800,
              lineHeight: 1.2,
              textShadow: "0 2px 12px rgba(0,0,0,0.6)",
            }}
          >
            {activity.title}
          </motion.h3>
          <motion.p
            className="text-white/70 mb-3 line-clamp-2"
            style={{ fontSize: "0.82rem", lineHeight: 1.4 }}
            animate={{ opacity: isHovered ? 1 : 0.7 }}
            transition={{ duration: 0.3 }}
          >
            {activity.description}
          </motion.p>

          {/* Schedule pill */}
          <motion.div
            className="flex items-center gap-1.5"
            animate={{ y: isHovered ? 0 : 4, opacity: isHovered ? 1 : 0.8 }}
            transition={{ duration: 0.3 }}
          >
            <div
              className="w-1.5 h-1.5 rounded-full"
              style={{ backgroundColor: "#00d4ff" }}
            />
            <span className="text-[#00d4ff]" style={{ fontSize: "0.78rem", fontWeight: 600 }}>
              {activity.schedule}
            </span>
          </motion.div>
        </div>
      </div>

      {/* Shine sweep on hover */}
      <motion.div
        className="absolute inset-0 pointer-events-none"
        style={{
          background:
            "linear-gradient(105deg, transparent 40%, rgba(255,255,255,0.07) 50%, transparent 60%)",
          backgroundSize: "200% 100%",
        }}
        animate={{
          backgroundPosition: isHovered ? "200% 0" : "-200% 0",
        }}
        transition={{ duration: 0.6, ease: "easeOut" }}
      />
    </motion.div>
  );
}

// Parallax section wrapper
function ParallaxSection({ children }: { children: React.ReactNode }) {
  const ref = useRef<HTMLDivElement>(null);
  const { scrollYProgress } = useScroll({
    target: ref,
    offset: ["start end", "end start"],
  });
  const y = useTransform(scrollYProgress, [0, 1], [60, -60]);

  return (
    <div ref={ref} className="relative overflow-hidden">
      <motion.div style={{ y }}>{children}</motion.div>
    </div>
  );
}

export function FestivalActivities() {
  const scrollRef = useRef<HTMLDivElement>(null);
  const [canScrollLeft, setCanScrollLeft] = useState(false);
  const [canScrollRight, setCanScrollRight] = useState(true);
  const [activeIndex, setActiveIndex] = useState(0);
  const [scrollSnaps, setScrollSnaps] = useState<number[]>([]);

  const checkScroll = useCallback(() => {
    const el = scrollRef.current;
    if (!el) return;
    
    const maxScroll = el.scrollWidth - el.clientWidth;
    setCanScrollLeft(el.scrollLeft > 10);
    setCanScrollRight(el.scrollLeft < maxScroll - 10);
    
    // Estimate card width based on screen size + gap
    const cardWidth = window.innerWidth >= 768 ? 440 : 320; 
    
    // Calculate how many snap points exist
    const snapCount = Math.max(1, Math.ceil(maxScroll / cardWidth) + 1);
    
    // Create snap positions and remove duplicates (so the last dot doesn't snap to same position twice)
    const rawSnaps = Array.from({ length: snapCount }).map((_, i) => Math.min(i * cardWidth, maxScroll));
    const uniqueSnaps = Array.from(new Set(rawSnaps));
    setScrollSnaps(uniqueSnaps);
    
    // Find the closest snap to current scroll position
    let closestIndex = 0;
    let minDiff = Infinity;
    uniqueSnaps.forEach((snap, i) => {
      const diff = Math.abs(snap - el.scrollLeft);
      if (diff < minDiff) {
        minDiff = diff;
        closestIndex = i;
      }
    });

    // Automatically snap to last dot if we reached the end
    if (el.scrollLeft >= maxScroll - 10) {
      closestIndex = uniqueSnaps.length - 1;
    }

    setActiveIndex(closestIndex);
  }, []);

  useEffect(() => {
    const el = scrollRef.current;
    if (!el) return;
    
    el.addEventListener("scroll", checkScroll, { passive: true });
    checkScroll();

    const onWheel = (e: WheelEvent) => {
      // Ignore native horizontal shift-scroll
      if (e.shiftKey) return;
      // Ignore if purely horizontal wheel motion
      if (Math.abs(e.deltaY) === 0) return;

      const isAtLeftEdge = el.scrollLeft <= 0;
      const isAtRightEdge = el.scrollLeft >= el.scrollWidth - el.clientWidth - 1;

      // Allow natural vertical scroll when at the boundaries pulling outward
      if (isAtLeftEdge && e.deltaY < 0) return;
      if (isAtRightEdge && e.deltaY > 0) return;

      // Prevent vertical page scroll, and scroll horizontally instead
      e.preventDefault();
      el.scrollLeft += e.deltaY;
    };

    el.addEventListener("wheel", onWheel, { passive: false });

    return () => {
      el.removeEventListener("scroll", checkScroll);
      el.removeEventListener("wheel", onWheel);
    };
  }, [checkScroll]);

  const scroll = (dir: "left" | "right") => {
    const el = scrollRef.current;
    if (!el) return;
    el.scrollBy({ left: dir === "left" ? -400 : 400, behavior: "smooth" });
  };

  const sectionRef = useRef<HTMLDivElement>(null);
  const { scrollYProgress } = useScroll({
    target: sectionRef,
    offset: ["start end", "center center"],
  });
  const titleY = useTransform(scrollYProgress, [0, 1], [50, 0]);
  const titleOpacity = useTransform(scrollYProgress, [0, 0.5], [0, 1]);

  return (
    <section
      id="activities"
      ref={sectionRef}
      className="relative py-20 overflow-hidden"
      style={{ background: "#0c0c0e" }}
    >
      {/* Ambient background blobs */}
      <div
        className="absolute pointer-events-none"
        style={{
          top: "10%",
          left: "-10%",
          width: "500px",
          height: "500px",
          background: "radial-gradient(circle, rgba(0,180,255,0.06) 0%, transparent 70%)",
          borderRadius: "50%",
          filter: "blur(40px)",
        }}
      />
      <div
        className="absolute pointer-events-none"
        style={{
          bottom: "5%",
          right: "-5%",
          width: "400px",
          height: "400px",
          background: "radial-gradient(circle, rgba(156,108,255,0.07) 0%, transparent 70%)",
          borderRadius: "50%",
          filter: "blur(50px)",
        }}
      />

      {/* Header */}
      <div className="px-8 md:px-16 mb-12">
        <div className="flex flex-col md:flex-row md:items-end md:justify-between gap-6">
          <div>
            <motion.div 
              className="flex items-center gap-3 mb-5"
              initial={{ opacity: 0, x: -20 }}
              whileInView={{ opacity: 1, x: 0 }}
              viewport={{ once: true }}
            >
              <div className="w-8 h-[2px]" style={{ backgroundColor: "#00d4ff" }} />
              <span
                className="tracking-[0.2em] uppercase"
                style={{ color: "#00d4ff", fontSize: "0.72rem", fontWeight: 600 }}
              >
                What's On
              </span>
            </motion.div>

            <div style={{ overflow: "hidden" }}>
              <motion.h2
                initial={{ y: "100%", opacity: 0 }}
                whileInView={{ y: 0, opacity: 1 }}
                viewport={{ once: true }}
                transition={{ duration: 1, ease: [0.22, 1, 0.36, 1] }}
                className="text-white leading-none mb-0"
                style={{ fontSize: "clamp(3rem, 8vw, 6rem)", fontWeight: 900, lineHeight: 0.95 }}
              >
                Festival
              </motion.h2>
            </div>
            <div style={{ overflow: "hidden" }}>
              <motion.h2
                initial={{ y: "100%", opacity: 0 }}
                whileInView={{ y: 0, opacity: 1 }}
                viewport={{ once: true }}
                transition={{ duration: 1, ease: [0.22, 1, 0.36, 1], delay: 0.08 }}
                style={{
                  fontSize: "clamp(3rem, 8vw, 6rem)",
                  fontWeight: 900,
                  lineHeight: 0.95,
                  WebkitTextStroke: "2px #00d4ff",
                  color: "transparent",
                  letterSpacing: "-0.02em",
                }}
              >
                Activities
              </motion.h2>
            </div>
          </div>
          <motion.p
            initial={{ opacity: 0, y: 20 }}
            whileInView={{ opacity: 1, y: 0 }}
            viewport={{ once: true }}
            transition={{ delay: 0.4 }}
            className="text-white/50 max-w-sm"
            style={{ fontSize: "0.95rem", lineHeight: 1.6, paddingBottom: "0.5rem" }}
          >
            From iconic water splashes to electrifying DJ nights — eleven days of pure celebration
            with something for every soul.
          </motion.p>
        </div>
      </div>

      {/* Carousel container */}
      <div className="relative">
        {/* Left fade */}
        <div
          className="absolute left-0 top-0 bottom-0 w-24 pointer-events-none z-10"
          style={{
            background: "linear-gradient(to right, #0c0c0e, transparent)",
            opacity: canScrollLeft ? 1 : 0,
            transition: "opacity 0.3s",
          }}
        />
        {/* Right fade */}
        <div
          className="absolute right-0 top-0 bottom-0 w-24 pointer-events-none z-10"
          style={{
            background: "linear-gradient(to left, #0c0c0e, transparent)",
            opacity: canScrollRight ? 1 : 0,
            transition: "opacity 0.3s",
          }}
        />

        {/* Scrollable row */}
        <div
          ref={scrollRef}
          className="flex gap-5 overflow-x-auto pl-8 md:pl-16 pr-8 md:pr-16 pt-4 pb-8"
          style={{
            scrollbarWidth: "none",
            msOverflowStyle: "none",
            cursor: "grab",
            WebkitOverflowScrolling: "touch",
          }}
        >
          {activities.map((activity, i) => (
            <motion.div
              key={activity.id}
              initial={{ opacity: 0, y: 40 }}
              whileInView={{ opacity: 1, y: 0 }}
              viewport={{ once: true, margin: "-50px" }}
              transition={{ duration: 0.5, delay: i * 0.08, ease: "easeOut" }}
            >
              <TiltCard activity={activity} />
            </motion.div>
          ))}
        </div>
      </div>

      {/* Controls row */}
      <div className="flex items-center justify-between px-8 md:px-16 mt-8">
        {/* Dot indicators */}
        <div className="flex items-center gap-2">
          {scrollSnaps.map((snapPosition, i) => (
            <button
              key={i}
              onClick={() => {
                const el = scrollRef.current;
                if (!el) return;
                el.scrollTo({ left: snapPosition, behavior: "smooth" });
              }}
              className="rounded-full transition-all duration-300"
              style={{
                width: i === activeIndex ? "24px" : "6px",
                height: "6px",
                backgroundColor: i === activeIndex ? "#00d4ff" : "rgba(255,255,255,0.2)",
              }}
            />
          ))}
        </div>

        {/* Arrow buttons */}
        <div className="flex items-center gap-3">
          <motion.button
            whileHover={{ scale: 1.1 }}
            whileTap={{ scale: 0.95 }}
            onClick={() => scroll("left")}
            disabled={!canScrollLeft}
            className="w-12 h-12 rounded-full flex items-center justify-center transition-all duration-300"
            style={{
              border: `1.5px solid ${canScrollLeft ? "rgba(0,212,255,0.5)" : "rgba(255,255,255,0.1)"}`,
              background: canScrollLeft ? "rgba(0,212,255,0.08)" : "rgba(255,255,255,0.02)",
              color: canScrollLeft ? "#00d4ff" : "rgba(255,255,255,0.2)",
              cursor: canScrollLeft ? "pointer" : "not-allowed",
            }}
          >
            <ChevronLeft size={20} />
          </motion.button>
          <motion.button
            whileHover={{ scale: 1.1 }}
            whileTap={{ scale: 0.95 }}
            onClick={() => scroll("right")}
            disabled={!canScrollRight}
            className="w-12 h-12 rounded-full flex items-center justify-center transition-all duration-300"
            style={{
              border: `1.5px solid ${canScrollRight ? "rgba(0,212,255,0.5)" : "rgba(255,255,255,0.1)"}`,
              background: canScrollRight ? "rgba(0,212,255,0.08)" : "rgba(255,255,255,0.02)",
              color: canScrollRight ? "#00d4ff" : "rgba(255,255,255,0.2)",
              cursor: canScrollRight ? "pointer" : "not-allowed",
            }}
          >
            <ChevronRight size={20} />
          </motion.button>
        </div>
      </div>

      {/* Disclaimer */}
      {/* 
      <p
        className="text-center mt-10 text-white/25"
        style={{ fontSize: "0.72rem", letterSpacing: "0.04em" }}
      >
        * Activities are tentative and subject to change at the organizer's discretion.
      </p>
      */}
    </section>
  );
}
