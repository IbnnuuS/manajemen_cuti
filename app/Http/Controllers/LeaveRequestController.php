<?php

namespace App\Http\Controllers;

use App\Models\LeaveRequest;
use App\Models\LeaveBalance;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Services\LeaveRequestService;

class LeaveRequestController extends Controller
{
    protected $leaveRequestService;

    public function __construct(LeaveRequestService $leaveRequestService)
    {
        $this->leaveRequestService = $leaveRequestService;
    }
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

        try {
            $leaveRequest = $this->leaveRequestService->submitRequest($request->user(), $request->all());

            return response()->json([
                'success' => true,
                'message' => 'Leave request submitted successfully.',
                'data' => $leaveRequest->load('leaveType')
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function cancel($id, Request $request): JsonResponse
    {
        $leaveRequest = LeaveRequest::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$leaveRequest) {
            return response()->json(['success' => false, 'message' => 'Request not found.'], 404);
        }

        try {
            $canceledRequest = $this->leaveRequestService->cancelRequest($leaveRequest);

            return response()->json([
                'success' => true,
                'message' => 'Leave request canceled successfully.',
                'data' => $canceledRequest
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
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

        try {
            $approvedRequest = $this->leaveRequestService->approveRequest($leaveRequest, $request->user(), $request->notes);

            return response()->json([
                'success' => true,
                'message' => 'Leave request approved.',
                'data' => $approvedRequest
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
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

        try {
            $rejectedRequest = $this->leaveRequestService->rejectRequest($leaveRequest, $request->user(), $request->notes);

            return response()->json([
                'success' => true,
                'message' => 'Leave request rejected.',
                'data' => $rejectedRequest
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
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

        try {
            $this->leaveRequestService->deleteRequest($leaveRequest, $user->id);

            return response()->json([
                'success' => true,
                'message' => 'Leave request deleted successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
}
