<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AktivitasRequest extends FormRequest
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
            'tanggal' => 'required|date',
            'id_sasaran' => 'required',
            'aktivitas' => 'required',
            // 'satuan' => 'required',
            'waktu' => 'required|numeric',
            'hasil' => 'required|numeric',
            'keterangan' => 'required'
        ];
    }
}
