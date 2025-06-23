
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DiscordController extends Controller
{
    public function sendNotification($message, $embed = null)
    {
        $webhookUrl = env('DISCORD_WEBHOOK_URL');
        
        if (!$webhookUrl) {
            Log::warning('Discord webhook URL not configured');
            return false;
        }

        $payload = [
            'content' => $message,
            'username' => 'Food Request System',
            'avatar_url' => 'https://via.placeholder.com/64x64/4CAF50/FFFFFF?text=🍽️'
        ];

        if ($embed) {
            $payload['embeds'] = [$embed];
        }

        try {
            $response = Http::post($webhookUrl, $payload);
            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Failed to send Discord notification: ' . $e->getMessage());
            return false;
        }
    }

    public function notifyNewRequest($foodRequest)
    {
        $embed = [
            'title' => '🍽️ New Food Request',
            'color' => 3447003, // Blue
            'fields' => [
                [
                    'name' => 'Item',
                    'value' => $foodRequest->item_name,
                    'inline' => true
                ],
                [
                    'name' => 'Requested by',
                    'value' => $foodRequest->user->name,
                    'inline' => true
                ],
                [
                    'name' => 'Status',
                    'value' => 'Pending Review',
                    'inline' => true
                ]
            ],
            'timestamp' => $foodRequest->created_at->toISOString()
        ];

        return $this->sendNotification('📋 A new food request has been submitted!', $embed);
    }

    public function notifyRequestApproved($foodRequest)
    {
        $embed = [
            'title' => '✅ Food Request Approved',
            'color' => 65280, // Green
            'fields' => [
                [
                    'name' => 'Item',
                    'value' => $foodRequest->item_name,
                    'inline' => true
                ],
                [
                    'name' => 'User',
                    'value' => $foodRequest->user->name,
                    'inline' => true
                ]
            ],
            'timestamp' => now()->toISOString()
        ];

        return $this->sendNotification('🎉 A food request has been approved!', $embed);
    }
}
