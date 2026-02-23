<x-forms::card :title="__('Excerpt')">
    <x-forms::textarea name="excerpt" :default="$excerpt ?? old('excerpt')" class="lang auto-size text-counter"/>
</x-forms::card>
