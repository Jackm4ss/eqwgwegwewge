const SYNE: React.CSSProperties = { fontFamily: "'Tilt Warp', sans-serif" };
const SG: React.CSSProperties = { fontFamily: "'Tilt Warp', sans-serif" };

const items = [
  { text: "MALAYSIA 2026", accent: false },
  { text: "APR 9-19", accent: true },
  { text: "FREE ENTRY", accent: true },
  { text: "SONGKRAN FESTIVAL", accent: false },
  { text: "EQ SOLUTIONS", accent: false },
  { text: "สงกรานต์", accent: true },
];

export function SectionDivider({ reverse = false }: { reverse?: boolean }) {
  const track = [...items, ...items];

  return (
    <>
      <style>{`
        @keyframes section-divider-fwd {
          from { transform: translateX(0); }
          to { transform: translateX(-50%); }
        }
        @keyframes section-divider-rev {
          from { transform: translateX(-50%); }
          to { transform: translateX(0); }
        }
        .section-divider-track-fwd { animation: section-divider-fwd 10s linear infinite; }
        .section-divider-track-rev { animation: section-divider-rev 10s linear infinite; }
      `}</style>
      <div
        className="overflow-hidden py-3"
        style={{
          background: "rgba(10,10,20,0.96)",
          borderTop: "1px solid rgba(237,232,220,0.07)",
          borderBottom: "1px solid rgba(237,232,220,0.07)",
          backdropFilter: "blur(8px)",
          WebkitBackdropFilter: "blur(8px)",
        }}
      >
        <div
          className={`flex whitespace-nowrap ${
            reverse ? "section-divider-track-rev" : "section-divider-track-fwd"
          }`}
        >
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
              <span
                className="inline-block h-[4px] w-[4px] rounded-full"
                style={{ background: "rgba(237,232,220,0.14)" }}
              />
            </span>
          ))}
        </div>
      </div>
    </>
  );
}