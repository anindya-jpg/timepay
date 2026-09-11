<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class KaryawanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $idKaryawan = $this->route('karyawan')?->id_karyawan;

        return [
            'nip' => ['required', 'string', 'max:30', Rule::unique('karyawan', 'nip')->ignore($idKaryawan, 'id_karyawan')],
            'nama' => ['required', 'string', 'max:100'],
            'jenis_kelamin' => ['required', Rule::in(['L', 'P'])],
            'alamat' => ['nullable', 'string'],
            'no_hp' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:100'],
            'no_rekening' => ['nullable', 'string', 'max:30'],
            'username' => ['required', 'string', 'max:50', Rule::unique('karyawan', 'username')->ignore($idKaryawan, 'id_karyawan')],
            'password' => [$idKaryawan ? 'nullable' : 'required', 'string', 'min:8'],
            'id_jabatan' => ['required', 'exists:jabatan,id_jabatan'],
            'tanggal_masuk' => ['nullable', 'date'],
            'status_aktif' => ['required', Rule::in(['aktif', 'nonaktif'])],
        ];
    }
}
