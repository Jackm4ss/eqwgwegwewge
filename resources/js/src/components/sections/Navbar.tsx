import { useState, useEffect, memo } from "react";
import { motion, AnimatePresence } from "motion/react";
import { Menu, X, Info, Music, Sparkles, Calendar, Image as ImageIcon, HelpCircle, ChevronRight, ArrowUpRight } from "lucide-react";

const S: React.CSSProperties = { fontFamily: "'Space Grotesk', sans-serif" };

const ScrollbarStyles = memo(() => (
  <style dangerouslySetInnerHTML={{ __html: `
    .custom-scrollbar::-webkit-scrollbar {
      width: 4px;
    }
    .custom-scrollbar::-webkit-scrollbar-track {
      background: rgba(255, 255, 255, 0.02);
      border-radius: 10px;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb {
      background: rgba(47, 167, 216, 0.3);
      border-radius: 10px;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover {
      background: rgba(47, 167, 216, 0.5);
    }
  `}} />
));

const NavLink = memo(({ l, onClick, isMobile = false }: { l: any, onClick: (id: string) => void, isMobile?: boolean }) => {
  const [isTapped, setIsTapped] = useState(false);

  const handleTap = () => {
    setIsTapped(true);
    setTimeout(() => setIsTapped(false), 500);
    onClick(l.id);
  };

  if (isMobile) {
    return (
      <motion.button
        initial={{ opacity: 0, x: 20 }}
        animate={{
          opacity: 1,
          x: 0,
          backgroundColor: isTapped ? "rgba(255, 255, 255, 0.15)" : "transparent"
        }}
        whileTap={{ scale: 0.98 }}
        transition={{ delay: 0.1 }}
        onClick={handleTap}
        className="flex items-center justify-between w-full py-4 px-4 rounded-xl hover:bg-white/5 transition-colors group text-left"
        style={{ ...S, transform: "translateZ(0)" }}
      >
        <div className="flex items-center gap-4">
          <div className="w-10 h-10 rounded-full bg-[#2FA7D8]/10 flex items-center justify-center text-[#2FA7D8] group-hover:bg-[#2FA7D8] group-hover:text-white transition-all duration-300">
            <l.icon size={18} />
          </div>
          <span className="text-[#EDE8DC] font-medium text-lg">{l.label}</span>
        </div>
        <ChevronRight size={18} className="text-white/20 group-hover:text-[#2FA7D8] transition-colors" />
      </motion.button>
    );
  }

  return (
    <button
      onClick={() => onClick(l.id)}
      className="relative group flex items-center gap-2"
      style={{ ...S, fontSize: "0.8rem", color: "rgba(237,232,220,0.85)", letterSpacing: "0.06em" }}
    >
      <l.icon size={14} className="group-hover:text-[#2FA7D8] transition-colors duration-200" />
      <span className="group-hover:text-[#EDE8DC] transition-colors duration-200">{l.label}</span>
      <span className="absolute -bottom-0.5 left-0 w-0 h-px bg-[#2FA7D8] group-hover:w-full transition-all duration-300" />
    </button>
  );
});

