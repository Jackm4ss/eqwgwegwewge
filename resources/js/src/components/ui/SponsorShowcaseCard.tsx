import { LazyImage } from "./LazyImage";

const TILT: React.CSSProperties = { fontFamily: "'Tilt Warp', sans-serif" };
const VENUE_SPONSOR_URL = "/images/123.png";

const CARD_LABEL: React.CSSProperties = {
  ...TILT,
  fontSize: "clamp(0.72rem, 0.95vw, 0.84rem)",
  fontWeight: 800,
  letterSpacing: "0.16em",
  color: "rgba(0,0,0,0.68)",
  marginBottom: 0,
};

const SPONSOR_LOGOS = [
  {
    src: "/images/Royal_Thai_Embassy_Seal.svg.png",
    alt: "Royal Thai Embassy",
    defaultClassName: "h-16 md:h-20 lg:h-24 w-auto object-contain scale-125",
    ticketClassName: "h-[34px] w-auto object-contain",
  },
  {
    src: "/images/ditp-new.png",
    alt: "DITP",
    defaultClassName: "h-8 md:h-9 lg:h-10 w-auto object-contain",
    ticketClassName: "h-[15px] w-auto object-contain",
  },
  {
    src: "/images/amazing thailand.png",
    alt: "Amazing Thailand",
    defaultClassName: "h-9 md:h-10 lg:h-11 w-auto object-contain",
    ticketClassName: "h-[17px] w-auto object-contain",
  },
  {
    src: "/images/singha-seeklogo.png",
    alt: "Singha",
    defaultClassName: "h-9 md:h-10 lg:h-11 w-auto object-contain",
    ticketClassName: "h-[17px] w-auto object-contain",
  },
  {
    src: "/images/Snake-Brand-Logo.png",
    alt: "Snake Brand",
    defaultClassName: "h-16 md:h-20 lg:h-24 w-auto object-contain scale-125",
    ticketClassName: "h-[34px] w-auto object-contain",
  },
  {
    src: "/images/thaigo.png",
    alt: "Thaigo",
    defaultClassName: "h-9 md:h-10 lg:h-11 w-auto object-contain",
    ticketClassName: "h-[17px] w-auto object-contain",
  },
  {
    src: "/images/Layer 0.png",
    alt: "Layer 0",
    defaultClassName: "h-9 md:h-10 lg:h-11 w-auto object-contain",
    ticketClassName: "h-[17px] w-auto object-contain",
  },
];

const MEDIA_PARTNERS = [
  { src: "/images/wob.png", alt: "WOB" },
  { src: "/images/noodou.png", alt: "Noodou" },
];

type SponsorShowcaseCardProps = {
  className?: string;
  variant?: "default" | "ticket";
};

