<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Listar todos los usuarios
     */
    public function index()
    {
        $users = User::select('id', 'name', 'email')->get();
        return response()->json([
            'success' => true,
            'data' => $users
        ]);
    }
}
