<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FoodRequest;
use App\Models\User;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function stats(Request $request)
    {
        $user = $request->user();
        $isAdmin = $user->role === 'admin';

        if ($isAdmin) {
            // Admin stats - all requests
            $totalRequests = FoodRequest::count();
            $pendingRequests = FoodRequest::where('status', FoodRequest::STATUS_PENDING)->count();
            $approvedRequests = FoodRequest::where('status', FoodRequest::STATUS_APPROVED)->count();
            $rejectedRequests = FoodRequest::where('status', FoodRequest::STATUS_REJECTED)->count();
            $totalUsers = User::where('role', 'user')->count();

            // Monthly breakdown
            $monthlyStats = FoodRequest::selectRaw('
                MONTH(created_at) as month,
                YEAR(created_at) as year,
                COUNT(*) as total,
                SUM(CASE WHEN status = "approved" THEN 1 ELSE 0 END) as approved,
                SUM(CASE WHEN status = "rejected" THEN 1 ELSE 0 END) as rejected,
                SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pending
            ')
            ->where('created_at', '>=', Carbon::now()->subMonths(6))
            ->groupBy('year', 'month')
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->get();

        } else {
            // User stats - only their requests
            $totalRequests = FoodRequest::where('user_id', $user->id)->count();
            $pendingRequests = FoodRequest::where('user_id', $user->id)
                ->where('status', FoodRequest::STATUS_PENDING)->count();
            $approvedRequests = FoodRequest::where('user_id', $user->id)
                ->where('status', FoodRequest::STATUS_APPROVED)->count();
            $rejectedRequests = FoodRequest::where('user_id', $user->id)
                ->where('status', FoodRequest::STATUS_REJECTED)->count();
            $totalUsers = null;
            $monthlyStats = null;
        }

        return response()->json([
            'total_requests' => $totalRequests,
            'pending_requests' => $pendingRequests,
            'approved_requests' => $approvedRequests,
            'rejected_requests' => $rejectedRequests,
            'total_users' => $totalUsers,
            'monthly_stats' => $monthlyStats
        ]);
    }

    public function recentActivity(Request $request)
    {
        $user = $request->user();
        $isAdmin = $user->role === 'admin';

        $query = FoodRequest::with(['user', 'approver']);

        if (!$isAdmin) {
            $query->where('user_id', $user->id);
        }

        $recentRequests = $query->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return response()->json($recentRequests);
    }
}