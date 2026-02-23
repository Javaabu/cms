<div class="media-details card bg-gray text-white">
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <dl>
                    <dt>{{ _d('File Name:') }}</dt>
                    <dd>{{ $media->file_name }}</dd>
                </dl>

                <dl>
                    <dt>{{ _d('File Type:') }}</dt>
                    <dd>{{ $media->mime_type }}</dd>
                </dl>

                <dl>
                    <dt>{{ _d('File Size:') }}</dt>
                    <dd>{{ $media->human_readable_size }}</dd>
                </dl>
            </div>
            <div class="col-md-6">
                @if($media->type_slug == 'image')
                    @php
                        $image = \Spatie\Image\Image::load($media->getPath());
                        $width = $image->getWidth();
                        $height = $image->getHeight();
                    @endphp
                    <dl>
                        <dt>{{ _d('Dimensions:') }}</dt>
                        <dd>{{ _d(':width x :height', compact('width', 'height')) }}</dd>
                    </dl>
                @endif

                <dl>
                    <dt>{{ _d('Uploaded On:') }}</dt>
                    <dd>{{ $media->created_at ? $media->created_at->format('F j, Y \a\t H:i') : '-' }}</dd>
                </dl>

                <dl>
                    <dt>{{ $media->model instanceof App\Helpers\User\User ? _d('Uploaded By') : _d('Uploaded To') }}</dt>
                    <dd>{!! $media->model ? $media->model->admin_link : '-' !!}</dd>
                </dl>
            </div>
        </div>
    </div>
</div>


