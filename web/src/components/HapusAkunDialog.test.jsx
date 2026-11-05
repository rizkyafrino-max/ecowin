import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { EcoApi } from '../api/endpoints';
import { ApiError } from '../api/client';
import HapusAkunDialog from './HapusAkunDialog';

vi.mock('../api/endpoints', () => ({ EcoApi: { hapusAkun: vi.fn() } }));

const tombolHapus = () => screen.getByRole('button', { name: 'Hapus akun saya' });
const isi = (nilai) => fireEvent.change(screen.getByLabelText(/Ketik HAPUS/), { target: { value: nilai } });

describe('HapusAkunDialog', () => {
  beforeEach(() => vi.clearAllMocks());

  it('tombol hapus baru aktif setelah mengetik HAPUS persis (huruf besar)', () => {
    render(<HapusAkunDialog open onClose={() => {}} onDeleted={() => {}} />);
    expect(tombolHapus()).toBeDisabled();
    isi('hapus');
    expect(tombolHapus()).toBeDisabled();
    isi('HAPUS');
    expect(tombolHapus()).toBeEnabled();
  });

  it('memanggil API lalu keluar setelah berhasil', async () => {
    EcoApi.hapusAkun.mockResolvedValue({ message: 'Akunmu sudah dihapus.' });
    const onDeleted = vi.fn();
    render(<HapusAkunDialog open onClose={() => {}} onDeleted={onDeleted} />);
    isi('HAPUS');
    fireEvent.click(tombolHapus());
    await waitFor(() => expect(onDeleted).toHaveBeenCalledTimes(1));
    expect(EcoApi.hapusAkun).toHaveBeenCalledWith('HAPUS');
  });

  it('menampilkan alasan dari server (mis. saldo belum nol) dan tidak keluar', async () => {
    EcoApi.hapusAkun.mockRejectedValue(new ApiError('x', 422, { akun: ['Saldomu masih Rp 12.000. Ajukan penarikan sampai saldo Rp 0, lalu hapus akun.'] }));
    const onDeleted = vi.fn();
    render(<HapusAkunDialog open onClose={() => {}} onDeleted={onDeleted} />);
    isi('HAPUS');
    fireEvent.click(tombolHapus());
    expect(await screen.findByText(/Saldomu masih Rp 12\.000/)).toBeInTheDocument();
    expect(onDeleted).not.toHaveBeenCalled();
    expect(tombolHapus()).toBeEnabled(); // bisa dicoba lagi setelah saldo ditarik
  });

  it('Batal menutup dialog tanpa memanggil API', () => {
    const onClose = vi.fn();
    render(<HapusAkunDialog open onClose={onClose} onDeleted={() => {}} />);
    fireEvent.click(screen.getByRole('button', { name: 'Batal' }));
    expect(onClose).toHaveBeenCalled();
    expect(EcoApi.hapusAkun).not.toHaveBeenCalled();
  });
});
