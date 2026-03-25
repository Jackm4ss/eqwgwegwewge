import React from "react";
import { MapPin, Clock, Ticket, Building2, ArrowUpRight } from "lucide-react";
import { motion } from "motion/react";

import { SpotlightCard } from "../ui/SpotlightCard";
import ShapeGrid from "../ui/ShapeGrid/ShapeGrid";

const TILT: React.CSSProperties = {
  fontFamily: "'Tilt Warp', sans-serif",
};

export function EventDetails() {
  return (
    <section
      id="details"
      className="w-full min-h-screen flex items-center justify-center px-6 py-20 relative overflow-hidden"
    >
      <div className="absolute inset-0 z-0 opacity-20 pointer-events-none">
        <ShapeGrid
          direction="diagonal"
          speed={0.5}
          squareSize={80}
          borderColor="rgba(255,255,255,0.12)"
          hoverFillColor="rgba(255,255,255,0.04)"
        />
      </div>

      <div className="w-full max-w-6xl relative z-10">
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
          {/* Section Header */}
          <div className="mb-14">
            <motion.div
              className="flex items-center gap-3 mb-5"
              initial={{ opacity: 0, x: -20 }}
              whileInView={{ opacity: 1, x: 0 }}
              viewport={{ once: true }}
            >
              <div className="w-8 h-px bg-black/50" />
              <span
                className="tracking-[0.2em] uppercase text-xs"
                style={{ ...TILT, color: "rgba(0,0,0,0.72)" }}
              >
                The Hub
              </span>
            </motion.div>

            <div>
              <div style={{ overflow: "hidden" }}>
                <motion.h2
                  initial={{ y: "100%", opacity: 0 }}
                  whileInView={{ y: 0, opacity: 1 }}
                  viewport={{ once: true }}
                  transition={{ duration: 1, ease: [0.22, 1, 0.36, 1] }}
                  style={{
                    ...TILT,
                    fontSize: "clamp(2.4rem,5.5vw,4.8rem)",
                    color: "#111111",
                    lineHeight: 0.95,
                    letterSpacing: "0.01em",
                    marginBottom: 0,
                  }}
                >
                  Event
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
                    fontSize: "clamp(2.4rem,5.5vw,4.8rem)",
                    color: "#111111",
                    lineHeight: 0.95,
                    letterSpacing: "0.01em",
                  }}
                >
                  Details
                </motion.h2>
              </div>
            </div>
          </div>

          {/* Main Layout */}
          <div className="flex flex-col lg:flex-row gap-6 lg:gap-8">
            {/* Left column */}
            <SpotlightCard
              spotlightColor="rgba(255,255,255,0.22)"
              className="lg:w-[55%] pb-10 min-h-[380px]"
              style={{
                background:
                  "linear-gradient(135deg, rgba(255,255,255,0.38) 0%, rgba(210,238,245,0.72) 55%, rgba(175,225,239,0.52) 100%)",
                border: "1px solid rgba(255,255,255,0.45)",
                boxShadow: "0 10px 35px rgba(0,0,0,0.08)",
              }}
            >
              <div
                className="absolute -top-20 -left-10 w-80 h-80 rounded-full pointer-events-none"
                style={{
                  background: "radial-gradient(circle, rgba(255,255,255,0.22) 0%, transparent 70%)",
                }}
              />

              <div className="flex items-center gap-2 mb-2 relative z-10">
                <div
                  className="w-5 h-5 rounded flex items-center justify-center"
                  style={{ color: "rgba(0,0,0,0.72)" }}
                >
                  <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                    <rect x="1" y="2.5" width="14" height="12" rx="2" stroke="currentColor" strokeWidth="1.3" />
                    <path d="M1 6.5h14" stroke="currentColor" strokeWidth="1.3" />
                    <path d="M5 1v3M11 1v3" stroke="currentColor" strokeWidth="1.3" strokeLinecap="round" />
                  </svg>
                </div>
                <span
                  className="tracking-[0.16em] uppercase text-xs"
                  style={{ ...TILT, color: "rgba(0,0,0,0.58)" }}
                >
                  Festival Dates
                </span>
              </div>

              <div
                className="relative z-10 tracking-[0.12em] uppercase"
                style={{
                  ...TILT,
                  fontSize: "clamp(1.35rem, 3vw, 2.3rem)",
                  color: "rgba(0,0,0,0.82)",
                  lineHeight: 1,
                  marginBottom: "0.18em",
                }}
              >
                APR
              </div>

              <div
                className="relative z-10"
                style={{
                  ...TILT,
                  fontSize: "clamp(4.8rem, 13vw, 10rem)",
                  lineHeight: 0.85,
                  letterSpacing: "0.01em",
                  color: "#111111",
                  marginBottom: "0.15em",
                }}
              >
                9–19
              </div>

              <div className="relative z-10 flex items-end gap-6 mt-auto pt-6 flex-wrap">
                <span
                  style={{
                    ...TILT,
                    fontSize: "0.95rem",
                    color: "rgba(0,0,0,0.7)",
                    letterSpacing: "0.06em",
                  }}
                >
                  2026
                </span>
                <span
                  style={{
                    ...TILT,
                    fontSize: "0.72rem",
                    color: "rgba(0,0,0,0.56)",
                    letterSpacing: "0.04em",
                  }}
                >
                  11 days of celebration
                </span>
              </div>
            </SpotlightCard>

            {/* Right column */}
            <div className="lg:w-[45%] lg:pl-2 flex flex-col justify-between gap-4 pt-2">
              <SpotlightCard
                spotlightColor="rgba(255,255,255,0.18)"
                style={{
                  background: "rgba(255,255,255,0.34)",
                  border: "1px solid rgba(255,255,255,0.42)",
                  boxShadow: "0 10px 30px rgba(0,0,0,0.06)",
                }}
              >
                <div className="flex items-center gap-2 mb-2">
                  <MapPin size={13} style={{ color: "#111111" }} />
                  <span
                    className="tracking-[0.16em] uppercase text-xs"
                    style={{ ...TILT, color: "rgba(0,0,0,0.58)" }}
                  >
                    Venue
                  </span>
                </div>

                <p
                  style={{
                    ...TILT,
                    fontSize: "1.15rem",
                    color: "#111111",
                    lineHeight: 1.2,
                    marginBottom: "0.3rem",
                  }}
                >
                  Forecourt, One Utama
                </p>

                <p style={{ ...TILT, fontSize: "0.72rem", color: "rgba(0,0,0,0.58)" }}>
                  (Old Wing) · Malaysia
                </p>

                <button
                  className="flex items-center gap-1.5 mt-3 group"
                  style={{
                    ...TILT,
                    fontSize: "0.72rem",
                    color: "#111111",
                    letterSpacing: "0.03em",
                    background: "none",
                    border: "none",
                    cursor: "pointer",
                    padding: 0,
                  }}
                >
                  <span>Get Directions</span>
                  <ArrowUpRight
                    size={12}
                    className="group-hover:translate-x-0.5 group-hover:-translate-y-0.5 transition-transform"
                  />
                </button>
              </SpotlightCard>

              <SpotlightCard
                spotlightColor="rgba(255,255,255,0.18)"
                style={{
                  background: "rgba(255,255,255,0.34)",
                  border: "1px solid rgba(255,255,255,0.42)",
                  boxShadow: "0 10px 30px rgba(0,0,0,0.06)",
                }}
              >
                <div className="flex items-center gap-2 mb-3">
                  <Clock size={13} style={{ color: "#111111" }} />
                  <span
                    className="tracking-[0.16em] uppercase text-xs"
                    style={{ ...TILT, color: "rgba(0,0,0,0.58)" }}
                  >
                    Daily Hours
                  </span>
                </div>

                <div className="flex items-baseline gap-3 flex-wrap">
                  <span
                    style={{
                      ...TILT,
                      fontSize: "1.85rem",
                      color: "#111111",
                      lineHeight: 1,
                    }}
                  >
                    12PM
                  </span>
                  <span style={{ ...TILT, fontSize: "0.68rem", color: "rgba(0,0,0,0.45)" }}>
                    to
                  </span>
                  <span
                    style={{
                      ...TILT,
                      fontSize: "1.85rem",
                      color: "#111111",
                      lineHeight: 1,
                    }}
                  >
                    12AM
                  </span>
                </div>

                <p
                  style={{
                    ...TILT,
                    fontSize: "0.68rem",
                    color: "rgba(0,0,0,0.52)",
                    marginTop: "0.45rem",
                  }}
                >
                  12 hours of festivities
                </p>
              </SpotlightCard>

              <div className="flex flex-col sm:flex-row gap-4">
                <SpotlightCard
                  spotlightColor="rgba(255,255,255,0.18)"
                  className="flex-1"
                  style={{
                    background: "rgba(255,255,255,0.34)",
                    border: "1px solid rgba(255,255,255,0.42)",
                    boxShadow: "0 10px 30px rgba(0,0,0,0.06)",
                  }}
                >
                  <div className="flex items-center gap-2 mb-2">
                    <Ticket size={13} style={{ color: "#111111" }} />
                    <span
                      className="tracking-[0.16em] uppercase text-xs"
                      style={{ ...TILT, color: "rgba(0,0,0,0.58)" }}
                    >
                      Admission
                    </span>
                  </div>

                  <p
                    style={{
                      ...TILT,
                      fontSize: "1.7rem",
                      color: "#111111",
                      letterSpacing: "0.02em",
                      lineHeight: 1,
                      marginBottom: "0.3rem",
                    }}
                  >
                    FREE
                  </p>

                  <div className="flex items-center gap-1.5 mt-1">
                    <span
                      className="w-1.5 h-1.5 rounded-full"
                      style={{ backgroundColor: "rgba(0,0,0,0.68)" }}
                    />
                    <span style={{ ...TILT, fontSize: "0.65rem", color: "rgba(0,0,0,0.56)" }}>
                      Open to everyone
                    </span>
                  </div>
                </SpotlightCard>

                <SpotlightCard
                  spotlightColor="rgba(255,255,255,0.18)"
                  className="flex-1"
                  style={{
                    background: "rgba(255,255,255,0.34)",
                    border: "1px solid rgba(255,255,255,0.42)",
                    boxShadow: "0 10px 30px rgba(0,0,0,0.06)",
                  }}
                >
                  <div className="flex items-center gap-2 mb-2">
                    <Building2 size={13} style={{ color: "#111111" }} />
                    <span
                      className="tracking-[0.16em] uppercase text-xs"
                      style={{ ...TILT, color: "rgba(0,0,0,0.58)" }}
                    >
                      Organised By
                    </span>
                  </div>

                  <p
                    style={{
                      ...TILT,
                      fontSize: "0.95rem",
                      color: "#111111",
                      lineHeight: 1.2,
                      marginBottom: "0.3rem",
                    }}
                  >
                    EQ Solutions
                  </p>

                  <p
                    style={{
                      ...TILT,
                      fontSize: "0.64rem",
                      color: "rgba(0,0,0,0.52)",
                      lineHeight: 1.45,
                    }}
                  >
                    Bringing cultures together,
                    <br />
                    one festival at a time.
                  </p>
                </SpotlightCard>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  );
}