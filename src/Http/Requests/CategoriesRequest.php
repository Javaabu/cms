<?php

namespace Javaabu\Cms\Http\Requests;

use Javaabu\Cms\Models\Category;
use Illuminate\Foundation\Http\FormRequest;

class CategoriesRequest extends FormRequest
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
        $type = $this->route('type');
        $type_id = $type->id ?? -1;

        $rules = [
            'name'             => 'string|max:255',
            'slug'             => 'string|max:255',
            'icon'             => 'nullable|string|max:255',
            'featured_image'   => 'nullable|file|mimes:jpeg,jpg,png,gif,webp|max:10240',
            'parent'           => 'nullable|exists:categories,id,type_id,' . $type_id,
            'hide_translation' => 'boolean',
            'order_column'     => 'nullable|integer|between:0,9999999',
        ];

        if ($category = $this->route('category')) {
            // prevent circular parent selection
            $not_in = Category::where('type_id', $type_id)
                              ->descendantsOf($category->id)
                              ->pluck('id')
                              ->all();

            // the current category can't be a parent of itself
            $not_in[] = $category->id;

            $rules['parent'] .= '|not_in:' . implode(',', $not_in);
        } else {
            $rules['name'] .= '|required';
            $rules['slug'] .= '|required';
        }

        return $rules;
    }
}
