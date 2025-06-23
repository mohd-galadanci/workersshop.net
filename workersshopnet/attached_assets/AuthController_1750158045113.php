
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'ippis_no' => 'required|string|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'department' => 'required|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $validator->errors()
            ], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'ippis_no' => $request->ippis_no,
            'password' => Hash::make($request->password),
            'department' => $request->department,
            'role' => 'user'
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'User registered successfully',
            'user' => $user,
            'token' => $token
        ], 201);
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ippis_no' => 'required|string',
            'password' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $validator->errors()
            ], 422);
        }

        $credentials = $request->only('ippis_no', 'password');
        
        if (Auth::attempt($credentials)) {
            $user = Auth::user();
            $token = $user->createToken('auth_token')->plainTextToken;
            
            return response()->json([
                'message' => 'Login successful',
                'user' => $user,
                'token' => $token
            ]);
        }

        return response()->json([
            'error' => 'Invalid credentials'
        ], 401);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        
        return response()->json([
            'message' => 'Logged out successfully'
        ]);
    }

    public function faceAuth(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'photo' => 'required|image|mimes:jpeg,png,jpg|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Invalid image file',
                'messages' => $validator->errors()
            ], 422);
        }

        try {
            $image = $request->file('photo')->getPathname();
            $client = new Client(['timeout' => 30]);
            
            $response = $client->post('http://face:5000/verify', [
                'multipart' => [
                    [
                        'name' => 'image',
                        'contents' => fopen($image, 'r')
                    ]
                ]
            ]);

            $data = json_decode($response->getBody(), true);
            
            if (isset($data['user_id']) && $data['verified']) {
                $user = User::find($data['user_id']);
                if ($user) {
                    $token = $user->createToken('face_auth_token')->plainTextToken;
                    return response()->json([
                        'message' => 'Face authentication successful',
                        'user' => $user,
                        'token' => $token
                    ]);
                }
            }

            return response()->json([
                'error' => 'Face authentication failed'
            ], 401);

        } catch (RequestException $e) {
            return response()->json([
                'error' => 'Face recognition service unavailable',
                'message' => $e->getMessage()
            ], 503);
        }
    }
}
