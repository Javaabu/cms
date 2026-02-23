<x-forms::checkbox name="never_expire" :default="! optional($model)->expire_at" :disabled="$is_translation" />

<div
    data-enable-section-checkbox="#never_expire"
    data-disable="true"
    data-hide-fields="true"
    style="display: none"
>
    <x-forms::datetime name="expire_at" :label="$type->getFeatureName(\Javaabu\Cms\Enums\PostTypeFeatures::EXPIREABLE)" :disabled="$is_translation" />
</div>
