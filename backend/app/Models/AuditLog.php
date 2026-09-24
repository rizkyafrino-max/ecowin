<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    use HasFactory;

    protected $table = 'audit_log';

    protected $fillable = [
        'user_id',
        'aksi',
        'detail',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}