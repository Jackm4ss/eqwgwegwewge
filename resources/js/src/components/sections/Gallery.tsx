import { useRef, useState } from "react";
import { motion, useScroll, useTransform } from "motion/react";

const SYNE: React.CSSProperties = { fontFamily: "'Syne', sans-serif" };
const SG: React.CSSProperties = { fontFamily: "'Space Grotesk', sans-serif" };

const imgs = [
  { src: "https://images.unsplash.com/photo-1753146753326-39852f654be1?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHxTb25na3JhbiUyMHdhdGVyJTIwZmVzdGl2YWwlMjBUaGFpbGFuZCUyMGNlbGVicmF0aW9ufGVufDF8fHx8MTc3MzY4ODIzN3ww&ixlib=rb-4.1.0&q=80&w=1080", label: "Water Festival Celebration", col: "col-span-2 row-span-2" },
  { src: "https://images.unsplash.com/photo-1706448420873-79cea552a9af?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHxUaGFpJTIwY3VsdHVyYWwlMjBmZXN0aXZhbCUyMGNvbG9yZnVsJTIwY3Jvd2R8ZW58MXx8fHwxNzczNjg4MjM3fDA&ixlib=rb-4.1.0&q=80&w=1080", label: "Cultural Festivities", col: "" },
  { src: "https://images.unsplash.com/photo-1693670984742-c008c239daf2?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHx3YXRlciUyMHNwbGFzaCUyMGZlc3RpdmFsJTIwcGVvcGxlJTIwam95fGVufDF8fHx8MTc3MzY4ODIzOHww&ixlib=rb-4.1.0&q=80&w=1080", label: "Water Splashes", col: "" },
  { src: "https://images.unsplash.com/photo-1757439160077-dd5d62a4d851?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHxsaXZlJTIwYmFuZCUyMHBlcmZvcm1hbmNlJTIwc3RhZ2UlMjBjb25jZXJ0JTIwbmlnaHR8ZW58MXx8fHwxNzczNjg4MjQyfDA&ixlib=rb-4.1.0&q=80&w=1080", label: "Live Performances", col: "" },
  { src: "https://images.unsplash.com/photo-1758346970449-260518dec078?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHxUaGFpJTIwdHJhZGl0aW9uYWwlMjBmb29kJTIwYXV0aGVudGljJTIwY3Vpc2luZXxlbnwxfHx8fDE3NzM2ODgyNDF8MA&ixlib=rb-4.1.0&q=80&w=1080", label: "Authentic Thai Food", col: "" },
  { src: "https://images.unsplash.com/photo-1759165270475-95a0ed2a9db0?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHx0cmFkaXRpb25hbCUyMFRoYWklMjB0ZW1wbGUlMjBnb2xkZW4lMjBhcmNoaXRlY3R1cmV8ZW58MXx8fHwxNzczNjg4MjQyfDA&ixlib=rb-4.1.0&q=80&w=1080", label: "Thai Heritage", col: "" },
];

