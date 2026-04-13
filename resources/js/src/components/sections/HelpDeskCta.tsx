import { motion } from "motion/react";
import { ArrowUpRight, ShieldAlert } from "lucide-react";
import { getSpaPaths } from "@/lib/spaRouting";

type HelpDeskCtaProps = {
  tone?: "dark" | "light";
  layout?: "stacked" | "inline";
  className?: string;
};

const TILT: React.CSSProperties = { fontFamily: "'Tilt Warp', sans-serif" };

export function HelpDeskCta({
  tone = "dark",
  layout = "stacked",
  className = "",
}: HelpDeskCtaProps) {
  const helpdeskUrl = getSpaPaths("report")[0] ?? "/report";
  const isDark = tone === "dark";
  const isInline = layout === "inline";

  const handleOpenHelpDesk = () => {
    window.location.assign(helpdeskUrl);
  };

  return (
    <motion.div
      initial={{ opacity: 0, y: 18 }}
      whileInView={{ opacity: 1, y: 0 }}
      viewport={{ once: true, amount: 0.35 }}
      transition={{ duration: 0.55, ease: [0.22, 1, 0.36, 1] }}
      className={[
        "rounded-[28px] border px-5 py-5 shadow-[0_20px_44px_rgba(15,23,42,0.14)] backdrop-blur-xl md:px-6",
        isInline ? "flex flex-col gap-5 md:flex-row md:items-center md:justify-between" : "text-center",
        className,
      ].join(" ")}
      style={{
        background: isDark
          ? "linear-gradient(145deg, rgba(7, 20, 37, 0.82), rgba(9, 78, 119, 0.78))"
          : "linear-gradient(145deg, rgba(240, 249, 255, 0.92), rgba(224, 242, 254, 0.82))",
        borderColor: isDark ? "rgba(125, 211, 252, 0.28)" : "rgba(14, 116, 144, 0.18)",
      }}
    >
      <div className={isInline ? "max-w-2xl text-left" : "mx-auto max-w-xl"}>
        <div
          className={[
            "mb-3 inline-flex items-center gap-2 rounded-full border px-3 py-1.5",
            isInline ? "" : "justify-center",
          ].join(" ")}
          style={{
            borderColor: isDark ? "rgba(186, 230, 253, 0.22)" : "rgba(8, 47, 73, 0.12)",
            background: isDark ? "rgba(8, 47, 73, 0.24)" : "rgba(255, 255, 255, 0.66)",
          }}
        >
          <ShieldAlert
            size={15}
            style={{ color: isDark ? "#7dd3fc" : "#0f766e", flexShrink: 0 }}
          />
          <span
            style={{
              ...TILT,
              fontSize: "0.72rem",
              letterSpacing: "0.12em",
              textTransform: "uppercase",
              color: isDark ? "rgba(224, 242, 254, 0.96)" : "rgba(15, 23, 42, 0.72)",
            }}
          >
            Help Desk
          </span>
        </div>

        <h3
          style={{
            ...TILT,
            fontSize: isInline ? "clamp(1.2rem, 2vw, 1.55rem)" : "clamp(1.12rem, 2vw, 1.45rem)",
            lineHeight: 1.25,
            color: "#389DD2",
          }}
        >
          Lost an item, need to report an issue, or want to file a complaint?
        </h3>

        <p
          className={isInline ? "mt-2 max-w-xl" : "mt-2"}
          style={{
            ...TILT,
            fontSize: "0.8rem",
            lineHeight: 1.75,
            color: isDark ? "rgba(224, 242, 254, 0.82)" : "rgba(8, 47, 73, 0.7)",
          }}
        >
          Please go to the Help Desk page so the team can review your request and follow up properly.
        </p>
      </div>

      <div className={isInline ? "md:flex-shrink-0" : "mt-4"}>
        <button
          type="button"
          onClick={handleOpenHelpDesk}
          className="group inline-flex items-center justify-center gap-2 rounded-full px-6 py-3 transition-transform hover:scale-[1.03] active:scale-[0.98]"
          style={{
            ...TILT,
            fontSize: "0.82rem",
            letterSpacing: "0.12em",
            textTransform: "uppercase",
            color: "#ffffff",
            background: "linear-gradient(135deg, #0284C7, #0EA5E9)",
            boxShadow: "0 4px 14px rgba(2,132,199,0.35)",
          }}
        >
          Open Help Desk
          <ArrowUpRight
            size={16}
            className="transition-transform duration-200 group-hover:translate-x-0.5 group-hover:-translate-y-0.5"
          />
        </button>
      </div>
    </motion.div>
  );
}
