import { useEffect, useRef } from 'react';

interface Ripple {
  x: number;
  y: number;
  radius: number;
  maxRadius: number;
  opacity: number;
  speed: number;
}

interface Bubble {
  x: number;
  y: number;
  radius: number;
  speed: number;
  opacity: number;
  wobble: number;
  wobbleSpeed: number;
}

interface WaterAnimationProps {
  onCanvasReady?: (addRipple: (x: number, y: number) => void) => void;
}

export function WaterAnimation({ onCanvasReady }: WaterAnimationProps) {
  const canvasRef = useRef<HTMLCanvasElement>(null);

  useEffect(() => {
    const canvas = canvasRef.current;
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    if (!ctx) return;

    let animationId: number;
    const ripples: Ripple[] = [];
    const bubbles: Bubble[] = [];

    const resize = () => {
      canvas.width = window.innerWidth;
      canvas.height = window.innerHeight;
    };
    resize();
    window.addEventListener('resize', resize);

    // Initialize bubbles
    for (let i = 0; i < 45; i++) {
      bubbles.push({
        x: Math.random() * canvas.width,
        y: Math.random() * canvas.height,
        radius: Math.random() * 5 + 1,
        speed: Math.random() * 0.6 + 0.15,
        opacity: Math.random() * 0.3 + 0.05,
        wobble: Math.random() * Math.PI * 2,
        wobbleSpeed: Math.random() * 0.02 + 0.005,
      });
    }

    const addRipple = (x: number, y: number) => {
      ripples.push({
        x,
        y,
        radius: 0,
        maxRadius: Math.random() * 100 + 60,
        opacity: 0.6,
        speed: Math.random() * 0.7 + 0.4,
      });
    };

    if (onCanvasReady) onCanvasReady(addRipple);

    let lastRippleTime = 0;

    const animate = (timestamp: number) => {
      ctx.clearRect(0, 0, canvas.width, canvas.height);

      // Auto-create ripples periodically
      if (timestamp - lastRippleTime > 1400) {
        addRipple(
          Math.random() * canvas.width,
          Math.random() * canvas.height
        );
        lastRippleTime = timestamp;
      }

      // Draw and update ripples
      for (let i = ripples.length - 1; i >= 0; i--) {
        const r = ripples[i];
        r.radius += r.speed;
        r.opacity = (1 - r.radius / r.maxRadius) * 0.45;

        // Outer ring
        ctx.beginPath();
        ctx.arc(r.x, r.y, r.radius, 0, Math.PI * 2);
        ctx.shadowColor = 'rgba(255, 255, 255, 0.8)';
        ctx.shadowBlur = 8;
        ctx.strokeStyle = `rgba(255, 255, 255, ${r.opacity * 0.9})`;
        ctx.lineWidth = 1.5;
        ctx.stroke();
        ctx.shadowBlur = 0; // Reset shadow for other drawings

        // Inner ring
        if (r.radius > 25) {
          ctx.beginPath();
          ctx.arc(r.x, r.y, r.radius * 0.6, 0, Math.PI * 2);
          ctx.strokeStyle = `rgba(255, 255, 255, ${r.opacity * 0.5})`;
          ctx.lineWidth = 0.8;
          ctx.stroke();
        }

        // Innermost ring
        if (r.radius > 50) {
          ctx.beginPath();
          ctx.arc(r.x, r.y, r.radius * 0.3, 0, Math.PI * 2);
          ctx.strokeStyle = `rgba(255, 255, 255, ${r.opacity * 0.3})`;
          ctx.lineWidth = 0.5;
          ctx.stroke();
        }

        if (r.radius >= r.maxRadius) {
          ripples.splice(i, 1);
        }
      }

      // Draw and update bubbles
      bubbles.forEach(b => {
        b.y -= b.speed;
        b.wobble += b.wobbleSpeed;
        b.x += Math.sin(b.wobble) * 0.4;

        if (b.y < -10) {
          b.y = canvas.height + 10;
          b.x = Math.random() * canvas.width;
        }

        // Bubble body
        ctx.beginPath();
        ctx.arc(b.x, b.y, b.radius, 0, Math.PI * 2);
        ctx.shadowColor = 'rgba(255, 255, 255, 0.8)';
        ctx.shadowBlur = 6;
        ctx.fillStyle = `rgba(255, 255, 255, ${b.opacity * 0.7})`;
        ctx.fill();
        ctx.shadowBlur = 0; // Reset shadow

        // Bubble highlight
        ctx.beginPath();
        ctx.arc(
          b.x - b.radius * 0.3,
          b.y - b.radius * 0.35,
          b.radius * 0.35,
          0,
          Math.PI * 2
        );
        ctx.fillStyle = `rgba(255, 255, 255, ${b.opacity})`;
        ctx.fill();
      });

      animationId = requestAnimationFrame(animate);
    };

    animationId = requestAnimationFrame(animate);

    return () => {
      cancelAnimationFrame(animationId);
      window.removeEventListener('resize', resize);
    };
  }, []);

  return (
    <canvas
      ref={canvasRef}
      className="fixed inset-0 pointer-events-none"
      style={{ zIndex: 0 }}
      aria-hidden="true"
    />
  );
}
