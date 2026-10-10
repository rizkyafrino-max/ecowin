<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class ProfileController extends Controller
{
    public function show(Request $request): UserResource
    {
        return new UserResource($request->user()->load('nasabah.bankSampah'));
    }

    public function update(UpdateProfileRequest $request, AuditLogger $audit): UserResource
    {
        $user = $request->user();
        $data = $request->validated();

        DB::transaction(function () use ($user, $data, $audit): void {
            $before = $user->only('nama');

            if (isset($data['nama'])) {
                $user->forceFill(['nama' => $data['nama']])->save();
            }

            if ($user->nasabah) {
                $before += $user->nasabah->only(['no_hp', 'alamat_rt_rw']);
                $user->nasabah->fill(Arr::only($data, ['nama', 'no_hp', 'alamat_rt_rw']))->save();
            }

            $audit->log('ubah_profil', $user, $before, $data, $user);
        });

        return new UserResource($user->fresh()->load('nasabah.bankSampah'));
    }
}
