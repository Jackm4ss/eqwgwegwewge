import React, { type CSSProperties } from "react";
import { MapPin, Clock, Ticket, Building2, ArrowUpRight } from "lucide-react";
import { motion } from "motion/react";

import { SpotlightCard } from "../ui/SpotlightCard";
import ShapeGrid from "../ui/ShapeGrid/ShapeGrid";
import { SECTION_BACKGROUND } from "./sectionContrastTheme";

const TILT: CSSProperties = {
  fontFamily: "'Tilt Warp', sans-serif",
};

const TITLE_HIGHLIGHT_TEXT_STYLE: CSSProperties = {
  color: "#35d8f7",
  textShadow: "2px 3px 0 rgba(31, 52, 71, 0.22), 0 3px 8px rgba(31, 52, 71, 0.10)",
};

const NUMBER_HIGHLIGHT_TEXT_STYLE: CSSProperties = {
  color: "#35d8f7",
  textShadow: "1px 2px 0 rgba(31, 52, 71, 0.18), 0 2px 6px rgba(31, 52, 71, 0.08)",
};

const LABEL_TEXT_STYLE: CSSProperties = {
  color: "rgba(0,0,0,0.62)",
};

const BODY_TEXT_STYLE: CSSProperties = {
  color: "rgba(0,0,0,0.78)",
};

const META_TEXT_STYLE: CSSProperties = {
  color: "rgba(0,0,0,0.58)",
};

const PANEL_STYLE: CSSProperties = {
  background:
    "linear-gradient(rgba(255,255,255,0.08) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,0.08) 1px, transparent 1px), rgba(186, 230, 253, 0.78)",
  backgroundSize: "26px 26px, 26px 26px, auto",
  border: "1px solid rgba(255,255,255,0.42)",
  boxShadow: "0 18px 60px rgba(0,0,0,0.10)",
  backdropFilter: "blur(12px)",
  WebkitBackdropFilter: "blur(12px)",
};

const SOFT_CARD_STYLE: CSSProperties = {
  background: "rgba(255,255,255,0.40)",
  border: "1px solid rgba(255,255,255,0.42)",
  boxShadow: "0 10px 30px rgba(0,0,0,0.06)",
};

const DATE_CARD_STYLE: CSSProperties = {
  background:
    "linear-gradient(135deg, rgba(255,255,255,0.42) 0%, rgba(210,238,245,0.74) 55%, rgba(175,225,239,0.54) 100%)",
  border: "1px solid rgba(255,255,255,0.45)",
  boxShadow: "0 10px 35px rgba(0,0,0,0.08)",
};

