<?php

namespace Javaabu\Cms\Media\Attachment;

use Closure;
use Javaabu\Cms\Media\Attachment\Attachment;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Collection as DbCollection;
use Javaabu\Cms\Media\Attachment\HasAttachments\HasAttachments;

class AttachmentRepository
{
    /** @var Attachment */
    protected $model;

    /**
     * @param Attachment $model
     */
    public function __construct(Attachment $model)
    {
        $this->model = $model;
    }

    /**
     * Get all attachments in the collection.
     *
     * @param HasAttachments $model
     * @param string $collectionName
     * @param array|callable $filter
     *
     * @return Collection
     */
    public function getCollection(HasAttachments $model, string $collectionName, $filter = []): Collection
    {
        return $this->applyFilterToAttachmentCollection($model->loadAttachments($collectionName), $filter);
    }

    /**
     * Apply given filters on media.
     *
     * @param Collection $attachments
     * @param array|callable $filter
     *
     * @return Collection
     */
    protected function applyFilterToAttachmentCollection(Collection $attachments, $filter): Collection
    {
        if (is_array($filter)) {
            $filter = $this->getDefaultFilterFunction($filter);
        }

        return $attachments->filter($filter);
    }

    /**
     * Convert the given array to a filter function.
     *
     * @param $filters
     *
     * @return Closure
     */
    protected function getDefaultFilterFunction(array $filters): Closure
    {
        return function (Attachment $attachment) use ($filters) {
            $media = $attachment->media;

            foreach ($filters as $property => $value) {
                if (! Arr::has($media->custom_properties, $property)) {
                    return false;
                }

                if (Arr::get($media->custom_properties, $property) !== $value) {
                    return false;
                }
            }

            return true;
        };
    }

    public function all(): DbCollection
    {
        return $this->model->all();
    }

    public function getByModelType(string $modelType): DbCollection
    {
        return $this->model->where('model_type', $modelType)->get();
    }

    public function getByIds(array $ids): DbCollection
    {
        return $this->model->whereIn('id', $ids)->get();
    }

    public function getByModelTypeAndCollectionName(string $modelType, string $collectionName): DbCollection
    {
        return $this->model
            ->where('model_type', $modelType)
            ->where('collection_name', $collectionName)
            ->get();
    }

    public function getByCollectionName(string $collectionName): DbCollection
    {
        return $this->model
            ->where('collection_name', $collectionName)
            ->get();
    }
}





