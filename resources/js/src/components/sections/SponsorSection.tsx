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
  marginBottom: 12,
};
const CARD_VALUE: React.CSSProperties = {
  ...SYNE,
  fontWeight: 700,
  fontSize: "clamp(1.2rem, 1.6vw, 1.6rem)",
  letterSpacing: "-0.03em",
  color: "#050508",
};

const SPONSOR_LOGOS = [
  { src: "/images/Royal_Thai_Embassy_Seal.svg.png", alt: "Royal Thai Embassy", className: "h-12 md:h-14 lg:h-16 w-auto object-contain" },
  { src: "/images/ditp.jpeg", alt: "DITP", className: "h-9 md:h-10 lg:h-12 w-auto rounded-md bg-white p-1.5 object-contain" },
  { src: "/images/amazing thailand.png", alt: "Amazing Thailand", className: "h-10 md:h-12 lg:h-14 w-auto object-contain" },
  { src: "/images/singha-seeklogo.png", alt: "Singha", className: "h-11 md:h-12 lg:h-14 w-auto object-contain" },
  { src: "/images/Snake-Brand-Logo.png", alt: "Snake Brand", className: "h-11 md:h-12 lg:h-14 w-auto object-contain" },
];

const MEDIA_PARTNERS = [
  { src: "/images/wob.png", alt: "WOB" },
  { src: "/images/noodou.png", alt: "Noodou" },
];

export function SponsorSection() {
  return (
    <section
      aria-label="Sponsors"
      className="relative px-6 py-18 md:px-12 md:py-20 lg:px-16 "
    >
      <div className="relative mx-auto max-w-7xl">
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          whileInView={{ opacity: 1, y: 0 }}
          viewport={{ once: true }}
          transition={{ duration: 0.7, ease: "easeOut" }}
          className="rounded-[32px] border border-white/15 px-6 py-8 md:px-10 md:py-10 lg:px-12 lg:py-12"
          style={{
            background: "linear-gradient(180deg, rgba(255,255,255,0.18) 0%, rgba(255,255,255,0.08) 100%)",
            backdropFilter: "blur(14px)",
            WebkitBackdropFilter: "blur(14px)",
            boxShadow: "0 22px 55px rgba(0,0,0,0.2)",
          }}
        >
          <div className="grid gap-6 md:grid-cols-[1.15fr_1.15fr_3fr_1.6fr] md:items-center lg:gap-8">
            <div className="flex flex-col items-center justify-center border-b border-white/10 pb-6 text-center md:min-h-[160px] md:border-b-0 md:border-r md:pb-0 md:pr-6 lg:min-h-[180px] lg:pr-8">
              <p style={CARD_LABEL}>ORGANISER</p>
              <span style={CARD_VALUE}>eq solutions</span>
            </div>

            <div className="flex flex-col items-center justify-center border-b border-white/10 pb-6 text-center md:min-h-[160px] md:border-b-0 md:border-r md:pb-0 md:pr-6 lg:min-h-[180px] lg:pr-8">
              <p style={CARD_LABEL}>VENUE SPONSOR</p>
              <img src={VENUE_SPONSOR_URL} alt="1 Utama" className="h-10 md:h-12 lg:h-14 w-auto object-contain" />
            </div>

            <div className="border-b border-white/10 pb-6 text-center md:min-h-[160px] md:border-b-0 md:border-r md:pb-0 md:pr-6 lg:min-h-[180px] lg:pr-8">
              <p style={CARD_LABEL}>SPONSORS</p>
              <div className="flex flex-wrap items-center justify-center gap-x-5 gap-y-4 md:gap-x-6 md:gap-y-5 lg:gap-x-8">
                {SPONSOR_LOGOS.map((logo) => (
                  <img key={logo.alt} src={logo.src} alt={logo.alt} className={logo.className} />
                ))}
              </div>
            </div>

            <div className="text-center md:min-h-[160px] md:flex md:flex-col md:items-center md:justify-center lg:min-h-[180px]">
              <p style={CARD_LABEL}>MEDIA PARTNERS</p>
              <div className="flex items-center justify-center gap-4 md:gap-5">
                {MEDIA_PARTNERS.map((logo) => (
                  <img key={logo.alt} src={logo.src} alt={logo.alt} className="h-14 w-14 md:h-16 md:w-16 lg:h-20 lg:w-20 rounded-full object-cover shadow-[0_6px_20px_rgba(0,0,0,0.14)]" />
                ))}
              </div>
            </div>
          </div>
        </motion.div>
      </div>
    </section>
  );
}
