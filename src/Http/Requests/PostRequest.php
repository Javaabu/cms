<?php

namespace Javaabu\Cms\Http\Requests;

use Illuminate\Support\Str;
use Illuminate\Routing\Route;
use Illuminate\Validation\Rule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rules\Unique;
use Javaabu\Cms\Enums\PageStyles;
use Javaabu\Cms\Models\PostType;
use Javaabu\Helpers\Enums\PublishStatuses;
use Illuminate\Foundation\Http\FormRequest;
use Javaabu\Helpers\Media\AllowedMimeTypes;

class PostRequest extends FormRequest
{
//    use ForceEnglishErrorMessages;

    protected string $morph_class = 'post';

    /**
     * The base actions
     *
     * @var array
     */
    protected array $base_actions = [
        'publish',
        'reject',
        'draft',
    ];

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function getBaseActions(): array
    {
        return $this->base_actions;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var PostType $post_type */
        $post_type = $this->getRoutePostType();
        $model = $this->getRouteModel();

        $published_at = $this->input('published_at') ?? optional($model)->published_at;

        $rules = [
            'content'           => 'nullable|string',
            'excerpt'           => 'nullable|string|max:200',
            'status'            => 'in:' . implode(',', PublishStatuses::getKeys()),
            'published_at'      => 'nullable|date',
            'tags'              => 'array',
            'tags.*'            => 'string|max:255|required',
            'action'            => 'in:' . implode(',', $this->actions()),
            'featured_image'    => AllowedMimeTypes::getAttachmentValidationRule('image'),
            'hide_translations' => 'boolean',
            'recently_updated'  => 'boolean',
        ];

        $rules['title'] = 'string|max:500';
        $rules['slug'] = ['string', 'max:255'];

//        if ($post_type->hasFeature(PostTypeFeatures::CATEGORIES)) {
//            $rules['categories'] = 'array|ids_exist:categories,id,type_id,' . $post_type->categoryType->id;
//        }
//
//        if ($post_type->hasFeature(PostTypeFeatures::CITY)) {
//            $rules['city'] = 'nullable|exists:cities,id';
//        }

        //======================================================================
        // DOCUMENT ATTACHMENT RULES
        //======================================================================
        $rules['documents'] = 'array';
        $rules['documents.*'] = AllowedMimeTypes::getAttachmentValidationRule('document');

        //======================================================================
        // DOCUMENT NUMBER RULES
        //======================================================================
        $rules['document_no'] = 'nullable|string|max:255';

        //======================================================================
        // IMAGE GALLERY RULES
        // `used in Gallery Format Rules instead`
        //======================================================================
        // $rules['image_gallery'] = 'array';
        // $rules['image_gallery.*'] = AllowedMimeTypes::getValidationRule('image');

        //======================================================================
        // EXPIREABLE RULES
        //======================================================================
        $rules['expire_at'] = 'nullable|date|after:' . ($published_at ?: 'published_at');
        $rules['never_expire'] = 'nullable|boolean';

        //======================================================================
        // GALLERY FORMAT RULES
        //======================================================================
        $rules['format'] = 'in:' . implode(',', array_keys(AllowedMimeTypes::getAllowedTypes()));
        // $rules['video_url'] = 'nullable|url|string'; // Used in video link rules instead
        $rules['image_gallery'] = 'array';
        $rules['image_gallery.*'] = AllowedMimeTypes::getAttachmentValidationRule('image');

        //======================================================================
        // VIDEO LINK RULES
        //======================================================================
        $rules['video_url'] = 'nullable|url|string';

        //======================================================================
        // PAGE STYLE RULES
        //======================================================================
        $rules['page_style'] = 'nullable|in:' . implode(',', PageStyles::getKeys());
        $rules['sidebar_menu'] = [
            'nullable',
            'required_if:page_style,' . PageStyles::SIDEBAR->value,
            'exists:menus,id',
        ];

        if (! $model) {
            $rules['title'] .= '|required';
            $rules['slug'][] = 'required';
            if (config('cms.should_translate')) $rules['lang'] = 'required';
        }

        return $rules;
    }

    /**
     * Get the route post type
     *
     * @return Route|object|string
     */
    protected function getRoutePostType()
    {
        return $this->route('post_type');
    }

    /**
     * Get the route model
     *
     * @return Route|object|string
     */
    protected function getRouteModel()
    {
        return $this->route('post');
    }

    /**
     * Get the all the actions
     *
     * @return array
     */
    protected function actions(): array
    {
        return array_merge($this->baseActions(), $this->customActions());
    }

    /**
     * Get the base actions
     *
     * @return array
     */
    protected function baseActions(): array
    {
        return $this->base_actions;
    }

    /**
     * Get the custom actions
     *
     * @return array
     */
    protected function customActions(): array
    {
        return property_exists($this, 'custom_actions') ? $this->custom_actions : [];
    }

    /**
     * Get the model table name
     *
     * @return string
     */
    protected function tableName(): string
    {
        return property_exists($this, 'table_name') ? $this->table_name : Str::plural($this->morphClass());
    }

    /**
     * Get the model type
     *
     * @return string
     */
    protected function morphClass(): string
    {
        return $this->morph_class;
    }

    /**
     * Get the model class
     *
     * @return string
     */
    protected function modelClass(): string
    {
        return Model::getActualClassNameForMorph($this->morphClass());
    }

    /**
     * Get the actions
     *
     * @return array
     */

    protected function postSlugUniqueRule(): Unique
    {
        return Rule::unique('posts', 'slug')->where('type', $this->getRoutePostType()->slug);
    }
}
