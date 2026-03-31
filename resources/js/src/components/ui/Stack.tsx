import React, { useState, useEffect, useCallback } from 'react';
import { motion, AnimatePresence } from 'motion/react';
import { LazyImage } from './LazyImage';

interface Card {
  id: number | string;
  src: string;
}

interface StackProps {
  randomRotation?: boolean;
  sensitivity?: number;
  sendToBackOnClick?: boolean;
  cardDimensions?: { width: number | string; height: number | string };
  cardsData: Card[];
  autoplay?: boolean;
  autoplayDelay?: number;
}

export const Stack: React.FC<StackProps> = ({
  randomRotation = false,
  sensitivity = 200,
  sendToBackOnClick = false,
  cardDimensions = { width: 300, height: 400 },
  cardsData,
  autoplay = false,
  autoplayDelay = 1400,
}) => {
  // Memutarbalikkan array agar item pertama di cardsData muncul paling atas (index terbesar)
  const [cards, setCards] = useState([...cardsData].reverse());

  const popCard = useCallback((direction: 'left' | 'right' = 'right') => {
    setCards((prev) => {
      if (prev.length === 0) return prev;
      const newCards = [...prev];
      const last = newCards.pop()!;

      // Memberikan jeda waktu agar animasi 'exit' (lempar kartu) bisa selesai dijalankan
      // sebelum kartu dimunculkan kembali di tumpukan paling bawah.
      setTimeout(() => {
        setCards((current) => [last, ...current]);
      }, 300);

      return newCards;
    });
  }, []);

  useEffect(() => {
    if (!autoplay) return;
    const interval = setInterval(() => popCard('right'), autoplayDelay);
    return () => clearInterval(interval);
  }, [autoplay, autoplayDelay, popCard]);

  const handleDragEnd = (event: any, info: any) => {
    if (info.offset.x > sensitivity) {
      popCard('right');
    } else if (info.offset.x < -sensitivity) {
      popCard('left');
    }
  };

  const handleClick = () => {
    if (sendToBackOnClick) {
      popCard('right');
    }
  };

  return (
    <div style={{ position: 'relative', width: cardDimensions.width, height: cardDimensions.height, perspective: 1000, margin: '0 auto' }}>
      <AnimatePresence mode="popLayout">
        {cards.map((card, index) => {
          const isTop = index === cards.length - 1;
          const indexFromTop = cards.length - 1 - index;
          return (
            <motion.div
              key={card.id as string | number}
              style={{
                position: 'absolute',
                top: 0,
                left: 0,
                width: '100%',
                height: '100%',
                zIndex: index,
                borderRadius: 24,
                overflow: 'hidden',
                boxShadow: '0 15px 40px rgba(0,0,0,0.35)',
                transformOrigin: 'bottom center',
                cursor: isTop && sendToBackOnClick ? 'pointer' : (isTop ? 'grab' : 'auto'),
              }}
              // Posisi awal saat kartu baru muncul (di tumpukan paling belakang)
              initial={{ x: 0, scale: 0.8, y: 40, opacity: 0 }}
              animate={{
                x: 0,
                scale: 1 - indexFromTop * 0.05,
                y: indexFromTop * 15,
                rotate: randomRotation ? (Number(card.id) % 2 === 0 ? 3 : -3) * indexFromTop : 0,
                opacity: 1 - indexFromTop * 0.15,
              }}
              // Animasi "lempar" kartu (Swipe) saat keluar dari tumpukan
              exit={{ 
                x: 200, 
                y: 50,
                rotate: 15,
                opacity: 0, 
                scale: 0.9,
              }}
              transition={{ type: 'spring', stiffness: 300, damping: 20, mass: 1, duration: 0.3 }}
              drag={isTop ? 'x' : false}
              dragConstraints={{ left: 0, right: 0 }}
              dragElastic={0.8}
              dragSnapToOrigin={true}
              onDragEnd={isTop ? handleDragEnd : undefined}
              onClick={isTop ? handleClick : undefined}
            >
              <LazyImage
                src={card.src}
                alt="stack-card"
                loading="lazy"
                wrapperClassName="h-full w-full pointer-events-none"
                className="h-full w-full object-cover pointer-events-none"
              />
              <div style={{ position: 'absolute', inset: 0, background: 'linear-gradient(135deg, rgba(5,5,8,0.25) 0%, transparent 60%)', pointerEvents: 'none' }} />
            </motion.div>
          );
        })}
      </AnimatePresence>
    </div>
  );
};
