import { useEffect, useState } from "react";
import { motion, useMotionValue, useSpring } from "motion/react";

export function CustomCursor() {
  const [hovering, setHovering] = useState(false);
  const [clicking, setClicking] = useState(false);

  const mx = useMotionValue(-100);
  const my = useMotionValue(-100);

  const dotX = useSpring(mx, { stiffness: 500, damping: 30 });
  const dotY = useSpring(my, { stiffness: 500, damping: 30 });
  const ringX = useSpring(mx, { stiffness: 150, damping: 25 });
  const ringY = useSpring(my, { stiffness: 150, damping: 25 });

  useEffect(() => {
    const move = (e: MouseEvent) => { mx.set(e.clientX); my.set(e.clientY); };
    const over = (e: MouseEvent) => {
      setHovering(!!(e.target as HTMLElement).closest("a,button,[data-hover]"));
    };
    const down = () => setClicking(true);
    const up = () => setClicking(false);

    window.addEventListener("mousemove", move);
    document.addEventListener("mouseover", over);
    window.addEventListener("mousedown", down);
    window.addEventListener("mouseup", up);
    return () => {
      window.removeEventListener("mousemove", move);
      document.removeEventListener("mouseover", over);
      window.removeEventListener("mousedown", down);
      window.removeEventListener("mouseup", up);
    };
  }, []);

  return (
    <div className="hidden md:block">
      <motion.div
        className="fixed pointer-events-none z-[99999] rounded-full border border-[#2FA7D8]"
        style={{ x: ringX, y: ringY, translateX: "-50%", translateY: "-50%", top: 0, left: 0 }}
        animate={{ width: hovering ? 56 : 36, height: hovering ? 56 : 36, opacity: hovering ? 0.8 : 0.4 }}
        transition={{ duration: 0.25, ease: "easeOut" }}
      />
      <motion.div
        className="fixed pointer-events-none z-[99999] rounded-full bg-[#2FA7D8]"
        style={{ x: dotX, y: dotY, translateX: "-50%", translateY: "-50%", top: 0, left: 0 }}
        animate={{ width: clicking ? 3 : hovering ? 10 : 5, height: clicking ? 3 : hovering ? 10 : 5 }}
        transition={{ duration: 0.15 }}
      />
    </div>
  );
}
