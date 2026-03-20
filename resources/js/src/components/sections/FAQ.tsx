import { useState } from "react";
import { motion, AnimatePresence } from "motion/react";

const SYNE: React.CSSProperties = { fontFamily: "'Syne', sans-serif" };
const SG: React.CSSProperties = { fontFamily: "'Space Grotesk', sans-serif" };

const faqs = [
  { q: "Is the event free to attend?", a: "Yes! Songkran Festival 2026 is completely FREE ENTRY for everyone. Simply show up at the Forecourt, One Utama (Old Wing) and enjoy all the festivities." },
  { q: "When and where is the festival?", a: "April 9 to 19, 2026 — daily from 12 PM to 12 AM (midnight) at Forecourt, One Utama Shopping Centre (Old Wing), Petaling Jaya, Malaysia." },
  { q: "What should I wear to the water play?", a: "We recommend clothes you don't mind getting soaked! Thai-inspired colourful outfits are highly encouraged. Bring a change of clothes and a towel." },
  { q: "Is it suitable for families and children?", a: "Absolutely. Songkran Festival 2026 is a family-friendly event with activities for all ages — including a Kids Fashion Show, water play, games, and more." },
  { q: "Will there be food and drinks?", a: "Yes! Authentic Thai street food stalls and beverages are available throughout the festival grounds, open daily from 12 PM onwards." },
  { q: "Who is organising this festival?", a: "The festival is proudly organised by EQ Solutions, dedicated to bringing iconic cultural celebrations to life in Malaysia." },
  { q: "Are activities confirmed?", a: "Some activities, including guest appearances, are tentative and subject to change. Follow our official channels for the latest updates." },
];

export function FAQ() {
  const [open, setOpen] = useState<number | null>(null);

  return (
    <section id="faq" style={{ background: "#050508" }} className="py-24 md:py-36 relative overflow-hidden">
      {/* Background giant number */}
      <div className="absolute pointer-events-none select-none" style={{ bottom: "-4%", right: "-2%", opacity: 0.025 }}>
        <span style={{ ...SYNE, fontWeight: 800, fontSize: "clamp(200px,30vw,400px)", color: "#EDE8DC", lineHeight: 1 }}>?</span>
      </div>

      <div className="max-w-5xl mx-auto px-6 md:px-12 lg:px-16">
        {/* Header */}
        <div className="grid md:grid-cols-2 gap-12 items-end mb-16 md:mb-24">
          <div>
            <motion.div className="flex items-center gap-3 mb-8" initial={{ opacity: 0 }} whileInView={{ opacity: 1 }} viewport={{ once: true }}>
              <div className="w-8 h-px bg-[#2FA7D8]" />
              <span style={{ ...SG, fontSize: "0.7rem", letterSpacing: "0.25em", color: "#2FA7D8", textTransform: "uppercase" }}>Questions</span>
            </motion.div>
            <div style={{ overflow: "hidden" }}>
              <motion.h2
                initial={{ y: "100%", opacity: 0 }} whileInView={{ y: 0, opacity: 1 }} viewport={{ once: true }}
                transition={{ duration: 1, ease: [0.22, 1, 0.36, 1] }}
                style={{ ...SYNE, fontWeight: 800, fontSize: "clamp(2.4rem,5.5vw,5rem)", color: "#EDE8DC", lineHeight: 1, letterSpacing: "-0.03em" }}
              >
                FAQ
              </motion.h2>
            </div>
          </div>
          <motion.p
            initial={{ opacity: 0, y: 20 }} whileInView={{ opacity: 1, y: 0 }} viewport={{ once: true }} transition={{ delay: 0.3 }}
            style={{ ...SG, fontSize: "0.9rem", color: "rgba(237,232,220,0.35)", lineHeight: 1.8 }}
          >
            Everything you need to know before your visit to Songkran Festival 2026.
          </motion.p>
        </div>

        {/* Accordion */}
        <div>
          {faqs.map((faq, i) => (
            <motion.div
              key={i}
              initial={{ opacity: 0, y: 16 }}
              whileInView={{ opacity: 1, y: 0 }}
              viewport={{ once: true, amount: 0.3 }}
              transition={{ delay: i * 0.06, duration: 0.6 }}
              style={{ borderBottom: "1px solid rgba(237,232,220,0.07)" }}
            >
              <button
                onClick={() => setOpen(open === i ? null : i)}
                className="w-full flex items-start justify-between gap-6 py-7 text-left group"
                data-hover
              >
                <div className="flex items-start gap-5">
                  <span style={{ ...SYNE, fontWeight: 700, fontSize: "0.75rem", color: open === i ? "#2FA7D8" : "rgba(237,232,220,0.2)", letterSpacing: "0.1em", minWidth: 28, paddingTop: 2, transition: "color 0.3s" }}>
                    {String(i + 1).padStart(2, "0")}
                  </span>
                  <span
                    style={{ ...SYNE, fontWeight: 600, fontSize: "clamp(0.95rem,1.5vw,1.15rem)", color: open === i ? "#EDE8DC" : "rgba(237,232,220,0.7)", lineHeight: 1.4, transition: "color 0.3s" }}
                  >
                    {faq.q}
                  </span>
                </div>
                <motion.div
                  animate={{ rotate: open === i ? 45 : 0 }}
                  transition={{ duration: 0.25 }}
                  className="flex-shrink-0 mt-0.5"
                  style={{ color: open === i ? "#2FA7D8" : "rgba(237,232,220,0.3)", fontSize: "1.4rem", lineHeight: 1 }}
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
                    <p style={{ ...SG, fontSize: "0.92rem", color: "rgba(237,232,220,0.5)", lineHeight: 1.8, paddingLeft: 52, paddingBottom: 24 }}>
                      {faq.a}
                    </p>
                  </motion.div>
                )}
              </AnimatePresence>
            </motion.div>
          ))}
        </div>
      </div>
    </section>
  );
}
