<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFormFieldRequest;
use App\Http\Requests\UpdateFormFieldRequest;
use App\Models\Form;
use App\Models\FormField;
use Illuminate\Http\JsonResponse;

class FormFieldController extends Controller
{
    public function index(Form $form): JsonResponse
    {
        $fields = $form->fields()->orderBy('order_position', 'asc')->get();

        return response()->json([
            'status' => 'success',
            'data' => $fields,
        ]);
    }

    public function store(StoreFormFieldRequest $request, Form $form): JsonResponse
    {
        $validated = $request->validated();

        if (! isset($validated['order_position'])) {
            $validated['order_position'] = $form->fields()->max('order_position') + 1;
        }

        $field = $form->fields()->create($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Field berhasil ditambahkan',
            'data' => $field,
        ], 201);
    }

    public function update(UpdateFormFieldRequest $request, Form $form, FormField $field): JsonResponse
    {
        if ($field->form_id !== $form->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Field tidak ditemukan pada form ini',
            ], 404);
        }

        $field->update($request->validated());

        return response()->json([
            'status' => 'success',
            'message' => 'Field berhasil diperbarui',
            'data' => $field,
        ]);
    }

    public function destroy(Form $form, FormField $field): JsonResponse
    {
        if ($field->form_id !== $form->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Field tidak ditemukan pada form ini',
            ], 404);
        }

        $field->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Field berhasil dihapus',
        ]);
    }
}