export function Navbar() {
  const [scrolled, setScrolled] = useState(false);
  const [open, setOpen] = useState(false);

  useEffect(() => {
    let ticking = false;
    const fn = () => {
      if (!ticking) {
        window.requestAnimationFrame(() => {
          const isScrolled = window.scrollY > 80;
          setScrolled((prev) => {
            if (prev !== isScrolled) return isScrolled;
            return prev;
          });
          ticking = false;
        });
        ticking = true;
      }
    };
    window.addEventListener("scroll", fn, { passive: true });
    return () => window.removeEventListener("scroll", fn);
  }, []);

  const go = (id: string) => {
    setTimeout(() => setOpen(false), 400);
    setTimeout(() => {
      const el = document.querySelector(id);
      if (el) {
        const offset = 80;
        const bodyRect = document.body.getBoundingClientRect().top;
        const elementRect = el.getBoundingClientRect().top;
        const elementPosition = elementRect - bodyRect;
        const offsetPosition = elementPosition - offset;

        window.scrollTo({
          top: offsetPosition,
          behavior: "smooth"
        });
      }
    }, 300);
  };

  const links = [
    { id: "#about", label: "About", icon: Info },
    { id: "#lineup", label: "Line Up", icon: Music },
    { id: "#activities", label: "Activities", icon: Sparkles },
    { id: "#schedule", label: "Schedule", icon: Calendar },
    { id: "#gallery", label: "Gallery", icon: ImageIcon },
    { id: "#faq", label: "FAQ", icon: HelpCircle },
  ];

  return (
    <>
      <ScrollbarStyles />
      <motion.nav
        initial={{ y: -80, opacity: 0 }}
        animate={{ y: 0, opacity: 1 }}
        transition={{ delay: 0.5, duration: 0.9, ease: [0.22, 1, 0.36, 1] }}
        className="fixed top-0 left-0 right-0 z-[200] px-4 lg:px-8 pt-4"
        style={{ transform: "translateZ(0)" }}
      >
        <div className={`rounded-2xl transition-all duration-500 ${scrolled ? "bg-[#070810]/85 lg:backdrop-blur-xl border border-white/6 shadow-2xl shadow-black/50" : "bg-transparent"}`}>
          <div className="flex items-center justify-between px-5 py-3">
            <button
              onClick={() => window.scrollTo({ top: 0, behavior: "smooth" })}
              className="flex items-center gap-2 group"
            >
              <span style={{ ...S, fontFamily: "'Syne', sans-serif", fontWeight: 800, fontSize: "1.1rem", letterSpacing: "0.15em", color: "#EDE8DC" }}>
                SONGKRAN
              </span>
            </button>

            <div className="hidden lg:flex items-center gap-7">
              {links.map((l) => (
                <NavLink key={l.id} l={l} onClick={go} />
              ))}
            </div>

            <div className="flex items-center gap-3">
              <button
                onClick={() => window.location.href = '/register'}
                className="hidden lg:flex items-center gap-2 rounded-full px-5 py-2 text-[#050508] transition-all hover:scale-105 active:scale-95 group"
                style={{ ...S, background: "#2FA7D8", fontSize: "0.8rem", fontWeight: 600, letterSpacing: "0.04em" }}
              >
                Free Entry
                <ArrowUpRight size={15} className="transition-transform duration-300 group-hover:translate-x-1 group-hover:-translate-y-1" />
              </button>
              <button
                onClick={() => setOpen(!open)}
                className="lg:hidden w-10 h-10 flex items-center justify-center rounded-full bg-white/5 border border-white/10"
                style={{ color: "rgba(237,232,220,0.8)" }}
              >
                {open ? <X size={20} /> : <Menu size={20} />}
              </button>
            </div>
          </div>
        </div>
      </motion.nav>

      <AnimatePresence>
        {open && (
          <>
            <motion.div
              initial={{ opacity: 0 }}
              animate={{ opacity: 1 }}
              exit={{ opacity: 0 }}
              onClick={() => setOpen(false)}
              className="fixed inset-0 bg-black/60 lg:backdrop-blur-sm z-[250] lg:hidden"
              style={{ transform: "translateZ(0)" }}
            />

            <motion.div
              initial={{ x: "100%" }}
              animate={{ x: 0 }}
              exit={{ x: "100%" }}
              transition={{ type: "tween", duration: 0.35, ease: [0.22, 1, 0.36, 1] }}
              className="fixed top-0 right-0 bottom-0 w-[85%] max-w-[320px] bg-[#070810] border-l border-white/10 z-[251] lg:hidden flex flex-col p-6 shadow-2xl"
              style={{ transform: "translateZ(0)" }}
            >
              <div className="flex items-center justify-between mb-8">
                <span style={{ ...S, fontFamily: "'Syne', sans-serif", fontWeight: 800, fontSize: "1.1rem", letterSpacing: "0.15em", color: "#EDE8DC" }}>
                  MENU
                </span>
                <button
                  onClick={() => setOpen(false)}
                  className="w-10 h-10 flex items-center justify-center rounded-full bg-white/5 border border-white/10"
                >
                  <X size={20} style={{ color: "#EDE8DC" }} />
                </button>
              </div>

              <div className="flex-1 overflow-y-auto pr-2 -mr-2 custom-scrollbar flex flex-col gap-1">
                {links.map((l) => (
                  <NavLink key={l.id} l={l} onClick={go} isMobile />
                ))}
              </div>

              <div className="mt-8">
                <button
                  onClick={() => window.location.href = '/register'}
                  className="w-full rounded-2xl py-4 flex items-center justify-center gap-2 text-[#050508] font-bold text-lg shadow-lg shadow-[#2FA7D8]/20"
                  style={{ ...S, background: "#2FA7D8" }}
                >
                  Free Entry
                </button>
                <p className="text-center text-white/20 mt-6 text-xs" style={S}>
                  (c) 2026 SONGKRAN FESTIVAL
                </p>
              </div>
            </motion.div>
          </>
        )}
      </AnimatePresence>
    </>
  );
}
