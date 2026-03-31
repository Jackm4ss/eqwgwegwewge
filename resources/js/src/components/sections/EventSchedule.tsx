import { useState, useRef } from "react";
import { motion, AnimatePresence, useInView } from "motion/react";
import { Crown, Music, Waves, Mic2, Star, Drama } from "lucide-react";
import { SECTION_BACKGROUND } from "./sectionContrastTheme";

const TILT: React.CSSProperties = { fontFamily: "'Tilt Warp', sans-serif" };

const TITLE_HIGHLIGHT_TEXT_STYLE: React.CSSProperties = {
  color: "#349CD2",

};

type Ev = { time: string; name: string; icon: React.ElementType; color: string; special?: boolean };
type Day = { date: string; dayLabel: string; shortDate: string; events: Ev[] };

const schedule: Day[] = [
  {
    date: "April 9th",
    dayLabel: "Opening Day",
    shortDate: "09",
    events: [
      { time: "12PM", name: "Festival Opens", icon: Star, color: "#2FA7D8" },
      { time: "5PM", name: "Water Play", icon: Waves, color: "#18C7CC" },
      { time: "5PM", name: "Live Band", icon: Music, color: "#c084fc" },
      { time: "9PM", name: "DJ Performance", icon: Mic2, color: "#f472b6" },
    ],
  },
  {
    date: "April 10th",
    dayLabel: "Grand Opening",
    shortDate: "10",
    events: [
      { time: "12PM", name: "Festival Opens", icon: Star, color: "#2FA7D8" },
      { time: "5:30PM", name: "Grand Opening", icon: Crown, color: "#2FA7D8", special: true },
      { time: "5PM", name: "Water Play", icon: Waves, color: "#18C7CC" },
      { time: "5PM", name: "Live Band", icon: Music, color: "#c084fc" },
      { time: "9PM", name: "DJ Performance", icon: Mic2, color: "#f472b6" },
    ],
  },
  {
    date: "April 11th",
    dayLabel: "Thai Kids Fashion Show",
    shortDate: "11",
    events: [
      { time: "12PM", name: "Festival Opens", icon: Star, color: "#2FA7D8" },
      { time: "2PM - 5PM", name: "Thai Kids Fashion Show", icon: Drama, color: "#f472b6", special: true },
      { time: "5PM", name: "Water Play", icon: Waves, color: "#18C7CC" },
      { time: "5PM", name: "Live Band", icon: Music, color: "#c084fc" },
      { time: "9PM", name: "DJ Performance", icon: Mic2, color: "#f472b6" },
    ],
  },
  {
    date: "Apr 12 – 19",
    dayLabel: "Daily Programme",
    shortDate: "12–19",
    events: [
      { time: "12PM", name: "Festival Opens", icon: Star, color: "#2FA7D8" },
      { time: "5PM", name: "Water Play", icon: Waves, color: "#18C7CC" },
      { time: "5PM", name: "Live Band", icon: Music, color: "#c084fc" },
      { time: "9PM", name: "DJ Performance", icon: Mic2, color: "#f472b6" },
    ],
  },
];

