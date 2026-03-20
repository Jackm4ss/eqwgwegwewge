import { useState, useRef } from "react";
import { motion, AnimatePresence, useInView } from "motion/react";
import { Crown, Music, Waves, Mic2, Gamepad2, Star, Drama } from "lucide-react";

const SYNE: React.CSSProperties = { fontFamily: "'Syne', sans-serif" };
const SG: React.CSSProperties = { fontFamily: "'Space Grotesk', sans-serif" };

type Ev = { time: string; name: string; icon: React.ElementType; color: string; special?: boolean };
type Day = { date: string; dayLabel: string; shortDate: string; events: Ev[] };

const schedule: Day[] = [
  {
    date: "Thursday, April 9", dayLabel: "Opening Day", shortDate: "09",
    events: [
      { time: "12:00 PM", name: "Festival Opens", icon: Star, color: "#2FA7D8", special: true },
      { time: "5:00 PM", name: "Water Play Begins", icon: Waves, color: "#18C7CC" },
      { time: "All Day", name: "Games & Water Sports", icon: Gamepad2, color: "#4ade80" },
      { time: "Evening", name: "Live Band Session", icon: Music, color: "#c084fc" },
      { time: "Late Night", name: "DJ Performance", icon: Mic2, color: "#f472b6" },
    ],
  },
  {
    date: "Friday, April 10", dayLabel: "Opening Ceremony", shortDate: "10",
    events: [
      { time: "TBC Evening", name: "Grand Opening Ceremony", icon: Crown, color: "#2FA7D8", special: true },
      { time: "Ceremony", name: "With the Thai Ambassador", icon: Star, color: "#2FA7D8", special: true },
      { time: "5:00 PM", name: "Water Play", icon: Waves, color: "#18C7CC" },
      { time: "Evening", name: "Live Band Session", icon: Music, color: "#c084fc" },
      { time: "Night", name: "DJ Performance", icon: Mic2, color: "#f472b6" },
    ],
  },
  {
    date: "Saturday, April 11", dayLabel: "Special Day", shortDate: "11",
    events: [
      { time: "TBC", name: "Kids Fashion Show", icon: Drama, color: "#f472b6", special: true },
      { time: "TBC", name: "Guest Appearance", icon: Star, color: "#2FA7D8", special: true },
      { time: "5:00 PM", name: "Water Play", icon: Waves, color: "#18C7CC" },
      { time: "Evening", name: "Live Band Session", icon: Music, color: "#c084fc" },
      { time: "Night", name: "DJ Performance", icon: Mic2, color: "#f472b6" },
    ],
  },
  {
    date: "Apr 12 – 19", dayLabel: "Daily Programme", shortDate: "12–19",
    events: [
      { time: "12:00 PM", name: "Festival Opens Daily", icon: Star, color: "#2FA7D8" },
      { time: "5:00 PM", name: "Water Play", icon: Waves, color: "#18C7CC" },
      { time: "All Day", name: "Games & Water Sports", icon: Gamepad2, color: "#4ade80" },
      { time: "Evening", name: "Live Band Session", icon: Music, color: "#c084fc" },
      { time: "Night", name: "DJ Performance", icon: Mic2, color: "#f472b6" },
    ],
  },
];

