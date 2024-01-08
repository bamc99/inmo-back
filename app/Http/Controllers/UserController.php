<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Check if email exists
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkEmail(Request $request) {

        $request->validate([
            'email' => 'required|email',
        ]);

        $email = $request->email;

        $user = User::where('email', $email)->first();
        if ($user) {
            // Si el correo electrónico ya está registrado, devolver exists:true
            return response()->json(['exists' => true], 200);
        } else {
            // Si el correo electrónico no está registrado, devolver exists:false
            return response()->json(['exists' => false], 200);
        }
    }
}
