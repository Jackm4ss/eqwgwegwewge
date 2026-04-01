import { motion } from "motion/react";
import { SponsorShowcaseCard } from "../ui/SponsorShowcaseCard";

export function SponsorSection() {
  return (
    <section
      aria-label="Sponsors"
      className="relative px-6 py-14 md:px-12 md:py-16 lg:px-16"
    >
      <div className="relative mx-auto max-w-7xl">
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          whileInView={{ opacity: 1, y: 0 }}
          viewport={{ once: true }}
          transition={{ duration: 0.7, ease: "easeOut" }}
        >
          <SponsorShowcaseCard />
        </motion.div>
      </div>
    </section>
  );
}
