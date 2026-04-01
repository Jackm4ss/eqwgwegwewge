import { memo, useCallback, useState, type CSSProperties } from "react";
import { motion } from "motion/react";
import { SECTION_BACKGROUND } from "./sectionContrastTheme";

const TILT: CSSProperties = { fontFamily: "'Tilt Warp', sans-serif" };

const TITLE_HIGHLIGHT_TEXT_STYLE: CSSProperties = {
  color: "#349CD2",
  textShadow: "2px 3px 0 rgba(31, 52, 71, 0.22), 0 3px 8px rgba(31, 52, 71, 0.10)",
};

const LABEL_TEXT_STYLE: CSSProperties = {
  color: "rgba(0,0,0,0.68)",
};

const QUESTION_TEXT_STYLE: CSSProperties = {
  color: "#349CD2",
  fontSize: "1.2rem",
};

const ANSWER_TEXT_STYLE: CSSProperties = {
  color: "rgba(0,0,0,0.68)",
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

type FAQItem = {
  q: string;
  a: string[];
  list?: boolean;
};

type FAQAccordionItemProps = {
  faq: FAQItem;
  index: number;
  isOpen: boolean;
  onToggle: (index: number) => void;
};

const FAQ_PANEL_TRANSITION = "260ms cubic-bezier(0.22, 1, 0.36, 1)";

const faqs: FAQItem[] = [
  {
    q: "Ticket and Entry Requirements",
    list: true,
    a: [
      "Age Restriction: Minor under 13 Years old need to be accompanied by an adult or guardian.",
      "Valid QR code and Original ID cards or passports must be presented at the venue.",
      "Name Matching: QR Ticket must be registered to the attendee's full name, matching their official ID.",
      "Re-entry on the same day does not require QR scanning provided if the valid UV stamp of the day is still visible.",
    ],
  },
  {
    q: "Age Requirement",
    a: ["Minors under the age of 13 require the presence of a parent or legal guardian."],
  },
  {
    q: "Visiting with Family",
    a: ["For families with children below the age of 17, registration is mandatory for parents only."],
  },
  {
    q: "Prohibited Items",
    list: true,
    a: [
      "To ensure safety, the following are generally prohibited:",
      "Weapons, sharp objects, and fireworks.",
      "Drugs and illegal substances.",
      "External food and drinks.",
    ],
  },
  {
    q: "Safety & Behavioral Guidelines",
    list: true,
    a: [
      "Water Fight Safety: Do not aim high-pressure water guns at faces or eyes.",
      "Cultural Respect: Do not splash food vendors, crew on duty, the elderly, or young children.",
      "Public Decency: No pushing, punching or any physical aggression. including foul language, E.G Spitting or actions that are considered disrespectful",
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
      "By entering the event, you consent to being photographed or recorded, with the content being used for promotional purposes",
    ],
  },
  {
    q: "Why do I need to provide my NRIC or Passport number?",
    a: [
      "This is a mandatory security requirement for identity verification and to ensure a safe environment for all high-profile events and international guests.",
    ],
  },
  {
    q: "Is my image being recorded?",
    a: [
      "Yes. For security purposes, the venue is monitored by CCTV. Additionally, official event photographers may capture footage for promotional use.",
    ],
  },
  {
    q: "How long will you keep my data?",
    a: [
      "We retain registration data for [e.g., 3 months] after the event to conclude administrative reports, after which it is securely deleted, unless a longer period is required by law.",
    ],
  },
  {
    q: "Can I bring a guest who hasn't registered?",
    a: [
      "No. To maintain strict access control and crowd management, only pre-registered visitors with valid credentials will be granted entry.",
    ],
  },
  {
    q: "Is there a limit to how many people can enter at one time?",
    a: [
      "Yes. Entry is on a first come, first served basis due to venue capacity limits. If full capacity is reached, visitors will be placed on a waiting list and allowed in as others exit the event grounds.",
    ],
  },
  {
    q: "CCTV & Surveillance",
    a: [
      "The event venue are under 24-hour video surveillance for the purposes of security and crime prevention. By entering, you consent to the collection and processing of your image in accordance with our Privacy Policy.",
    ],
  },
  {
    q: "Who is organising this festival?",
    a: [
      "This event is proudly organised by EQ Solutions, in close collaboration with the Royal Thai Embassy and 1 Utama Shopping Centre, bringing together a vibrant cultural celebration for the public.",
    ],
  },
  {
    q: "Are activities confirmed?",
    a: [
      "Yes, a range of exciting activities are planned, including water play zones, live performances, games, and cultural showcases.",
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
      "No worries! You can retrieve your registration QR code from your confirmation email, or re-register on-site by scanning the registration QR displayed at the entrance.",
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
      "There are locker and storage spaces prepared however, visitors are encouraged to bring minimal belongings. The organiser is not responsible for any loss or damage to personal items.",
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
    a: ["Yes, a first aid station will be available throughout the event for any medical assistance."],
  },
  {
    q: "Can I bring my own water guns or buckets?",
    a: [
      "Yes! You are welcome to bring your own water guns. However, buckets, high-pressure devices, or unsafe equipment are not permitted.",
    ],
  },
  {
    q: "Will there be security at the event?",
    a: [
      "Yes, security personnel and event crew will be stationed throughout the venue to ensure a safe and enjoyable experience for all visitors.",
    ],
  },
];

const FAQAccordionItem = memo(function FAQAccordionItem({
  faq,
  index,
  isOpen,
  onToggle,
}: FAQAccordionItemProps) {
  const answerId = `faq-answer-${index}`;

  return (
    <motion.div
      initial={{ opacity: 0, y: 16 }}
      whileInView={{ opacity: 1, y: 0 }}
      viewport={{ once: true, amount: 0.3 }}
      transition={{ delay: index * 0.04, duration: 0.6 }}
      className="overflow-hidden rounded-2xl"
      style={{
        background: isOpen ? "rgba(255,255,255,0.42)" : "rgba(255,255,255,0.28)",
        border: `1px solid ${isOpen ? "rgba(0,0,0,0.12)" : "rgba(255,255,255,0.30)"}`,
        boxShadow: isOpen ? "0 10px 28px rgba(0,0,0,0.08)" : "none",
      }}
    >
      <button
        type="button"
        onClick={() => onToggle(index)}
        aria-expanded={isOpen}
        aria-controls={answerId}
        className="group flex w-full items-start justify-between gap-6 px-5 py-6 text-left md:px-6"
      >
        <div className="flex items-start gap-5">
          <span
            style={{
              ...TILT,
              fontSize: "0.72rem",
              color: isOpen ? "rgba(0,0,0,0.72)" : "rgba(0,0,0,0.42)",
              letterSpacing: "0.08em",
              minWidth: 28,
              paddingTop: 2,
              transition: "color 0.22s ease",
            }}
          >
            {String(index + 1).padStart(2, "0")}
          </span>

          <span
            style={{
              ...TILT,
              fontSize: "clamp(0.92rem,1.4vw,1.08rem)",
              ...QUESTION_TEXT_STYLE,
              lineHeight: 1.45,
            }}
          >
            {faq.q}
          </span>
        </div>

        <span
          aria-hidden="true"
          className="mt-0.5 flex-shrink-0"
          style={{
            color: isOpen ? "#111111" : "rgba(0,0,0,0.42)",
            fontSize: "1.3rem",
            lineHeight: 1,
            transform: isOpen ? "rotate(45deg)" : "rotate(0deg)",
            transition: "transform 0.22s ease, color 0.22s ease",
            willChange: "transform",
          }}
        >
          +
        </span>
      </button>

      <div
        id={answerId}
        className="grid"
        style={{
          gridTemplateRows: isOpen ? "1fr" : "0fr",
          opacity: isOpen ? 1 : 0,
          transition: `grid-template-rows ${FAQ_PANEL_TRANSITION}, opacity 160ms ease`,
        }}
      >
        <div style={{ overflow: "hidden", minHeight: 0 }}>
          <div
            style={{
              ...TILT,
              fontSize: "0.76rem",
              ...ANSWER_TEXT_STYLE,
              lineHeight: 1.8,
              paddingLeft: 52,
              paddingRight: 24,
              paddingBottom: 24,
              transform: isOpen ? "translateY(0)" : "translateY(-8px)",
              transition: `transform ${FAQ_PANEL_TRANSITION}, opacity 160ms ease`,
              opacity: isOpen ? 1 : 0,
              pointerEvents: isOpen ? "auto" : "none",
            }}
          >
            <div className="space-y-3">
              {faq.a.map((line, lineIndex) =>
                faq.list ? (
                  <div key={`${faq.q}-${lineIndex}`} className="flex gap-3">
                    <span className="pt-1 text-[0.9em] leading-none text-black/45">-</span>
                    <p>{line}</p>
                  </div>
                ) : (
                  <p key={`${faq.q}-${lineIndex}`}>{line}</p>
                ),
              )}
            </div>
          </div>
        </div>
      </div>
    </motion.div>
  );
});

export function FAQ() {
  const [open, setOpen] = useState<number | null>(null);
  const handleToggle = useCallback((index: number) => {
    setOpen((current) => (current === index ? null : index));
  }, []);

  return (
    <section
      id="faq"
      className="relative overflow-hidden py-20 md:py-36"
      style={SECTION_BACKGROUND}
    >
      <div
        className="pointer-events-none absolute select-none"
        style={{ bottom: "-4%", right: "-2%", opacity: 0.04 }}
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

      <div className="relative z-10 mx-auto max-w-5xl px-6 md:px-12 lg:px-16">
        <div className="rounded-[32px] p-6 md:rounded-[40px] md:p-10 lg:p-14" style={PANEL_STYLE}>
          <div className="mb-16 grid items-end gap-12 md:mb-20 md:grid-cols-2">
            <div>
              <motion.div
                className="mb-8 flex items-center gap-3"
                initial={{ opacity: 0 }}
                whileInView={{ opacity: 1 }}
                viewport={{ once: true }}
              >
                <div className="h-px w-8 bg-black/50" />
                <span
                  style={{
                    ...TILT,
                    fontSize: "0.72rem",
                    letterSpacing: "0.18em",
                    ...LABEL_TEXT_STYLE,
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
                    ...TITLE_HIGHLIGHT_TEXT_STYLE,
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
                ...LABEL_TEXT_STYLE,
                lineHeight: 1.8,
              }}
            >
              Everything you need to know before your visit to Songkran Festival 2026.
            </motion.p>
          </div>

          <div className="space-y-3">
            {faqs.map((faq, i) => (
              <FAQAccordionItem
                key={faq.q}
                faq={faq}
                index={i}
                isOpen={open === i}
                onToggle={handleToggle}
              />
            ))}
          </div>
        </div>
      </div>
    </section>
  );
}
