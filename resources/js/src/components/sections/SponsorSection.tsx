import { motion } from "motion/react";

const SYNE: React.CSSProperties = { fontFamily: "'Tilt Warp', sans-serif" };
const SG: React.CSSProperties = { fontFamily: "'Tilt Warp', sans-serif" };
const VENUE_SPONSOR_URL = "/images/123.png";
const CARD_LABEL: React.CSSProperties = {
  ...SG,
  fontSize: "clamp(0.72rem, 0.95vw, 0.84rem)",
  fontWeight: 800,
  letterSpacing: "0.16em",
  color: "rgba(0,0,0,0.68)",
  marginBottom: 0,
};
const CARD_VALUE: React.CSSProperties = {
  ...SYNE,
  fontWeight: 700,
  fontSize: "clamp(1rem, 1.28vw, 1.3rem)",
  letterSpacing: "-0.03em",
  color: "#050508",
};

const SPONSOR_LOGOS = [
  { src: "/images/Royal_Thai_Embassy_Seal.svg.png", alt: "Royal Thai Embassy", className: "h-9 md:h-10 lg:h-11 w-auto object-contain" },
  { src: "/images/ditp-new.png", alt: "DITP", className: "h-7 md:h-8 lg:h-9 w-auto object-contain" },
  { src: "/images/amazing thailand.png", alt: "Amazing Thailand", className: "h-8 md:h-9 lg:h-10 w-auto object-contain" },
  { src: "/images/singha-seeklogo.png", alt: "Singha", className: "h-8 md:h-9 lg:h-10 w-auto object-contain" },
  { src: "/images/Snake-Brand-Logo.png", alt: "Snake Brand", className: "h-8 md:h-9 lg:h-10 w-auto object-contain" },
  { src: "/images/thaigo.png", alt: "Thaigo", className: "h-8 md:h-9 lg:h-10 w-auto object-contain" },
  { src: "/images/Layer 0.png", alt: "Layer 0", className: "h-8 md:h-9 lg:h-10 w-auto object-contain" },
];

const MEDIA_PARTNERS = [
  { src: "/images/wob.png", alt: "WOB" },
  { src: "/images/noodou.png", alt: "Noodou" },
];

const CARD_SECTION_CLASS =
  "flex h-full flex-col items-center border-b border-white/10 pb-5 text-center md:min-h-[168px] md:border-b-0 md:pb-0";
const CARD_TITLE_ROW_CLASS = "flex min-h-[2.5rem] w-full items-start justify-center";
const CARD_CONTENT_ROW_CLASS = "flex w-full flex-1 items-center justify-center pt-3 md:pt-4";
const LOGO_FRAME_CLASS = "flex min-h-[44px] items-center justify-center";

export function SponsorSection() {
  return (
    <section
      aria-label="Sponsors"
      className="relative px-6 py-14 md:px-12 md:py-16 lg:px-16"
    >
      <div className="relative mx-auto max-w-7xl">
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          whileInView={{ opacity: 1, y: 0 }}
          viewport={{ once: true }}
          transition={{ duration: 0.7, ease: "easeOut" }}
          className="rounded-[32px] border border-white/15 px-6 py-7 md:px-8 md:py-8 lg:px-10 lg:py-9"
          style={{
            background: "linear-gradient(180deg, rgba(255,255,255,0.36) 0%, rgba(255,255,255,0.22) 100%)",
            boxShadow: "0 18px 42px rgba(0,0,0,0.12)",
          }}
        >
          <div className="grid gap-5 md:grid-cols-[0.95fr_1.05fr_2.65fr_1.2fr] md:items-stretch lg:gap-6">
            <div className={`${CARD_SECTION_CLASS} md:border-r md:pr-5 lg:pr-6`}>
              <div className={CARD_TITLE_ROW_CLASS}>
                <p style={CARD_LABEL}>ORGANISER</p>
              </div>
              <div className={CARD_CONTENT_ROW_CLASS}>
                <div className={`${LOGO_FRAME_CLASS} px-3`}>
                  <img src="/images/eq-solution.png" alt="eq solutions" className="h-7 md:h-8 lg:h-9 w-auto object-contain" />
                </div>
              </div>
            </div>

            <div className={`${CARD_SECTION_CLASS} md:border-r md:pr-5 lg:pr-6`}>
              <div className={CARD_TITLE_ROW_CLASS}>
                <p style={CARD_LABEL}>VENUE SPONSOR</p>
              </div>
              <div className={CARD_CONTENT_ROW_CLASS}>
                <div className={`${LOGO_FRAME_CLASS} px-3`}>
                  <img src={VENUE_SPONSOR_URL} alt="1 Utama" className="h-8 md:h-9 lg:h-10 w-auto object-contain" />
                </div>
              </div>
            </div>

            <div className={`${CARD_SECTION_CLASS} md:border-r md:pr-5 lg:pr-6`}>
              <div className={CARD_TITLE_ROW_CLASS}>
                <p style={CARD_LABEL}>SPONSORS</p>
              </div>
              <div className={CARD_CONTENT_ROW_CLASS}>
                <div className="grid w-full max-w-[420px] grid-cols-4 place-items-center gap-x-3 gap-y-3 md:gap-x-4 md:gap-y-3.5">
                  {SPONSOR_LOGOS.map((logo) => (
                    <div key={logo.alt} className={`${LOGO_FRAME_CLASS} w-full`}>
                      <img src={logo.src} alt={logo.alt} className={logo.className} />
                    </div>
                  ))}
                </div>
              </div>
            </div>

            <div className={`${CARD_SECTION_CLASS} border-b-0 pb-0`}>
              <div className={CARD_TITLE_ROW_CLASS}>
                <p style={CARD_LABEL}>MEDIA PARTNERS</p>
              </div>
              <div className={CARD_CONTENT_ROW_CLASS}>
                <div className="flex flex-wrap items-center justify-center gap-3 md:gap-4">
                  {MEDIA_PARTNERS.map((logo) => (
                    <div key={logo.alt} className="flex min-h-[56px] items-center justify-center">
                      <img src={logo.src} alt={logo.alt} className="h-12 w-12 md:h-14 md:w-14 lg:h-16 lg:w-16 rounded-full object-cover shadow-[0_6px_20px_rgba(0,0,0,0.14)]" />
                    </div>
                  ))}
                </div>
              </div>
            </div>
          </div>
        </motion.div>
      </div>
    </section>
  );
}
