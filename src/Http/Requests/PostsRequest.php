<?php

namespace Javaabu\Cms\Http\Requests;

use Javaabu\Cms\Models\PostType;
use Javaabu\Cms\Enums\PostTypeFeatures;
use Illuminate\Foundation\Http\FormRequest;

class PostsRequest extends FormRequest
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
        $post_type = $this->route('type');
        $post = $this->route('post');

        $rules = [
            'title'             => 'string|max:500',
            'slug'              => 'string|max:255',
            'content'           => 'nullable|string',
            'excerpt'           => 'nullable|string|max:500',
            'status'            => 'nullable|string|in:draft,published,pending',
            'published_at'      => 'nullable|date',
            'featured_image'    => 'nullable',
            'hide_translation'  => 'boolean',
            'menu_order'        => 'nullable|integer|between:0,9999999',
        ];

        // Categories validation if post type has categories feature
        if ($post_type && $post_type->hasFeature(PostTypeFeatures::CATEGORIES)) {
            if ($post_type->category_type_id) {
                $rules['categories'] = 'nullable|array';
                $rules['categories.*'] = 'exists:categories,id,type_id,' . $post_type->category_type_id;
            }
        }

        // Department validation if exists
        $rules['department'] = 'nullable|exists:departments,id';

        // Document number validation
        $rules['document_no'] = 'nullable|string|max:255';

        // Documents validation
        $rules['documents'] = 'nullable|array';
        $rules['documents.*'] = 'file|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx|max:51200';

        // Image gallery validation
        $rules['image_gallery'] = 'nullable|array';
        $rules['image_gallery.*'] = 'file|mimes:jpeg,jpg,png,gif,webp|max:10240';

        // Expiry validation
        $published_at = $this->input('published_at') ?? optional($post)->published_at;
        $rules['expire_at'] = 'nullable|date|after:' . ($published_at ?: 'published_at');
        $rules['never_expire'] = 'nullable|boolean';

        // Video link validation
        $rules['video_url'] = 'nullable|url|string|max:500';

        // Format validation
        $rules['format'] = 'nullable|string|in:photo,video,gallery';

        // Page style validation
        $rules['page_style'] = 'nullable|string|max:255';
        $rules['sidebar_menu'] = 'nullable|exists:menus,id';

        // Reference number validation
        $rules['ref_no'] = 'nullable|string|max:255';

        // Recently updated flag
        $rules['recently_updated'] = 'nullable|boolean';

        // On create, make required fields
        if (! $post) {
            $rules['title'] .= '|required';
            $rules['slug'] .= '|required';
        }

        return $rules;
    }
}
