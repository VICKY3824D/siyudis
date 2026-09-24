<?php

namespace App\Http\Controllers;

use App\Models\ProgramStudi;
use Illuminate\Http\JsonResponse;

class ProgramStudiController extends Controller
{
    /**
     * Display a listing of program studi.
     */
    public function index(): JsonResponse
    {
        $prodi = ProgramStudi::with('kaprodi:id,nama,email')->get();

        return response()->json([
            'status' => 'success',
            'data' => $prodi,
        ]);
    }
}
