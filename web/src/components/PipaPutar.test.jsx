import { act, render, screen } from '@testing-library/react';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import PipaPutar from './PipaPutar';

const posisi = () => screen.getByRole('img', { name: /pipa BioporiPrint/i }).style.backgroundPosition;

beforeEach(() => vi.useFakeTimers());
afterEach(() => { vi.useRealTimers(); vi.restoreAllMocks(); });

describe('PipaPutar', () => {
  it('menggeser frame sprite tiap ~83 ms dan berulang setelah 36 frame', () => {
    window.matchMedia = vi.fn().mockReturnValue({ matches: false });
    render(<PipaPutar />);
    const awal = posisi();

    act(() => { vi.advanceTimersByTime(84); });
    expect(posisi()).not.toBe(awal);

    act(() => { vi.advanceTimersByTime(84 * 35); }); // total 36 frame = satu putaran penuh
    expect(posisi()).toBe(awal);
  });

  it('diam pada frame pertama bila pengguna memilih kurangi gerakan', () => {
    window.matchMedia = vi.fn().mockReturnValue({ matches: true });
    render(<PipaPutar />);
    const awal = posisi();

    act(() => { vi.advanceTimersByTime(2000); });
    expect(posisi()).toBe(awal);
  });

  it('berhenti saat dilepas (popup ditutup) dan memakai ukuran sprite 6x6', () => {
    window.matchMedia = vi.fn().mockReturnValue({ matches: false });
    const clear = vi.spyOn(globalThis, 'clearInterval');
    const { unmount } = render(<PipaPutar ukuran={96} />);
    const el = screen.getByRole('img');
    expect(el.style.backgroundSize).toBe('600% 600%');
    expect(el.style.width).toBe('96px');

    unmount();
    expect(clear).toHaveBeenCalled();
  });
});
