import { BrowserRouter, Navigate, Route, Routes } from 'react-router-dom';
import { LoadingState } from './components/States';
import { AuthProvider, useAuth } from './context/AuthContext';
import AppLayout from './layouts/AppLayout';
import Dashboard from './pages/Dashboard';
import Fitur from './pages/Fitur';
import AuthCallback from './pages/AuthCallback';
import Masuk from './pages/Masuk';
import Organik from './pages/Organik';
import Penarikan from './pages/Penarikan';
import Profil from './pages/Profil';
import Saldo from './pages/Saldo';
import Transaksi from './pages/Transaksi';

/** Halaman di balik login: sesi valid langsung masuk, selain itu kembali ke Login Google. */
function Protected() {
  const { status } = useAuth();
  if (status === 'loading') return <div className="mx-auto max-w-md p-8"><LoadingState rows={3} /></div>;
  if (status !== 'authed') return <Navigate to="/masuk" replace />;
  return <AppLayout />;
}

export default function App() {
  return (
    <BrowserRouter>
      <AuthProvider>
        <Routes>
          <Route path="/masuk" element={<Masuk />} />
          <Route path="/daftar" element={<Masuk daftar />} />
          <Route path="/auth/callback" element={<AuthCallback />} />
          <Route element={<Protected />}>
            <Route index element={<Dashboard />} />
            <Route path="transaksi" element={<Transaksi />} />
            <Route path="organik" element={<Organik />} />
            <Route path="biopori" element={<Navigate to="/organik" replace />} />
            <Route path="fitur" element={<Fitur />} />
            <Route path="saldo" element={<Saldo />} />
            <Route path="penarikan" element={<Penarikan />} />
            <Route path="profil" element={<Profil />} />
          </Route>
          <Route path="*" element={<Navigate to="/" replace />} />
        </Routes>
      </AuthProvider>
    </BrowserRouter>
  );
}
