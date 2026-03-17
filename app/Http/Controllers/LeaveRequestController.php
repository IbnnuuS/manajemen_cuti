<?php

namespace App\Http\Controllers;

use App\Models\LeaveRequest;
use App\Models\LeaveBalance;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class LeaveRequestController extends Controller
{
    /**
     * ==========================================
     * USER ENDPOINTS
     * ==========================================
     */

    public function myRequests(Request $request): JsonResponse
    {
        $requests = LeaveRequest::with(['leaveType', 'responder', 'deleter'])
            ->where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $requests
        ]);
    }

    public function submit(Request $request): JsonResponse
    {
        $request->validate([
            'leave_type_id' => 'required|exists:leave_types,id',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'required|string',
        ]);

        $user = $request->user();
        $start = Carbon::parse($request->start_date);
        $end = Carbon::parse($request->end_date);
        
        // Cek jika cutinya ke masa lalu
        if ($start->isPast() && !$start->isToday()) {
             return response()->json(['success' => false, 'message' => 'Start date cannot be in the past.'], 400);
        }

        // Hitung hari, mengabaikan weekend
        $totalDays = $start->diffInDaysFiltered(function (Carbon $date) {
            return !$date->isWeekend();
        }, $end);

        if ($totalDays == 0) {
            return response()->json([
                'success' => false,
                'message' => 'The selected dates fall entirely on weekends. 0 days to request.'
            ], 400);
        }

        // Cek Balance
        $balance = LeaveBalance::where('user_id', $user->id)
            ->where('leave_type_id', $request->leave_type_id)
            ->where('year', date('Y'))
            ->first();

        if (!$balance) {
            return response()->json([
                'success' => false,
                'message' => 'No leave balance found for this type.'
            ], 400);
        }

        // Cek kuota yang pending
        $pendingDays = LeaveRequest::where('user_id', $user->id)
            ->where('leave_type_id', $request->leave_type_id)
            ->where('status', 'pending')
            ->whereYear('start_date', date('Y'))
            ->sum('total_days');

        $availableBalance = $balance->total_quota - $balance->used - $pendingDays;

        if ($availableBalance < $totalDays) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient leave balance. You are requesting ' . $totalDays . ' days but only have ' . max(0, $availableBalance) . ' days remaining (including pending requests).',
            ], 400);
        }

        $leaveRequest = LeaveRequest::create([
            'user_id' => $user->id,
            'leave_type_id' => $request->leave_type_id,
            'start_date' => $start->format('Y-m-d'),
            'end_date' => $end->format('Y-m-d'),
            'total_days' => $totalDays,
            'reason' => $request->reason,
            'status' => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Leave request submitted successfully.',
            'data' => $leaveRequest->load('leaveType')
        ], 201);
    }

    public function cancel($id, Request $request): JsonResponse
    {
        $leaveRequest = LeaveRequest::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$leaveRequest) {
            return response()->json(['success' => false, 'message' => 'Request not found.'], 404);
        }

        if ($leaveRequest->status !== 'pending') {
            return response()->json(['success' => false, 'message' => 'You can only cancel pending requests.'], 400);
        }

        $leaveRequest->status = 'canceled';
        $leaveRequest->save();

        return response()->json([
            'success' => true,
            'message' => 'Leave request canceled successfully.',
            'data' => $leaveRequest
        ]);
    }

    /**
     * ==========================================
     * ADMIN ENDPOINTS
     * ==========================================
     */

    public function index(Request $request): JsonResponse
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Forbidden. Admins only.'], 403);
        }

        $requests = LeaveRequest::with(['user', 'leaveType', 'responder'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $requests
        ]);
    }

    public function approve($id, Request $request): JsonResponse
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Forbidden. Admins only.'], 403);
        }

        $leaveRequest = LeaveRequest::find($id);

        if (!$leaveRequest) {
            return response()->json(['success' => false, 'message' => 'Request not found.'], 404);
        }

        if ($leaveRequest->status !== 'pending') {
            return response()->json(['success' => false, 'message' => 'Request is already ' . $leaveRequest->status], 400);
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
        $leaveRequest->responded_by = $request->user()->id;
        $leaveRequest->responded_at = now();
        $leaveRequest->admin_notes = $request->notes ?? null;
        $leaveRequest->save();

        return response()->json([
            'success' => true,
            'message' => 'Leave request approved.',
            'data' => $leaveRequest
        ]);
    }

    public function reject($id, Request $request): JsonResponse
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Forbidden. Admins only.'], 403);
        }

        $leaveRequest = LeaveRequest::find($id);

        if (!$leaveRequest) {
            return response()->json(['success' => false, 'message' => 'Request not found.'], 404);
        }

        if ($leaveRequest->status !== 'pending') {
            return response()->json(['success' => false, 'message' => 'Request is already ' . $leaveRequest->status], 400);
        }

        $leaveRequest->status = 'rejected';
        $leaveRequest->responded_by = $request->user()->id;
        $leaveRequest->responded_at = now();
        $leaveRequest->admin_notes = $request->notes ?? null;
        $leaveRequest->save();

        return response()->json([
            'success' => true,
            'message' => 'Leave request rejected.',
            'data' => $leaveRequest
        ]);
    }

    /**
     * ==========================================
     * SHARED ENDPOINT
     * ==========================================
     */

    public function destroy($id, Request $request): JsonResponse
    {
        $user = $request->user();
        $leaveRequest = LeaveRequest::find($id);

        if (!$leaveRequest) {
            return response()->json(['success' => false, 'message' => 'Request not found.'], 404);
        }

        // Validasi otoritas: hanya admin atau pemilik yg bisa hapus.
        if ($user->role !== 'admin' && $leaveRequest->user_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Forbidden. You do not own this request.'], 403);
        }

        // Jika request di-approve dan mau dihapus, sebaiknya kembalikan kuota (opsional, tapi baiknya iya)
        if ($leaveRequest->status === 'approved') {
            $balance = LeaveBalance::where('user_id', $leaveRequest->user_id)
                ->where('leave_type_id', $leaveRequest->leave_type_id)
                ->where('year', date('Y', strtotime($leaveRequest->start_date)))
                ->first();
                
            if ($balance) {
                // Jangan sampai negatif
                $balance->used = max(0, $balance->used - $leaveRequest->total_days);
                $balance->save();
            }
        }

        $leaveRequest->deleted_by = $user->id;
        $leaveRequest->deleted_at = now();
        // Pakai update langsung karena kolom deleted_at tidak ditangani otomatis soft deletes (asumsi belum dipasang traitnya)
        $leaveRequest->save();
        
        // Kita juga bisa tambahkan delete() standar laravel jika ternyata ada SoftDeletes di DB maupun table
        $leaveRequest->delete();

        return response()->json([
            'success' => true,
            'message' => 'Leave request deleted successfully.'
        ]);
    }
}
