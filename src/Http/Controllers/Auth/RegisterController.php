<?php

namespace OpenDominion\Http\Controllers\Auth;

use Hash;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Auth\RedirectsUsers;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use OpenDominion\Events\UserActivatedEvent;
use OpenDominion\Events\UserRegisteredEvent;
use OpenDominion\Http\Controllers\AbstractController;
use OpenDominion\Models\User;

class RegisterController extends AbstractController
{
    use RedirectsUsers;

    /**
     * Where to redirect users after registration.
     *
     * @var string
     */
    protected $redirectTo = '/';

    /**
     * Show the application registration form.
     *
     * @return Response
     */
    public function showRegistrationForm()
    {
        return view('pages.auth.register');
    }

    /**
     * Handle a registration request for the application.
     *
     * @param Request $request
     * @return Response
     */
    public function register(Request $request)
    {
        $this->validate($request, [
            'display_name' => 'required|unique:users',
            'email' => 'required|email|unique:users',
            'password' => 'required|confirmed|min:8',
            'terms' => 'required',
        ]);

        $alwaysActivate = true;

        $user = $this->create($request->all(), $alwaysActivate);

        #event(new UserRegisteredEvent($user));

        $message = 'You have been successfully registered. An activation email has been dispatched to your address.';
        $message = $alwaysActivate ? 'You have been successfully registered. You can now login.' : $message;

        return redirect($this->redirectPath());
    }

    /**
     * Handle an activation request for the application.
     *
     * @param Request $request
     * @param string $activation_code
     * @return Response
     */
    public function activate(Request $request, string $activation_code)
    {
        try {
            $user = User::where(['activated' => false, 'activation_code' => $activation_code])
                ->firstOrFail();

        } catch (ModelNotFoundException $e) {
            return redirect()
                ->route('home')
                ->withErrors(['Invalid activation code']);
        }

        $user->activated = true;
        $user->save();

        auth()->login($user);

        event(new UserActivatedEvent($user));

        return redirect()->route('dashboard');
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param array $data
     * @return User
     */
    protected function create(array $data, bool $alwaysActivate)
    {
        $activate = (env('APP_ENV') == 'local' or $alwaysActivate) ? 1 : 0;

        return User::create([
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'display_name' => $data['display_name'],
            'activation_code' => Str::random(),
            'activated' => $activate,#in_array(request()->getHost(), ['odarena.local','odarena.virtual']),
        ]);
    }
}
