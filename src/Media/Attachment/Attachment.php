<?php

namespace Javaabu\Cms\Media\Attachment;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Javaabu\Cms\Media\Attachment\Conversion\AttachmentConversionCollection;
use Javaabu\Cms\Media\Attachment\UrlGenerator\AttachmentUrlGeneratorFactory;
use Javaabu\Cms\Media\Media;
use Spatie\MediaLibrary\Conversions\Conversion;
use Spatie\MediaLibrary\MediaCollections\Models\Concerns\IsSorted;

class Attachment extends Model
{
    use IsSorted;

    protected $morphClass = 'attachment';

    /**
     * An attachment belongs to a media item
     *
     * @return BelongsTo
     */
    public function media()
    {
        return $this->belongsTo(Media::class);
    }

    /**
     * An attachment belongs to a model
     *
     * @return MorphTo
     */
    public function model()
    {
        return $this->morphTo('model');
    }

    /*
     * Get all the names of the registered media conversions.
     */
    public function getMediaConversionNames(): array
    {
        $conversions = AttachmentConversionCollection::createForAttachment($this);

        return $conversions->map(function (Conversion $conversion) {
            return $conversion->getName();
        })->toArray();
    }

    /**
     * Check if has a generated conversion
     *
     * @param string $conversionName
     * @return bool
     */
    public function hasGeneratedConversion(string $conversionName): bool
    {
        $media = $this->media;

        return $media ? $media->hasGeneratedConversion($conversionName) : false;
    }

    /**
     * Mark conversion as generated
     *
     * @param string $conversionName
     * @param bool $generated
     * @return Attachment
     */
    public function markAsConversionGenerated(string $conversionName, bool $generated): self
    {
        $media = $this->media;

        if ($media) {
            $media->markAsConversionGenerated($conversionName, $generated);
        }

        return $this;
    }

    /**
     * Get the path to the original media file.
     *
     * @param string $conversionName
     * @return string
     */
    public function getPath(string $conversionName = ''): string
    {
        $urlGenerator = AttachmentUrlGeneratorFactory::createForAttachment($this, $conversionName);

        return $urlGenerator->getPath();
    }

    /**
     * Get the url to the original media file.
     *
     * @param string $conversionName
     * @return string
     */
    public function getUrl(string $conversionName = ''): string
    {
        $urlGenerator = AttachmentUrlGeneratorFactory::createForAttachment($this, $conversionName);

        return $urlGenerator->getUrl();
    }

    /**
     * Get temporary url
     *
     * @param DateTimeInterface $expiration
     * @param string $conversionName
     * @param array $options
     * @return string
     */
    public function getTemporaryUrl(DateTimeInterface $expiration, string $conversionName = '', array $options = []): string
    {
        $urlGenerator = AttachmentUrlGeneratorFactory::createForAttachment($this, $conversionName);

        return $urlGenerator->getTemporaryUrl($expiration, $options);
    }
}
