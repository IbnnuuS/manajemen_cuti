<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\LeaveBalance;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AdminController extends Controller
{
    /**
     * Get statistics and actionable pending requests for the Admin Dashboard.
     */
    public function getDashboardStats(Request $request): JsonResponse
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Forbidden.'], 403);
        }

        // Aggregate stats
        $stats = [
            'pending' => LeaveRequest::where('status', 'pending')->count(),
            'approved' => LeaveRequest::where('status', 'approved')->count(),
            'rejected' => LeaveRequest::where('status', 'rejected')->count(),
        ];

        // Fetch pending actionable items (Latest 10)
        $actionables = LeaveRequest::with(['user:id,name,email', 'leaveType:id,name'])
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'stats' => $stats,
                'actionables' => $actionables
            ]
        ]);
    }

    /**
     * Get a paginated list of all users.
     */
    public function getUsersPaginated(Request $request): JsonResponse
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['success' => false], 403);
        }

        // Return paginated users (e.g. 10 per page) along with their current year balances
        $users = User::with(['leaveBalances' => function($query) {
             $query->where('year', date('Y'))->with('leaveType');
        }])->orderBy('id', 'desc')->paginate(10);

        return response()->json($users);
    }

    /**
     * Store a newly created employee/user and assign default leave balances.
     */
    public function storeUser(Request $request): JsonResponse
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Forbidden.'], 403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', Password::defaults()],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'user' // default constraint
        ]);

        // Explicit requirements logic: Automatically grant 12 Day Annual & 6 Day Sick for new users.
        $annualLeave = LeaveType::where('name', 'Annual Leave')->first();
        $sickLeave = LeaveType::where('name', 'Sick Leave')->first();
        $currentYear = date('Y');

        if ($annualLeave) {
            LeaveBalance::create([
                'user_id' => $user->id,
                'leave_type_id' => $annualLeave->id,
                'total_quota' => 12,
                'used' => 0,
                'year' => $currentYear
            ]);
        }

        if ($sickLeave) {
            LeaveBalance::create([
                'user_id' => $user->id,
                'leave_type_id' => $sickLeave->id,
                'total_quota' => 6,
                'used' => 0,
                'year' => $currentYear
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'User berhasil ditambahkan dan kuota dipasang.',
            'data' => $user
        ], 201);
    }

    /**
     * Delete a user and their associated leave data.
     */
    public function destroyUser(Request $request, int $id): JsonResponse
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Forbidden.'], 403);
        }

        // Prevent admin from deleting themselves
        if ($request->user()->id === $id) {
            return response()->json(['success' => false, 'message' => 'Tidak dapat menghapus akun Anda sendiri.'], 422);
        }

        $user = User::find($id);

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Pengguna tidak ditemukan.'], 404);
        }

        // Delete associated leave balances and requests first (cascade)
        LeaveBalance::where('user_id', $id)->delete();
        \App\Models\LeaveRequest::where('user_id', $id)->delete();

        $user->tokens()->delete(); // revoke all sanctum tokens
        $user->delete();

        return response()->json([
            'success' => true,
            'message' => "Karyawan {$user->name} berhasil dihapus beserta semua data cutinya."
        ]);
    }
}
