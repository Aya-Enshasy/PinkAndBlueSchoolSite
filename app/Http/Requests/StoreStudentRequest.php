<?php

namespace App\Http\Requests;

use App\Models\Student;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class StoreStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'min:10', 'max:255'],
            'grade' => ['required', Rule::in(Student::GRADE_OPTIONS)],
            'academic_year' => ['required', 'string', 'max:20'],
            'student_id_number' => [
                'required',
                'digits:9',
                Rule::unique('students', 'student_id_number')->where(fn ($query) => $query->where('academic_year', $this->input('academic_year'))),
            ],
            'father_id_number' => ['required', 'digits:9'],
            'mobile_number' => ['required', 'digits:10'],
            'alternative_mobile_number' => ['nullable', 'digits:10'],
            'birth_date' => ['required', 'date', 'before_or_equal:today'],
            'student_id_image' => ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:2048'],
            'father_id_image' => ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:2048'],
            'birth_certificate_image' => ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:2048'],
        ];
    }

    protected function passedValidation(): void
    {
        Log::info('StoreStudentRequest validation passed', [
            'payload' => Arr::except($this->all(), ['student_id_image', 'father_id_image', 'birth_certificate_image']),
            'ip' => $this->ip(),
            'user_id' => optional($this->user())->id,
        ]);
    }

    protected function failedValidation(Validator $validator): void
    {
        Log::warning('StoreStudentRequest validation failed', [
            'payload' => Arr::except($this->all(), ['student_id_image', 'father_id_image', 'birth_certificate_image']),
            'errors' => $validator->errors()->toArray(),
            'ip' => $this->ip(),
            'user_id' => optional($this->user())->id,
        ]);

        parent::failedValidation($validator);
    }
}
