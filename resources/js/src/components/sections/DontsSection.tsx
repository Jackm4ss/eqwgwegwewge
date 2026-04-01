import { useEffect, type CSSProperties } from "react";
import { motion } from "motion/react";
import { LazyImage } from "../ui/LazyImage";
import {
  PANEL_BACKGROUND,
  PANEL_BACKGROUND_SIZE,
  PANEL_BORDER,
} from "./sectionContrastTheme";

const SG: CSSProperties = { fontFamily: "'Space Grotesk', sans-serif" };

const iconPath = (fileName: string) =>
  `/images/icons/${encodeURIComponent(fileName)}`;

const PROHIBITED_ITEMS = [
  { label: "Trolley", fileName: "Trolley.png" },
  { label: "Outside Tables & Chairs", fileName: "Outside Tables & Chairs.png" },
  { label: "Outside Foods & Drinks", fileName: "Outside Foods &Drinks.png" },
  { label: "Drugs", fileName: "Drugs.png" },
  { label: "Weapon", fileName: "Weapon.png" },
  {
    label: "Glassware or Dangerous Item Into Wet Zone",
    fileName: "Glassware or Dangerous Item Into Wet Zone.png",
  },
  { label: "Running", fileName: "Running.png" },
  { label: "Don't Be Aggressive", fileName: "Don_t Be Agressive.png" },
  { label: "Pushing & Rough Play", fileName: "Pushing & Rough Play.png" },
  {
    label: "Climbing on Booth, Structures & Stage",
    fileName: "Climbing on Booth, Structures & Stage.png",
  },
  { label: "Water Play in Dry Zone", fileName: "Water Play in Dry Zone.png" },
  {
    label: "Water Throwing at Performers, Crew, Stage Equipment",
    fileName: "Water Throwing at Perfomers, Crew, Stage Equipment.png",
  },
  {
    label: "Water Soaker Refilling at Vendor's Washing Area",
    fileName: "Water Soaker Refilling at Vendor_s Washing Area.png",
  },
  {
    label: "Spraying People While Eating",
    fileName: "Spraying People While Eating.png",
  },
  {
    label: "Standing On Chair or Table",
    fileName: "Standing On Chair or Table.png",
  },
  {
    label: "Entering Restricted Area",
    fileName: "Entering Restricted Area.png",
  },
  { label: "Revealing Clothing", fileName: "Revealing Clothing.png" },
  { label: "Sexual Harassment", fileName: "Sexual Harrassment.png" },
] as const;

export function DontsSection() {
  useEffect(() => {
    if (typeof window === "undefined") {
      return;
    }

    PROHIBITED_ITEMS.slice(0, 4).forEach((item) => {
      const image = new Image();
      image.decoding = "async";
      image.src = iconPath(item.fileName);
    });
  }, []);

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
                  <div className="mb-4 flex h-[88px] w-[88px] flex-shrink-0 items-center justify-center transition-transform duration-300 group-hover:scale-105 md:h-[100px] md:w-[100px]">
                    <LazyImage
                      src={iconPath(item.fileName)}
                      alt=""
                      aria-hidden="true"
                      loading={idx < 4 ? "eager" : "lazy"}
                      fetchPriority={idx === 0 ? "high" : "auto"}
                      wrapperClassName="h-full w-full"
                      className="h-full w-full object-contain drop-shadow-[0_6px_16px_rgba(0,0,0,0.14)]"
                      showSkeleton
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
