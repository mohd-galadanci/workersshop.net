
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FoodRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function generateReport(Request $request)
    {
        // Only admins can generate reports
        if ($request->user()->role !== 'admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $fromDate = $request->get('from_date', Carbon::now()->subMonth()->format('Y-m-d'));
        $toDate = $request->get('to_date', Carbon::now()->format('Y-m-d'));
        $department = $request->get('department');
        $status = $request->get('status');

        $query = FoodRequest::with(['user', 'approver'])
            ->whereBetween('requested_date', [$fromDate, $toDate]);

        if ($department) {
            $query->whereHas('user', function ($q) use ($department) {
                $q->where('department', $department);
            });
        }

        if ($status) {
            $query->where('status', $status);
        }

        $requests = $query->orderBy('requested_date', 'desc')->get();

        // Generate summary statistics
        $summary = [
            'total_requests' => $requests->count(),
            'approved_requests' => $requests->where('status', FoodRequest::STATUS_APPROVED)->count(),
            'rejected_requests' => $requests->where('status', FoodRequest::STATUS_REJECTED)->count(),
            'pending_requests' => $requests->where('status', FoodRequest::STATUS_PENDING)->count(),
            'departments' => $requests->groupBy('user.department')->map->count(),
            'date_range' => ['from' => $fromDate, 'to' => $toDate]
        ];

        return response()->json([
            'summary' => $summary,
            'requests' => $requests
        ]);
    }

    public function exportReport(Request $request)
    {
        // Only admins can export reports
        if ($request->user()->role !== 'admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $fromDate = $request->get('from_date', Carbon::now()->subMonth()->format('Y-m-d'));
        $toDate = $request->get('to_date', Carbon::now()->format('Y-m-d'));

        $requests = FoodRequest::with(['user', 'approver'])
            ->whereBetween('requested_date', [$fromDate, $toDate])
            ->orderBy('requested_date', 'desc')
            ->get();

        $csvData = [];
        $csvData[] = ['Date', 'Employee Name', 'IPPIS No', 'Department', 'Item', 'Quantity', 'Status', 'Approved By', 'Notes'];

        foreach ($requests as $request) {
            $csvData[] = [
                $request->requested_date->format('Y-m-d'),
                $request->user->name,
                $request->user->ippis_no,
                $request->user->department,
                $request->item,
                $request->quantity,
                ucfirst($request->status),
                $request->approver ? $request->approver->name : '-',
                $request->notes ?: '-'
            ];
        }

        $filename = 'food_requests_' . $fromDate . '_to_' . $toDate . '.csv';
        
        return response()->json([
            'filename' => $filename,
            'data' => $csvData
        ]);
    }
}