function GalleryItem({ src, label, col, index }: { src: string; label: string; col: string; index: number }) {
  const [hovered, setHovered] = useState(false);
  const ref = useRef<HTMLDivElement>(null);

  const onMove = (e: React.MouseEvent) => {
    const el = ref.current;
    if (!el) return;
    const r = el.getBoundingClientRect();
    const x = ((e.clientX - r.left) / r.width - 0.5) * 12;
    const y = ((e.clientY - r.top) / r.height - 0.5) * 12;
    el.style.transform = `perspective(800px) rotateY(${x}deg) rotateX(${-y}deg) scale(1.03)`;
  };
  const onLeave = () => {
    if (ref.current) ref.current.style.transform = "perspective(800px) rotateY(0) rotateX(0) scale(1)";
    setHovered(false);
  };

  return (
    <motion.div
      initial={{ opacity: 0, y: 30 }}
      whileInView={{ opacity: 1, y: 0 }}
      viewport={{ once: true, amount: 0.2 }}
      transition={{ duration: 0.8, ease: [0.22, 1, 0.36, 1], delay: index * 0.06 }}
      className={`relative overflow-hidden cursor-pointer ${col}`}
      style={{
        borderRadius: 16,
        height: index === 0 ? "420px" : "200px",
        transformStyle: "preserve-3d",
        transition: "transform 0.3s cubic-bezier(0.22,1,0.36,1)",
      }}
      ref={ref}
      onMouseMove={onMove}
      onMouseLeave={onLeave}
      onMouseEnter={() => setHovered(true)}
      data-hover
    >
      <img
        src={src}
        alt={label}
        className="w-full h-full object-cover"
        style={{ transition: "transform 0.6s cubic-bezier(0.22,1,0.36,1)", transform: hovered ? "scale(1.08)" : "scale(1)" }}
      />
      {/* Always-visible subtle gradient */}
      <div style={{ position: "absolute", inset: 0, background: "linear-gradient(to top, rgba(5,5,8,0.8) 0%, transparent 50%)" }} />

      {/* Hover overlay */}
      <motion.div
        style={{ position: "absolute", inset: 0, background: "rgba(5,5,8,0.45)" }}
        animate={{ opacity: hovered ? 1 : 0 }}
        transition={{ duration: 0.3 }}
      />

      {/* Label */}
      <motion.div
        className="absolute bottom-0 left-0 right-0 p-4"
        animate={{ y: hovered ? 0 : 8, opacity: hovered ? 1 : 0.7 }}
        transition={{ duration: 0.3 }}
      >
        <p style={{ ...SG, fontSize: "0.8rem", color: "#EDE8DC", fontWeight: 500 }}>{label}</p>
      </motion.div>

      {/* Corner index */}
      <div style={{ position: "absolute", top: 12, right: 12, width: 28, height: 28, borderRadius: "50%", background: "rgba(5,5,8,0.6)", border: "1px solid rgba(237,232,220,0.15)", display: "flex", alignItems: "center", justifyContent: "center" }}>
        <span style={{ ...SG, fontSize: "0.6rem", color: "rgba(237,232,220,0.5)" }}>0{index + 1}</span>
      </div>
    </motion.div>
  );
}

export function Gallery() {
  const ref = useRef(null);
  const { scrollYProgress } = useScroll({ target: ref, offset: ["start end", "end start"] });
  const titleX = useTransform(scrollYProgress, [0, 1], ["-5%", "5%"]);

  return (
    <section id="gallery" ref={ref} style={{ background: "#070810" }} className="py-24 md:py-36 relative overflow-hidden">
      {/* Full-width kinetic title */}
      <div className="overflow-hidden mb-12 md:mb-16">
        <motion.div
          style={{ x: titleX }}
          className="flex items-center gap-8 whitespace-nowrap"
        >
          <div className="flex items-center gap-3 flex-shrink-0 ml-6 md:ml-12 lg:ml-16">
            <div className="w-8 h-px bg-[#2FA7D8]" />
            <span style={{ ...SG, fontSize: "0.7rem", letterSpacing: "0.25em", color: "#2FA7D8", textTransform: "uppercase" }}>Visual Journey</span>
          </div>
          <div style={{ overflow: "hidden" }} className="flex-shrink-0">
            <motion.h2
              initial={{ y: "100%", opacity: 0 }}
              whileInView={{ y: 0, opacity: 1 }}
              viewport={{ once: true }}
              transition={{ duration: 1, ease: [0.22, 1, 0.36, 1] }}
              style={{ ...SYNE, fontWeight: 800, fontSize: "clamp(2.4rem,5.5vw,5rem)", color: "#EDE8DC", lineHeight: 1, letterSpacing: "-0.03em" }}
            >
              Gallery
            </motion.h2>
          </div>
        </motion.div>
      </div>

      <div className="max-w-7xl mx-auto px-6 md:px-12 lg:px-16">
        {/* Asymmetric mosaic */}
        <div className="grid grid-cols-2 md:grid-cols-3 gap-3 md:gap-4">
          {imgs.map((img, i) => (
            <GalleryItem key={img.label} {...img} index={i} />
          ))}
        </div>
      </div>
    </section>
  );
}
