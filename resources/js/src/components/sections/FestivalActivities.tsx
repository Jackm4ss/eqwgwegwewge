import { useRef, useEffect, useState, useCallback, type CSSProperties } from "react";
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
import { SECTION_BACKGROUND } from "./sectionContrastTheme";

const TILT: CSSProperties = {
  fontFamily: "'Tilt Warp', sans-serif",
};

const TITLE_HIGHLIGHT_TEXT_STYLE: CSSProperties = {
  color: "#349CD2",
  textShadow: "2px 3px 0 rgba(31, 52, 71, 0.22), 0 3px 8px rgba(31, 52, 71, 0.10)",
};

const DETAIL_TEXT_STYLE: CSSProperties = {
  color: "rgba(0,0,0,0.72)",
};

const PANEL_STYLE: CSSProperties = {
  background:
    "linear-gradient(rgba(255,255,255,0.09) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,0.09) 1px, transparent 1px), rgba(186, 230, 253, 0.78)",
  backgroundSize: "26px 26px, 26px 26px, auto",
  border: "1px solid rgba(255,255,255,0.42)",
  boxShadow: "0 18px 60px rgba(0,0,0,0.10)",
  backdropFilter: "blur(12px)",
  WebkitBackdropFilter: "blur(12px)",
};

const BADGE_STYLES: Record<string, CSSProperties> = {
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
    image: "/images/foto-1.png",
    size: "wide",
  },
  {
    id: 2,
    icon: <Music size={20} />,
    badge: "DAILY",
    title: "Live Band",
    description: "Every evening throughout all 11 nights",
    schedule: "Nightly, 9th–19th",
    image: "/images/foto-2.png",
  },
  {
    id: 3,
    icon: <Disc3 size={20} />,
    badge: "NIGHTLY",
    title: "DJ Performances",
    description: "Dance under the stars every night",
    schedule: "Every Night",
    image: "/images/foto-3.png",
  },
  {
    id: 4,
    icon: <Waves size={20} />,
    badge: "DAILY",
    title: "Water Play",
    description: "Iconic Songkran splashes — joyful, symbolic, unforgettable",
    schedule: "5PM+ Daily",
    image: "/images/foto-4.png",
    size: "wide",
  },
  {
    id: 5,
    icon: <Star size={20} />,
    badge: "SPECIAL",
    title: "Kids Fashion Show",
    description: "Thai-inspired looks by our youngest stars",
    schedule: "11th April",
    image: "/images/foto-5.png",
  },
  {
    id: 6,
    icon: <Zap size={20} />,
    badge: "SPECIAL",
    title: "Guest Appearance",
    description: "A mystery guest that will light up the stage",
    schedule: "11th April (TBC)",
    image: "/images/foto-6.png",
  },
  {
    id: 7,
    icon: <Gamepad2 size={20} />,
    badge: "DAILY",
    title: "Games & Water Sports",
    description: "Interactive competitions for all ages",
    schedule: "Daily, 9th–19th",
    image: "/images/foto-7.png",
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

    setTilt({ x: y * 10, y: -x * 10 });
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
      whileHover={{ scale: 1.015, y: -4, zIndex: 10 }}
      transition={{ duration: 0.28, ease: "easeOut" }}
      className={`group relative h-[380px] flex-shrink-0 cursor-pointer select-none overflow-hidden rounded-[24px] ${isWide ? "w-[420px] md:w-[480px]" : "w-[300px] md:w-[340px]"
        }`}
    >
      <div
        className="absolute inset-0 transition-transform duration-700 ease-out"
        style={{
          transform: isHovered ? "scale(1.06)" : "scale(1)",
        }}
      >
        <img
          src={activity.image}
          alt={activity.title}
          onLoad={() => setImgLoaded(true)}
          className={`h-full w-full object-cover transition-all duration-500 ${isHovered ? "brightness-100 grayscale-0" : "brightness-95 grayscale-[10%]"
            } ${imgLoaded ? "opacity-100" : "opacity-0"}`}
        />
      </div>

      <div
        className={`absolute inset-0 transition-opacity duration-500 ${isHovered ? "opacity-95" : "opacity-88"
          }`}
        style={{
          background:
            "linear-gradient(to top, rgba(240,248,255,0.90) 0%, rgba(240,248,255,0.44) 42%, rgba(240,248,255,0.06) 100%)",
        }}
      />

      <motion.div
        className="pointer-events-none absolute inset-0 rounded-[24px]"
        animate={{
          boxShadow: isHovered
            ? "inset 0 0 0 1.5px rgba(0,0,0,0.16), 0 18px 40px rgba(0,0,0,0.12)"
            : "inset 0 0 0 1px rgba(255,255,255,0.24)",
        }}
        transition={{ duration: 0.3 }}
      />

      <div
        className="absolute inset-0 flex flex-col justify-between p-5"
        style={{ transform: "translateZ(20px)" }}
      >
        <div className="flex items-start justify-between">
          <div
            className="flex h-9 w-9 items-center justify-center rounded-xl transition-colors duration-300"
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
            className="rounded-full px-2.5 py-1 text-[10px] tracking-widest"
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
              textShadow: "0 1px 0 rgba(255,255,255,0.18)",
            }}
          >
            {activity.title}
          </motion.h3>

          <motion.p
            style={{
              ...TILT,
              fontSize: "0.74rem",
              lineHeight: 1.5,
              color: "rgba(0,0,0,0.72)",
              marginBottom: "0.8rem",
            }}
            animate={{ opacity: isHovered ? 1 : 0.9 }}
            transition={{ duration: 0.25 }}
            className="line-clamp-2"
          >
            {activity.description}
          </motion.p>

          <motion.div
            className="flex items-center gap-1.5"
            animate={{ y: isHovered ? 0 : 4, opacity: isHovered ? 1 : 0.88 }}
            transition={{ duration: 0.25 }}
          >
            <div
              className="h-1.5 w-1.5 rounded-full"
              style={{ backgroundColor: "rgba(0,0,0,0.72)" }}
            />
            <span
              style={{
                ...TILT,
                fontSize: "0.68rem",
                color: "rgba(0,0,0,0.72)",
              }}
            >
              {activity.schedule}
            </span>
          </motion.div>
        </div>
      </div>

      <motion.div
        className="pointer-events-none absolute inset-0"
        style={{
          background:
            "linear-gradient(105deg, transparent 40%, rgba(255,255,255,0.20) 50%, transparent 60%)",
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
  const sectionRef = useRef<HTMLElement | null>(null);

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

    const rawSnaps = Array.from({ length: snapCount }, (_, i) =>
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

  useScroll({
    target: sectionRef,
    offset: ["start end", "center center"],
  });

  return (
    <section
      id="activities"
      ref={sectionRef}
      className="relative overflow-hidden py-20"
      style={SECTION_BACKGROUND}
    >
      <div
        className="absolute pointer-events-none"
        style={{
          top: "10%",
          left: "-10%",
          width: "500px",
          height: "500px",
          background: "radial-gradient(circle, rgba(255,255,255,0.10) 0%, transparent 70%)",
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
          background: "radial-gradient(circle, rgba(255,255,255,0.08) 0%, transparent 70%)",
          borderRadius: "50%",
          filter: "blur(50px)",
        }}
      />

      <div className="relative z-10 mx-auto max-w-7xl px-6 md:px-12 lg:px-16">
        <div className="rounded-[32px] p-6 md:rounded-[40px] md:p-10 lg:p-14" style={PANEL_STYLE}>
          <div className="mb-12">
            <div className="flex flex-col gap-6 md:flex-row md:items-end md:justify-between">
              <div>
                <motion.div
                  className="mb-5 flex items-center gap-3"
                  initial={{ opacity: 0, x: -20 }}
                  whileInView={{ opacity: 1, x: 0 }}
                  viewport={{ once: true }}
                >
                  <div className="h-[2px] w-8 bg-black/50" />
                  <span
                    className="uppercase tracking-[0.14em]"
                    style={{
                      ...TILT,
                      color: "rgba(0,0,0,0.72)",
                      fontSize: "0.72rem",
                    }}
                  >
                    What&apos;s On
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
                      ...TITLE_HIGHLIGHT_TEXT_STYLE,
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
                      ...TITLE_HIGHLIGHT_TEXT_STYLE,
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
                  ...DETAIL_TEXT_STYLE,
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
              className="flex gap-5 overflow-x-auto pb-8 pt-4"
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

          <div className="mt-8 flex items-center justify-between">
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
                className="flex h-12 w-12 items-center justify-center rounded-full transition-all duration-300"
                style={{
                  border: `1.5px solid ${canScrollLeft ? "rgba(0,0,0,0.24)" : "rgba(0,0,0,0.08)"}`,
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
                className="flex h-12 w-12 items-center justify-center rounded-full transition-all duration-300"
                style={{
                  border: `1.5px solid ${canScrollRight ? "rgba(0,0,0,0.24)" : "rgba(0,0,0,0.08)"}`,
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