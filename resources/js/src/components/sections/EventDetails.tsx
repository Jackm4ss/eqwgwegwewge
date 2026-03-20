import React, { useRef, useState } from "react";
import { MapPin, Clock, Ticket, Building2, ArrowUpRight } from "lucide-react";
import { motion } from "motion/react";

import { SpotlightCard } from "../ui/SpotlightCard";
import ShapeGrid from "../ui/ShapeGrid/ShapeGrid";

const SONGKRAN_PILLARS = [
  { label: "Thai New Year", highlight: true },
  { label: "Buddhist Ceremony", highlight: false },
  { label: "Cultural Procession", highlight: false },
  { label: "Water Blessing Ritual", highlight: false },
  { label: "Traditional Music & Dance", highlight: false },
  { label: "Merit Making", highlight: false },
  { label: "Community & Unity", highlight: false },
];

export function EventDetails() {
  return (
    <section
      id="details"
      className="w-full min-h-screen flex items-center justify-center px-6 py-20 relative overflow-hidden"
      style={{ backgroundColor: "#0C0D11" }}
    >
      <div className="absolute inset-0 z-0 opacity-40">
        <ShapeGrid 
          direction="diagonal" 
          speed={0.5} 
          squareSize={80} 
          borderColor="rgba(0, 207, 255, 0.1)" 
          hoverFillColor="rgba(0, 207, 255, 0.05)"
        />
      </div>
      <div className="w-full max-w-6xl relative z-10">

        {/* Section Header */}
        <div className="mb-14">
          <motion.div 
            className="flex items-center gap-3 mb-5"
            initial={{ opacity: 0, x: -20 }}
            whileInView={{ opacity: 1, x: 0 }}
            viewport={{ once: true }}
          >
            <div className="w-8 h-px" style={{ backgroundColor: "#00CFFF" }} />
            <span
              className="tracking-[0.25em] uppercase text-xs"
              style={{ color: "#00CFFF", fontWeight: 600 }}
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
                  fontFamily: "'Syne', sans-serif",
                  fontWeight: 800, 
                  fontSize: "clamp(2.4rem,5.5vw,5rem)", 
                  color: "#EDE8DC", 
                  lineHeight: 0.95, 
                  letterSpacing: "-0.03em",
                  marginBottom: 0
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
                  fontFamily: "'Syne', sans-serif",
                  fontWeight: 800, 
                  fontSize: "clamp(2.4rem,5.5vw,5rem)", 
                  color: "#EDE8DC", 
                  lineHeight: 0.95, 
                  letterSpacing: "-0.03em" 
                }}
              >
                Details
              </motion.h2>
            </div>
          </div>
        </div>

        {/* Main Layout: Asymmetric */}
        <div className="flex flex-col lg:flex-row gap-0">

          {/* ── HERO: Date Block (dominant left column) ── */}
          <SpotlightCard
            spotlightColor="rgba(0, 207, 255, 0.2)"
            className="lg:w-[55%] pb-10 min-h-[380px]"
            style={{
              background: "linear-gradient(135deg, rgba(0,207,255,0.07) 0%, rgba(20,22,28,0.6) 60%, rgba(40,20,10,0.4) 100%)",
              border: "1px solid rgba(0,207,255,0.12)"
            }}
          >
            {/* Ambient glow */}
            <div
              className="absolute -top-20 -left-10 w-80 h-80 rounded-full pointer-events-none"
              style={{
                background: "radial-gradient(circle, rgba(0,207,255,0.08) 0%, transparent 70%)",
              }}
            />

            {/* Top: icon + label */}
            <div className="flex items-center gap-2 mb-2 relative z-10">
              <div
                className="w-5 h-5 rounded flex items-center justify-center"
                style={{ color: "#00CFFF" }}
              >
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                  <rect x="1" y="2.5" width="14" height="12" rx="2" stroke="currentColor" strokeWidth="1.3" />
                  <path d="M1 6.5h14" stroke="currentColor" strokeWidth="1.3" />
                  <path d="M5 1v3M11 1v3" stroke="currentColor" strokeWidth="1.3" strokeLinecap="round" />
                </svg>
              </div>
              <span
                className="tracking-[0.2em] uppercase text-xs"
                style={{ color: "rgba(255,255,255,0.45)", fontWeight: 600 }}
              >
                Festival Dates
              </span>
            </div>

            {/* Month */}
            <div
              className="relative z-10 tracking-[0.15em] uppercase"
              style={{
                fontSize: "clamp(1.8rem, 4vw, 3rem)",
                fontWeight: 800,
                color: "rgba(255,255,255,0.9)",
                lineHeight: 1,
                marginBottom: "0.1em",
              }}
            >
              APR
            </div>

            {/* Hero Date Numbers */}
            <div
              className="relative z-10"
              style={{
                fontSize: "clamp(6rem, 16vw, 12rem)",
                fontWeight: 900,
                lineHeight: 0.85,
                letterSpacing: "-0.04em",
                color: "#00CFFF",
                marginBottom: "0.15em",
              }}
            >
              9–19
            </div>

            {/* Year + duration */}
            <div className="relative z-10 flex items-end gap-6 mt-auto pt-6">
              <span
                style={{
                  fontSize: "1.1rem",
                  fontWeight: 700,
                  color: "rgba(255,255,255,0.5)",
                  letterSpacing: "0.08em",
                }}
              >
                2026
              </span>
              <span
                style={{
                  fontSize: "0.8rem",
                  color: "rgba(255,255,255,0.35)",
                  letterSpacing: "0.05em",
                }}
              >
                11 days of celebration
              </span>
            </div>
          </SpotlightCard>

          {/* ── RIGHT COLUMN: Glassmorphism Info Stack ── */}
          <div className="lg:w-[45%] lg:pl-12 flex flex-col justify-between gap-4 pt-2">

            {/* VENUE */}
            <SpotlightCard spotlightColor="rgba(0, 207, 255, 0.15)">
              <div className="flex items-center gap-2 mb-2">
                <MapPin size={13} style={{ color: "#00CFFF" }} />
                <span
                  className="tracking-[0.2em] uppercase text-xs"
                  style={{ color: "rgba(255,255,255,0.38)", fontWeight: 600 }}
                >
                  Venue
                </span>
              </div>
              <p
                style={{
                  fontSize: "1.35rem",
                  fontWeight: 700,
                  color: "#ffffff",
                  lineHeight: 1.2,
                  marginBottom: "0.3rem",
                }}
              >
                Forecourt, One Utama
              </p>
              <p style={{ fontSize: "0.8rem", color: "rgba(255,255,255,0.4)" }}>
                (Old Wing) · Malaysia
              </p>
              <button
                className="flex items-center gap-1.5 mt-3 group"
                style={{
                  fontSize: "0.75rem",
                  color: "#00CFFF",
                  fontWeight: 600,
                  letterSpacing: "0.05em",
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

            {/* DAILY HOURS */}
            <SpotlightCard spotlightColor="rgba(244, 160, 51, 0.1)">
              <div className="flex items-center gap-2 mb-3">
                <Clock size={13} style={{ color: "#00CFFF" }} />
                <span
                  className="tracking-[0.2em] uppercase text-xs"
                  style={{ color: "rgba(255,255,255,0.38)", fontWeight: 600 }}
                >
                  Daily Hours
                </span>
              </div>
              <div className="flex items-baseline gap-3">
                <span
                  style={{
                    fontSize: "2.2rem",
                    fontWeight: 900,
                    color: "#ffffff",
                    letterSpacing: "-0.02em",
                    lineHeight: 1,
                  }}
                >
                  12PM
                </span>
                <span style={{ fontSize: "0.7rem", color: "rgba(255,255,255,0.3)" }}>to</span>
                <span
                  style={{
                    fontSize: "2.2rem",
                    fontWeight: 900,
                    color: "#ffffff",
                    letterSpacing: "-0.02em",
                    lineHeight: 1,
                  }}
                >
                  12AM
                </span>
              </div>
              <p style={{ fontSize: "0.75rem", color: "rgba(255,255,255,0.3)", marginTop: "0.4rem" }}>
                12 hours of festivities
              </p>
            </SpotlightCard>

            {/* ADMISSION + ORGANISER row */}
            <div className="flex flex-row gap-4">
              {/* Admission */}
              <SpotlightCard spotlightColor="rgba(74, 222, 128, 0.15)" className="flex-1">
                <div className="flex items-center gap-2 mb-2">
                  <Ticket size={13} style={{ color: "#4ade80" }} />
                  <span
                    className="tracking-[0.2em] uppercase text-xs"
                    style={{ color: "rgba(255,255,255,0.38)", fontWeight: 600 }}
                  >
                    Admission
                  </span>
                </div>
                <p
                  style={{
                    fontSize: "2rem",
                    fontWeight: 900,
                    color: "#4ade80",
                    letterSpacing: "0.04em",
                    lineHeight: 1,
                    marginBottom: "0.3rem",
                  }}
                >
                  FREE
                </p>
                <div className="flex items-center gap-1.5 mt-1">
                  <span
                    className="w-1.5 h-1.5 rounded-full"
                    style={{ backgroundColor: "#4ade80" }}
                  />
                  <span style={{ fontSize: "0.72rem", color: "rgba(255,255,255,0.4)" }}>
                    Open to everyone
                  </span>
                </div>
              </SpotlightCard>

              {/* Organiser */}
              <SpotlightCard spotlightColor="rgba(156, 163, 175, 0.15)" className="flex-1">
                <div className="flex items-center gap-2 mb-2">
                  <Building2 size={13} style={{ color: "rgba(255,255,255,0.4)" }} />
                  <span
                    className="tracking-[0.2em] uppercase text-xs"
                    style={{ color: "rgba(255,255,255,0.38)", fontWeight: 600 }}
                  >
                    Organised By
                  </span>
                </div>
                <p
                  style={{
                    fontSize: "1.1rem",
                    fontWeight: 800,
                    color: "#ffffff",
                    lineHeight: 1.2,
                    marginBottom: "0.3rem",
                  }}
                >
                  EQ Solutions
                </p>
                <p style={{ fontSize: "0.72rem", color: "rgba(255,255,255,0.3)", lineHeight: 1.4 }}>
                  Bringing cultures together,<br />one festival at a time.
                </p>
              </SpotlightCard>
            </div>

          </div>
        </div>

        {/* ── BOTTOM STRIP: The Spirit of Songkran ── */}
        {/* Hidden per user request */}

      </div>
    </section>
  );
}