export function EventSchedule() {
  const [active, setActive] = useState(0);
  const ref = useRef(null);
  const inView = useInView(ref, { once: true, amount: 0.15 });
  const getDisplayDate = (day: Day) =>
    day.dayLabel === "Daily Programme" ? "April 12th - 19th" : day.date;
  const getDisplayShortDate = (day: Day) =>
    day.dayLabel === "Daily Programme" ? "12-19" : day.shortDate;

  return (
    <section id="schedule" className="py-24 md:py-36 relative overflow-hidden" style={SECTION_BACKGROUND}>
      <div
        className="absolute inset-0 pointer-events-none"
        style={{
          background: "radial-gradient(ellipse at 50% 100%, rgba(255,255,255,0.10), transparent 60%)",
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
          {/* Header */}
          <div className="grid lg:grid-cols-2 gap-8 mb-16 md:mb-20 items-end">
            <div>
              <motion.div
                className="flex items-center gap-3 mb-8"
                initial={{ opacity: 0 }}
                whileInView={{ opacity: 1 }}
                viewport={{ once: true }}
              >
                <div className="w-8 h-px bg-black/50" />
                <span
                  style={{
                    ...TILT,
                    fontSize: "0.72rem",
                    letterSpacing: "0.18em",
                    color: "rgba(0,0,0,0.72)",
                    textTransform: "uppercase",
                  }}
                >
                  EVENT TIMELINE
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
                    fontSize: "clamp(2.3rem,5.5vw,4.2rem)",
                    ...TITLE_HIGHLIGHT_TEXT_STYLE,
                    lineHeight: 1,
                    letterSpacing: "0.01em",
                  }}
                >
                  Event Schedule
                </motion.h2>
              </div>
            </div>

            <motion.p
              initial={{ opacity: 0, y: 20 }}
              whileInView={{ opacity: 1, y: 0 }}
              viewport={{ once: true }}
              transition={{ delay: 0.3 }}
              style={{
                ...TILT,
                fontSize: "0.8rem",
                color: "rgba(0,0,0,0.58)",
                lineHeight: 1.8,
              }}
            >
              Select a day to see the full lineup. All times are local Malaysia time (MYT, UTC+8).
            </motion.p>
          </div>

          <div ref={ref} className="grid lg:grid-cols-12 gap-6 lg:gap-10">
            {/* Day Selector */}
            <div className="lg:col-span-4 flex lg:flex-col gap-3 overflow-x-auto lg:overflow-visible pb-2 lg:pb-0">
              {schedule.map((day, i) => (
                <motion.button
                  key={`${day.dayLabel}-${i}`}
                  initial={{ opacity: 0, x: -20 }}
                  animate={inView ? { opacity: 1, x: 0 } : {}}
                  transition={{ delay: i * 0.08, duration: 0.6 }}
                  onClick={() => setActive(i)}
                  className="flex-shrink-0 text-left rounded-2xl px-5 py-4 transition-all duration-300 group relative overflow-hidden"
                  style={{
                    background: active === i ? "rgba(255,255,255,0.42)" : "rgba(255,255,255,0.20)",
                    border: `1px solid ${active === i ? "rgba(0,0,0,0.18)" : "rgba(255,255,255,0.28)"}`,
                    minWidth: 180,
                    boxShadow: active === i ? "0 10px 24px rgba(0,0,0,0.08)" : "none",
                  }}
                >

                  <p
                    style={{
                      ...TILT,
                      fontSize: "0.62rem",
                      letterSpacing: "0.14em",
                      color: active === i ? "#111111" : "rgba(0,0,0,0.55)",
                      textTransform: "uppercase",
                      marginBottom: 4,
                    }}
                  >
                    APR {getDisplayShortDate(day)}
                  </p>

                  <p
                    style={{
                      ...TILT,
                      fontSize: "0.95rem",
                      color: active === i ? "#111111" : "rgba(0,0,0,0.72)",
                    }}
                  >
                    {day.dayLabel}
                  </p>

                  <p
                    style={{
                      ...TILT,
                      fontSize: "0.68rem",
                      color: "rgba(0,0,0,0.50)",
                      marginTop: 4,
                    }}
                  >
                    {getDisplayDate(day)}
                  </p>
                </motion.button>
              ))}
            </div>

            {/* Events Panel */}
            <div className="lg:col-span-8">
              <div
                style={{
                  background: "rgba(255,255,255,0.24)",
                  border: "1px solid rgba(255,255,255,0.35)",
                  borderRadius: 24,
                  padding: "28px 24px",
                  minHeight: 380,
                  boxShadow: "0 12px 36px rgba(0,0,0,0.08)",
                  backdropFilter: "blur(8px)",
                  WebkitBackdropFilter: "blur(8px)",
                }}
              >
                <AnimatePresence mode="wait">
                  <motion.div
                    key={active}
                    initial={{ opacity: 0, y: 12 }}
                    animate={{ opacity: 1, y: 0 }}
                    exit={{ opacity: 0, y: -12 }}
                    transition={{ duration: 0.35, ease: "easeOut" }}
                  >
                    <div
                      className="flex items-center gap-4 mb-6 pb-5"
                      style={{ borderBottom: "1px solid rgba(0,0,0,0.10)" }}
                    >
                      <div
                        style={{
                          minWidth: 52,
                          width: "fit-content",
                          height: 52,
                          borderRadius: 14,
                          background: "rgba(255,255,255,0.42)",
                          border: "1px solid rgba(0,0,0,0.10)",
                          display: "flex",
                          alignItems: "center",
                          justifyContent: "center",
                          flexShrink: 0,
                          padding: "0 12px",
                        }}
                      >
                        <span
                          style={{
                            ...TILT,
                            color: "#111111",
                            fontSize: "1rem",
                          }}
                        >
                          {getDisplayShortDate(schedule[active])}
                        </span>
                      </div>

                      <div>
                        <p
                          style={{
                            ...TILT,
                            fontSize: "1rem",
                            color: "#111111",
                          }}
                        >
                          {schedule[active].dayLabel}
                        </p>
                        <p
                          style={{
                            ...TILT,
                            fontSize: "0.68rem",
                            color: "rgba(0,0,0,0.52)",
                            marginTop: 4,
                          }}
                        >
                          {schedule[active].date} · 12PM–12AM
                        </p>
                      </div>
                    </div>

                    <div className="space-y-3">
                      {schedule[active].events.map((ev, i) => {
                        const EIcon = ev.icon;
                        return (
                          <motion.div
                            key={ev.name}
                            initial={{ opacity: 0, x: 10 }}
                            animate={{ opacity: 1, x: 0 }}
                            transition={{ delay: i * 0.06, duration: 0.4 }}
                            className="flex items-center gap-4 rounded-xl px-4 py-3 group"
                            style={{
                              background: ev.special ? "rgba(255,255,255,0.42)" : "rgba(255,255,255,0.22)",
                              border: `1px solid ${ev.special ? "rgba(0,0,0,0.12)" : "rgba(255,255,255,0.24)"}`,
                            }}
                          >
                            <EIcon size={15} style={{ color: ev.color, flexShrink: 0 }} />

                            <p
                              style={{
                                ...TILT,
                                fontSize: "0.82rem",
                                color: "#111111",
                                flex: 1,
                              }}
                            >
                              {ev.name}

                            </p>

                            <span
                              style={{
                                ...TILT,
                                fontSize: "0.64rem",
                                color: "rgba(0,0,0,0.52)",
                                flexShrink: 0,
                              }}
                            >
                              {ev.time}
                            </span>
                          </motion.div>
                        );
                      })}
                    </div>
                  </motion.div>
                </AnimatePresence>
              </div>

              {/* Legend */}
              <div className="flex flex-wrap gap-5 mt-5 px-1">
                {[
                  { icon: Star, label: "Festival Opens", color: "#2FA7D8" },
                  { icon: Crown, label: "Grand Opening", color: "#2FA7D8" },
                  { icon: Drama, label: "Thai Kids Show", color: "#f472b6" },
                  { icon: Waves, label: "Water Play", color: "#18C7CC" },
                  { icon: Music, label: "Live Band", color: "#c084fc" },
                  { icon: Mic2, label: "DJ Performance", color: "#f472b6" },
                ].map(({ icon: I, label, color }) => (
                  <div key={label} className="flex items-center gap-1.5">
                    <I size={11} style={{ color }} />
                    <span
                      style={{
                        ...TILT,
                        fontSize: "0.64rem",
                        color: "rgba(0,0,0,0.56)",
                        letterSpacing: "0.04em",
                      }}
                    >
                      {label}
                    </span>
                  </div>
                ))}
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  );
}
