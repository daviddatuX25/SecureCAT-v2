<?php

namespace App\Http\Requests;

use App\Models\ResultSheetTemplate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreResultSheetTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['super_admin', 'admin', 'test_administrator']) ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'mode' => ['required', Rule::in([ResultSheetTemplate::MODE_HTML, ResultSheetTemplate::MODE_DOCX])],
            'paper_size' => ['sometimes', Rule::in([
                ResultSheetTemplate::PAPER_A4,
                ResultSheetTemplate::PAPER_LEGAL,
                ResultSheetTemplate::PAPER_LETTER,
            ])],
            'orientation' => ['sometimes', Rule::in([
                ResultSheetTemplate::ORIENTATION_PORTRAIT,
                ResultSheetTemplate::ORIENTATION_LANDSCAPE,
            ])],
            'logical_unit' => ['sometimes', Rule::in([
                ResultSheetTemplate::LOGICAL_FULL,
                ResultSheetTemplate::LOGICAL_HALF_A4,
                ResultSheetTemplate::LOGICAL_HALF_LEGAL,
                ResultSheetTemplate::LOGICAL_HALF_LETTER,
            ])],
            'content' => ['required_if:mode,' . ResultSheetTemplate::MODE_HTML, 'nullable', 'string'],
            'docx' => ['required_if:mode,' . ResultSheetTemplate::MODE_DOCX, 'nullable', 'file', 'mimes:docx', 'max:5120'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
