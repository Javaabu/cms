<?php

namespace Javaabu\Cms\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CategoryTypesRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $category_type = $this->route('category_type');

        $rules = [
            'name'             => 'string|max:255',
            'singular_name'    => 'string|max:255',
            'slug'             => 'string|max:255',
            'hide_translation' => 'boolean',
        ];

        // On create, make required fields
        if (! $category_type) {
            $rules['name'] .= '|required';
            $rules['singular_name'] .= '|required';
            $rules['slug'] .= '|required';
        }

        return $rules;
    }
}
