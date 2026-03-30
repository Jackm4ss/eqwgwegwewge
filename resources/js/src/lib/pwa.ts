import { getSpaConfig } from './spaRouting';

export type BeforeInstallPromptEvent = Event & {
  readonly platforms?: string[];
  readonly userChoice: Promise<{
    outcome: 'accepted' | 'dismissed';
    platform: string;
  }>;
  prompt: () => Promise<void>;
};

export function registerServiceWorker() {
  if (typeof window === 'undefined' || !('serviceWorker' in navigator) || !import.meta.env.PROD) {
    return;
  }

  if (!getSpaConfig().pwa.enabled) {
    return;
  }

  window.addEventListener('load', () => {
    void (async () => {
      try {
        const registration = await navigator.serviceWorker.register('/sw.js');
        void registration.update().catch(() => undefined);
      } catch (error) {
        console.error('Service worker registration failed.', error);
      }
    })();
  }, { once: true });
}
