<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LeaveRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'leave_type_id',
        'start_date',
        'end_date',
        'total_days',
        'reason',
        'status',
        'responded_by',
        'admin_notes',
        'responded_at',
        'deleted_by',
        'deleted_at'
    ];

    // 🔹 RELASI

    // user yang submit
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // jenis cuti
    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class);
    }

    // admin yang approve/reject
    public function responder()
    {
        return $this->belongsTo(User::class, 'responded_by');
    }

    // siapa yang delete
    public function deleter()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }
}

