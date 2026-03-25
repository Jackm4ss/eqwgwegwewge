import { GrainOverlay } from "../ui/GrainOverlay";
import { CustomCursor } from "../ui/CustomCursor";
import { Navbar } from "../sections/Navbar";
import { HeroSection } from "../sections/HeroSection";
import { Marquee } from "../ui/Marquee";
import { AboutFestival } from "../sections/AboutFestival";
import { EventDetails } from "../sections/EventDetails";
import { FestivalActivities } from "../sections/FestivalActivities";
import { EventSchedule } from "../sections/EventSchedule";
import { FAQ } from "../sections/FAQ";
import { Footer } from "../sections/Footer";

export function LandingPage() {
  return (
    <div className="min-h-screen custom-cursor-area">
      <GrainOverlay />
      <CustomCursor />
      <Navbar />
      <HeroSection />

      <div
        style={{
          backgroundImage: "url('/images/BACKGROUND.jpg')",
          backgroundSize: "cover",
          backgroundPosition: "center",
          backgroundRepeat: "no-repeat",
        }}
      >
        <Marquee />
        <AboutFestival />
        <EventDetails />
        <Marquee reverse />
        <FestivalActivities />
        <EventSchedule />
        <FAQ />
      </div>

      <Footer />
    </div>
  );
}
