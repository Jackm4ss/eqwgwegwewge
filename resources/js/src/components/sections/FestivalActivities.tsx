import { useRef, useEffect, useState, useCallback } from "react";
import { motion, useScroll } from "motion/react";
import {
  Crown,
  Music,
  Disc3,
  Waves,
  Star,
  Zap,
  Gamepad2,
  ShoppingBag,
  ChevronLeft,
  ChevronRight,
} from "lucide-react";

const TILT: React.CSSProperties = { fontFamily: "'Tilt Warp', sans-serif" };

const BADGE_STYLES: Record<string, React.CSSProperties> = {
  HIGHLIGHT: {
    background: "rgba(255, 215, 64, 0.9)",
    color: "#111111",
  },
  DAILY: {
    background: "rgba(0, 229, 160, 0.88)",
    color: "#111111",
  },
  NIGHTLY: {
    background: "rgba(156, 108, 255, 0.88)",
    color: "#ffffff",
  },
  SPECIAL: {
    background: "rgba(255, 159, 67, 0.9)",
    color: "#111111",
  },
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
    image:
      "/images/foto-4.png",
    size: "wide",
  },
  {
    id: 2,
    icon: <Music size={20} />,
    badge: "DAILY",
    title: "Live Band",
    description: "Every evening throughout all 11 nights",
    schedule: "Nightly, 9th–19th",
    image:
      "/images/foto-2.png",
  },
  {
    id: 3,
    icon: <Disc3 size={20} />,
    badge: "NIGHTLY",
    title: "DJ Performances",
    description: "Dance under the stars every night",
    schedule: "Every Night",
    image:
      "/images/foto-3.png",
  },
  {
    id: 4,
    icon: <Waves size={20} />,
    badge: "DAILY",
    title: "Water Play",
    description: "Iconic Songkran splashes — joyful, symbolic, unforgettable",
    schedule: "5PM+ Daily",
    image:
      "/images/foto-1.png", size: "wide",
  },
  {
    id: 5,
    icon: <Star size={20} />,
    badge: "SPECIAL",
    title: "Kids Fashion Show",
    description: "Thai-inspired looks by our youngest stars",
    schedule: "11th April",
    image:
      "/images/foto-5.png",
  },
  {
    id: 6,
    icon: <Zap size={20} />,
    badge: "SPECIAL",
    title: "Guest Appearance",
    description: "A mystery guest that will light up the stage",
    schedule: "11th April (TBC)",
    image:
      "/images/foto-6.png",
  },
  {
    id: 7,
    icon: <Gamepad2 size={20} />,
    badge: "DAILY",
    title: "Games & Water Sports",
    description: "Interactive competitions for all ages",
    schedule: "Daily, 9th–19th",
    image:
      "/images/foto-7.png",
  },
  {
    id: 8,
    icon: <ShoppingBag size={20} />,
    badge: "DAILY",
    title: "Food & Retail Bazaar",
    description: "Authentic Thai street food & curated retail",
    schedule: "All Day, Daily",
    image:
      "https://images.unsplash.com/photo-1677297256774-5412e81427c0?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHxmb29kJTIwYmF6YWFyJTIwc3RyZWV0JTIwZm9vZCUyMG1hcmtldCUyMGZlc3RpdmFsfGVufDF8fHx8MTc3MzcwNDQ4MHww&ixlib=rb-4.1.0&q=80&w=1080&utm_source=figma&utm_medium=referral",
    size: "wide",
  },
];

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
    setTilt({ x: y * 12, y: -x * 12 });
  }, []);

  const handleMouseLeave = useCallback(() => {
    setTilt({ x: 0, y: 0 });
    setIsHovered(false);
  }, []);

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
      whileHover={{ scale: 1.02, y: -4, zIndex: 10 }}
      transition={{ duration: 0.28, ease: "easeOut" }}
      className={`relative flex-shrink-0 rounded-[24px] overflow-hidden cursor-pointer group ${isWide ? "w-[420px] md:w-[480px]" : "w-[300px] md:w-[340px]"
        } h-[380px] select-none`}
    >
      <div
        className="absolute inset-0 transition-transform duration-700 ease-out"
        style={{
          transform: isHovered ? "scale(1.08)" : "scale(1)",
        }}
      >
        <img
          src={activity.image}
          alt={activity.title}
          onLoad={() => setImgLoaded(true)}
          className={`w-full h-full object-cover transition-all duration-500 ${isHovered ? "grayscale-0 brightness-100" : "grayscale-[15%] brightness-90"
            } ${imgLoaded ? "opacity-100" : "opacity-0"}`}
        />
      </div>

      <div
        className={`absolute inset-0 transition-opacity duration-500 ${isHovered ? "opacity-90" : "opacity-80"
          }`}
        style={{
          background:
            "linear-gradient(to top, rgba(240,248,255,0.96) 0%, rgba(240,248,255,0.6) 48%, rgba(240,248,255,0.16) 100%)",
        }}
      />

      <motion.div
        className="absolute inset-0 rounded-[24px] pointer-events-none"
        animate={{
          boxShadow: isHovered
            ? "inset 0 0 0 1.5px rgba(0,0,0,0.18), 0 18px 40px rgba(0,0,0,0.14)"
            : "inset 0 0 0 1px rgba(255,255,255,0.30)",
        }}
        transition={{ duration: 0.3 }}
      />

      <div
        className="absolute inset-0 p-5 flex flex-col justify-between"
        style={{ transform: "translateZ(20px)" }}
      >
        <div className="flex items-start justify-between">
          <div
            className="w-9 h-9 rounded-xl flex items-center justify-center transition-colors duration-300"
            style={{
              background: isHovered ? "rgba(0,0,0,0.82)" : "rgba(255,255,255,0.72)",
              color: isHovered ? "#ffffff" : "#111111",
              border: "1px solid rgba(255,255,255,0.4)",
              boxShadow: "0 8px 20px rgba(0,0,0,0.08)",
            }}
          >
            {activity.icon}
          </div>

          <span
            className="text-[10px] tracking-widest px-2.5 py-1 rounded-full"
            style={{
              ...TILT,
              letterSpacing: "0.12em",
              ...BADGE_STYLES[activity.badge],
              boxShadow: "0 8px 20px rgba(0,0,0,0.08)",
            }}
          >
            {activity.badge}
          </span>
        </div>

        <div>
          <motion.h3
            style={{
              ...TILT,
              fontSize: isWide ? "1.35rem" : "1.15rem",
              color: "#111111",
              lineHeight: 1.2,
              marginBottom: "0.45rem",
              textShadow: "0 1px 0 rgba(255,255,255,0.25)",
            }}
          >
            {activity.title}
          </motion.h3>

          <motion.p
            style={{
              ...TILT,
              fontSize: "0.74rem",
              lineHeight: 1.5,
              color: "rgba(0,0,0,0.70)",
              marginBottom: "0.8rem",
            }}
            animate={{ opacity: isHovered ? 1 : 0.84 }}
            transition={{ duration: 0.25 }}
            className="line-clamp-2"
          >
            {activity.description}
          </motion.p>

          <motion.div
            className="flex items-center gap-1.5"
            animate={{ y: isHovered ? 0 : 4, opacity: isHovered ? 1 : 0.85 }}
            transition={{ duration: 0.25 }}
          >
            <div
              className="w-1.5 h-1.5 rounded-full"
              style={{ backgroundColor: "rgba(0,0,0,0.75)" }}
            />
            <span
              style={{
                ...TILT,
                fontSize: "0.68rem",
                color: "rgba(0,0,0,0.74)",
              }}
            >
              {activity.schedule}
            </span>
          </motion.div>
        </div>
      </div>

      <motion.div
        className="absolute inset-0 pointer-events-none"
        style={{
          background:
            "linear-gradient(105deg, transparent 40%, rgba(255,255,255,0.22) 50%, transparent 60%)",
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

    const cardWidth = window.innerWidth >= 768 ? 440 : 320;

    const snapCount = Math.max(1, Math.ceil(maxScroll / cardWidth) + 1);
    const rawSnaps = Array.from({ length: snapCount }).map((_, i) =>
      Math.min(i * cardWidth, maxScroll)
    );
    const uniqueSnaps = Array.from(new Set(rawSnaps));
    setScrollSnaps(uniqueSnaps);

    let closestIndex = 0;
    let minDiff = Infinity;
    uniqueSnaps.forEach((snap, i) => {
      const diff = Math.abs(snap - el.scrollLeft);
      if (diff < minDiff) {
        minDiff = diff;
        closestIndex = i;
      }
    });

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
      if (e.shiftKey) return;
      if (Math.abs(e.deltaY) === 0) return;

      const isAtLeftEdge = el.scrollLeft <= 0;
      const isAtRightEdge = el.scrollLeft >= el.scrollWidth - el.clientWidth - 1;

      if (isAtLeftEdge && e.deltaY < 0) return;
      if (isAtRightEdge && e.deltaY > 0) return;

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

  return (
    <section
      id="activities"
      ref={sectionRef}
      className="relative py-20 overflow-hidden"
    >
      <div
        className="absolute pointer-events-none"
        style={{
          top: "10%",
          left: "-10%",
          width: "500px",
          height: "500px",
          background: "radial-gradient(circle, rgba(255,255,255,0.12) 0%, transparent 70%)",
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
          background: "radial-gradient(circle, rgba(255,255,255,0.10) 0%, transparent 70%)",
          borderRadius: "50%",
          filter: "blur(50px)",
        }}
      />

      <div className="max-w-7xl mx-auto px-6 md:px-12 lg:px-16 relative z-10">
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
          <div className="mb-12">
            <div className="flex flex-col md:flex-row md:items-end md:justify-between gap-6">
              <div>
                <motion.div
                  className="flex items-center gap-3 mb-5"
                  initial={{ opacity: 0, x: -20 }}
                  whileInView={{ opacity: 1, x: 0 }}
                  viewport={{ once: true }}
                >
                  <div className="w-8 h-[2px] bg-black/50" />
                  <span
                    className="tracking-[0.14em] uppercase"
                    style={{
                      ...TILT,
                      color: "rgba(0,0,0,0.72)",
                      fontSize: "0.72rem",
                    }}
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
                    style={{
                      ...TILT,
                      fontSize: "clamp(2.6rem, 7vw, 5.2rem)",
                      color: "#111111",
                      lineHeight: 0.96,
                    }}
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
                      ...TILT,
                      fontSize: "clamp(2.6rem, 7vw, 5.2rem)",
                      color: "rgba(0,0,0,0.72)",
                      lineHeight: 0.96,
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
                style={{
                  ...TILT,
                  color: "rgba(0,0,0,0.60)",
                  fontSize: "0.8rem",
                  lineHeight: 1.7,
                  paddingBottom: "0.5rem",
                }}
                className="max-w-sm"
              >
                From iconic water splashes to electrifying DJ nights — eleven days of pure celebration
                with something for every soul.
              </motion.p>
            </div>
          </div>

          <div className="relative">
            <div
              ref={scrollRef}
              className="flex gap-5 overflow-x-auto pt-4 pb-8"
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

          <div className="flex items-center justify-between mt-8">
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
                    backgroundColor: i === activeIndex ? "#111111" : "rgba(0,0,0,0.22)",
                  }}
                />
              ))}
            </div>

            <div className="flex items-center gap-3">
              <motion.button
                whileHover={{ scale: 1.06 }}
                whileTap={{ scale: 0.96 }}
                onClick={() => scroll("left")}
                disabled={!canScrollLeft}
                className="w-12 h-12 rounded-full flex items-center justify-center transition-all duration-300"
                style={{
                  border: `1.5px solid ${canScrollLeft ? "rgba(0,0,0,0.24)" : "rgba(0,0,0,0.08)"
                    }`,
                  background: canScrollLeft ? "rgba(255,255,255,0.34)" : "rgba(255,255,255,0.16)",
                  color: canScrollLeft ? "#111111" : "rgba(0,0,0,0.22)",
                  cursor: canScrollLeft ? "pointer" : "not-allowed",
                  boxShadow: canScrollLeft ? "0 10px 24px rgba(0,0,0,0.08)" : "none",
                }}
              >
                <ChevronLeft size={20} />
              </motion.button>

              <motion.button
                whileHover={{ scale: 1.06 }}
                whileTap={{ scale: 0.96 }}
                onClick={() => scroll("right")}
                disabled={!canScrollRight}
                className="w-12 h-12 rounded-full flex items-center justify-center transition-all duration-300"
                style={{
                  border: `1.5px solid ${canScrollRight ? "rgba(0,0,0,0.24)" : "rgba(0,0,0,0.08)"
                    }`,
                  background: canScrollRight ? "rgba(255,255,255,0.34)" : "rgba(255,255,255,0.16)",
                  color: canScrollRight ? "#111111" : "rgba(0,0,0,0.22)",
                  cursor: canScrollRight ? "pointer" : "not-allowed",
                  boxShadow: canScrollRight ? "0 10px 24px rgba(0,0,0,0.08)" : "none",
                }}
              >
                <ChevronRight size={20} />
              </motion.button>
            </div>
          </div>
        </div>
      </div>
    </section>
  );
}