<?php

namespace App\Http\Controllers;
use App\Models\User;
use App\Models\Gimnasio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;


class AuthController extends Controller
{


public function register(Request $request)

{
     //  dd($request->all());
    $request->validate([
        'name'          => 'required|string|max:255',
        'email'         => 'required|string|email|max:255|unique:users',
        'password'      => 'required|string|min:6|confirmed',
        'gym_name'      => 'required|string|max:255',
    ]);

    // Crear el gimnasio primero
    $gimnasio = Gimnasio::create([
        'nombre' => $request->gym_name,
    ]);

    // Crear el usuario asociado al gimnasio
    $user = User::create([
        'name'        => $request->name,
        'email'       => $request->email,
        'password'    => bcrypt($request->password),
        'gimnasio_id' => $gimnasio->id,
    ]);

    // Crear token
    $token = $user->createToken('token-gym')->plainTextToken;

    return response()->json([
        'message' => 'Registro exitoso',
        'access_token' => $token,
        'token_type'   => 'Bearer',
        'user'         => $user,
        'gimnasio'     => $gimnasio,
    ], 201);
}

public function login(Request $request)
{
    // Validar email y contraseña
    $request->validate([
        'email'    => 'required|email',
        'password' => 'required|string',
    ]);

    // Buscar usuario por email
    $user = User::where('email', $request->email)->first();

    // Verificar credenciales
    if (!$user || !Hash::check($request->password, $user->password)) {
        return response()->json(['message' => 'Credenciales incorrectas'], 401);
    }

    // Crear token de acceso
    $token = $user->createToken('token-gym')->plainTextToken;

    // Cargar el gimnasio relacionado
    $user->load('gimnasio');

//dd($user);
    // Responder con token y usuario (incluyendo el gimnasio)
    return response()->json([
        'message'      => 'Inicio de sesión exitoso',
        'access_token' => $token,
        'token_type'   => 'Bearer',
        'user'         => $user
    ]);
}


public function logout(Request $request)
{
    // Elimina solo el token actual
    $request->user()->currentAccessToken()->delete();

    return response()->json([
        'message' => 'Sesión cerrada correctamente'
    ]);
}

}
