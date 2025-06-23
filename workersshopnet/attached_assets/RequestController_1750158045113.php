
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FoodRequest;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class RequestController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function index(Request $request)
    {
        $query = FoodRequest::with(['user', 'approver']);
        
        // If user is admin, show all requests, otherwise show only their own
        if ($request->user()->role !== 'admin') {
            $query->where('user_id', $request->user()->id);
        }

        // Filter by status if provided
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by date range if provided
        if ($request->has('from_date')) {
            $query->where('requested_date', '>=', $request->from_date);
        }

        if ($request->has('to_date')) {
            $query->where('requested_date', '<=', $request->to_date);
        }

        $requests = $query->orderBy('created_at', 'desc')->paginate(15);

        return response()->json($requests);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'item' => 'required|string|max:255',
            'quantity' => 'required|integer|min:1|max:100',
            'requested_date' => 'required|date|after_or_equal:today',
            'notes' => 'nullable|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $validator->errors()
            ], 422);
        }

        // Check if user has pending requests for the same date
        $existingRequest = FoodRequest::where('user_id', $request->user()->id)
            ->where('requested_date', $request->requested_date)
            ->where('status', FoodRequest::STATUS_PENDING)
            ->first();

        if ($existingRequest) {
            return response()->json([
                'error' => 'You already have a pending request for this date'
            ], 409);
        }

        $foodRequest = FoodRequest::create([
            'user_id' => $request->user()->id,
            'item' => $request->item,
            'quantity' => $request->quantity,
            'requested_date' => $request->requested_date,
            'status' => FoodRequest::STATUS_PENDING,
            'notes' => $request->notes
        ]);

        return response()->json([
            'message' => 'Food request submitted successfully',
            'request' => $foodRequest->load('user')
        ], 201);
    }

    public function show($id)
    {
        $foodRequest = FoodRequest::with(['user', 'approver'])->findOrFail($id);
        
        // Users can only view their own requests unless they're admin
        if (auth()->user()->role !== 'admin' && $foodRequest->user_id !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return response()->json($foodRequest);
    }

    public function update(Request $request, $id)
    {
        $foodRequest = FoodRequest::findOrFail($id);
        
        // Users can only update their own pending requests
        if ($foodRequest->user_id !== auth()->id() || !$foodRequest->isPending()) {
            return response()->json(['error' => 'Cannot update this request'], 403);
        }

        $validator = Validator::make($request->all(), [
            'item' => 'sometimes|required|string|max:255',
            'quantity' => 'sometimes|required|integer|min:1|max:100',
            'requested_date' => 'sometimes|required|date|after_or_equal:today',
            'notes' => 'nullable|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $validator->errors()
            ], 422);
        }

        $foodRequest->update($request->only(['item', 'quantity', 'requested_date', 'notes']));

        return response()->json([
            'message' => 'Request updated successfully',
            'request' => $foodRequest->load('user')
        ]);
    }

    public function approve(Request $request, $id)
    {
        // Only admins can approve requests
        if ($request->user()->role !== 'admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $foodRequest = FoodRequest::findOrFail($id);
        
        if (!$foodRequest->isPending()) {
            return response()->json(['error' => 'Request cannot be approved'], 400);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:approved,rejected',
            'notes' => 'nullable|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $validator->errors()
            ], 422);
        }

        $foodRequest->update([
            'status' => $request->status,
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
            'notes' => $request->notes
        ]);

        return response()->json([
            'message' => 'Request ' . $request->status . ' successfully',
            'request' => $foodRequest->load(['user', 'approver'])
        ]);
    }

    public function destroy($id)
    {
        $foodRequest = FoodRequest::findOrFail($id);
        
        // Users can only delete their own pending requests
        if ($foodRequest->user_id !== auth()->id() || !$foodRequest->isPending()) {
            return response()->json(['error' => 'Cannot delete this request'], 403);
        }

        $foodRequest->delete();

        return response()->json(['message' => 'Request deleted successfully']);
    }
}
