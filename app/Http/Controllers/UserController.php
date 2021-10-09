<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return User::query()->paginate(15);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        try {
            $this->validate($request, [
                'name' => ['required', 'string', 'regex:/^[\pL\s\-]+$/u', 'min:3', 'max:255'],
                'email' => ['required', 'email', 'unique:users,email'],
                'password' => ['required', 'string', 'min:6', 'max:255'],
                'is_admin' => ['required', 'boolean'],
                'default_language_id' => ['nullable', 'integer', 'exists:languages,id'],
            ]);
        } catch (ValidationException $e) {
            return response(['status' => false, 'errors' => $e->errors()], $e->status);
        }

        $user = new User;
        $user->name = $request->input('name');
        $user->email = $request->input('email');
        $user->password = Hash::make($request->input('password'));
        $user->is_admin = $request->input('is_admin');
        $user->default_language_id = $request->input('default_language_id');
        $user->api_token = Str::random(60);
        $user->save();

        return $user->find($user->id);
    }

    /**
     * Display the specified resource.
     *
     * @param \App\Models\User $user
     * @return \Illuminate\Http\Response
     */
    public function show(User $user)
    {
        return $user;
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\User $user
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, User $user)
    {
        try {
            $this->validate($request, [
                'name' => ['nullable', 'string', 'regex:/^[\pL\s\-]+$/u', 'min:3', 'max:255'],
                'email' => ['nullable', 'email', 'unique:users,email,' . $user->id],
                'password' => ['nullable', 'string', 'min:6', 'max:255'],
                'is_admin' => ['nullable', 'boolean'],
                'default_language_id' => ['nullable', 'integer', 'exists:languages,id'],
            ]);
        } catch (ValidationException $e) {
            return response(['status' => false, 'errors' => $e->errors()], $e->status);
        }
        $updated = false;

        if ($request->has('name') && $request->input('name') != null && $user->name != $request->input('name')) {
            $user->name = $request->input('name');
            $updated = true;
        }
        if ($request->has('email') && $request->input('email') != null && $user->email != $request->input('email')) {
            $user->email = $request->input('email');
            $updated = true;
        }
        if ($request->has('password') && $request->input('password') != null && !Hash::check($request->input('password'), $user->password)) {
            $user->password = Hash::make($request->input('password'));
            $updated = true;
        }
        if ($request->has('is_admin') && $request->input('is_admin') != null && $user->is_admin != $request->input('is_admin')) {
            $user->is_admin = $request->input('is_admin');
            $updated = true;
        }
        if ($request->has('default_language_id') && $request->input('default_language_id') != null && $user->default_language_id != $request->input('default_language_id')) {
            $user->default_language_id = $request->input('default_language_id');
            $updated = true;
        }
        if ($updated) {
            $user->update();
            return $user;
        }
        return response(null, 204);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Models\User $user
     * @return \Illuminate\Http\Response
     */
    public function destroy(User $user)
    {
        $user->delete();
        return response(null, 204);

    }


    public function register(Request $request)
    {
        try {
            $this->validate($request, [
                'name' => ['required', 'string', 'regex:/^[\pL\s\-]+$/u', 'min:3', 'max:255'],
                'email' => ['required', 'email', 'unique:users,email'],
                'password' => ['required', 'string', 'min:6', 'max:255'],
                'default_language_id' => ['nullable', 'integer', 'exists:languages,id'],
            ]);
        } catch (ValidationException $e) {
            return response(['status' => false, 'errors' => $e->errors()], $e->status);
        }

        $user = new User;
        $user->name = $request->input('name');
        $user->email = $request->input('email');
        $user->password = Hash::make($request->input('password'));
        $user->default_language_id = $request->input('default_language_id');
        $user->api_token = Str::random(60);
        $user->is_admin = 0;
        $user->save();

        return $user->find($user->id);
    }

    public function login(Request $request)
    {
        try {
            $this->validate($request, [
                'email' => ['required', 'email'],
                'password' => ['required', 'string', 'min:6', 'max:255'],
            ]);
        } catch (ValidationException $e) {
            return response(['status' => false, 'errors' => $e->errors()], $e->status);
        }

        $user = User::query()->where('email', '=', $request->input('email'))->first();

        if (empty($user)) {
            http_response_code(422);
            exit("CREDENTIALS_ERROR");
        }

        if (!Hash::check($request->input('password'), $user->password)) {
            http_response_code(422);
            exit("CREDENTIALS_ERROR");
        }

        if ($user->api_token == null) {
            $user->api_token = Str::random(60);
            $user->update();
        }

        return $user;
    }

    public function current_user()
    {
        return auth()->user();
    }

    public function update_profile(Request $request)
    {
        try {
            $this->validate($request, [
                'name' => ['nullable', 'string', 'regex:/^[\pL\s\-]+$/u', 'min:3', 'max:255'],
                'default_language_id' => ['nullable', 'integer', 'exists:languages,id'],
            ]);
        } catch (ValidationException $e) {
            return response(['status' => false, 'errors' => $e->errors()], $e->status);
        }
        $user = auth()->user();

        $updated = false;

        if ($request->has('name') && $request->input('name') != null && $user->name != $request->input('name')) {
            $user->name = $request->input('name');
            $updated = true;
        }
        if ($request->has('default_language_id') && $request->input('default_language_id') != null && $user->default_language_id != $request->input('default_language_id')) {
            $user->default_language_id = $request->input('default_language_id');
            $updated = true;
        }
        if ($updated) {
            $user->update();
            return $user;
        }
        return response(null, 204);
    }

    public function logout()
    {
        $user = auth()->user();
        $user->api_token = null;
        $user->update();

        return response(null, 204);
    }

    public function change_password(Request $request)
    {
        try {
            $this->validate($request, [
                'old_password' => ['required', 'string', 'min:6', 'max:255'],
                'new_password' => ['required', 'string', 'min:6', 'max:255'],
            ]);
        } catch (ValidationException $e) {
            return response(['status' => false, 'errors' => $e->errors()], $e->status);
        }

        $user = auth()->user();

        if (Hash::check($request->input('old_password'), $user->password) == false) {
            http_response_code(422);
            exit("WRONG_OLD_PASSWORD");
        }

        if (Hash::check($request->input('new_password'), $user->password)) {
            http_response_code(422);
            exit("NEW_SAME_AS_OLD");
        }

        $user->password = Hash::make($request->input('new_password'));
        $user->update();

        return $user;
    }

    public function forgot_password(Request $request)
    {
        try {
            $this->validate($request, [
                'email' => ['required', 'email'],
            ]);
        } catch (ValidationException $e) {
            return response(['status' => false, 'errors' => $e->errors()], $e->status);
        }

        $user = User::query()->where('email', '=', $request->input('email'))->first();
        if (!empty($user)) {
            if (DB::table('password_resets')
                    ->where('email', '=', $request->input('email'))
                    ->where('created_at', '>=', (time() - 60 * 15))
                    ->count() > 0) {
                http_response_code(422);
                exit("MESSAGE_SENT_BEFORE_15_MINUTES");
            }
            Password::sendResetLink(['email' => $user->email]);
        }
        return "MESSAGE_SENT";
    }

    public function reset_password(Request $request)
    {
        try {
            $this->validate($request, [
                'email' => ['required', 'email'],
                'token' => ['required', 'string'],
                'new_password' => ['required', 'string', 'min:6', 'max:255'],
            ]);
        } catch (ValidationException $e) {
            return response(['status' => false, 'errors' => $e->errors()], $e->status);
        }

        $tokenData = DB::table('password_resets')
            ->where('token', $request->input('token'))
            ->where('email', $request->input('email'))
            ->first();

        if (empty($tokenData)) {
            http_response_code(422);
            exit("WRONG_TOKEN");
        }

        /** @var User $user */
        $user = User::query()->where('email', '=', $request->input('email'))->first();

        if (Hash::check($request->input('new_password'), $user->password)) {
            http_response_code(422);
            exit("NEW_SAME_AS_OLD");
        }

        $user->api_token = null;
        $user->password = Hash::make($request->input('new_password'));
        $user->update();

        return $user;
    }

}
