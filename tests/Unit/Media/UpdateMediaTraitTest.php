<?php

namespace Javaabu\Cms\Tests\Unit\Media;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Javaabu\Cms\Media\UpdateMedia;
use Javaabu\Cms\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class UpdateMediaTraitTest extends TestCase
{
    #[Test]
    public function update_single_media_replaces_existing_media_when_a_file_is_uploaded(): void
    {
        $file = UploadedFile::fake()->create('brochure.pdf', 10, 'application/pdf');

        $model = new FakeUpdateMediaModel();
        $request = Request::create('/media', 'POST');
        $request->files->set('brochure', $file);

        $result = $model->updateSingleMedia('brochure', $request);

        $this->assertSame('saved:brochure', $result);
        $this->assertSame(['brochure'], $model->clearedCollections);
        $this->assertSame([$file], $model->addedFiles);
        $this->assertCount(1, $model->generatedFileNames);
        $this->assertStringEndsWith('.pdf', $model->generatedFileNames[0]);
    }

    #[Test]
    public function update_single_media_clears_existing_media_when_the_field_is_present_but_empty(): void
    {
        $model = new FakeUpdateMediaModel();
        $request = Request::create('/media', 'POST', ['thumbnail' => null]);

        $result = $model->updateSingleMedia('thumbnail', $request);

        $this->assertSame(0, $result);
        $this->assertSame(['thumbnail'], $model->clearedCollections);
        $this->assertSame([], $model->addedFiles);
    }

    #[Test]
    public function update_single_media_returns_false_when_nothing_was_submitted_and_scope_has_media_applies_optional_filter(): void
    {
        $model = new FakeUpdateMediaModel();
        $request = Request::create('/media', 'POST');

        $this->assertFalse($model->updateSingleMedia('gallery', $request));

        $query = new FakeHasMediaQuery();
        $model->scopeHasMedia($query, 'documents');
        $this->assertSame('media', $query->relation);
        $this->assertSame('documents', $query->collectionName);

        $queryWithoutCollection = new FakeHasMediaQuery();
        $model->scopeHasMedia($queryWithoutCollection);
        $this->assertNull($queryWithoutCollection->collectionName);
    }
}

class FakeUpdateMediaModel
{
    use UpdateMedia;

    public array $clearedCollections = [];
    public array $addedFiles = [];
    public array $generatedFileNames = [];

    public function clearMediaCollection($collection): void
    {
        $this->clearedCollections[] = $collection;
    }

    public function addMedia($file): object
    {
        $this->addedFiles[] = $file;

        return new class ($this) {
            public function __construct(private FakeUpdateMediaModel $owner) {}

            public function usingFileName(string $fileName): self
            {
                $this->owner->generatedFileNames[] = $fileName;

                return $this;
            }

            public function toMediaCollection(string $collection): string
            {
                return "saved:{$collection}";
            }
        };
    }
}

class FakeHasMediaQuery
{
    public ?string $relation = null;
    public ?string $collectionName = null;

    public function whereHas(string $relation, callable $callback): self
    {
        $this->relation = $relation;
        $callback(new class ($this) {
            public function __construct(private FakeHasMediaQuery $owner) {}

            public function whereCollectionName(string $collectionName): void
            {
                $this->owner->collectionName = $collectionName;
            }
        });

        return $this;
    }
}
