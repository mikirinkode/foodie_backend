<?php

namespace App\Http\Controllers\API;

use App\Actions\Fortify\PasswordValidationRules;
use Exception;
use App\Models\User;
use Illuminate\Http\Request;
use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{

    use PasswordValidationRules; // library untuk validasi password dari laravel

    // function login
    public function login(Request $request)
    {
        try {
            // validasi input
            $request->validate([
                'email' => 'email|required',
                'password' => 'required'
            ]);

            // menegecek credentials (login)
            $credentials = request(['email', 'password']);
            if (!Auth::attempt($credentials)){
                return ResponseFormatter::error([
                    'message' => 'Unauthorized'
                ], 'Authentication Failed', 500);
            }

            // jika hash tidak sesuai maka beri error
            // mengecek hash, user nya bener apa kgk
            $user = User::where('email', $request->email)->first();
            if (!Hash::check($request->password, $user->password, [])) // cek apakah password sesuai
            {
                // jika salah
                throw new \Exception('Invalid Credentials');
            }

            // jika valid, maka login
            $tokenResult = $user->createToken('authToken')->plainTextToken; // createToken bawaan laravel
            return ResponseFormatter::success([
                'access_token' => $tokenResult,
                'token_type' => 'Bearer',
                'user' => $user // bawa data user yang diloginkan ke aplikasi kotlin
            ], 'Authenticated');
        } catch(Exception $error){
            // jika ada error di luar penanganan login, maka masukknya kesini
            return ResponseFormatter::error([
                'message' => 'Something went wrong', 
                'error' => $error
            ], 'Authentication Failed', 500);
        }
    }

    public function register (Request $request){
        try {
            // validasi data daftar
            $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'string', 'max:255', 'unique:users'],
                'password' => $this->passwordRules()
            ]);

            // jika valid, maka buat user baru
            User::create([
                'name' => $request->name,
                'email' => $request->email,
                'address' => $request->address,
                'houseNumber' => $request->houseNumber,
                'phoneNumber' => $request->phoneNumber,
                'city' => $request->city,
                'password' => Hash::make($request->password)
            ]);

            // ambil data yang baru dibuat di database 
            $user  = User::where('email', $request->email)->first();

            // kita akan return juga tokennya, agar user bisa sekalian login 
            $tokenResult = $user->createToken('authToken')->plainTextToken;

            return ResponseFormatter::success([
                'access_token' => $tokenResult,
                'token_type' => 'Bearer',
                'user' => $user
            ]);

        } catch (Exception $error) {
            return ResponseFormatter::error([
                'message' => 'something went wrong',
                'error' => $error
            ], 'Authentication Failed', 500);
        }
    }
}
