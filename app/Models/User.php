<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    // user → leave_balances
    public function leaveBalances()
    {
        return $this->hasMany(LeaveBalance::class);
    }

    // user → leave_requests (submit)
    public function leaveRequests()
    {
        return $this->hasMany(LeaveRequest::class, 'user_id');
    }

    // user → leave_requests (responded_by)
    public function respondedRequests()
    {
        return $this->hasMany(LeaveRequest::class, 'responded_by');
    }

    // user → leave_requests (deleted_by)
    public function deletedRequests()
    {
        return $this->hasMany(LeaveRequest::class, 'deleted_by');
    }
}
