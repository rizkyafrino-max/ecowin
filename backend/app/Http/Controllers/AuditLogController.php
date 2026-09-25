<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $query = AuditLog::with('user');

        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }
        if ($request->has('aksi')) {
            $query->where('aksi', $request->aksi);
        }
        if ($request->has('dari')) {
            $query->whereDate('created_at', '>=', $request->dari);
        }
        if ($request->has('sampai')) {
            $query->whereDate('created_at', '<=', $request->sampai);
        }

        return response()->json($query->latest()->get());
    }
}