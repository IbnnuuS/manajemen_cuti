<?php

namespace App\Services;

use App\Models\LeaveRequest;
use App\Models\LeaveBalance;
use Carbon\Carbon;
use Exception;

class LeaveRequestService
{
    /**
     * Submit a new leave request.
     */
    public function submitRequest($user, array $data): LeaveRequest
    {
        $start = Carbon::parse($data['start_date']);
        $end = Carbon::parse($data['end_date']);
        
        if ($start->isPast() && !$start->isToday()) {
            throw new Exception("Start date cannot be in the past.");
        }

        $totalDays = $start->diffInDaysFiltered(function (Carbon $date) {
            return !$date->isWeekend();
        }, $end);

        if ($totalDays == 0) {
            throw new Exception("The selected dates fall entirely on weekends. 0 days to request.");
        }

        $hasOverlap = LeaveRequest::where('user_id', $user->id)
            ->whereIn('status', ['pending', 'approved'])
            ->where(function ($query) use ($start, $end) {
                $query->whereBetween('start_date', [$start->format('Y-m-d'), $end->format('Y-m-d')])
                      ->orWhereBetween('end_date', [$start->format('Y-m-d'), $end->format('Y-m-d')])
                      ->orWhere(function ($q) use ($start, $end) {
                          $q->where('start_date', '<=', $start->format('Y-m-d'))
                            ->where('end_date', '>=', $end->format('Y-m-d'));
                      });
            })
            ->exists();

        if ($hasOverlap) {
            throw new Exception("The requested dates overlap with an existing pending or approved leave request.");
        }

        $balance = LeaveBalance::where('user_id', $user->id)
            ->where('leave_type_id', $data['leave_type_id'])
            ->where('year', date('Y'))
            ->first();

        if (!$balance) {
            throw new Exception("No leave balance found for this type.");
        }

        $pendingDays = LeaveRequest::where('user_id', $user->id)
            ->where('leave_type_id', $data['leave_type_id'])
            ->where('status', 'pending')
            ->whereYear('start_date', date('Y'))
            ->sum('total_days');

        $availableBalance = $balance->total_quota - $balance->used - $pendingDays;

        if ($availableBalance < $totalDays) {
            throw new Exception("Insufficient leave balance. You are requesting {$totalDays} days but only have " . max(0, $availableBalance) . " days remaining (including pending requests).");
        }

        return LeaveRequest::create([
            'user_id' => $user->id,
            'leave_type_id' => $data['leave_type_id'],
            'start_date' => $start->format('Y-m-d'),
            'end_date' => $end->format('Y-m-d'),
            'total_days' => $totalDays,
            'reason' => $data['reason'],
            'status' => 'pending',
        ]);
    }

    /**
     * Cancel a specific leave request.
     */
    public function cancelRequest(LeaveRequest $leaveRequest): LeaveRequest
    {
        if ($leaveRequest->status !== 'pending') {
            throw new Exception("You can only cancel pending requests.");
        }

        $leaveRequest->status = 'canceled';
        $leaveRequest->save();

        return $leaveRequest;
    }

    /**
     * Approve a specific leave request and deduct quota.
     */
    public function approveRequest(LeaveRequest $leaveRequest, $adminUser, ?string $notes): LeaveRequest
    {
        if ($leaveRequest->status !== 'pending') {
            throw new Exception("Request is already {$leaveRequest->status}");
        }

        // Potong kuota user
        $balance = LeaveBalance::where('user_id', $leaveRequest->user_id)
            ->where('leave_type_id', $leaveRequest->leave_type_id)
            ->where('year', date('Y', strtotime($leaveRequest->start_date)))
            ->first();

        if ($balance) {
            $balance->used += $leaveRequest->total_days;
            $balance->save();
        }

        $leaveRequest->status = 'approved';
        $leaveRequest->responded_by = $adminUser->id;
        $leaveRequest->responded_at = now();
        $leaveRequest->admin_notes = $notes;
        $leaveRequest->save();

        return $leaveRequest;
    }

    /**
     * Reject a specific leave request.
     */
    public function rejectRequest(LeaveRequest $leaveRequest, $adminUser, ?string $notes): LeaveRequest
    {
        if ($leaveRequest->status !== 'pending') {
            throw new Exception("Request is already {$leaveRequest->status}");
        }

        $leaveRequest->status = 'rejected';
        $leaveRequest->responded_by = $adminUser->id;
        $leaveRequest->responded_at = now();
        $leaveRequest->admin_notes = $notes;
        $leaveRequest->save();

        return $leaveRequest;
    }

    /**
     * Delete a leave request. Reverts quota if it was approved.
     */
    public function deleteRequest(LeaveRequest $leaveRequest, $deleterId): void
    {
        if (!in_array($leaveRequest->status, ['approved', 'rejected', 'canceled'])) {
            throw new Exception("You can only delete requests with a final status (approved, rejected, canceled).");
        }

        if ($leaveRequest->status === 'approved') {
            $balance = LeaveBalance::where('user_id', $leaveRequest->user_id)
                ->where('leave_type_id', $leaveRequest->leave_type_id)
                ->where('year', date('Y', strtotime($leaveRequest->start_date)))
                ->first();
                
            if ($balance) {
                $balance->used = max(0, $balance->used - $leaveRequest->total_days);
                $balance->save();
            }
        }

        $leaveRequest->deleted_by = $deleterId;
        $leaveRequest->save();
        $leaveRequest->delete();
    }
}