export function EventDetails() {
  return (
    <section
      id="details"
      className="relative flex min-h-screen w-full items-center justify-center overflow-hidden px-6 py-20"
      style={SECTION_BACKGROUND}
    >
      <div className="pointer-events-none absolute inset-0 z-0 opacity-[0.14]">
        <ShapeGrid
          direction="diagonal"
          speed={0.5}
          squareSize={80}
          borderColor="rgba(255,255,255,0.10)"
          hoverFillColor="rgba(255,255,255,0.03)"
        />
      </div>

      <div className="relative z-10 w-full max-w-6xl">
        <div className="rounded-[32px] p-6 md:rounded-[40px] md:p-10 lg:p-14" style={PANEL_STYLE}>
          <div className="grid gap-6 lg:grid-cols-[minmax(0,0.54fr)_minmax(0,0.46fr)] lg:gap-8">
            <div className="flex flex-col gap-6 md:gap-8 h-full">
              <div className="max-w-[28rem]">
                <motion.div
                  className="mb-5 flex items-center gap-3"
                  initial={{ opacity: 0, x: -20 }}
                  whileInView={{ opacity: 1, x: 0 }}
                  viewport={{ once: true }}
                >
                  <div className="h-px w-8 bg-black/45" />
                  <span
                    className="text-xs uppercase tracking-[0.2em]"
                    style={{ ...TILT, ...LABEL_TEXT_STYLE }}
                  >
                    OVERVIEW
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
                        ...TITLE_HIGHLIGHT_TEXT_STYLE,
                        fontSize: "clamp(2.4rem,5.5vw,4.8rem)",
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
                        ...TITLE_HIGHLIGHT_TEXT_STYLE,
                        fontSize: "clamp(2.4rem,5.5vw,4.8rem)",
                        lineHeight: 0.95,
                        letterSpacing: "0.01em",
                      }}
                    >
                      Details
                    </motion.h2>
                  </div>
                </div>
              </div>

              <SpotlightCard
                spotlightColor="rgba(255,255,255,0.22)"
                className="flex-1 min-h-[250px] pb-7 md:min-h-[270px] h-full"
                contentClassName="justify-start h-full"
                style={DATE_CARD_STYLE}
              >
              <div
                className="pointer-events-none absolute -left-10 -top-20 h-80 w-80 rounded-full"
                style={{
                  background: "radial-gradient(circle, rgba(255,255,255,0.20) 0%, transparent 70%)",
                }}
              />

              <div className="relative z-10 mb-2 flex items-center gap-2">
                <div
                  className="flex h-5 w-5 items-center justify-center rounded"
                  style={{ color: "#35d8f7" }}
                >
                  <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                    <rect
                      x="1"
                      y="2.5"
                      width="14"
                      height="12"
                      rx="2"
                      stroke="currentColor"
                      strokeWidth="1.3"
                    />
                    <path d="M1 6.5h14" stroke="currentColor" strokeWidth="1.3" />
                    <path
                      d="M5 1v3M11 1v3"
                      stroke="currentColor"
                      strokeWidth="1.3"
                      strokeLinecap="round"
                    />
                  </svg>
                </div>

                <span
                  className="text-xs uppercase tracking-[0.16em]"
                  style={{ ...TILT, ...LABEL_TEXT_STYLE }}
                >
                  Festival Dates
                </span>
              </div>

              <div
                className="relative z-10 uppercase tracking-[0.12em]"
                style={{
                  ...TILT,
                  ...LABEL_TEXT_STYLE,
                  fontSize: "clamp(1.1rem, 2.2vw, 1.65rem)",
                  lineHeight: 1,
                  marginBottom: "0.18em",
                  color: "#35d8f7",
                }}
              >
                APR
              </div>

              <div
                className="relative z-10"
                style={{
                  ...TILT,
                  ...TITLE_HIGHLIGHT_TEXT_STYLE,
                  fontSize: "clamp(3.9rem, 8.2vw, 6.6rem)",
                  lineHeight: 0.82,
                  letterSpacing: "0.01em",
                  marginBottom: "0.1em",
                }}
              >
                9-19
              </div>

                <div className="relative z-10 flex flex-wrap items-end gap-4 pt-3">
                  <span
                    style={{
                      ...TILT,
                      ...BODY_TEXT_STYLE,
                      fontSize: "0.95rem",
                      letterSpacing: "0.06em",
                    }}
                  >
                    2026
                  </span>

                  <span
                    style={{
                      ...TILT,
                      ...META_TEXT_STYLE,
                      fontSize: "0.72rem",
                      letterSpacing: "0.04em",
                    }}
                  >
                    11 days of celebration
                  </span>
                </div>
              </SpotlightCard>
            </div>

            <div className="flex flex-col justify-between gap-4 h-full">
              <SpotlightCard
                spotlightColor="rgba(255,255,255,0.18)"
                className="w-full"
                contentClassName="justify-start"
                style={SOFT_CARD_STYLE}
              >
                <div className="mb-2 flex items-center gap-2">
                  <MapPin size={13} style={{ color: "#35d8f7" }} />
                  <span
                    className="text-xs uppercase tracking-[0.16em]"
                    style={{ ...TILT, ...LABEL_TEXT_STYLE }}
                  >
                    Venue
                  </span>
                </div>

                <p
                  style={{
                    ...TILT,
                    ...BODY_TEXT_STYLE,
                    fontSize: "1.8rem",
                    lineHeight: 1.2,
                    marginBottom: "0.3rem",
                    color: "#35d8f7",
                  }}
                >
                  @Gf Forecourt Outdoor Carpark 1 Utama, Malaysia
                </p>

                <p
                  style={{
                    ...TILT,
                    ...META_TEXT_STYLE,
                    fontSize: "1rem",
                    color: "#35d8f7",
                  }}
                >
                  1 Utama | Malaysia
                </p>

                <a
                  href="https://maps.app.goo.gl/nV1FXduimtyZ2cbXA"
                  target="_blank"
                  rel="noopener noreferrer"
                  className="group mt-3 flex cursor-pointer items-center gap-1.5"
                  style={{
                    ...TILT,
                    fontSize: "1.5rem",
                    letterSpacing: "0.03em",
                    textDecoration: "none",
                    color: "#35d8f7",
                  }}
                >
                  <span className="border-b border-transparent transition-all duration-300 group-hover:border-[#35d8f7] group-hover:text-[#35d8f7]">
                    Get Directions
                  </span>
                  <ArrowUpRight
                    size={12}
                    className="transition-all duration-300 group-hover:-translate-y-0.5 group-hover:translate-x-0.5 group-hover:text-[#35d8f7]"
                  />
                </a>
              </SpotlightCard>

              <SpotlightCard
                spotlightColor="rgba(255,255,255,0.18)"
                className="w-full"
                contentClassName="justify-start"
                style={SOFT_CARD_STYLE}
              >
                <div className="mb-3 flex items-center gap-2">
                  <Clock size={13} style={{ color: "#35d8f7" }} />
                  <span
                    className="text-xs uppercase tracking-[0.16em]"
                    style={{ ...TILT, ...LABEL_TEXT_STYLE }}
                  >
                    Daily Hours
                  </span>
                </div>

                <div className="flex flex-wrap items-baseline gap-3">
                  <span
                    style={{
                      ...TILT,
                      ...NUMBER_HIGHLIGHT_TEXT_STYLE,
                      fontSize: "1.85rem",
                      lineHeight: 1,
                    }}
                  >
                    12PM
                  </span>
                  <span
                    style={{
                      ...TILT,
                      ...META_TEXT_STYLE,
                      fontSize: "0.68rem",
                    }}
                  >
                    to
                  </span>
                  <span
                    style={{
                      ...TILT,
                      ...NUMBER_HIGHLIGHT_TEXT_STYLE,
                      fontSize: "1.85rem",
                      lineHeight: 1,
                    }}
                  >
                    12AM
                  </span>
                </div>

                <p
                  style={{
                    ...TILT,
                    ...META_TEXT_STYLE,
                    fontSize: "1rem",
                    marginTop: "0.45rem",
                    color: "#35d8f7"
                  }}
                >
                  12 hours of festivities
                </p>
              </SpotlightCard>

              <div className="flex flex-col gap-4 sm:flex-row w-full">
                <SpotlightCard
                  spotlightColor="rgba(255,255,255,0.18)"
                  className="h-full flex-1"
                  contentClassName="justify-start"
                  style={SOFT_CARD_STYLE}
                >
                  <div className="mb-2 flex items-center gap-2">
                    <Ticket size={13} style={{ color: "#35d8f7" }} />
                    <span
                      className="text-xs uppercase tracking-[0.16em]"
                      style={{ ...TILT, ...LABEL_TEXT_STYLE }}
                    >
                      Admission
                    </span>
                  </div>

                  <p
                    style={{
                      ...TILT,
                      ...NUMBER_HIGHLIGHT_TEXT_STYLE,
                      fontSize: "2rem",
                      letterSpacing: "0.02em",
                      lineHeight: 1,
                      marginBottom: "0.3rem",
                    }}
                  >
                    FREE
                  </p>

                  <div className="mt-1 flex items-center gap-1.5">
                    <span
                      className="h-1.5 w-1.5 rounded-full"
                      style={{ backgroundColor: "#35d8f7" }}
                    />
                    <span
                      style={{
                        ...TILT,
                        ...META_TEXT_STYLE,
                        fontSize: "1rem",
                        color: "#35d8f7"
                      }}
                    >
                      Open to everyone
                    </span>
                  </div>
                </SpotlightCard>

                <a
                  href="https://www.eqsolutions.com.my/"
                  target="_blank"
                  rel="noopener noreferrer"
                  className="group block h-full flex-1 cursor-pointer no-underline"
                >
                  <SpotlightCard
                    spotlightColor="rgba(255,255,255,0.18)"
                    className="h-full transition-all duration-300 group-hover:-translate-y-1 group-hover:border-[#35d8f7]"
                    contentClassName="justify-start"
                    style={SOFT_CARD_STYLE}
                  >
                    <div className="mb-2 flex items-center gap-2">
                      <Building2
                        size={13}
                        style={{ color: "#35d8f7" }}
                        className="transition-colors duration-300 group-hover:text-[#35d8f7]"
                      />
                      <span
                        className="text-xs uppercase tracking-[0.16em] transition-colors duration-300 group-hover:text-[#35d8f7]"
                        style={{ ...TILT, ...LABEL_TEXT_STYLE }}
                      >
                        Organised By
                      </span>
                    </div>

                    <p
                      className="transition-colors duration-300 group-hover:text-[#35d8f7]"
                      style={{
                        ...TILT,
                        ...BODY_TEXT_STYLE,
                        fontSize: "1.3rem",
                        lineHeight: 1.2,
                        marginBottom: "0.3rem",
                        color: "#35d8f7"
                      }}
                    >
                      EQ Solutions
                      <ArrowUpRight
                        strokeWidth={3}
                        size={14}
                        className="ml-1 inline-block translate-y-2 -translate-x-2 opacity-0 text-[#35d8f7] transition-all duration-300 group-hover:translate-y-0 group-hover:translate-x-0 group-hover:opacity-100"
                      />
                    </p>
                  </SpotlightCard>
                </a>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  );
}
