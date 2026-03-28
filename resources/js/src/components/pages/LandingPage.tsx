import { useEffect, useState } from "react";
import { motion, useScroll, AnimatePresence } from "motion/react";
import { GrainOverlay } from "../ui/GrainOverlay";
import { CustomCursor } from "../ui/CustomCursor";
import { Navbar } from "../sections/Navbar";
import { HeroSection } from "../sections/HeroSection";
import { Marquee } from "../ui/Marquee";
import { AboutFestival } from "../sections/AboutFestival";
import { EventDetails } from "../sections/EventDetails";
import { FestivalActivities } from "../sections/FestivalActivities";
import { EventSchedule } from "../sections/EventSchedule";
import { FAQ } from "../sections/FAQ";
import { SponsorSection } from "../sections/SponsorSection";
import { Footer } from "../sections/Footer";

// Komponen Global Scroll Indicator (Panah Bawah -> Mentok Bawah jadi Panah Atas)
function ScrollIndicator() {
  const { scrollYProgress } = useScroll();
  const [isAtBottom, setIsAtBottom] = useState(false);

  useEffect(() => {
    return scrollYProgress.on("change", (latest) => {
      // Jika nyaris mentok bawah, ubah jadi mode "Back to top"
      setIsAtBottom(latest > 0.95);
    });
  }, [scrollYProgress]);

  return (
    <motion.div
      initial={{ opacity: 0 }}
      animate={{ opacity: 1 }}
      className={`fixed bottom-6 right-6 md:bottom-8 md:right-10 z-[150] flex flex-col items-center transition-all duration-300 ${
        isAtBottom ? "pointer-events-auto cursor-pointer" : "pointer-events-none"
      }`}
      onClick={() => isAtBottom && window.scrollTo({ top: 0, behavior: "smooth" })}
    >
      {/* Dark pill backing to ensure WCAG AAA high contrast */}
      <div className="bg-[#050508]/90 backdrop-blur-md border border-white/20 w-10 h-10 rounded-full shadow-[0_8px_16px_rgba(0,0,0,0.6)] flex items-center justify-center hover:scale-110 active:scale-95 transition-transform">
        {/* Animated Arrow (Chevron) - Rotates based on position */}
        <motion.svg
          width="20"
          height="20"
          viewBox="0 0 24 24"
          fill="none"
          stroke="#3FD7F5"
          strokeWidth="3"
          strokeLinecap="round"
          strokeLinejoin="round"
          animate={{
            y: isAtBottom ? [3, -3, 3] : [-3, 3, -3],
            opacity: [0.6, 1, 0.6],
            rotate: isAtBottom ? 180 : 0,
          }}
          transition={{
            y: { duration: 1.5, repeat: Infinity, ease: "easeInOut" },
            opacity: { duration: 1.5, repeat: Infinity, ease: "easeInOut" },
            rotate: { duration: 0.4, ease: "backOut" }
          }}
        >
          <polyline points="6 9 12 15 18 9"></polyline>
        </motion.svg>
      </div>
    </motion.div>
  );
}

export function LandingPage() {
  return (
    <div className="min-h-screen" style={{ fontFamily: "'Tilt Warp', sans-serif" }}>
      <GrainOverlay />
      {/* <CustomCursor /> */}
      <Navbar />
      <ScrollIndicator />
      <HeroSection />

      <div
        style={{
          backgroundImage: "url('/images/BACKGROUND.jpg')",
          backgroundSize: "cover",
          backgroundPosition: "center",
          backgroundRepeat: "no-repeat",
        }}
      >
        <Marquee />
        <AboutFestival />
        <EventDetails />
        <Marquee reverse />
        <FestivalActivities />
        <EventSchedule />
        <FAQ />
        <SponsorSection />
      </div>

      <Footer />
    </div>
  );
}
