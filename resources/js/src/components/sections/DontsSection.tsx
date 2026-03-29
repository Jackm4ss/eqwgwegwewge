import type { CSSProperties } from "react";
import { motion } from "motion/react";
import {
  Activity,
  AlertTriangle,
  ArrowUp,
  Briefcase,
  Crosshair,
  Droplet,
  Droplets,
  Hand,
  Mic,
  Mountain,
  Pill,
  ShoppingCart,
  Users,
  Utensils,
  UtensilsCrossed,
  Wine,
} from "lucide-react";
import {
  PANEL_BACKGROUND,
  PANEL_BACKGROUND_SIZE,
  PANEL_BORDER,
} from "./sectionContrastTheme";

const SG: CSSProperties = { fontFamily: "'Space Grotesk', sans-serif" };

const PROHIBITED_ITEMS = [
  { label: "Trolley", icon: ShoppingCart },
  { label: "Outside Tables & Chairs", icon: Briefcase },
  { label: "Outside Foods & Drinks", icon: Utensils },
  { label: "Drugs", icon: Pill },
  { label: "Weapon", icon: Crosshair },
  { label: "Glassware or Dangerous Item Into Wet Zone", icon: Wine },
  { label: "Running", icon: Activity },
  { label: "Pushing & Rough Play", icon: Users },
  { label: "Climbing on Booth, Structures & Stage", icon: Mountain },
  { label: "Water Play in Dry Zone", icon: Droplets },
  { label: "Water Throwing at Performers, Crew, Stage Equipment", icon: Mic },
  { label: "Water Soaker Refilling at Vendor's Washing Area", icon: Droplet },
  { label: "Spraying People While Eating", icon: UtensilsCrossed },
  { label: "Standing On Chair or Table", icon: ArrowUp },
  { label: "Entering Restricted Area", icon: AlertTriangle },
  { label: "Sexual Harassment", icon: Hand },
] as const;

export function DontsSection() {
  return (
    <section
      aria-label="Festival don'ts"
      className="relative overflow-hidden px-6 py-18 md:px-12 md:py-20 lg:px-16"
    >
      <div className="relative mx-auto max-w-7xl">
        <motion.div
          initial={{ opacity: 0, y: 28 }}
          whileInView={{ opacity: 1, y: 0 }}
          viewport={{ once: true, amount: 0.2 }}
          transition={{ duration: 0.7, ease: "easeOut" }}
          className="rounded-[32px] border border-white/8 px-4 py-8 md:px-8 md:py-10 lg:px-10"
          style={{
            background: PANEL_BACKGROUND,
            backgroundSize: PANEL_BACKGROUND_SIZE,
            border: PANEL_BORDER,
            boxShadow: "0 18px 42px rgba(0,0,0,0.08)",
          }}
        >
          <div className="mx-auto mb-6 flex max-w-3xl flex-col items-center text-center md:mb-8">
            <motion.div
              initial={{ opacity: 0, scale: 0.92 }}
              whileInView={{ opacity: 1, scale: 1 }}
              viewport={{ once: true }}
              transition={{ delay: 0.1, duration: 0.45, ease: "easeOut" }}
              className="mb-5 rounded-full border border-[#ff3344] px-8 py-2.5 shadow-[0_0_20px_rgba(230,0,18,0.22)]"
              style={{ background: "#E60012" }}
            >
              <span
                style={{
                  ...SG,
                  fontWeight: 700,
                  fontSize: "clamp(0.9rem, 2.2vw, 1.25rem)",
                  letterSpacing: "0.22em",
                  color: "#ffffff",
                }}
              >
                DON'T
              </span>
            </motion.div>

            {/*
              Festival Safety Reminders
              Please keep the celebration fun, respectful, and safe for everyone by avoiding
              the actions and items below.
            */}
          </div>

          <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 sm:gap-5 lg:grid-cols-4 lg:gap-6">
            {PROHIBITED_ITEMS.map((item, idx) => {
              const Icon = item.icon;

              return (
                <motion.div
                  key={item.label}
                  initial={{ opacity: 0, y: 20 }}
                  whileInView={{ opacity: 1, y: 0 }}
                  viewport={{ once: true, amount: 0.15 }}
                  transition={{ duration: 0.45, delay: Math.min(idx * 0.03, 0.3), ease: "easeOut" }}
                  className="group flex h-full flex-col items-center rounded-[28px] border border-white/30 bg-white/[0.28] px-3 py-5 text-center transition-all duration-300 hover:-translate-y-1 hover:border-[#ff3344]/35 hover:bg-white/[0.38] md:px-4 md:py-6"
                  style={{ boxShadow: "0 10px 28px rgba(0,0,0,0.10)" }}
                >
                  <div className="relative mb-4 flex h-[88px] w-[88px] flex-shrink-0 items-center justify-center rounded-full border-[3px] border-[#E60012] bg-[#030305] shadow-[0_4px_15px_rgba(0,0,0,0.3)] transition-colors duration-300 group-hover:bg-[#E60012]/10 md:h-[100px] md:w-[100px]">
                    <div className="absolute z-20 h-[3.5px] w-[138%] rotate-[-45deg] bg-[#E60012] transition-transform duration-300 group-hover:scale-105" />
                    <Icon
                      size={40}
                      strokeWidth={1.2}
                      className="z-10 text-[#EDE8DC] transition-all duration-300 group-hover:scale-110 group-hover:text-white md:h-[44px] md:w-[44px]"
                    />
                  </div>

                  <span
                    style={{
                      ...SG,
                      fontSize: "clamp(0.78rem, 1.7vw, 0.92rem)",
                      color: "rgba(0,0,0,0.78)",
                      lineHeight: 1.45,
                      fontWeight: 500,
                    }}
                    className="text-balance transition-colors duration-300 group-hover:text-black"
                  >
                    {item.label}
                  </span>
                </motion.div>
              );
            })}
          </div>
        </motion.div>
      </div>
    </section>
  );
}
