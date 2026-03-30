import type { CSSProperties } from "react";

export const ACCENT = "#35d8f7";
export const INK_STRONG = "#111111";
export const INK = "rgba(0,0,0,0.74)";
export const INK_MUTED = "rgba(0,0,0,0.58)";
export const INK_SOFT = "rgba(0,0,0,0.45)";

export const SECTION_BACKGROUND: CSSProperties = {
  background: "transparent",
};

export const PANEL_BACKGROUND =
  "linear-gradient(rgba(255,255,255,0.16) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,0.16) 1px, transparent 1px), rgba(186, 230, 253, 0.72)";
export const PANEL_BACKGROUND_SIZE = "26px 26px, 26px 26px, auto";
export const PANEL_BORDER = "1px solid rgba(255,255,255,0.45)";
export const PANEL_SHADOW = "0 18px 60px rgba(0,0,0,0.10)";

export const CARD_BACKGROUND = "rgba(255,255,255,0.42)";
export const CARD_BACKGROUND_SOFT = "rgba(255,255,255,0.24)";
export const CARD_BORDER = "1px solid rgba(255,255,255,0.35)";
export const CARD_SHADOW = "0 12px 36px rgba(0,0,0,0.08)";

export const ACCENT_TEXT_SHADOW = "0 1px 0 rgba(255,255,255,0.18)";
export const SOFT_TEXT_SHADOW = "0 1px 0 rgba(255,255,255,0.32)";
export const SMALL_ACCENT_SHADOW = "0 1px 0 rgba(255,255,255,0.14)";

export const HEADING_OUTLINE: CSSProperties = {
  WebkitTextStroke: "1.8px rgba(17,17,17,0.84)",
  textShadow: ACCENT_TEXT_SHADOW,
};

export const NUMBER_OUTLINE: CSSProperties = {
  WebkitTextStroke: "1.2px rgba(17,17,17,0.84)",
  textShadow: SMALL_ACCENT_SHADOW,
};
