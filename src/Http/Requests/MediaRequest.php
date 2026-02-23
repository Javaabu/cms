<?php

namespace Javaabu\Cms\Http\Requests;

use Javaabu\Cms\Media\AllowedMimeTypes;
use Illuminate\Foundation\Http\FormRequest;

class MediaRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        if (if_route_pattern(['member.*'])) {
            $media = $this->route('member_media');
        } else {
            $media = $this->route('media');
        }

        $rules = [
            'file'        => [
                'mimetypes:' . AllowedMimeTypes::getAllowedMimeTypesString($this->input('type')),
                'max:' . ($this->input('type') != 'image' ? get_setting('max_upload_file_size') : get_setting('max_image_file_size')),
            ],
            'type'        => 'nullable|string|in:' . implode(',', AllowedMimeTypes::getAllowedTypes()),
            'name'        => 'string|max:255',
            'description' => 'nullable|string',
            'tags'        => 'array',
            'tags.*'      => 'string|max:255|required',
        ];

        if ($media) {
            //
        } else {
            $rules['file'][] = 'required';
        }

        return $rules;
    }
}





