export function GrainOverlay() {
  return (
    <>
      <style>{`
        @keyframes grain-move {
          0%,100% { transform: translate(0%,0%) }
          10% { transform: translate(-2%,-3%) }
          20% { transform: translate(3%,2%) }
          30% { transform: translate(-1%,-4%) }
          40% { transform: translate(4%,1%) }
          50% { transform: translate(-3%,3%) }
          60% { transform: translate(2%,-2%) }
          70% { transform: translate(-4%,4%) }
          80% { transform: translate(1%,-1%) }
          90% { transform: translate(3%,-3%) }
        }
        .grain-wrap {
          position: fixed;
          inset: 0;
          pointer-events: none;
          z-index: 9998;
          overflow: hidden;
        }
        .grain-svg {
          position: absolute;
          top: -25%;
          left: -25%;
          width: 150%;
          height: 150%;
          opacity: 0.13;
          animation: grain-move 8s steps(10) infinite;
          mix-blend-mode: overlay;
        }
      `}</style>
      <div className="grain-wrap">
        <svg className="grain-svg">
          <filter id="gf">
            <feTurbulence type="fractalNoise" baseFrequency="0.72" numOctaves="4" stitchTiles="stitch" />
            <feColorMatrix type="saturate" values="0" />
          </filter>
          <rect width="100%" height="100%" filter="url(#gf)" />
        </svg>
      </div>
    </>
  );
}
