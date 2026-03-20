import { useState, useCallback, useRef } from 'react';
import { useForm } from 'react-hook-form';
import { motion, AnimatePresence } from 'motion/react';
import { Toaster, toast } from 'sonner';
import {
  Mail, ArrowLeft, Loader2, Droplets, ShieldCheck,
  LayoutDashboard, QrCode, Activity, HardDrive, CheckCircle2, ChevronRight
} from 'lucide-react';
import { Link, useNavigate } from 'react-router';
import { WaterAnimation } from './WaterAnimation';

interface ForgotPasswordFormData {
  email: string;
}

function LotusIcon({ className }: { className?: string }) {
  return (
    <svg viewBox="0 0 120 120" className={className} aria-hidden="true" fill="currentColor">
      <ellipse cx="60" cy="90" rx="8" ry="5" opacity="0.9" />
      <path d="M60 90 C60 90 38 68 38 46 C38 28 48 16 60 16 C72 16 82 28 82 46 C82 68 60 90 60 90Z" opacity="0.75" />
      <path d="M60 90 C60 90 22 72 16 50 C12 32 22 18 34 20 C46 22 60 90 60 90Z" opacity="0.6" />
      <path d="M60 90 C60 90 98 72 104 50 C108 32 98 18 86 20 C74 22 60 90 60 90Z" opacity="0.6" />
      <path d="M60 90 C60 90 8 80 6 56 C4 36 16 22 28 26 C42 30 60 90 60 90Z" opacity="0.4" />
      <path d="M60 90 C60 90 112 80 114 56 C116 36 104 22 92 26 C78 30 60 90 60 90Z" opacity="0.4" />
    </svg>
  );
}

function FieldError({ id, message }: { id: string; message?: string }) {
  if (!message) return null;
  return (
    <motion.p
      id={id}
      role="alert"
      initial={{ opacity: 0, scale: 0.95 }}
      animate={{ opacity: 1, scale: 1 }}
      className="mt-1.5 text-red-500 text-xs font-medium flex items-center gap-1.5"
    >
      <span className="w-1 h-1 rounded-full bg-red-500" />
      {message}
    </motion.p>
  );
}

