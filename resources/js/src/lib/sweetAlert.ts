export type SweetAlertResult = {
  isConfirmed?: boolean;
  isDismissed?: boolean;
};

export type SweetAlertInstance = {
  fire: (options: Record<string, unknown>) => Promise<SweetAlertResult>;
};

let sweetAlertLoader: Promise<SweetAlertInstance> | null = null;

function ensureSweetAlertAssets() {
  if (typeof document === 'undefined') {
    return;
  }

  const styleId = 'swal2-vuexy-style';

  if (!document.getElementById(styleId)) {
    const link = document.createElement('link');
    link.id = styleId;
    link.rel = 'stylesheet';
    link.href = '/assets-vuexy/vendor/libs/sweetalert2/sweetalert2.css';
    document.head.appendChild(link);
  }
}

export function ensureSweetAlert(): Promise<SweetAlertInstance> {
  if (typeof window === 'undefined' || typeof document === 'undefined') {
    return Promise.reject(new Error('SweetAlert requires a browser environment.'));
  }

  const globalWindow = window as Window & { Swal?: SweetAlertInstance };

  if (globalWindow.Swal) {
    ensureSweetAlertAssets();
    return Promise.resolve(globalWindow.Swal);
  }

  if (sweetAlertLoader) {
    return sweetAlertLoader;
  }

  sweetAlertLoader = new Promise<SweetAlertInstance>((resolve, reject) => {
    ensureSweetAlertAssets();

    const existingScript = document.getElementById('swal2-vuexy-script') as HTMLScriptElement | null;

    if (existingScript) {
      existingScript.addEventListener('load', () => {
        if (globalWindow.Swal) {
          resolve(globalWindow.Swal);
          return;
        }

        reject(new Error('Failed to load SweetAlert.'));
      }, { once: true });
      existingScript.addEventListener('error', () => reject(new Error('Failed to load SweetAlert.')), { once: true });
      return;
    }

    const script = document.createElement('script');
    script.id = 'swal2-vuexy-script';
    script.src = '/assets-vuexy/vendor/libs/sweetalert2/sweetalert2.js';
    script.async = true;
    script.onload = () => {
      if (globalWindow.Swal) {
        resolve(globalWindow.Swal);
        return;
      }

      reject(new Error('Failed to load SweetAlert.'));
    };
    script.onerror = () => reject(new Error('Failed to load SweetAlert.'));
    document.body.appendChild(script);
  }).catch((error) => {
    sweetAlertLoader = null;
    throw error;
  });

  return sweetAlertLoader;
}
