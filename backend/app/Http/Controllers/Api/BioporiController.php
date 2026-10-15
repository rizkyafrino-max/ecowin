<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\DecisionRequest;
use App\Http\Requests\StoreAktivitasBioporiRequest;
use App\Http\Requests\StoreTitikBioporiRequest;
use App\Http\Resources\AktivitasBioporiResource;
use App\Http\Resources\TitikBioporiResource;
use App\Models\AktivitasBiopori;
use App\Models\TitikBiopori;
use App\Services\BioporiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Organik -> Aktivitas Biopori -> BioporiPrint.
 */
class BioporiController extends Controller
{
    public function __construct(private BioporiService $service) {}

    /**
     * Lokasi Biopori aktif di Bank Sampah pengguna.
     */
    public function titik(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();

        // Titik resmi (disetujui) di Bank Sampah pengguna. Nasabah juga melihat pengajuannya sendiri
        // yang masih menunggu / ditolak; pengajuan nasabah lain tidak pernah terlihat.
        $titik = TitikBiopori::query()
            ->when(! $user->isAdmin(), fn ($q) => $q->where('bank_sampah_id', ($user->isNasabah() ? $user->nasabah?->bank_sampah_id : $user->bank_sampah_id) ?? 0))
            ->where('status', 'aktif')
            ->where(function ($q) use ($user): void {
                $q->where('verifikasi', TitikBiopori::VERIFIKASI_APPROVED);
                if ($user->isNasabah()) {
                    $q->orWhere('diajukan_oleh', $user->id);
                } else {
                    $q->orWhere('verifikasi', TitikBiopori::VERIFIKASI_PENDING);
                }
            })
            ->orderBy('nama_lokasi')
            ->get();

        return TitikBioporiResource::collection($titik);
    }

    /**
     * Nasabah mengajukan titik Biopori baru (menunggu verifikasi petugas).
     */
    public function ajukanTitik(StoreTitikBioporiRequest $request): JsonResponse
    {
        $titik = $this->service->ajukanTitik($request->user(), $request->validated());

        return (new TitikBioporiResource($titik))->response()->setStatusCode(201);
    }

    public function approveTitik(DecisionRequest $request, TitikBiopori $titik): TitikBioporiResource
    {
        $this->authorize('verify', $titik);

        return new TitikBioporiResource($this->service->setujuiTitik($request->user(), $titik, $request->input('catatan')));
    }

    public function rejectTitik(DecisionRequest $request, TitikBiopori $titik): TitikBioporiResource
    {
        $this->authorize('verify', $titik);

        return new TitikBioporiResource($this->service->tolakTitik($request->user(), $titik, (string) $request->input('catatan')));
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $request->validate(['status' => ['nullable', 'in:pending,approved,rejected']]);

        return AktivitasBioporiResource::collection(
            AktivitasBiopori::query()
                ->visibleTo($request->user())
                ->with('titikBiopori')
                ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
                ->latest('id')
                ->paginate(25)
        );
    }

    public function show(AktivitasBiopori $aktivitas): AktivitasBioporiResource
    {
        $this->authorize('view', $aktivitas);

        return new AktivitasBioporiResource($aktivitas->load('titikBiopori'));
    }

    public function store(StoreAktivitasBioporiRequest $request): JsonResponse
    {
        $aktivitas = $this->service->lapor($request->user(), $request->safe()->except('foto_bukti'), $request->file('foto_bukti'));

        return (new AktivitasBioporiResource($aktivitas->load('titikBiopori')))->response()->setStatusCode(201);
    }

    /**
     * Foto bukti disajikan lewat endpoint terotorisasi (disk privat), bukan URL publik.
     */
    public function foto(AktivitasBiopori $aktivitas): StreamedResponse
    {
        $this->authorize('view', $aktivitas);

        $disk = Storage::disk(config('ecowin.upload.disk'));
        abort_unless($aktivitas->foto_bukti_path && $disk->exists($aktivitas->foto_bukti_path), 404);

        return $disk->response($aktivitas->foto_bukti_path, 'bukti-biopori-'.$aktivitas->id.'.jpg', [
            'Content-Type' => 'image/jpeg',
            'Cache-Control' => 'private, max-age=300',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function approve(DecisionRequest $request, AktivitasBiopori $aktivitas): AktivitasBioporiResource
    {
        $this->authorize('verify', $aktivitas);

        return new AktivitasBioporiResource($this->service->setujui($request->user(), $aktivitas, $request->input('catatan'))->load('titikBiopori'));
    }

    public function reject(DecisionRequest $request, AktivitasBiopori $aktivitas): AktivitasBioporiResource
    {
        $this->authorize('verify', $aktivitas);

        return new AktivitasBioporiResource($this->service->tolak($request->user(), $aktivitas, (string) $request->input('catatan'))->load('titikBiopori'));
    }
}