export function ForgotPasswordPage() {
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [isSent, setIsSent] = useState(false);
  const addRippleRef = useRef<((x: number, y: number) => void) | null>(null);
  const navigate = useNavigate();

  const {
    register,
    handleSubmit,
    formState: { errors },
  } = useForm<ForgotPasswordFormData>({ mode: 'onTouched' });

  const handleCanvasReady = useCallback((fn: (x: number, y: number) => void) => {
    addRippleRef.current = fn;
  }, []);

  const handlePageClick = useCallback((e: React.MouseEvent) => {
    if (addRippleRef.current) {
      addRippleRef.current(e.clientX, e.clientY);
    }
  }, []);

  const onSubmit = async (data: ForgotPasswordFormData) => {
    setIsSubmitting(true);
    // Simulate API call
    await new Promise(r => setTimeout(r, 2000));
    setIsSubmitting(false);
    setIsSent(true);
    toast.success('Instruksi pemulihan terkirim!', {
      description: `Periksa email: ${data.email} untuk langkah selanjutnya.`,
    });
  };

  const inputBase =
    'w-full px-4 py-3.5 pl-11 rounded-xl border-2 transition-all duration-300 bg-white text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-offset-1 shadow-sm';

  const inputClass = (hasError: boolean) =>
    `${inputBase} ${
      hasError
        ? 'border-red-400 focus:border-red-500 focus:ring-red-200'
        : 'border-sky-100 focus:border-sky-500 focus:ring-sky-200 hover:border-sky-200'
    }`;

  return (
    <div
      className="min-h-screen relative overflow-x-hidden flex items-stretch"
      style={{ background: 'linear-gradient(145deg, #0C4A6E 0%, #0369A1 30%, #0284C7 60%, #0EA5E9 100%)' }}
      onClick={handlePageClick}
    >
      <Toaster position="top-center" richColors />
      <WaterAnimation onCanvasReady={handleCanvasReady} />

      {/* Decorative background elements (consistent with Login) */}
      <div className="fixed inset-0 pointer-events-none overflow-hidden opacity-30 select-none" style={{ zIndex: 1 }}>
        <div className="absolute top-0 left-0 w-full h-full" style={{ background: 'url("https://www.transparenttextures.com/patterns/cubes.png")' }} />
        <div className="absolute -top-32 -left-32 w-96 h-96 bg-sky-200/20 rounded-full blur-[100px]" />
        <div className="absolute -bottom-32 -right-32 w-96 h-96 bg-sky-400/20 rounded-full blur-[100px]" />
      </div>

      <div className="relative flex flex-col lg:flex-row w-full items-stretch" style={{ zIndex: 2 }}>
        {/* ── LEFT PANEL – FORM ── */}
        <motion.main
          initial={{ opacity: 0, x: -30 }}
          animate={{ opacity: 1, x: 0 }}
          transition={{ duration: 0.8 }}
          className="flex-1 flex items-center justify-center p-6 lg:p-12"
        >
          <div className="w-full max-w-[480px]">
            {/* Back to Login */}
            <motion.div
              initial={{ opacity: 0, y: -10 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ delay: 0.3 }}
            >
              <Link
                to="/"
                className="inline-flex items-center gap-2 text-sky-100/70 hover:text-white transition-colors mb-8 group bg-white/5 hover:bg-white/10 px-4 py-2 rounded-full border border-white/10"
              >
                <ArrowLeft className="w-4 h-4 transition-transform group-hover:-translate-x-1" />
                <span className="text-sm font-semibold">Kembali ke Halaman Masuk</span>
              </Link>
            </motion.div>

            {/* Main Card */}
            <motion.div
              initial={{ opacity: 0, y: 20 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ delay: 0.2, duration: 0.6 }}
              className="bg-white rounded-[2.5rem] shadow-[0_30px_100px_rgba(0,0,0,0.4)] overflow-hidden border border-white/20"
            >
              <div className="p-8 lg:p-10">
                <AnimatePresence mode="wait">
                  {!isSent ? (
                    <motion.div
                      key="form"
                      initial={{ opacity: 0, scale: 0.95 }}
                      animate={{ opacity: 1, scale: 1 }}
                      exit={{ opacity: 0, scale: 0.95 }}
                    >
                      <div className="flex items-center gap-4 mb-8">
                        <div className="w-14 h-14 bg-sky-100 rounded-2xl flex items-center justify-center text-sky-600 shadow-inner">
                          <LockIcon />
                        </div>
                        <div>
                          <h1 className="text-2xl font-bold text-slate-800 leading-tight" style={{ fontFamily: '"Kanit", sans-serif' }}>
                            Lupa Kata Sandi?
                          </h1>
                          <p className="text-slate-500 text-sm mt-1">
                            Masukkan email Anda untuk instruksi pemulihan.
                          </p>
                        </div>
                      </div>

                      <form onSubmit={handleSubmit(onSubmit)} className="space-y-6">
                        <div>
                          <label htmlFor="email" className="block text-slate-700 text-sm font-bold mb-2 ml-1">
                            Alamat Email Petugas
                          </label>
                          <div className="relative group">
                            <Mail className="absolute left-4 top-1/2 -translate-y-1/2 text-sky-400 group-focus-within:text-sky-600 transition-colors w-5 h-5" />
                            <input
                              id="email"
                              type="email"
                              placeholder="nama@songkran.com"
                              className={inputClass(!!errors.email)}
                              {...register('email', {
                                required: 'Alamat email wajib diisi',
                                pattern: {
                                  value: /^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}$/i,
                                  message: 'Format email tidak valid (harus mengandung @)',
                                },
                              })}
                            />
                          </div>
                          <AnimatePresence>
                            <FieldError id="err-email" message={errors.email?.message} />
                          </AnimatePresence>
                        </div>

                        <motion.button
                          type="submit"
                          disabled={isSubmitting}
                          whileHover={{ scale: 1.01, y: -2 }}
                          whileTap={{ scale: 0.98 }}
                          className="w-full bg-gradient-to-r from-sky-600 to-sky-500 text-white font-black py-4 rounded-xl shadow-lg shadow-sky-600/30 hover:shadow-sky-600/40 disabled:opacity-70 disabled:cursor-not-allowed transition-all flex items-center justify-center gap-2 group tracking-wide"
                        >
                          {isSubmitting ? (
                            <>
                              <Loader2 className="w-5 h-5 animate-spin" />
                              <span>Sedang Memproses...</span>
                            </>
                          ) : (
                            <>
                              <span>Kirim Instruksi Pemulihan</span>
                              <ChevronRight className="w-5 h-5 group-hover:translate-x-1 transition-transform" />
                            </>
                          )}
                        </motion.button>
                      </form>
                    </motion.div>
                  ) : (
                    <motion.div
                      key="success"
                      initial={{ opacity: 0, y: 20 }}
                      animate={{ opacity: 1, y: 0 }}
                      className="text-center py-6"
                    >
                      <div className="w-20 h-20 bg-emerald-100 rounded-full flex items-center justify-center text-emerald-600 mx-auto mb-6 shadow-inner">
                        <CheckCircle2 className="w-10 h-10 animate-bounce" />
                      </div>
                      <h2 className="text-2xl font-bold text-slate-800 leading-tight mb-3" style={{ fontFamily: '"Kanit", sans-serif' }}>
                        Email Terkirim!
                      </h2>
                      <p className="text-slate-600 text-sm leading-relaxed mb-8">
                        Kami telah mengirimkan instruksi pembaharuan kata sandi ke alamat email Anda. Silakan cek kotak masuk atau folder spam.
                      </p>
                      
                      <button
                        onClick={() => setIsSent(false)}
                        className="text-sky-600 font-bold text-sm hover:underline"
                      >
                        Tidak terima email? Coba lagi
                      </button>
                    </motion.div>
                  )}
                </AnimatePresence>
              </div>

              {/* Footer info */}
              <div className="bg-slate-50 border-t border-slate-100 p-6 text-center">
                <p className="text-slate-500 text-xs flex items-center justify-center gap-2 font-medium">
                  Informasi lebih lanjut? Hubungi <span className="text-sky-700 font-bold">IT Support Desk</span>
                </p>
              </div>
            </motion.div>
          </div>
        </motion.main>

        {/* ── RIGHT PANEL – INFO (Consistent with Login) ── */}
        <motion.aside
          initial={{ opacity: 0, x: 30 }}
          animate={{ opacity: 1, x: 0 }}
          transition={{ duration: 0.8 }}
          className="hidden lg:flex lg:w-[42%] flex-col justify-center items-center px-12 py-12 text-white bg-white/5 backdrop-blur-sm border-l border-white/10"
        >
          <motion.div
            initial={{ opacity: 0, y: -20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ delay: 0.4 }}
            className="flex flex-center gap-2 bg-white/10 border border-white/10 px-5 py-2.5 rounded-full mb-10"
          >
            <Activity className="w-4 h-4 text-sky-200" />
            <span className="text-sky-100 text-[10px] tracking-[0.2em] font-black uppercase">
              Gate Management System
            </span>
          </motion.div>

          {/* Branded Identity */}
          <div className="text-center mb-12">
            <motion.div
              animate={{ y: [0, -10, 0] }}
              transition={{ duration: 5, repeat: Infinity, ease: 'easeInOut' }}
              className="w-24 h-24 text-sky-200 mx-auto mb-6"
            >
              <LotusIcon className="w-full h-full" />
            </motion.div>
            <h2 className="text-5xl font-black tracking-tighter" style={{ fontFamily: '"Kanit", sans-serif' }}>
              SONGKRAN
            </h2>
            <p className="text-sky-200 text-lg tracking-[0.4em] font-light">OFFICIAL STAFF</p>
          </div>

          {/* Service Card (Consistent 3D tilt style) */}
          <div style={{ perspective: '1200px' }} className="w-full max-w-sm">
            <motion.div
              whileHover={{ rotateY: 5, rotateX: -2, y: -5 }}
              className="bg-white/10 backdrop-blur-md border border-white/15 rounded-[2rem] p-6 shadow-2xl"
            >
               <p className="text-sky-200 text-xs uppercase tracking-[0.2em] mb-4 font-black flex items-center gap-2">
                 <LayoutDashboard className="w-4 h-4" />
                 Fitur Keamanan
               </p>
               <ul className="space-y-4">
                  {[
                    { icon: ShieldCheck, text: 'Enkripsi Data End-to-End', color: 'text-emerald-300' },
                    { icon: QrCode, text: 'Pemulihan Akun Terverifikasi', color: 'text-sky-300' },
                  ].map((item, i) => (
                    <li key={i} className="flex items-center gap-4 text-white/90 text-sm">
                       <div className={`p-2 bg-white/5 rounded-xl border border-white/10 ${item.color}`}>
                         <item.icon className="w-4 h-4" />
                       </div>
                       <span className="font-medium">{item.text}</span>
                    </li>
                  ))}
               </ul>
            </motion.div>
          </div>

          <p className="mt-12 text-sky-300/60 text-[10px] uppercase font-mono tracking-widest text-center">
            Secured Access for Authorized Personnel Only<br/>
            © 2026 SONGKRAN IT DIVISION
          </p>
        </motion.aside>
      </div>
    </div>
  );
}

function LockIcon() {
  return (
    <svg viewBox="0 0 24 24" className="w-8 h-8" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round">
      <rect x="3" y="11" width="18" height="11" rx="2" ry="2" />
      <path d="M7 11V7a5 5 0 0 1 10 0v4" />
      <path d="M12 16v2" />
    </svg>
  );
}
