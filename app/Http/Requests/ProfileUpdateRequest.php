<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    // Mendapatkan aturan validasi yang berlaku saat mengupdate data profil
    public function rules(): array
    {
        $user = $this->user();
        $allowedDomain = $user->is_admin ? '@sman11.sch.id' : '@student.sman11.sch.id';

        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20', 'regex:/^[0-9]+$/'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->user()->id),
                function ($attribute, $value, $fail) use ($allowedDomain) {
                    if (!str_ends_with($value, $allowedDomain)) {
                        $fail('Email harus menggunakan domain ' . $allowedDomain);
                    }
                },
            ],
        ];
    }

    // Pesan validasi dalam Bahasa Indonesia
    public function messages(): array
    {
        return [
            'name.required' => 'Nama lengkap wajib diisi.',
            'name.max' => 'Nama lengkap tidak boleh lebih dari 255 karakter.',
            'phone.required' => 'Nomor telepon wajib diisi.',
            'phone.regex' => 'Nomor telepon hanya boleh berisi angka.',
            'phone.max' => 'Nomor telepon tidak boleh lebih dari 20 digit.',
            'email.required' => 'Alamat email wajib diisi.',
            'email.email' => 'Format alamat email tidak valid.',
            'email.unique' => 'Alamat email sudah digunakan.',
        ];
    }
}