export function EventSchedule() {
  const [active, setActive] = useState(0);
  const ref = useRef(null);
  const inView = useInView(ref, { once: true, amount: 0.15 });

  return (
    <section id="schedule" style={{ background: "#050508" }} className="py-24 md:py-36 relative overflow-hidden">
      <div className="absolute inset-0 pointer-events-none" style={{ background: "radial-gradient(ellipse at 50% 100%, rgba(24,199,204,0.04), transparent 60%)" }} />

      <div className="max-w-7xl mx-auto px-6 md:px-12 lg:px-16">
        {/* Header */}
        <div className="grid lg:grid-cols-2 gap-8 mb-16 md:mb-20 items-end">
          <div>
            <motion.div className="flex items-center gap-3 mb-8" initial={{ opacity: 0 }} whileInView={{ opacity: 1 }} viewport={{ once: true }}>
              <div className="w-8 h-px bg-[#18C7CC]" />
              <span style={{ ...SG, fontSize: "0.7rem", letterSpacing: "0.25em", color: "#18C7CC", textTransform: "uppercase" }}>Programme</span>
            </motion.div>
            <div style={{ overflow: "hidden" }}>
              <motion.h2
                initial={{ y: "100%", opacity: 0 }} whileInView={{ y: 0, opacity: 1 }} viewport={{ once: true }}
                transition={{ duration: 1, ease: [0.22, 1, 0.36, 1] }}
                style={{ ...SYNE, fontWeight: 800, fontSize: "clamp(2.4rem,5.5vw,4rem)", color: "#EDE8DC", lineHeight: 1, letterSpacing: "-0.03em" }}
              >
                Event Schedule
              </motion.h2>
            </div>
          </div>
          <motion.p
            initial={{ opacity: 0, y: 20 }} whileInView={{ opacity: 1, y: 0 }} viewport={{ once: true }} transition={{ delay: 0.3 }}
            style={{ ...SG, fontSize: "0.9rem", color: "rgba(237,232,220,0.35)", lineHeight: 1.8 }}
          >
            Select a day to see the full lineup. All times are local Malaysia time (MYT, UTC+8).
          </motion.p>
        </div>

        <div ref={ref} className="grid lg:grid-cols-12 gap-6 lg:gap-10">
          {/* Day Selector */}
          <div className="lg:col-span-4 flex lg:flex-col gap-3 overflow-x-auto lg:overflow-visible pb-2 lg:pb-0">
            {schedule.map((day, i) => (
              <motion.button
                key={day.shortDate}
                initial={{ opacity: 0, x: -20 }}
                animate={inView ? { opacity: 1, x: 0 } : {}}
                transition={{ delay: i * 0.08, duration: 0.6 }}
                onClick={() => setActive(i)}
                className="flex-shrink-0 text-left rounded-2xl px-5 py-4 transition-all duration-300 group relative overflow-hidden"
                style={{
                  background: active === i ? "rgba(244,160,51,0.1)" : "rgba(237,232,220,0.03)",
                  border: `1px solid ${active === i ? "rgba(244,160,51,0.3)" : "rgba(237,232,220,0.07)"}`,
                  minWidth: 180,
                }}
                data-hover
              >
                {active === i && (
                  <motion.div
                    layoutId="dayHighlight"
                    className="absolute left-0 top-0 bottom-0 w-0.5 rounded-r"
                    style={{ background: "#2FA7D8" }}
                  />
                )}
                <p style={{ ...SG, fontSize: "0.6rem", letterSpacing: "0.2em", color: active === i ? "#2FA7D8" : "rgba(237,232,220,0.25)", textTransform: "uppercase", marginBottom: 4 }}>
                  APR {day.shortDate}
                </p>
                <p style={{ ...SYNE, fontWeight: 700, fontSize: "1rem", color: active === i ? "#EDE8DC" : "rgba(237,232,220,0.45)" }}>
                  {day.dayLabel}
                </p>
                <p style={{ ...SG, fontSize: "0.72rem", color: "rgba(237,232,220,0.25)", marginTop: 2 }}>{day.date}</p>
              </motion.button>
            ))}
          </div>

          {/* Events Panel */}
          <div className="lg:col-span-8">
            <div style={{ background: "rgba(237,232,220,0.02)", border: "1px solid rgba(237,232,220,0.07)", borderRadius: 20, padding: "28px 24px", minHeight: 380 }}>
              <AnimatePresence mode="wait">
                <motion.div
                  key={active}
                  initial={{ opacity: 0, y: 12 }}
                  animate={{ opacity: 1, y: 0 }}
                  exit={{ opacity: 0, y: -12 }}
                  transition={{ duration: 0.35, ease: "easeOut" }}
                >
                  <div className="flex items-center gap-4 mb-6 pb-5" style={{ borderBottom: "1px solid rgba(237,232,220,0.07)" }}>
                    <div style={{ minWidth: 52, width: "fit-content", height: 52, borderRadius: 14, background: "rgba(244,160,51,0.12)", border: "1px solid rgba(244,160,51,0.2)", display: "flex", alignItems: "center", justifyContent: "center", flexShrink: 0, padding: "0 12px" }}>
                      <span style={{ ...SYNE, fontWeight: 800, color: "#2FA7D8", fontSize: "1.1rem" }}>{schedule[active].shortDate}</span>
                    </div>
                    <div>
                      <p style={{ ...SYNE, fontWeight: 700, fontSize: "1.1rem", color: "#EDE8DC" }}>{schedule[active].dayLabel}</p>
                      <p style={{ ...SG, fontSize: "0.75rem", color: "rgba(237,232,220,0.3)", marginTop: 2 }}>{schedule[active].date} · 12PM–12AM</p>
                    </div>
                  </div>

                  <div className="space-y-2">
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
                            background: ev.special ? `${ev.color}0e` : "rgba(237,232,220,0.025)",
                            border: `1px solid ${ev.special ? `${ev.color}25` : "rgba(237,232,220,0.05)"}`,
                          }}
                        >
                          <EIcon size={15} style={{ color: ev.color, flexShrink: 0 }} />
                          <p style={{ ...SG, fontSize: "0.88rem", color: ev.special ? ev.color : "#EDE8DC", fontWeight: ev.special ? 600 : 400, flex: 1 }}>
                            {ev.name}
                            {ev.special && (
                              <span style={{ marginLeft: 8, fontSize: "0.6rem", letterSpacing: "0.12em", background: `${ev.color}20`, color: ev.color, padding: "2px 7px", borderRadius: 100, border: `1px solid ${ev.color}30` }}>
                                HIGHLIGHT
                              </span>
                            )}
                          </p>
                          <span style={{ ...SG, fontSize: "0.7rem", color: "rgba(237,232,220,0.25)", flexShrink: 0 }}>{ev.time}</span>
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
                { icon: Waves, label: "Water Play", color: "#18C7CC" },
                { icon: Music, label: "Live Band", color: "#c084fc" },
                { icon: Mic2, label: "DJ Night", color: "#f472b6" },
                { icon: Gamepad2, label: "Games", color: "#4ade80" },
                { icon: Star, label: "Special", color: "#2FA7D8" },
              ].map(({ icon: I, label, color }) => (
                <div key={label} className="flex items-center gap-1.5">
                  <I size={11} style={{ color }} />
                  <span style={{ ...SG, fontSize: "0.68rem", color: "rgba(237,232,220,0.3)", letterSpacing: "0.06em" }}>{label}</span>
                </div>
              ))}
            </div>
          </div>
        </div>
      </div>
    </section>
  );
}
