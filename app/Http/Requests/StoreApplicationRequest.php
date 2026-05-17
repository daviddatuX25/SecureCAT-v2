<?php

namespace App\Http\Requests;

use App\Models\AcademicYear;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $activeAcademicYear = AcademicYear::active();

        return [
            'first_name' => ['required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'suffix' => ['nullable', 'string', 'max:20'],
            'birthdate' => ['required', 'date', 'before:-15 years', 'after:-50 years'],
            'sex' => ['required', 'string', 'in:male,female'],
            'email' => [
                'required',
                'email',
                $activeAcademicYear
                    ? Rule::unique('applications', 'email')->where('academic_year_id', $activeAcademicYear->id)
                    : 'email',
            ],
            'phone' => ['nullable', 'string', 'max:12'],
            'address_line' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'province' => ['nullable', 'string', 'max:100'],
            'zip_code' => ['nullable', 'string', 'max:10'],
            'gwa' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'course_preference_1' => ['required', 'integer', 'exists:courses,id'],
            'course_preference_2' => ['nullable', 'integer', 'exists:courses,id', 'different:course_preference_1'],
            'course_preference_3' => ['nullable', 'integer', 'exists:courses,id', 'different:course_preference_1', 'different:course_preference_2'],
            'appointment_id' => ['nullable', 'integer', 'exists:appointments,id'],
            'accept_immediately' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'An application with this email address already exists for the current academic year.',
        ];
    }
}
