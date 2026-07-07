<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PresensiRequest extends FormRequest
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
        $request = request();
         
         if ($request->jenis == 'datang') {
            return [
                'waktu_masuk' => 'required|date_format:H:i:s',
                'status' => 'required',
                'jenis' => 'required'
            ];
         }else{
            return [
                'waktu_keluar' => 'required|date_format:H:i:s',
                'status' => 'required',
                'jenis' => 'required'
            ];
         }
        
    }
}
