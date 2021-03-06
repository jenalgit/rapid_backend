<?php

namespace App\Http\Controllers\API;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;
use InfyOm\Generator\Utils\ResponseUtil;
use Validator;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\ThrottlesLogins;
use Illuminate\Foundation\Auth\AuthenticatesAndRegistersUsers;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Registration & Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users, as well as the
    | authentication of existing users. By default, this controller uses
    | a simple trait to add these behaviors. Why don't you explore it?
    |
    */

    use AuthenticatesAndRegistersUsers, ThrottlesLogins;

    protected $guard = 'api';

    /**
     * Create a new authentication controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        // $this->middleware($this->guestMiddleware(), ['except' => 'logout']);
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'name' => 'required|max:255',
            'email' => 'required|email|max:255|unique:users',
            'password' => 'required|confirmed|min:6',
        ]);
    }

    protected function handleUserWasAuthenticated(Request $request, $throttles, $token)
    {
        if ($throttles) {
            $this->clearLoginAttempts($request);
        }

        return $this->authenticated($request, Auth::guard($this->getGuard())->user(), $token);
    }

    protected function authenticated($request, $user, $token)
    {
        return Response::json(ResponseUtil::makeResponse('login successfully', [
            'user'    => $user,
            'request' => $request->all(),
            'token'   => $token
        ]));
    }

   /**
     * Handle a login request to the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function login(Request $request)
    {
        // $this->validateLogin($request);

        // If the class is using the ThrottlesLogins trait, we can automatically throttle
        // the login attempts for this application. We'll key this by the username and
        // the IP address of the client making these requests into this application.
        $throttles = $this->isUsingThrottlesLoginsTrait();
        if ($throttles && $lockedOut = $this->hasTooManyLoginAttempts($request)) {
            $this->fireLockoutEvent($request);

            return $this->sendLockoutResponse($request);
        }

        $credentials = $this->getCredentials($request);

        if ($token = Auth::guard($this->getGuard())->attempt($credentials)) {
            return $this->handleUserWasAuthenticated($request, $throttles, $token);
        }

        // If the login attempt was unsuccessful we will increment the number of attempts
        // to login and redirect the user back to the login form. Of course, when this
        // user surpasses their maximum number of attempts they will get locked out.
        if ($throttles && ! $lockedOut) {
            $this->incrementLoginAttempts($request);
        }

        return $this->sendFailedLoginResponse($request);
    }

    protected function sendFailedLoginResponse(Request $request)
    {
        return Response::json(ResponseUtil::makeError($this->getFailedLoginMessage()));
    }

    public function register(Request $request)
    {
        $validator = $this->validator($request->all());

        if ($validator->fails()) {
            $this->throwValidationException(
                $request, $validator
            );
        }

        $this->create($request->all());

        $credentials = [
            'email' => $request['username'],
            'password' => $request['password'],
        ];

        $token = Auth::guard($this->getGuard())->attempt($credentials);

        return Response::json(ResponseUtil::makeResponse('login successfully', [
            'token' => $token
        ]));
    }

    public function logout()
    {
        Auth::guard($this->getGuard())->logout();

        return Response::json(ResponseUtil::makeResponse('logout successfully', []));
    }

    public function refreshToken(Request $request) {
        $token = Auth::guard($this->getGuard())->refresh();
        return Response::json(ResponseUtil::makeResponse('refresh successfully', [
            'token' => $token,
        ]));
    }

    public function getUserInfo() {
        return Response::json(ResponseUtil::makeResponse('success', ['user' => Auth::user()]));
    }
}
