import { GrainOverlay } from "../ui/GrainOverlay";
import { CustomCursor } from "../ui/CustomCursor";
import { Navbar } from "../sections/Navbar";
import { HeroSection } from "../sections/HeroSection";
import { Marquee } from "../ui/Marquee";
import { AboutFestival } from "../sections/AboutFestival";
import { EventDetails } from "../sections/EventDetails";
import { FestivalActivities } from "../sections/FestivalActivities";
import { ArtistLineUp } from "../sections/ArtistLineUp";
import { EventSchedule } from "../sections/EventSchedule";
import { Gallery } from "../sections/Gallery";
import { FAQ } from "../sections/FAQ";
import { Footer } from "../sections/Footer";

export function LandingPage() {
  return (
    <div style={{ background: "#050508", cursor: "none" }} className="min-h-screen custom-cursor-area">
      <GrainOverlay />
      <CustomCursor />
      <Navbar />
      <HeroSection />
      <Marquee />
      <AboutFestival />
      <ArtistLineUp />
      <EventDetails />
      <Marquee reverse />
      <FestivalActivities />
      <EventSchedule />
      <Gallery />
      <FAQ />
      <Footer />
    </div>
  );
}
