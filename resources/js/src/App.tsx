import { lazy, Suspense, type ReactElement, useEffect } from 'react';
import { Routes, Route } from 'react-router';
import { LandingPage } from './components/pages/LandingPage';
import { getSpaConfig, getSpaPaths } from './lib/spaRouting';
import { captureTrafficAttribution, trackPublicVisit } from './lib/trafficAttribution';
import { Toaster } from 'sonner';

const LoginPage = lazy(() =>
  import('./components/auth/LoginPage').then((module) => ({ default: module.LoginPage }))
);
const RegisterPage = lazy(() =>
  import('./components/auth/RegisterPage').then((module) => ({ default: module.RegisterPage }))
);
const FoundItemsPage = lazy(() =>
  import('./components/pages/FoundItemsPage').then((module) => ({ default: module.FoundItemsPage }))
);
const ForgotQrPage = lazy(() =>
  import('./components/auth/ForgotQrPage').then((module) => ({ default: module.ForgotQrPage }))
);
const ReportPage = lazy(() =>
  import('./components/auth/ReportPage').then((module) => ({ default: module.ReportPage }))
);
const ReportTrackingPage = lazy(() =>
  import('./components/auth/ReportTrackingPage').then((module) => ({
    default: module.ReportTrackingPage,
  }))
);
const StaffLoginPage = lazy(() =>
  import('./components/auth/StaffLoginPage').then((module) => ({ default: module.StaffLoginPage }))
);
const StaffScannerPage = lazy(() =>
  import('./components/auth/StaffScannerPage').then((module) => ({
    default: module.StaffScannerPage,
  }))
);

function renderRoutes(paths: string[], element: ReactElement, key: string) {
  return paths.map((path) => (
    <Route key={`${key}:${path}`} path={path} element={element} />
  ));
}

function withRouteSuspense(element: ReactElement) {
  return <Suspense fallback={null}>{element}</Suspense>;
}

export default function App() {
  useEffect(() => {
    if (getSpaConfig().context !== 'public') {
      return;
    }

    const attribution = captureTrafficAttribution();

    trackPublicVisit(attribution);
  }, []);

  return (
    <>
      <Toaster position="top-center" richColors />
      <Routes>
        {renderRoutes(getSpaPaths('landing'), <LandingPage />, 'landing')}
        {renderRoutes(getSpaPaths('register'), withRouteSuspense(<RegisterPage />), 'register')}
        {renderRoutes(getSpaPaths('found'), withRouteSuspense(<FoundItemsPage />), 'found')}
        {renderRoutes(getSpaPaths('forgotQr'), withRouteSuspense(<ForgotQrPage />), 'forgotQr')}
        {renderRoutes(getSpaPaths('report'), withRouteSuspense(<ReportPage />), 'report')}
        {renderRoutes(
          getSpaPaths('reportTracking'),
          withRouteSuspense(<ReportTrackingPage />),
          'reportTracking'
        )}
        {renderRoutes(getSpaPaths('adminLogin'), withRouteSuspense(<LoginPage />), 'adminLogin')}
        {renderRoutes(
          getSpaPaths('staffLogin'),
          withRouteSuspense(<StaffLoginPage />),
          'staffLogin'
        )}
        {renderRoutes(
          getSpaPaths('staffHome'),
          withRouteSuspense(<StaffScannerPage />),
          'staffHome'
        )}
      </Routes>
    </>
  );
}
