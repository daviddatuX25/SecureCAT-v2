<?php

namespace App\Http\Requests;

use App\Models\ResultSheetTemplate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreResultSheetTemplateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['super_admin', 'admin', 'counselor']) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $mode = $this->input('mode', 'html');
        $rules = [
            'name' => ['required', 'string', 'max:100'],
            'mode' => ['required', Rule::in([ResultSheetTemplate::MODE_HTML, ResultSheetTemplate::MODE_DOCX])],
            'paper_size' => ['sometimes', Rule::in(['a4', 'legal', 'letter'])],
            'orientation' => ['sometimes', Rule::in(['portrait', 'landscape'])],
            'logical_unit' => ['sometimes', Rule::in(['full', 'half_a4', 'half_legal', 'half_letter'])],
            'is_active' => ['sometimes', 'boolean'],
        ];

        if ($mode === ResultSheetTemplate::MODE_HTML) {
            $rules['content'] = ['required', 'string'];
        }
        if ($mode === ResultSheetTemplate::MODE_DOCX) {
            $rules['docx'] = ['required', 'file', 'mimes:docx', 'max:5120'];
        }

        return $rules;
    }
}
