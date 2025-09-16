<?php

namespace App\Http\Controllers\API\Auth;

use App\Http\Controllers\Controller;
use App\Helpers\ApiResponse;
use App\Models\Acl\LoginHistory;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return ApiResponse::validationError($validator->errors(), 'Validation error');
        }

        $user = User::where('username', $request->username)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return ApiResponse::error('Invalid credentials', 401, [
                'success' => false,
                'message' => 'Invalid credentials'
            ]);
        }

        // if ($user->status != 1) {
        //     return ApiResponse::error('Account is inactive', 403, [
        //         'success' => false,
        //         'message' => 'Account is inactive'
        //     ]);
        // }

        LoginHistory::create([
            'header' => $request->header('User-Agent'),
            'location' => $request->ip(),
            'user_id' => $user->id,
        ]);

        $token = $user->createToken('api-m&f-token')->plainTextToken;

        $permissions = $user->getAllPermissions()->pluck('name');
        $roles = $user->getRoleNames();

        $responseData = [
            'success' => true,
            'message' => 'Login successful',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'user_type' => $user->user_type,
                'current_company_id' => $user->current_company_id,
                'company_location_id' => $user->company_location_id,
                'arrival_location_id' => $user->arrival_location_id,
                'roles' => $roles,
                'permissions' => $permissions,
            ]
        ];

        return ApiResponse::success($responseData, 'Login successful');
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        $responseData = [
            'success' => true,
            'message' => 'Successfully logged out'
        ];

        return ApiResponse::success($responseData, 'Logout successful');
    }

    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|string|exists:users,username',
        ]);

        if ($validator->fails()) {
            return ApiResponse::validationError($validator->errors(), 'Validation error');
        }

        $responseData = [
            'success' => true,
            'message' => 'Password reset link sent to your email'
        ];

        return ApiResponse::success($responseData, 'Password reset initiated');
    }

    public function validateToken(Request $request)
    {
        $user = $request->user();

        $permissions = $user->getAllPermissions()->pluck('name');
        $roles = $user->getRoleNames();

        $responseData = [
            'success' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'user_type' => $user->user_type,
                'current_company_id' => $user->current_company_id,
                'company_location_id' => $user->company_location_id,
                'arrival_location_id' => $user->arrival_location_id,
                'roles' => $roles,
                'permissions' => $permissions,
            ]
        ];

        return ApiResponse::success($responseData, 'Token is valid');
    }
}
