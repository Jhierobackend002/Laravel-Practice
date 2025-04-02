<?php

namespace App\Traits;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

trait Validation
{
    /**
     * Override the failedValidation method to return only the "errors" object.
     *
     * @param Validator $validator
     */
    protected function failedValidation(Validator $validator)
    {
        // Take the first error message for each field
        $required = collect($validator->errors())->mapWithKeys(function ($errors, $validate) {
            return [$validate => $errors[0]];
        })->toArray();

        $fields = array_keys($required);
        $summary = implode(', ', array_map(fn($input) => ucfirst(str_replace('_', ' ', $input)), $fields)) . ' is required';

        $response = array_merge($required, ['errors' => $summary]);

        throw new HttpResponseException(response()->json($response, 422));
    }
}
