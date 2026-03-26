import { motion } from "motion/react";
import { MapPin, Calendar, Clock, Instagram, Facebook, Twitter } from "lucide-react";

const SYNE: React.CSSProperties = { fontFamily: "'Syne', sans-serif" };
const SG: React.CSSProperties = { fontFamily: "'Space Grotesk', sans-serif" };

// Hand-drawn SVG scribble line
function ScribbleLine({ width = 200 }: { width?: number }) {
  return (
    <svg viewBox={`0 0 ${width} 8`} fill="none" style={{ width, height: 8, display: "block" }}>
      <motion.path
        d={`M2 4 Q${width * 0.2} 1 ${width * 0.4} 4 Q${width * 0.6} 7 ${width * 0.8} 4 Q${width * 0.92} 2 ${width - 2} 4`}
        stroke="#2FA7D8" strokeWidth="1.5" strokeLinecap="round" fill="none"
        initial={{ pathLength: 0, opacity: 0 }}
        whileInView={{ pathLength: 1, opacity: 0.6 }}
        viewport={{ once: true }}
        transition={{ duration: 1.5, ease: "easeOut", delay: 0.4 }}
      />
    </svg>
  );
}

export function Footer() {
  const scrollTo = (id: string) => {
    const el = document.querySelector(id);
    if (el) {
      const offset = 80;
      const bodyRect = document.body.getBoundingClientRect().top;
      const elementRect = el.getBoundingClientRect().top;
      const elementPosition = elementRect - bodyRect;
      const offsetPosition = elementPosition - offset;

      window.scrollTo({
        top: offsetPosition,
        behavior: "smooth"
      });
    }
  };

  return (
    <footer style={{ background: "#030305", position: "relative", overflow: "hidden" }}>
      {/* Giant watermark */}
      <div className="absolute inset-0 flex items-center justify-center pointer-events-none overflow-hidden">
        <span
          style={{ ...SYNE, fontWeight: 800, fontSize: "clamp(40px,10vw,260px)", color: "rgba(237,232,220,0.018)", letterSpacing: "-0.02em", userSelect: "none", whiteSpace: "nowrap", lineHeight: 1 }}
        >
          SONGKRAN
        </span>
      </div>

      {/* Top divider with gold gradient */}
      <div style={{ height: 1, background: "linear-gradient(90deg, transparent 0%, rgba(244,160,51,0.4) 50%, transparent 100%)" }} />

      {/* CTA strip */}
      <div className="relative py-16 md:py-20 px-6 md:px-12 lg:px-16" style={{ borderBottom: "1px solid rgba(237,232,220,0.05)" }}>
        <div className="max-w-7xl mx-auto">
          <div className="grid md:grid-cols-2 gap-10 items-center">
            <div>
              <motion.p
                initial={{ opacity: 0, y: 16 }} whileInView={{ opacity: 1, y: 0 }} viewport={{ once: true }}
                style={{ ...SG, fontSize: "0.7rem", letterSpacing: "0.22em", color: "#2FA7D8", textTransform: "uppercase", marginBottom: 12 }}
              >
                Don't Miss Out
              </motion.p>
              <div style={{ overflow: "hidden" }}>
                <motion.h2
                  initial={{ y: "100%", opacity: 0 }} whileInView={{ y: 0, opacity: 1 }} viewport={{ once: true }}
                  transition={{ duration: 1, ease: [0.22, 1, 0.36, 1] }}
                  style={{ ...SYNE, fontWeight: 800, fontSize: "clamp(2rem,4.5vw,4rem)", color: "#EDE8DC", lineHeight: 1.05, letterSpacing: "-0.03em" }}
                >
                  Join Us <br />
                  9 - 19 April
                </motion.h2>
              </div>
              <ScribbleLine width={220} />

              {/* Added Register Button */}
              <motion.div
                initial={{ opacity: 0, scale: 0.9 }}
                whileInView={{ opacity: 1, scale: 1 }}
                viewport={{ once: true }}
                transition={{ delay: 0.6, duration: 0.5, type: "spring" }}
                className="mt-8"
              >
                <button
                  onClick={() => window.location.href = '/register'}
                  className="group relative overflow-hidden rounded-full px-8 py-3.5 md:px-10 md:py-4 transition-all hover:scale-105 active:scale-95"
                  style={{ background: "#2FA7D8", boxShadow: "0 0 20px rgba(244,160,51,0.3)" }}
                >
                  <div className="absolute inset-0 bg-white/20 translate-y-full group-hover:translate-y-0 transition-transform duration-300 ease-out" />
                  <span style={{ ...SG, fontWeight: 700, fontSize: "0.9rem", letterSpacing: "0.1em", color: "#050508", position: "relative", zIndex: 10 }}>
                    REGISTER NOW
                  </span>
                </button>
              </motion.div>
            </div>
            {/* <div className="flex flex-wrap gap-3">
              {[
                { label: "April 9–19", icon: Calendar, color: "#2FA7D8" },
                { label: "One Utama", icon: MapPin, color: "#18C7CC", link: "https://maps.app.goo.gl/MUU1nHAw2zrs2cZ96" },
                { label: "12PM–12AM", icon: Clock, color: "rgba(237,232,220,0.5)" },
              ].map(({ label, icon: Icon, color, link }) => (
                link ? (
                  <a
                    key={label}
                    href={link}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="flex items-center gap-2 px-4 py-2.5 rounded-full hover:bg-[rgba(24,199,204,0.15)] hover:border-[#18C7CC]/50 transition-all duration-300 group"
                    style={{ background: "rgba(237,232,220,0.04)", border: "1px solid rgba(237,232,220,0.08)" }}
                  >
                    <Icon size={13} style={{ color }} className="group-hover:scale-110 transition-transform" />
                    <span style={{ ...SG, fontSize: "0.78rem" }} className="text-[rgba(237,232,220,0.55)] group-hover:text-white transition-colors">
                      {label}
                    </span>
                  </a>
                ) : (
                  <div key={label} className="flex items-center gap-2 px-4 py-2.5 rounded-full" style={{ background: "rgba(237,232,220,0.04)", border: "1px solid rgba(237,232,220,0.08)" }}>
                    <Icon size={13} style={{ color }} />
                    <span style={{ ...SG, fontSize: "0.78rem", color: "rgba(237,232,220,0.55)" }}>{label}</span>
                  </div>
                )
              ))}
              <a
                href="/register"
                className="flex items-center gap-2 px-4 py-2.5 rounded-full hover:bg-[rgba(46,204,113,0.15)] hover:border-[#2ecc71]/50 hover:scale-[1.03] transition-all duration-300 group cursor-pointer"
                style={{ background: "rgba(46,204,113,0.08)", border: "1px solid rgba(46,204,113,0.2)" }}
              >
                <span className="w-1.5 h-1.5 rounded-full bg-[#2ecc71] group-hover:shadow-[0_0_8px_#2ecc71] transition-shadow animate-pulse" />
                <span style={{ ...SG, fontSize: "0.78rem" }} className="text-[#2ecc71] group-hover:text-white transition-colors">
                  FREE ENTRY
                </span>
              </a>
            </div> */}
          </div>
        </div>
      </div>

      {/* Main footer grid */}
      <div className="relative py-14 px-6 md:px-12 lg:px-16" style={{ borderBottom: "1px solid rgba(237,232,220,0.05)" }}>
        <div className="max-w-7xl mx-auto grid md:grid-cols-3 gap-10 md:gap-16">
          {/* Brand */}
          <div>
            <div className="mb-5">
              <span style={{ ...SYNE, fontWeight: 800, fontSize: "1.2rem", letterSpacing: "0.15em", color: "#EDE8DC" }}>SONGKRAN</span>
            </div>
            <p style={{ ...SG, fontSize: "0.82rem", color: "rgba(237,232,220,0.3)", lineHeight: 1.8, marginBottom: 20 }}>
              Thailand's iconic water festival — brought to Malaysia for celebrating culture, renewal, and togetherness.
            </p>
            <div className="flex gap-3">
              {[
                { Icon: Instagram, url: "https://www.instagram.com/eqsolutions.my/" },
                { Icon: Facebook, url: "https://www.facebook.com/people/EQ-Solutions/" }
              ].map(({ Icon, url }, i) => (
                <a
                  key={i}
                  href={url}
                  target="_blank"
                  rel="noopener noreferrer"
                  style={{ width: 36, height: 36, borderRadius: 10, background: "rgba(237,232,220,0.05)", border: "1px solid rgba(237,232,220,0.08)", display: "flex", alignItems: "center", justifyContent: "center" }}
                  className="group hover:border-[#3FD7F5] hover:bg-[#3FD7F5]/10 hover:-translate-y-1 hover:scale-110 hover:shadow-[0_4px_16px_rgba(63,215,245,0.3)] transition-all duration-300"
                  data-hover
                >
                  <Icon size={14} className="text-[#EDE8DC]/40 group-hover:text-[#3FD7F5] transition-colors duration-300" />
                </a>
              ))}
            </div>
          </div>

          {/* Quick links */}
          <div>
            <p style={{ ...SYNE, fontWeight: 700, fontSize: "0.7rem", letterSpacing: "0.2em", color: "rgba(237,232,220,0.3)", textTransform: "uppercase", marginBottom: 16 }}>
              Navigate
            </p>
            <div className="flex flex-col gap-2.5">
              {[
                ["#about", "About"],
                ["#details", "Event Details"],
                ["#activities", "Festival Activities"],
                ["#schedule", "Event Schedule"],
                ["#faq", "FAQ"]
              ].map(([id, label]) => (
                <button key={id} onClick={() => scrollTo(id)} className="flex items-center gap-2 text-left group w-fit transition-all duration-300 hover:translate-x-1.5" data-hover>
                  <span className="text-[#2FA7D8] text-sm opacity-0 -translate-x-2 group-hover:opacity-100 group-hover:translate-x-0 transition-all duration-300">
                    →
                  </span>
                  <span style={{ ...SG, fontSize: "0.85rem", color: "rgba(237,232,220,0.4)" }} className="group-hover:text-white transition-colors">{label}</span>
                </button>
              ))}
            </div>
          </div>

          {/* Event info */}
          <div>
            <p style={{ ...SYNE, fontWeight: 700, fontSize: "0.7rem", letterSpacing: "0.2em", color: "rgba(237,232,220,0.3)", textTransform: "uppercase", marginBottom: 16 }}>
              Event Details
            </p>
            <div className="flex flex-col gap-4">
              {[
                { icon: MapPin, text: "@Gf Forecourt Outdoor Carpark 1 Utama, Malaysia", color: "#2FA7D8", link: "https://maps.app.goo.gl/MUU1nHAw2zrs2cZ96" },
                { icon: Calendar, text: "April 9–19, 2026", color: "#18C7CC" },
                { icon: Clock, text: "12 PM – 12 AM Daily", color: "rgba(237,232,220,0.4)" },
              ].map(({ icon: Icon, text, color, link }, i) => (
                <div key={i} className="flex items-start gap-3">
                  <Icon size={13} style={{ color, marginTop: 3, flexShrink: 0 }} />
                  {link ? (
                    <a
                      href={link}
                      target="_blank"
                      rel="noopener noreferrer"
                      style={{ ...SG, fontSize: "0.8rem", lineHeight: 1.6 }}
                      className="text-[#EDE8DC]/40 hover:text-[#3FD7F5] transition-colors decoration-[#3FD7F5]/50 hover:underline hover:underline-offset-4"
                    >
                      {text}
                    </a>
                  ) : (
                    <span style={{ ...SG, fontSize: "0.8rem", color: "rgba(237,232,220,0.4)", lineHeight: 1.6 }}>{text}</span>
                  )}
                </div>
              ))}
              <div style={{ paddingTop: 10, borderTop: "1px solid rgba(237,232,220,0.06)" }}>
                <p style={{ ...SG, fontSize: "0.68rem", color: "rgba(237,232,220,0.25)", marginBottom: 2 }}>Organised by</p>
                <a
                  href="https://www.eqsolutions.com.my/"
                  target="_blank"
                  rel="noopener noreferrer"
                  style={{ ...SYNE, fontWeight: 700, fontSize: "0.95rem" }}
                  className="inline-block text-[#2FA7D8] hover:text-[#3FD7F5] transition-colors relative group w-fit"
                >
                  EQ Solutions
                  <span className="absolute left-0 -bottom-0.5 w-0 h-[1.5px] bg-[#3FD7F5] transition-all duration-300 group-hover:w-full"></span>
                </a>
              </div>
            </div>
          </div>
        </div>
      </div>

      {/* Sponsors Section */}
      <div className="relative py-8 px-6 md:px-12 lg:px-16">
        <div className="max-w-7xl mx-auto text-center">
          <p
            style={{
              ...SYNE,
              fontWeight: 700,
              fontSize: "0.7rem",
              letterSpacing: "0.2em",
              color: "rgba(237,232,220,0.3)",
              textTransform: "uppercase",
              marginBottom: 16,
            }}
          >
            Our Sponsors
          </p>
          <div className="flex flex-wrap justify-center gap-6">
            <img
              src="/images/amazing thailand.png"
              alt="Amazing Thailand Sponsor"
              className="w-32 h-32 object-contain"
            />
            <img
              src="/images/ditp.jpeg"
              alt="DITP Sponsor"
              className="w-32 h-32 object-contain"
            />

            <img
              src="/images/singha-seeklogo.png"
              alt="Singha Sponsor"
              className="w-32 h-32 object-contain"
            />

            <img
              src="/images/Snake-Brand-Logo.png"
              alt="Snake Brand Sponsor"
              className="w-32 h-32 object-contain"
            />
            <img
              src="/images/noodou.png"
              alt="Noodou Sponsor"
              className="w-32 h-32 object-contain"
            />

            <img
              src="/images/123.png"
              alt="EQ Sponsor"
              className="w-32 h-32 object-contain"
            />
            <img
              src="/images/eq.png"
              alt="EQ Solution Sponsor"
              className="w-32 h-32 object-contain"
            />
            <img
              src="/images/Royal_Thai_Embassy_Seal.svg.png"
              alt="Royal Thai Embassy Sponsor"
              className="w-32 h-32 object-contain"
            />
            <img
              src="/images/wob.png"
              alt="WOB Sponsor"
              className="w-32 h-32 object-contain"
            />
          </div>
        </div>
      </div>

      {/* Bottom bar */}
      <div className="relative py-6 px-6 md:px-12 lg:px-16">
        <div className="max-w-7xl mx-auto flex flex-col md:flex-row items-center justify-between gap-3">
          <p style={{ ...SG, fontSize: "0.68rem", color: "rgba(237,232,220,0.18)", letterSpacing: "0.04em" }}>
            © {new Date().getFullYear()} Songkran Festival Malaysia · Organised by{" "}
            <a
              href="https://www.eqsolutions.com.my/"
              target="_blank"
              rel="noopener noreferrer"
              className="text-[#2FA7D8] hover:text-[#3FD7F5] transition-colors hover:underline"
            >
              EQ Solutions
            </a>
          </p>
          {/* 
          <div className="flex items-center gap-3">
            <span className="fi fi-th text-xl rounded-[2px] shadow-sm" aria-hidden="true" />
            <div style={{ width: 1, height: 16, background: "rgba(237,232,220,0.1)" }} />
            <span className="fi fi-my text-xl rounded-[2px] shadow-sm" aria-hidden="true" />
          </div>
          */}
        </div>
      </div>
    </footer>
  );
}
