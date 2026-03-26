import { useState } from "react";
import { motion, AnimatePresence } from "motion/react";

const TILT: React.CSSProperties = { fontFamily: "'Tilt Warp', sans-serif" };

type FAQItem = {
  q: string;
  a: string[];
  list?: boolean;
};

const faqs: FAQItem[] = [
  {
    q: "Ticket and Entry Requirements",
    list: true,
    a: [
      "Age Restriction: Minors under 13 years old need to be accompanied by an adult or guardian.",
      "Valid QR code and original ID cards or passports must be presented at the venue.",
      "Name Matching: QR ticket must be registered to the attendee's full name, matching their official ID.",
      "Re-entry on the same day does not require QR scanning, provided the valid UV stamp of the day is still visible.",
    ],
  },
  {
    q: "Prohibited Items",
    list: true,
    a: [
      "To ensure safety, the following are generally prohibited:",
      "Weapons, sharp objects, and fireworks.",
      "Drugs and illegal substances.",
      "Professional cameras (DSLR/mirrorless) and selfie sticks unless under 30 cm.",
      "External food and drinks.",
    ],
  },
  {
    q: "Safety & Behavioral Guidelines",
    list: true,
    a: [
      "Water Fight Safety: Do not aim high-pressure water guns at faces or eyes.",
      "Cultural Respect: Do not splash food vendors, crew on duty, the elderly, or young children.",
      "Liability: Organizers are not responsible for lost or stolen personal property.",
    ],
  },
  {
    q: "Event & Weather Policy",
    list: true,
    a: [
      "Rain or Shine: Events proceed regardless of weather unless conditions are deemed dangerous, in which case the organizer may amend the event.",
      "Changes: Organizers reserve the right to change schedules, lineups, or terms without prior notice.",
    ],
  },
  {
    q: "Media Rights",
    a: [
      "By entering the event, you consent to being photographed or recorded, with the content being used for promotional purposes.",
    ],
  },
  {
    q: "Do I need to scan the QR code every day?",
    a: [
      "You only need to scan once per day upon entry. If you have already scanned for the day, there is no need to scan again when re-entering.",
    ],
  },
  {
    q: "What if I lose my QR code?",
    a: [
      "No worries. You can retrieve your registration QR code from your confirmation email, or re-register on-site by scanning the registration QR displayed at the entrance.",
    ],
  },
  {
    q: "Is there a place to change clothes?",
    a: [
      "There are no changing facilities within the event grounds. Visitors are advised to change at the restrooms inside 1 Utama Shopping Centre before entering or after leaving the water play areas.",
    ],
  },
  {
    q: "Are there lockers or storage spaces for belongings?",
    a: [
      "There are locker and storage spaces prepared. However, visitors are encouraged to bring minimal belongings. The organiser is not responsible for any loss or damage to personal items.",
    ],
  },
  {
    q: "What items should I avoid bringing?",
    a: [
      "Please avoid bringing valuables, electronics without waterproof protection, and any prohibited or dangerous items. Water play areas will get very wet.",
    ],
  },
  {
    q: "What happens if it rains?",
    a: [
      "The festival will continue rain or shine. In the event of severe weather for safety reasons, certain activities or performances may be temporarily paused.",
    ],
  },
  {
    q: "Is parking available at the venue?",
    a: [
      "Yes, parking is available within 1 Utama Shopping Centre. Visitors are encouraged to arrive early, as parking may be limited during peak hours.",
    ],
  },
  {
    q: "Is first aid available on-site?",
    a: [
      "Yes, a first aid station will be available throughout the event for any medical assistance.",
    ],
  },
  {
    q: "Can I bring my own water guns or buckets?",
    a: [
      "Yes. You are welcome to bring your own water guns. However, buckets, high-pressure devices, or unsafe equipment are not permitted.",
    ],
  },
  {
    q: "Will there be security at the event?",
    a: [
      "Yes, security personnel and event crew will be stationed throughout the venue to ensure a safe and enjoyable experience for all visitors.",
    ],
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

          <div className="space-y-3">
            {faqs.map((faq, i) => (
              <motion.div
                key={faq.q}
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
                      <div
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
                        <div className="space-y-3">
                          {faq.a.map((line, lineIndex) => (
                            faq.list ? (
                              <div key={`${faq.q}-${lineIndex}`} className="flex gap-3">
                                <span className="pt-1 text-[0.9em] leading-none text-black/45">-</span>
                                <p>{line}</p>
                              </div>
                            ) : (
                              <p key={`${faq.q}-${lineIndex}`}>{line}</p>
                            )
                          ))}
                        </div>
                      </div>
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
