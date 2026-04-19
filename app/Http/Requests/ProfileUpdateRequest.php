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
            'phone' => ['required', 'string', 'max:20'],
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
}
