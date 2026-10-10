<?php

namespace App\Observers;

use App\Models\User;
use App\Services\Auth\ApiTokenIssuer;
use Illuminate\Support\Facades\DB;

class UserObserver
{
    public function __construct(private ApiTokenIssuer $tokens) {}

    /**
     * Email diganti => ikatan ke akun Google lama dilepas, agar pemilik email baru bisa login.
     */
    public function updating(User $user): void
    {
        if ($user->isDirty('email') && ! $user->isDirty('google_id')) {
            $user->google_id = null;
        }
    }

    /**
     * Akun dinonaktifkan / role diubah => semua sesi & token dicabut, sehingga
     * session lama tidak lagi memberi akses.
     */
    public function updated(User $user): void
    {
        if ($user->wasChanged(['status', 'role', 'bank_sampah_id', 'email'])) {
            $this->tokens->revokeAll($user);
            $user->forceFill(['remember_token' => null])->saveQuietly();

            if (config('session.driver') === 'database') {
                DB::table(config('session.table', 'sessions'))->where('user_id', $user->id)->delete();
            }
        }
    }
}
