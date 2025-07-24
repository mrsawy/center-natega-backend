<?php

namespace App\Http\Controllers;

use App\Models\User;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    //


    public function login(Request $request)
    {
        // Validate input
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        // Find user by email
        $user = User::where('email', $validated['email'])->first();

        // Check user and password
        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            // Throw validation exception with a custom message
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Create token
        $token = $user->createToken('auth_token', ["admin"])->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user,
        ]);
    }

    public function createUser(Request $request)
    {
        try {
            // Validate input
            $validated = $request->validate([
                "name" => ["required", "string", "max:255"],
                "email" => ["required", "string", "email", "max:255", "unique:users,email"],
                "password" => ["required", "string", "min:8", "confirmed"],
                "password_confirmation" => ["required", "string", "min:8"],
                "role" => ["required", "string", "in:admin,user"],
            ]);

            // Create user
            $user = User::create([
                "name" => $validated["name"],
                "email" => $validated["email"],
                "password" => bcrypt($validated["password"]),
                "role" => $validated["role"],
            ]);

            return response()->json([
                "message" => "User created successfully",
                "user" => $user,
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Return validation errors with 422 status
            return response()->json([
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            // Log error for debugging (optional)
            Log::error('User creation failed: ' . $e->getMessage());

            return response()->json([
                'message' => 'An error occurred while creating the user.',
            ], 500);
        }
    }
}
