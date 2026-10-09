<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreNasabahRequest;
use App\Http\Resources\MutasiSaldoResource;
use App\Http\Resources\NasabahResource;
use App\Models\Nasabah;
use App\Models\PenarikanSaldo;
use App\Services\NasabahService;
use Endroid\QrCode\Builder\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class NasabahController extends Controller
{
    /**
     * Daftar nasabah (Admin: semua, Petugas: Bank Sampah sendiri).
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Nasabah::class);

        $search = trim((string) $request->query('search'));

        $nasabah = Nasabah::query()
            ->visibleTo($request->user())
            ->with('bankSampah')
            ->when($search !== '', fn ($q) => $q->where(fn ($q) => $q
                ->where('nama', 'like', '%'.addcslashes($search, '%_\\').'%')
                ->orWhere('no_hp', 'like', '%'.addcslashes($search, '%_\\').'%')
                ->orWhere('nomor_nasabah', $search)))
            ->latest()
            ->paginate(min(100, max(1, $request->integer('per_page', 25))));

        return NasabahResource::collection($nasabah);
    }

    public function store(StoreNasabahRequest $request, NasabahService $service): JsonResponse
    {
        $nasabah = $service->daftarkan($request->user(), $request->validated());

        return (new NasabahResource($nasabah->load('bankSampah')))->response()->setStatusCode(201);
    }

    public function show(Request $request, Nasabah $nasabah): NasabahResource
    {
        $this->authorize('view', $nasabah);

        return new NasabahResource($nasabah->load('bankSampah'));
    }

    /**
     * Petugas scan QR Card -> cari nasabah. Hanya Bank Sampah miliknya (selain itu 404).
     */
    public function scan(Request $request, string $token): NasabahResource
    {
        abort_unless(preg_match('/^[A-Za-z0-9]{32,64}$/', $token) === 1, 404);

        $nasabah = Nasabah::query()->visibleTo($request->user())->where('kartu_qr_token', $token)->firstOrFail();

        return new NasabahResource($nasabah->load('bankSampah'));
    }

    /**
     * Ringkasan saldo nasabah yang login.
     */
    public function saldo(Request $request): JsonResponse
    {
        $nasabah = $this->nasabahSaya($request);

        return response()->json([
            'saldo' => (float) $nasabah->saldo,
            'saldo_ditahan' => $nasabah->saldoDitahan(),
            'saldo_tersedia' => $nasabah->saldoTersedia(),
            'total_pemasukan' => (float) $nasabah->mutasiSaldo()->where('tipe', 'kredit')->sum('jumlah'),
            'total_penarikan' => (float) $nasabah->penarikanSaldo()->whereIn('status', [PenarikanSaldo::STATUS_APPROVED, PenarikanSaldo::STATUS_COMPLETED])->sum('jumlah'),
        ]);
    }

    public function mutasi(Request $request): AnonymousResourceCollection
    {
        return MutasiSaldoResource::collection(
            $this->nasabahSaya($request)->mutasiSaldo()->latest('id')->paginate(25)
        );
    }

    /**
     * QR Card milik nasabah. Isi QR hanya token acak (tanpa data pribadi).
     */
    public function qr(Request $request): JsonResponse
    {
        $nasabah = $this->nasabahSaya($request);
        $qr = (new Builder(data: $nasabah->kartu_qr_token, size: 300, margin: 12))->build();

        return response()->json([
            'nomor_nasabah' => $nasabah->nomor_nasabah,
            'nama' => $nasabah->nama,
            'qr_value' => $nasabah->kartu_qr_token,
            'qr_png_base64' => base64_encode($qr->getString()),
        ]);
    }

    private function nasabahSaya(Request $request): Nasabah
    {
        $nasabah = $request->user()->nasabah;
        abort_unless($nasabah instanceof Nasabah, 404);

        return $nasabah;
    }
}
