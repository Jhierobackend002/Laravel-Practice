<?php

namespace App\Http\Requests;

use App\Services\GoogleServices;
use App\Traits\Authorization;
use App\Traits\Validation;
use Illuminate\Foundation\Http\FormRequest;

class FormSubmissionRequest extends FormRequest
{
    use Authorization, Validation;

    protected $googleServices;

    public function __construct(GoogleServices $googleServices)
    {
        parent::__construct();
        $this->googleServices = $googleServices;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'fullname' => ['required', 'string'],
            'email' => ['required', 'string', 'email'],
            'phone' => ['required', 'string'],
            'inquiry_type' => ['required', 'string'],
            'country' => ['required', 'string'],
            'accept_privacy' => ['required', 'integer', 'in:1'],
        ];
    }

    /**
     * Get custom error messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'fullname.required' => 'Fullname is required',
            'email.required' => 'Email Address is required',
            'email.email' => 'Please provide a valid email address',
            'phone.required' => 'Phone # is required',
            'inquiry_type.required' => 'Inquiry Type is required',
            'country.required' => 'Country is required',
            'accept_privacy.in' => 'You must accept the privacy policy',
        ];
    }

    /**
     * Perform additional validation to check for duplicate entries.
     *
     * @param Validator $validator
     */
    // public function withValidator($validator)
    // {
    //     $validator->after(function ($validator) {
    //         $ssid = env('SheetId');
    //         $sheet_tab = env('Sheets'); // Ensuring data is checked in Sheet2

    //         // Retrieve existing sheet data
    //         $existingData = app(\App\Services\GoogleServices::class)->sheets($ssid, $sheet_tab);

    //         if ($existingData) {
    //             $inputFullName = strtolower(trim($this->input('fullname')));
    //             $inputEmail = strtolower(trim($this->input('email')));

    //             foreach ($existingData as $row) {
    //                 $existingFullName = isset($row[0]) ? strtolower(trim($row[0])) : '';
    //                 $existingEmail = isset($row[1]) ? strtolower(trim($row[1])) : '';

    //                 if ($existingFullName === $inputFullName && $existingEmail === $inputEmail) {
    //                     throw new HttpResponseException(response()->json([
    //                         'duplicate_entry' => 'This account is already registered in our system'
    //                     ], 422));
    //                 }
    //             }
    //         }
    //     });
    // }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $ssid = env('SheetId');
            $sheet_tab = env('Sheets'); // Ensuring data is checked in Sheet2

            // Retrieve existing sheet data
            $existingData = app(\App\Services\GoogleServices::class)->sheets($ssid, $sheet_tab);

            if ($existingData) {
                $inputFullName = strtolower(trim($this->input('fullname')));
                $inputEmail = strtolower(trim($this->input('email')));

                foreach ($existingData as $row) {
                    $existingFullName = isset($row[0]) ? strtolower(trim($row[0])) : '';
                    $existingEmail = isset($row[1]) ? strtolower(trim($row[1])) : '';

                    if ($existingFullName === $inputFullName && $existingEmail === $inputEmail) {
                        // If both fullname and email already exist, block the request
                        $validator->errors()->add('duplicate_entry', 'This account is already registered in our system');
                        break;
                    }
                }
            }
        });
    }
}
