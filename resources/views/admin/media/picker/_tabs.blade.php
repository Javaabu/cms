@php
    $tabs = [
        'select-media' => [
            'title' => _d('Select from Media Library'),
        ],
    ];

    if (auth()->user()->can('edit_media')) {
        $tabs['new-media'] = [
            'title' => _d('Upload New')
        ];
    }
@endphp

<div class="card">
    <ul class="nav nav-tabs nav-fill" role="tablist">
        @foreach($tabs as $key => $tab)
            <li class="nav-item">
                <a href="#{{ $key }}" class="nav-link{{ $active == $key ? ' active' : '' }}" data-toggle="tab"
                   role="tab">
                    {{ $tab['title']  }}
                </a>
            </li>
        @endforeach
    </ul>
</div>


