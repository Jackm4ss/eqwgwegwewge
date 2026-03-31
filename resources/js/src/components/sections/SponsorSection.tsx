import { motion } from "motion/react";
import { LazyImage } from "../ui/LazyImage";

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
  { src: "/images/Royal_Thai_Embassy_Seal.svg.png", alt: "Royal Thai Embassy", className: "h-16 md:h-20 lg:h-24 w-auto object-contain scale-125" },
  { src: "/images/ditp-new.png", alt: "DITP", className: "h-8 md:h-9 lg:h-10 w-auto object-contain" },
  { src: "/images/amazing thailand.png", alt: "Amazing Thailand", className: "h-9 md:h-10 lg:h-11 w-auto object-contain" },
  { src: "/images/singha-seeklogo.png", alt: "Singha", className: "h-9 md:h-10 lg:h-11 w-auto object-contain" },
  { src: "/images/Snake-Brand-Logo.png", alt: "Snake Brand", className: "h-16 md:h-20 lg:h-24 w-auto object-contain scale-125" },
  { src: "/images/thaigo.png", alt: "Thaigo", className: "h-9 md:h-10 lg:h-11 w-auto object-contain" },
  { src: "/images/Layer 0.png", alt: "Layer 0", className: "h-9 md:h-10 lg:h-11 w-auto object-contain" },
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
          className="rounded-[32px] border border-white/15 px-6 py-10 md:px-12 md:py-12 lg:px-16 lg:py-14"
          style={{
            background: "linear-gradient(180deg, rgba(255,255,255,0.36) 0%, rgba(255,255,255,0.22) 100%)",
            boxShadow: "0 18px 42px rgba(0,0,0,0.12)",
          }}
        >
          {/* Top Row: Organiser & Venue Sponsor */}
          <div className="grid gap-10 border-b border-black/5 pb-10 md:grid-cols-2 md:gap-16 lg:gap-24">
            <div className="flex flex-col items-center text-center">
              <div className={CARD_TITLE_ROW_CLASS}>
                <p style={CARD_LABEL}>ORGANISER</p>
              </div>
              <div className={CARD_CONTENT_ROW_CLASS}>
                <div className={`${LOGO_FRAME_CLASS} px-3`}>
                  <LazyImage
                    src="/images/eq-solution.png"
                    alt="eq solutions"
                    loading="lazy"
                    wrapperClassName="flex items-center justify-center"
                    className="h-9 w-auto object-contain md:h-11 lg:h-13"
                  />
                </div>
              </div>
            </div>

            <div className="flex flex-col items-center text-center">
              <div className={CARD_TITLE_ROW_CLASS}>
                <p style={CARD_LABEL}>VENUE SPONSOR</p>
              </div>
              <div className={CARD_CONTENT_ROW_CLASS}>
                <div className={`${LOGO_FRAME_CLASS} px-3`}>
                  <LazyImage
                    src={VENUE_SPONSOR_URL}
                    alt="1 Utama"
                    loading="lazy"
                    wrapperClassName="flex items-center justify-center"
                    className="h-16 w-auto object-contain md:h-20 lg:h-24 scale-125"
                  />
                </div>
              </div>
            </div>
          </div>

          {/* Bottom Row: Sponsors & Media Partners */}
          <div className="mt-10 grid gap-12 md:grid-cols-[1.6fr_1fr] md:gap-16 lg:mt-14 lg:gap-24">
            <div className="flex flex-col items-center w-full">
              <div className="mb-8 flex w-full justify-center">
                <p style={CARD_LABEL}>SPONSORS</p>
              </div>
              <div className="w-full">
                <div className="grid w-full grid-cols-4 place-items-center gap-x-4 gap-y-10 md:gap-x-8 md:gap-y-12 lg:gap-x-12">
                  {SPONSOR_LOGOS.map((logo) => (
                    <div key={logo.alt} className="flex items-center justify-center">
                      <LazyImage
                        src={logo.src}
                        alt={logo.alt}
                        loading="lazy"
                        wrapperClassName="flex items-center justify-center"
                        className={logo.className}
                      />
                    </div>
                  ))}
                </div>
              </div>
            </div>

            <div className="flex flex-col items-center w-full">
              <div className="mb-8 flex w-full justify-center">
                <p style={CARD_LABEL}>MEDIA PARTNER</p>
              </div>
              <div className="w-full">
                <div className="flex flex-wrap items-center justify-center gap-6 md:gap-10">
                  {MEDIA_PARTNERS.map((logo) => (
                    <div key={logo.alt} className="flex min-h-[56px] items-center justify-center">
                      <LazyImage
                        src={logo.src}
                        alt={logo.alt}
                        loading="lazy"
                        wrapperClassName="flex items-center justify-center rounded-full"
                        className="h-16 w-16 rounded-full object-cover shadow-[0_8px_24px_rgba(0,0,0,0.15)] md:h-20 md:w-20 lg:h-24 lg:w-24"
                        style={{ border: "3px solid white" }}
                      />
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
