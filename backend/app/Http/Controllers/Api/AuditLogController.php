<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', AuditLog::class);

        $filters = $request->validate([
            'user_id' => ['nullable', 'integer'],
            'aksi' => ['nullable', 'string', 'max:100'],
            'dari' => ['nullable', 'date'],
            'sampai' => ['nullable', 'date'],
        ]);

        return response()->json(
            AuditLog::query()
                ->with('user:id,nama,email,role')
                ->when($filters['user_id'] ?? null, fn ($q, $v) => $q->where('user_id', $v))
                ->when($filters['aksi'] ?? null, fn ($q, $v) => $q->where('aksi', $v))
                ->when($filters['dari'] ?? null, fn ($q, $v) => $q->whereDate('created_at', '>=', $v))
                ->when($filters['sampai'] ?? null, fn ($q, $v) => $q->whereDate('created_at', '<=', $v))
                ->latest('id')
                ->paginate(50)
        );
    }
}
