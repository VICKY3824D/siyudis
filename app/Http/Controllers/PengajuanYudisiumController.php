<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePengajuanYudisiumRequest;
use App\Models\PengajuanFieldValue;
use App\Models\PengajuanYudisium;
use App\Models\YudisiumEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PengajuanYudisiumController extends Controller
{
    /**
     * Ambil struktur form dinamis (field pengajuan) sesuai event yudisium
     * yang sedang aktif untuk program studi mahasiswa yang login.
     */
    public function activeForm(Request $request): JsonResponse
    {
        $user = $request->user();

        $event = YudisiumEvent::with(['form.fields' => function ($query) {
            $query->where('is_visible', true)->orderBy('order_position');
        }])
            ->where('program_studi_id', $user->program_studi_id)
            ->where('is_active', true)
            ->latest('tgl_buka')
            ->first();

        if (! $event) {
            return response()->json([
                'status' => 'error',
                'message' => 'Belum ada periode yudisium yang aktif untuk program studi Anda',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'event' => $event->only(['id', 'nama_event', 'periode', 'tgl_buka', 'tgl_tutup']),
                'fields' => $event->form->fields,
            ],
        ]);
    }

    /**
     * Submit form pengajuan yudisium. Hanya bisa dilakukan 1x seumur mahasiswa
     * (tidak ada submit ulang dari nol, lihat User::pengajuanYudisium).
     */
    public function store(StorePengajuanYudisiumRequest $request): JsonResponse
    {
        $user = $request->user();

        if (PengajuanYudisium::where('user_id', $user->id)->exists()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Anda sudah pernah submit pengajuan yudisium',
            ], 409);
        }

        $event = YudisiumEvent::with('form.fields')
            ->findOrFail($request->integer('yudisium_event_id'));

        $this->validateFieldValues($event, $request->array('field_values'));

        $pengajuan = DB::transaction(function () use ($user, $event, $request) {
            $pengajuan = PengajuanYudisium::create([
                'user_id' => $user->id,
                'yudisium_event_id' => $event->id,
                'status' => 'submitted',
                'submitted_at' => now(),
            ]);

            foreach ($request->array('field_values') as $fieldValue) {
                PengajuanFieldValue::create([
                    'pengajuan_id' => $pengajuan->id,
                    'form_field_id' => $fieldValue['form_field_id'],
                    'value' => $fieldValue['value'] ?? null,
                ]);
            }

            return $pengajuan;
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Pengajuan yudisium berhasil disubmit',
            'data' => [
                'pengajuan_id' => $pengajuan->id,
                'status' => $pengajuan->status,
            ],
        ], 201);
    }

    /**
     * Cek status pengajuan milik mahasiswa yang sedang login.
     */
    public function me(Request $request): JsonResponse
    {
        $pengajuan = PengajuanYudisium::with(['yudisiumEvent', 'fieldValues.formField'])
            ->where('user_id', $request->user()->id)
            ->first();

        if (! $pengajuan) {
            return response()->json([
                'status' => 'error',
                'message' => 'Anda belum pernah submit pengajuan yudisium',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $pengajuan,
        ]);
    }

    /**
     * Validasi field_values terhadap field wajib & tipe data pada form yang aktif.
     * Dinamis karena tergantung konfigurasi field dari Super Admin.
     *
     * @param  array<int, array{form_field_id: int, value: mixed}>  $fieldValues
     */
    private function validateFieldValues(YudisiumEvent $event, array $fieldValues): void
    {
        $submitted = collect($fieldValues)->keyBy('form_field_id');
        $errors = [];

        foreach ($event->form->fields as $field) {
            $value = $submitted->get($field->id)['value'] ?? null;

            if ($field->is_required && ($value === null || $value === '')) {
                $errors["field_{$field->id}"] = "{$field->field_name} wajib diisi";

                continue;
            }

            if ($value === null || $value === '') {
                continue;
            }

            $error = match ($field->data_type) {
                'number' => is_numeric($value) ? null : "{$field->field_name} harus berupa angka",
                'single_option' => in_array($value, $field->options ?? [], true) ? null : "{$field->field_name} tidak sesuai pilihan yang tersedia",
                'link', 'pdf', 'image' => filter_var($value, FILTER_VALIDATE_URL) ? null : "{$field->field_name} harus berupa URL hasil upload yang valid",
                default => null,
            };

            if ($error) {
                $errors["field_{$field->id}"] = $error;
            }
        }

        if (! empty($errors)) {
            throw ValidationException::withMessages($errors);
        }
    }
}
