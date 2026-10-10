<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\DecisionRequest;
use App\Http\Requests\StorePenarikanRequest;
use App\Http\Resources\PenarikanSaldoResource;
use App\Models\Nasabah;
use App\Models\PenarikanSaldo;
use App\Services\PenarikanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PenarikanSaldoController extends Controller
{
    public function __construct(private PenarikanService $service) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $request->validate(['status' => ['nullable', 'in:pending,approved,rejected,completed']]);

        return PenarikanSaldoResource::collection(
            PenarikanSaldo::query()
                ->visibleTo($request->user())
                ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
                ->latest('id')
                ->paginate(25)
        );
    }

    public function show(PenarikanSaldo $penarikan): PenarikanSaldoResource
    {
        $this->authorize('view', $penarikan);

        return new PenarikanSaldoResource($penarikan);
    }

    public function store(StorePenarikanRequest $request): JsonResponse
    {
        $user = $request->user();
        $nasabah = $user->isNasabah()
            ? $user->nasabah
            : Nasabah::query()->findOrFail($request->integer('nasabah_id'));

        abort_unless($nasabah instanceof Nasabah, 404);

        $penarikan = $this->service->ajukan($user, $nasabah, $request->integer('jumlah'), $request->input('catatan'));

        return (new PenarikanSaldoResource($penarikan))->response()->setStatusCode(201);
    }

    public function approve(DecisionRequest $request, PenarikanSaldo $penarikan): PenarikanSaldoResource
    {
        $this->authorize('process', $penarikan);

        return new PenarikanSaldoResource($this->service->setujui($request->user(), $penarikan, $request->input('catatan')));
    }

    public function reject(DecisionRequest $request, PenarikanSaldo $penarikan): PenarikanSaldoResource
    {
        $this->authorize('process', $penarikan);

        return new PenarikanSaldoResource($this->service->tolak($request->user(), $penarikan, (string) $request->input('catatan')));
    }

    public function complete(Request $request, PenarikanSaldo $penarikan): PenarikanSaldoResource
    {
        $this->authorize('process', $penarikan);

        return new PenarikanSaldoResource($this->service->selesaikan($request->user(), $penarikan));
    }
}
