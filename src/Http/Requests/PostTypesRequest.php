<?php

namespace Javaabu\Cms\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PostTypesRequest extends FormRequest
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
        $post_type = $this->route('post_type');

        $rules = [
            'name'              => 'string|max:255',
            'singular_name'     => 'string|max:255',
            'slug'              => 'string|max:255',
            'icon'              => 'string|max:255',
            'category_type_id'  => 'nullable|exists:category_types,id',
            'features'          => 'nullable|array',
            'og_description'    => 'nullable|string|max:500',
            'order_column'      => 'nullable|integer|between:0,9999999',
            'hide_translation'  => 'boolean',
        ];

        // On create, make required fields
        if (! $post_type) {
            $rules['name'] .= '|required';
            $rules['singular_name'] .= '|required';
            $rules['slug'] .= '|required';
            $rules['icon'] .= '|required';
        }

        return $rules;
    }
}
