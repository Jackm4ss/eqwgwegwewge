const SYNE: React.CSSProperties = { fontFamily: "'Syne', sans-serif" };
const SG: React.CSSProperties = { fontFamily: "'Space Grotesk', sans-serif" };

const items = [
  { text: "สงกรานต์", accent: true },
  { text: "WATER FESTIVAL", accent: false },
  { text: "MALAYSIA 2026", accent: false },
  { text: "APR 9–19", accent: true },
  { text: "ONE UTAMA", accent: false },
  { text: "FREE ENTRY", accent: true },
  { text: "💦 RENEWAL & BLESSINGS", accent: false },
  { text: "EQ SOLUTIONS", accent: false },
];

export function Marquee({ reverse = false }: { reverse?: boolean }) {
  const track = [...items, ...items, ...items];

  return (
    <>
      <style>{`
        @keyframes marquee-fwd {
          from { transform: translateX(0); }
          to { transform: translateX(-33.333%); }
        }
        @keyframes marquee-rev {
          from { transform: translateX(-33.333%); }
          to { transform: translateX(0); }
        }
        .marquee-track-fwd { animation: marquee-fwd 28s linear infinite; }
        .marquee-track-rev { animation: marquee-rev 28s linear infinite; }
      `}</style>
      <div className="overflow-hidden py-4" style={{ background: "#0a0a14", borderTop: "1px solid rgba(237,232,220,0.06)", borderBottom: "1px solid rgba(237,232,220,0.06)" }}>
        <div className={`flex whitespace-nowrap ${reverse ? "marquee-track-rev" : "marquee-track-fwd"}`}>
          {track.map((item, i) => (
            <span key={i} className="inline-flex items-center gap-6 px-8">
              <span
                style={{
                  ...(item.accent ? SYNE : SG),
                  fontSize: "0.72rem",
                  letterSpacing: item.accent ? "0.18em" : "0.12em",
                  fontWeight: item.accent ? 700 : 400,
                  color: item.accent ? "#2FA7D8" : "rgba(237,232,220,0.35)",
                  textTransform: "uppercase",
                }}
              >
                {item.text}
              </span>
              <span style={{ color: "rgba(237,232,220,0.12)", fontSize: "0.5rem" }}>◆</span>
            </span>
          ))}
        </div>
      </div>
    </>
  );
}
