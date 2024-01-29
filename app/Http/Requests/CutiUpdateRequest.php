<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CutiUpdateRequest extends FormRequest
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
            'jenis_layanan' => 'required',
            'alasan' => 'required',
            'tanggal_mulai' => 'required|date',
            'tanggal_akhir' => 'required|date',
            'alamat' => 'required',
            'dokumen' => 'nullable|file|max:500|mimes:pdf'
        ];
    }
}
