import { type ReactElement } from 'react';
import { Routes, Route } from 'react-router';
import { LandingPage } from './components/pages/LandingPage';
import { LoginPage } from './components/auth/LoginPage';
import { RegisterPage } from './components/auth/RegisterPage';
import { ForgotQrPage } from './components/auth/ForgotQrPage';
import { StaffLoginPage } from './components/auth/StaffLoginPage';
import { StaffScannerPage } from './components/auth/StaffScannerPage';
import { getSpaPaths } from './lib/spaRouting';
import { Toaster } from 'sonner';

function renderRoutes(paths: string[], element: ReactElement, key: string) {
  return paths.map((path) => (
    <Route key={`${key}:${path}`} path={path} element={element} />
  ));
}

export default function App() {
  return (
    <>
      <Toaster position="top-center" richColors />
      <Routes>
        {renderRoutes(getSpaPaths('landing'), <LandingPage />, 'landing')}
        {renderRoutes(getSpaPaths('register'), <RegisterPage />, 'register')}
        {renderRoutes(getSpaPaths('forgotQr'), <ForgotQrPage />, 'forgotQr')}
        {renderRoutes(getSpaPaths('adminLogin'), <LoginPage />, 'adminLogin')}
        {renderRoutes(getSpaPaths('staffLogin'), <StaffLoginPage />, 'staffLogin')}
        {renderRoutes(getSpaPaths('staffHome'), <StaffScannerPage />, 'staffHome')}
      </Routes>
    </>
  );
}
