import { motion } from "motion/react";

const DONTS_IMAGE_URL = "/images/dont.jpeg";

export function DontsSection() {
  return (
    <section
      aria-label="Festival don'ts"
      className="relative px-6 py-18 md:px-12 md:py-20 lg:px-16"
    >
      <div className="relative mx-auto max-w-7xl">
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          whileInView={{ opacity: 1, y: 0 }}
          viewport={{ once: true }}
          transition={{ duration: 0.7, ease: "easeOut" }}
          className="rounded-[32px] border border-white/15 px-4 py-5 md:px-6 md:py-6 lg:px-8 lg:py-8"
          style={{
            background:
              "linear-gradient(180deg, rgba(255,255,255,0.16) 0%, rgba(255,255,255,0.06) 100%)",
            backdropFilter: "blur(14px)",
            WebkitBackdropFilter: "blur(14px)",
            boxShadow: "0 22px 55px rgba(0,0,0,0.2)",
          }}
        >
          <img
            src={DONTS_IMAGE_URL}
            alt="Festival prohibited items and behavior guidelines"
            loading="lazy"
            className="mx-auto h-auto w-full max-w-[1040px] object-contain"
          />
        </motion.div>
      </div>
    </section>
  );
}