export function SponsorShowcaseCard({
  className = "",
  variant = "default",
}: SponsorShowcaseCardProps) {
  const isTicketVariant = variant === "ticket";
  const cardLabelStyle: React.CSSProperties = isTicketVariant
    ? {
      ...CARD_LABEL,
      fontSize: "0.58rem",
      letterSpacing: "0.14em",
    }
    : CARD_LABEL;

  const outerClassName = isTicketVariant
    ? "rounded-[32px] border border-white/18 px-5 py-5"
    : "rounded-[32px] border border-white/15 px-6 py-10 md:px-12 md:py-12 lg:px-16 lg:py-14";
  const outerStyle = isTicketVariant
    ? {
      background: "linear-gradient(180deg, rgba(214,236,244,0.96) 0%, rgba(206,230,241,0.92) 100%)",
      boxShadow: "0 12px 24px rgba(0,0,0,0.10)",
    }
    : {
      background: "linear-gradient(180deg, rgba(255,255,255,0.36) 0%, rgba(255,255,255,0.22) 100%)",
      boxShadow: "0 18px 42px rgba(0,0,0,0.12)",
    };
  const topSectionClassName = isTicketVariant
    ? "grid grid-cols-2 gap-x-6 gap-y-5"
    : "grid gap-10 border-b border-black/5 pb-10 md:grid-cols-[1.6fr_1fr] md:gap-16 lg:gap-24";
  const bottomSectionClassName = isTicketVariant
    ? "mt-5 grid grid-cols-2 gap-x-6 gap-y-5 items-start"
    : "mt-10 grid gap-12 md:grid-cols-[1.6fr_1fr] md:gap-16 lg:mt-14 lg:gap-24";
  const titleSpacingClassName = isTicketVariant ? "mb-4" : "mb-8";
  const sponsorWrapClassName = isTicketVariant
    ? "grid w-full grid-cols-4 place-items-center gap-x-3 gap-y-4"
    : "flex flex-wrap items-center justify-center gap-x-6 gap-y-10 md:gap-x-10 md:gap-y-12 lg:gap-x-14";
  const mediaRowClassName = isTicketVariant
    ? "flex items-center justify-center gap-3"
    : "flex flex-wrap items-center justify-center gap-6 md:gap-10";
  const organiserLogoClassName = isTicketVariant
    ? "h-6 w-auto object-contain"
    : "h-9 w-auto object-contain md:h-11 lg:h-13";
  const venueLogoClassName = isTicketVariant
    ? "h-10 w-auto object-contain"
    : "h-16 w-auto object-contain md:h-20 lg:h-24 scale-125";
  const mediaLogoClassName = isTicketVariant
    ? "h-[42px] w-[42px] rounded-full object-cover shadow-[0_6px_18px_rgba(0,0,0,0.12)]"
    : "h-16 w-16 rounded-full object-cover shadow-[0_8px_24px_rgba(0,0,0,0.15)] md:h-20 md:w-20 lg:h-24 lg:w-24";

  return (
    <div
      className={`${outerClassName} ${className}`.trim()}
      style={outerStyle}
    >
      <div className={topSectionClassName}>
        <div className="flex flex-col items-center text-center">
          <div className="flex min-h-[2.5rem] w-full items-start justify-center">
            <p style={cardLabelStyle}>ORGANISER</p>
          </div>
          <div className={`flex w-full flex-1 items-center justify-center ${isTicketVariant ? "" : "pt-3 md:pt-4"}`.trim()}>
            <div className="flex min-h-[44px] items-center justify-center px-3">
              <LazyImage
                src="/images/eq-solution.png"
                alt="eq solutions"
                loading="lazy"
                wrapperClassName="flex items-center justify-center"
                className={organiserLogoClassName}
              />
            </div>
          </div>
        </div>

        <div className="flex flex-col items-center text-center">
          <div className="flex min-h-[2.5rem] w-full items-start justify-center">
            <p style={cardLabelStyle}>VENUE SPONSOR</p>
          </div>
          <div className={`flex w-full flex-1 items-center justify-center ${isTicketVariant ? "" : "pt-3 md:pt-4"}`.trim()}>
            <div className="flex min-h-[44px] items-center justify-center px-3">
              <LazyImage
                src={VENUE_SPONSOR_URL}
                alt="1 Utama"
                loading="lazy"
                wrapperClassName="flex items-center justify-center"
                className={venueLogoClassName}
              />
            </div>
          </div>
        </div>
      </div>

      <div className={bottomSectionClassName}>
        <div className="flex w-full flex-col items-center">
          <div className={`${titleSpacingClassName} flex w-full justify-center`.trim()}>
            <p style={cardLabelStyle}>SPONSORS</p>
          </div>
          <div className="w-full">
            <div className={sponsorWrapClassName}>
              {SPONSOR_LOGOS.map((logo) => (
                <div key={logo.alt} className="flex items-center justify-center">
                  <LazyImage
                    src={logo.src}
                    alt={logo.alt}
                    loading="lazy"
                    wrapperClassName="flex items-center justify-center"
                    className={isTicketVariant ? logo.ticketClassName : logo.defaultClassName}
                  />
                </div>
              ))}
            </div>
          </div>
        </div>

        <div className={`${isTicketVariant ? "w-full" : "w-auto"} flex flex-col items-center`.trim()}>
          <div className={`${titleSpacingClassName} flex w-full justify-center`.trim()}>
            <p style={cardLabelStyle}>MEDIA PARTNER</p>
          </div>
          <div className="w-full">
            <div className={mediaRowClassName}>
              {MEDIA_PARTNERS.map((logo) => (
                <div key={logo.alt} className="flex min-h-[56px] items-center justify-center">
                  <LazyImage
                    src={logo.src}
                    alt={logo.alt}
                    loading="lazy"
                    wrapperClassName="flex items-center justify-center rounded-full"
                    className={mediaLogoClassName}
                    style={{ border: "3px solid white" }}
                  />
                </div>
              ))}
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
