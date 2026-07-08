<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProfileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'nama' => 'nullable',
            'foto' => 'nullable|image|max:500|mimes:jpeg,png',
            'nip' => 'required|numeric|min:18',
            'jenis_kelamin' => 'nullable',
            'agama' => 'nullable',
            'status_perkawinan' => 'nullable',
            'golongan' => 'nullable',
            'tmt_golongan' => 'nullable|date',
            'pendidikan' => 'nullable',
            'tahun' => 'nullable'
        ];
    }
}
