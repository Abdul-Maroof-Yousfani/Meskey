<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Acl\{LoginHistory};
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Google2FA;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {

        $this->middleware('guest')->except('logout');
    }

    protected function authenticated(Request $request, $user)
    {
        // Create and store login history

        $previousLogin = LoginHistory::where('user_id', $user->id)
            ->where('header', $request->header('User-Agent'))
            ->exists();

        if (!$previousLogin) {
            if ($user->security == 1) {
                $user->sendTwoFactorAuthenticationCode();
                return redirect('/verify');
            } elseif ($user->security == 2 ) {
                return redirect('/setup-google-authenticator');
            }
        }

        LoginHistory::create([
            'header' => $request->header('User-Agent'),
            'location' => $request->ip(),
            'user_id' => $user->id,
        ]);

        return redirect()->intended($this->redirectPath());
    }

}
