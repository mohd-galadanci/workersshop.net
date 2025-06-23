
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class ChatbotController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function chat(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'message' => 'required|string|max:1000'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Invalid message',
                'messages' => $validator->errors()
            ], 422);
        }

        $userMessage = $request->input('message');
        $user = $request->user();

        // Generate context-aware response based on the food request system
        $response = $this->generateResponse($userMessage, $user);

        return response()->json([
            'message' => $response,
            'timestamp' => now()->toISOString()
        ]);
    }

    private function generateResponse($message, $user)
    {
        $message = strtolower($message);

        // FAQ responses for the food request system
        if (str_contains($message, 'help') || str_contains($message, 'how')) {
            return "I can help you with workersshop! You can:\n• Submit new food requests\n• Check your request status\n• View approved requests\n• Contact admin for issues\n\nWhat would you like to know?";
        }

        if (str_contains($message, 'request') && str_contains($message, 'food')) {
            return "To submit a food request:\n1. Go to the requests section\n2. Fill in the required details (item, quantity, date)\n3. Submit for approval\n\nYour requests will be reviewed by admin within 24 hours.";
        }

        if (str_contains($message, 'status')) {
            return "You can check your request status in the dashboard. Requests can be:\n• Pending - Waiting for approval\n• Approved - Ready for collection\n• Rejected - Contact admin for details";
        }

        if (str_contains($message, 'admin') || str_contains($message, 'contact')) {
            return "For admin assistance, please:\n• Use the contact form in the system\n• Email the admin directly\n• Check the help section for common issues";
        }

        if (str_contains($message, 'face') && str_contains($message, 'auth')) {
            return "Face authentication allows quick login:\n1. Take a clear photo with good lighting\n2. Ensure your face is centered\n3. The system will verify and log you in automatically";
        }

        if (str_contains($message, 'report')) {
            if ($user->role === 'admin') {
                return "As an admin, you can:\n• Generate reports by date range\n• Filter by department or status\n• Export data as CSV\n• View detailed analytics";
            } else {
                return "Reports are available to admin users only. You can view your personal request history in your dashboard.";
            }
        }

        if (str_contains($message, 'hello') || str_contains($message, 'hi')) {
            return "Hello " . $user->name . "! 👋 Welcome to the Food Request System. How can I assist you today?";
        }

        // Default response
        return "I'm here to help with the Food Request System. You can ask me about:\n• Submitting requests\n• Checking status\n• System features\n• Getting help\n\nWhat would you like to know?";
    }

    public function getHistory(Request $request)
    {
        // In a real implementation, you'd store chat history in the database
        // For now, return a simple response
        return response()->json([
            'history' => [],
            'message' => 'Chat history feature coming soon!'
        ]);
    }
}
