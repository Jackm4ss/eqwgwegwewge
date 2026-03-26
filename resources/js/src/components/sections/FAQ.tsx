import { useState } from "react";
import { motion, AnimatePresence } from "motion/react";

const TILT: React.CSSProperties = { fontFamily: "'Tilt Warp', sans-serif" };

const faqs = [
  {
    q: "Is the event free to attend?",
    a: "Yes! Songkran Festival Malaysia 2026 is a free-entry event, open to everyone. Participants are required to register via our website in advance, or may register on-site by scanning the QR code provided at the event entrance.",
  },
  {
    q: "When and where is the festival?",
    a: "The festival will take place at 1 Utama Shopping Centre (GF Forecourt Carpark) from 9th to 19th April 2026, operating daily from 12:00 PM to 12:00 AM.",
  },
  {
    q: "What should I wear to the water play?",
    a: "We recommend wearing light, comfortable clothing that you don’t mind getting wet. Non-slip footwear is highly encouraged, and don’t forget to bring waterproof protection for your belongings.",
  },
  {
    q: "Is it suitable for families and children?",
    a: "Absolutely! The festival is family-friendly and suitable for visitors of all ages. We encourage parents to supervise children, especially in water play areas.",
  },
  {
    q: "Will there be food and drinks?",
    a: "Yes! There will be a wide variety of food and beverage vendors, offering everything from local favourites to authentic Thai cuisine and refreshing drinks.",
  },
  {
    q: "Who is organising this festival?",
    a: "This event is proudly organised by EQ Solutions, in close collaboration with the Royal Thai Embassy and 1 Utama Shopping Centre, bringing together a vibrant cultural celebration for the public.",
  },
  {
    q: "Are activities confirmed?",
    a: "Yes, a range of exciting activities are planned, including water play zones, live performances, games, and cultural showcases.",
  },
];

export function FAQ() {
  const [open, setOpen] = useState<number | null>(null);

  return (
    <section id="faq" className="py-24 md:py-36 relative overflow-hidden">
      <div
        className="absolute pointer-events-none select-none"
        style={{ bottom: "-4%", right: "-2%", opacity: 0.05 }}
      >
        <span
          style={{
            ...TILT,
            fontSize: "clamp(180px,28vw,360px)",
            color: "rgba(0,0,0,0.08)",
            lineHeight: 1,
          }}
        >
          ?
        </span>
      </div>

      <div className="max-w-5xl mx-auto px-6 md:px-12 lg:px-16 relative z-10">
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
          <div className="grid md:grid-cols-2 gap-12 items-end mb-16 md:mb-20">
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
                  Questions
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
                    fontSize: "clamp(2.3rem,5.5vw,4.8rem)",
                    color: "#111111",
                    lineHeight: 1,
                    letterSpacing: "0.01em",
                  }}
                >
                  FAQ
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
              Everything you need to know before your visit to Songkran Festival 2026.
            </motion.p>
          </div>

          {/* Accordion */}
          <div className="space-y-3">
            {faqs.map((faq, i) => (
              <motion.div
                key={i}
                initial={{ opacity: 0, y: 16 }}
                whileInView={{ opacity: 1, y: 0 }}
                viewport={{ once: true, amount: 0.3 }}
                transition={{ delay: i * 0.06, duration: 0.6 }}
                className="rounded-2xl overflow-hidden"
                style={{
                  background: open === i ? "rgba(255,255,255,0.34)" : "rgba(255,255,255,0.20)",
                  border: `1px solid ${open === i ? "rgba(0,0,0,0.14)" : "rgba(255,255,255,0.26)"}`,
                  boxShadow: open === i ? "0 10px 28px rgba(0,0,0,0.08)" : "none",
                }}
              >
                <button
                  onClick={() => setOpen(open === i ? null : i)}
                  className="w-full flex items-start justify-between gap-6 py-6 px-5 md:px-6 text-left group"
                >
                  <div className="flex items-start gap-5">
                    <span
                      style={{
                        ...TILT,
                        fontSize: "0.72rem",
                        color: open === i ? "#111111" : "rgba(0,0,0,0.42)",
                        letterSpacing: "0.08em",
                        minWidth: 28,
                        paddingTop: 2,
                        transition: "color 0.3s",
                      }}
                    >
                      {String(i + 1).padStart(2, "0")}
                    </span>

                    <span
                      style={{
                        ...TILT,
                        fontSize: "clamp(0.92rem,1.4vw,1.08rem)",
                        color: open === i ? "#111111" : "rgba(0,0,0,0.74)",
                        lineHeight: 1.45,
                        transition: "color 0.3s",
                      }}
                    >
                      {faq.q}
                    </span>
                  </div>

                  <motion.div
                    animate={{ rotate: open === i ? 45 : 0 }}
                    transition={{ duration: 0.25 }}
                    className="flex-shrink-0 mt-0.5"
                    style={{
                      color: open === i ? "#111111" : "rgba(0,0,0,0.42)",
                      fontSize: "1.3rem",
                      lineHeight: 1,
                    }}
                  >
                    +
                  </motion.div>
                </button>

                <AnimatePresence>
                  {open === i && (
                    <motion.div
                      initial={{ height: 0, opacity: 0 }}
                      animate={{ height: "auto", opacity: 1 }}
                      exit={{ height: 0, opacity: 0 }}
                      transition={{ duration: 0.35, ease: [0.22, 1, 0.36, 1] }}
                      style={{ overflow: "hidden" }}
                    >
                      <p
                        style={{
                          ...TILT,
                          fontSize: "0.76rem",
                          color: "rgba(0,0,0,0.60)",
                          lineHeight: 1.8,
                          paddingLeft: 52,
                          paddingRight: 24,
                          paddingBottom: 24,
                        }}
                      >
                        {faq.a}
                      </p>
                    </motion.div>
                  )}
                </AnimatePresence>
              </motion.div>
            ))}
          </div>
        </div>
      </div>
    </section>
  );
}