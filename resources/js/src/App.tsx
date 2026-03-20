import { GrainOverlay } from "./components/ui/GrainOverlay";
import { CustomCursor } from "./components/ui/CustomCursor";
import { Navbar } from "./components/sections/Navbar";
import { HeroSection } from "./components/sections/HeroSection";
import { Marquee } from "./components/ui/Marquee";
import { AboutFestival } from "./components/sections/AboutFestival";
import { EventDetails } from "./components/sections/EventDetails";
import { FestivalActivities } from "./components/sections/FestivalActivities";
import { ArtistLineUp } from "./components/sections/ArtistLineUp";
import { EventSchedule } from "./components/sections/EventSchedule";
import { Gallery } from "./components/sections/Gallery";
import { FAQ } from "./components/sections/FAQ";
import { Footer } from "./components/sections/Footer";

export default function App() {
  return (
    <div style={{ background: "#050508", cursor: "none" }} className="min-h-screen">
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
