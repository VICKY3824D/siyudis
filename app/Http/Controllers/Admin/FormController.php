<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFormRequest;
use App\Http\Requests\UpdateFormRequest;
use App\Models\Form;
use Illuminate\Http\JsonResponse;

class FormController extends Controller
{
    public function index(): JsonResponse
    {
        $forms = Form::withCount('fields')->latest()->get();

        return response()->json([
            'status' => 'success',
            'data' => $forms,
        ]);
    }

    public function store(StoreFormRequest $request): JsonResponse
    {
        $form = Form::create($request->validated());

        return response()->json([
            'status' => 'success',
            'message' => 'Form berhasil dibuat',
            'data' => $form,
        ], 201);
    }

    public function show(Form $form): JsonResponse
    {
        $form->load(['fields' => function ($query) {
            $query->orderBy('order_position', 'asc');
        }]);

        return response()->json([
            'status' => 'success',
            'data' => $form,
        ]);
    }

    public function update(UpdateFormRequest $request, Form $form): JsonResponse
    {
        $form->update($request->validated());

        return response()->json([
            'status' => 'success',
            'message' => 'Form berhasil diperbarui',
            'data' => $form,
        ]);
    }

    public function destroy(Form $form): JsonResponse
    {
        $form->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Form berhasil dihapus',
        ]);
    }
}
