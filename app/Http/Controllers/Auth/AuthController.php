<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Responses\BusinessResponse;
use App\Models\User;
use App\Models\Business;
use App\Models\BusinessTiming;
use App\Models\Currency;
use App\Models\BusinessCurrency;
use App\Models\UserOtp;
use App\Models\BusinessPhoto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\RegisterationRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AuthController extends Controller
{
    public function register(RegisterationRequest $request)
    {
        DB::beginTransaction();

        try {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);
            $token = $user->createToken('api_token')->plainTextToken;

            $logoPath = null;
            if ($request->hasFile('business_logo')) {
                $extension = $request->file('business_logo')->getClientOriginalExtension();
                $fileName = strtolower(str_replace(' ', '_', $request->business_name) . '-logo-' . now()->format('Y-m-d-H-i-s') . '.' . $extension);
                $logoPath = $request->file('business_logo')->storeAs('uploads/logos', $fileName, 'public');
            }
            $profile = Business::create([
                'user_id' => $user->id,
                'business_name' => $request->business_name,
                'business_logo' => $logoPath,
                'registration_no' => $request->registration_no,
                'address' => $request->address,
                'phone_no' => $request->phone_no,
                'whats_app_no' => $request->whats_app_no,
                'flat_shop_number' => $request->flat_shop_number,
                'building_no' => $request->building_no,
                'road_no' => $request->road_no,
                'block_no' => $request->block_no,
                'city' => $request->city,
                'is_tax_enable' => $request->is_tax_enable,
                'tax' => $request->tax,
                'country_id' => $request->country_id,
            ]);

            if ($request->hasFile('photos')) {
                foreach ($request->file('photos') as $photo) {
                    $path = $photo->store('uploads/photos', 'public');
                    BusinessPhoto::create([
                        'business_id' => $profile->id,
                        'photo_path' => $path,
                    ]);
                }
            }

            // Assign default currency
            //  $defaultCurrency = Currency::where('is_default', true)->first();
            //BusinessCurrency::create([
            //  'business_id' => $profile->id,
            //'currency_id' => $defaultCurrency->id,
            //'is_default' => true,
            //]);

            DB::commit();

            return response()->json(
                [
                    'message' => 'Registration successful!',
                    'token' => $token,
                    'user' => $user,
                    'business' => new BusinessResponse($user->business),
                ],
                201,
            );
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(
                [
                    'message' => 'Registration failed!',
                    'error' => $e->getMessage(),
                ],
                500,
            );
        }
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $credentials['email'])->first();
        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        return response()->json(
            [
                'message' => 'Registration successful!',
                'token' => $user->createToken('api_token')->plainTextToken,
                'user' => $user,
                'business' => new BusinessResponse($user->business),
            ],
            201,
        );
    }
    //Logout
    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();
        return response()->json(['message' => 'Logged out successfully']);
    }

    public function generateOTP(Request $request)
    {
        DB::beginTransaction();

        try {
            $otp = rand(100000, 999999);

            UserOtp::where('user_id', auth()->user()->id)->delete();

            $newOtp = UserOtp::create([
                'user_id' => auth()->user()->id,
                'otp' => $otp,
                'otp_expiry' => Carbon::now()->addSecond(30),
            ]);

            // $this->sendOTP(auth()->user(), $otp);

            DB::commit();

            return response()->json(
                [
                    'message' => 'OTP sent successfully.',
                    'data' => $newOtp,
                ],
                200,
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(
                [
                    'message' => 'Failed to generate OTP.',
                    'error' => $e->getMessage(),
                ],
                500,
            );
        }
    }

    private function sendOTP(Request $request)
    {
        dd('1111111111111111111111111111');
        $request->user()->tokens()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }

    public function verifyOTP(Request $request)
    {
        $request->validate([
            'otp' => 'required|numeric',
        ]);

        DB::beginTransaction();

        try {
            $userOtp = UserOtp::where('user_id', auth()->user()->id)
                ->where('otp', $request->otp)
                ->where('otp_expiry', '>=', now())
                ->first();

            if (!$userOtp) {
                return response()->json(['message' => 'Invalid or expired OTP.'], 400);
            }

            $userOtp->delete();

            auth()
                ->user()
                ->update(['whatsapp_verified_at' => now()]);

            DB::commit();

            return response()->json(['message' => 'OTP verified successfully.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(
                [
                    'message' => 'Failed to verify OTP.',
                    'error' => $e->getMessage(),
                ],
                500,
            );
        }
    }

    public function profileUpdate(Request $request)
    {
        // dd($request);
        DB::beginTransaction();

        try {
            $user = auth()->user();
            $business = $user->business;

            // Validate the incoming request
            $data = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|max:255|unique:users,email,' . $user->id,
                // 'password' => 'nullable|min:6|confirmed',
                'business_name' => 'required|string|max:255',
                'registration_no' => 'required|string|max:255',
                'address' => 'nullable|string|max:255',
                'phone_no' => 'required|string|max:20',
                'whats_app_no' => 'nullable|string|max:20',
                'business_logo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
                'photos.*' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            ]);

            // Update user details
            $user->update([
                'name' => $data['name'],
                'email' => $data['email'],
            ]);

            // Handle business logo
            $logoPath = $business->business_logo;
            if ($request->hasFile('business_logo')) {
                // Delete the old logo if it exists
                if ($logoPath) {
                    \Storage::disk('public')->delete($logoPath);
                }

                $extension = $request->file('business_logo')->getClientOriginalExtension();
                $fileName = strtolower(preg_replace('/[^A-Za-z0-9]/', '-', str_replace(' ', '_', $request->business_name)) . '-logo-' . now()->format('Y-m-d-H-i-s') . '.' . $extension);

                $logoPath = $request->file('business_logo')->storeAs('uploads/logos', $fileName, 'public');
            }

            // Update business details
            $business->update([
                'business_name' => $data['business_name'],
                'business_logo' => $logoPath,
                'registration_no' => $data['registration_no'],
                'address' => $data['address'],
                'phone_no' => $data['phone_no'],
                'whats_app_no' => $data['whats_app_no'],
                'flat_shop_number' => $request->flat_shop_number,
                'building_no' => $request->building_no,
                'road_no' => $request->road_no,
                'block_no' => $request->block_no,
                'city' => $request->city,
                'is_tax_enable' => $request->is_tax_enable,
                'tax' => $request->tax,
                'country_id' => $request->country_id,
            ]);

            // Handle business photos
            if ($request->hasFile('photos')) {
                // Delete old photos
                foreach ($business->photos as $photo) {
                    \Storage::disk('public')->delete($photo->photo_path);
                    $photo->delete();
                }

                foreach ($request->file('photos') as $photo) {
                    $path = $photo->store('uploads/photos', 'public');
                    BusinessPhoto::create([
                        'business_id' => $business->id,
                        'photo_path' => $path,
                    ]);
                }
            }

            DB::commit();

            return response()->json(
                [
                    'message' => 'Registration successful!',
                    'user' => auth()->user(),
                    'business' => new BusinessResponse($user->business),
                ],
                201,
            );
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(
                [
                    'message' => 'Update failed!',
                    'error' => $e->getMessage(),
                ],
                500,
            );
        }
    }
}
