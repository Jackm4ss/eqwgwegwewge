import { Routes, Route } from 'react-router';
import { LandingPage } from './components/pages/LandingPage';
import { LoginPage } from './components/auth/LoginPage';
import { RegisterPage } from './components/auth/RegisterPage';
import { ForgotQrPage } from './components/auth/ForgotQrPage';
import { ForgotPasswordPage } from './components/auth/ForgotPasswordPage';
import { StaffLoginPage } from './components/auth/StaffLoginPage';
import { StaffScannerPage } from './components/auth/StaffScannerPage';
import { Toaster } from 'sonner';

export default function App() {
  return (
    <>
      <Toaster position="top-center" richColors />
      <Routes>
        <Route path="/" element={<LandingPage />} />
        <Route path="/login" element={<LoginPage />} />
        <Route path="/register" element={<RegisterPage />} />
        <Route path="/forgot-qr" element={<ForgotQrPage />} />
        <Route path="/forgot-password" element={<ForgotPasswordPage />} />
        <Route path="/staff/login" element={<StaffLoginPage />} />
        <Route path="/staff" element={<StaffScannerPage />} />
      </Routes>
    </>
  );
}
