<?php

namespace App\Http\Controllers;

use App\Models\LeaveType;
use App\Models\LeaveBalance;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class LeaveInfoController extends Controller
{
    /**
     * Get all leave types.
     */
    public function getLeaveTypes(): JsonResponse
    {
        $types = LeaveType::all();
        
        return response()->json([
            'success' => true,
            'data' => $types
        ]);
    }

    /**
     * Get the authenticated user's leave balances.
     */
    public function getMyBalances(Request $request): JsonResponse
    {
        $balances = LeaveBalance::with('leaveType')
            ->where('user_id', $request->user()->id)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $balances
        ]);
    }

    /**
     * Get all users with their leave balances for Admin view.
     */
    public function getAdminUsers(Request $request): JsonResponse
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Forbidden. Admins only.'], 403);
        }

        $users = \App\Models\User::with(['balances.leaveType'])->get();

        return response()->json([
            'success' => true,
            'data' => $users
        ]);
    }
}
