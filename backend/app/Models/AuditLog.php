<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    use HasFactory;

    protected $table = 'audit_log';

    // Audit log tidak punya updated_at
    const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'bank_sampah_id',
        'aksi',
        'detail',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function bankSampah()
    {
        return $this->belongsTo(BankSampah::class, 'bank_sampah_id');
    }
}
