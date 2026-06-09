<?php

namespace Javaabu\Cms\Tests\Unit\Media;

use Illuminate\Foundation\Auth\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Javaabu\Cms\Media\Media;
use Javaabu\Cms\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class MediaModelTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasTable('media')) {
            Schema::create('media', function (Blueprint $table) {
                $table->id();
                $table->nullableMorphs('model');
                $table->uuid('uuid')->nullable()->unique();
                $table->string('collection_name')->nullable();
                $table->string('name');
                $table->string('file_name')->nullable();
                $table->string('mime_type')->nullable();
                $table->string('disk')->nullable();
                $table->string('conversions_disk')->nullable();
                $table->unsignedBigInteger('size')->default(0);
                $table->json('manipulations')->nullable();
                $table->json('custom_properties')->nullable();
                $table->json('generated_conversions')->nullable();
                $table->json('responsive_images')->nullable();
                $table->unsignedInteger('order_column')->nullable();
                $table->json('translations')->nullable();
                $table->string('lang')->nullable();
                $table->boolean('hide_translation')->default(false);
                $table->timestamps();
            });
        }

        Route::get('/admin/media/{media}/edit', fn (Media $media) => $media->id)->name('admin.media.edit');
        Route::getRoutes()->refreshNameLookups();
    }

    #[Test]
    public function it_exposes_mime_based_accessors_and_type_filters(): void
    {
        $pdf = $this->createMedia([
            'name' => 'Annual report for testing truncation',
            'file_name' => 'report.pdf',
            'mime_type' => 'application/pdf',
        ]);
        $image = $this->createMedia([
            'name' => 'Homepage Banner',
            'file_name' => 'banner.png',
            'mime_type' => 'image/png',
        ]);

        $this->assertSame('pdf', $pdf->type_slug);
        $this->assertSame('zmdi zmdi-collection-pdf', $pdf->icon);
        $this->assertSame('fa fa-file-pdf', $pdf->web_icon);
        $this->assertSame('fal fa-file-pdf', $pdf->web_icon_light);
        $this->assertSame('Annual report f...', $pdf->short_name);
        $this->assertStringContainsString(route('admin.media.edit', $pdf), $pdf->admin_url);
        $this->assertSame([$pdf->id], Media::query()->hasType('pdf')->pluck('id')->all());
        $this->assertSame([$image->id], Media::query()->hasType('image')->pluck('id')->all());
    }

    #[Test]
    public function it_limits_user_visible_media_by_permissions_and_ownership(): void
    {
        config()->set('auth.guards.web_admin', ['driver' => 'session', 'provider' => 'users']);
        config()->set('auth.providers.users.model', User::class);

        $owned = $this->createMedia([
            'model_type' => MediaVisibilityUser::class,
            'model_id' => 7,
        ]);
        $other = $this->createMedia([
            'model_type' => MediaVisibilityUser::class,
            'model_id' => 9,
        ]);

        auth()->logout();
        $this->assertSame([], Media::query()->userVisible()->pluck('id')->all());

        auth()->setUser(new MediaVisibilityUser(7, ['create']));
        $this->assertSame([$owned->id], Media::query()->userVisible()->pluck('id')->all());

        auth()->setUser(new MediaVisibilityUser(7, ['create', 'edit_other_users_media']));
        $this->assertEqualsCanonicalizing([$owned->id, $other->id], Media::query()->userVisible()->pluck('id')->all());
    }

    #[Test]
    public function it_searches_by_name_and_exposes_member_and_raw_url_accessors(): void
    {
        Route::get('/member/media/{media}', fn (Media $media) => $media->id)->name('Member\.media.show');
        Route::getRoutes()->refreshNameLookups();

        $match = $this->createMedia([
            'name' => 'Annual Budget Report',
            'file_name' => 'budget.pdf',
            'mime_type' => 'application/pdf',
        ]);
        $this->createMedia([
            'name' => 'Team Photo',
            'file_name' => 'photo.png',
            'mime_type' => 'image/png',
        ]);

        $partial = \Mockery::mock(Media::class)->makePartial();
        $partial->shouldReceive('getUrl')->andReturn('https://cdn.example.test/files/budget.pdf');

        $this->assertSame([$match->id], Media::query()->search('Budget')->pluck('id')->all());
        $this->assertArrayHasKey('model', Media::query()->withRelations()->getEagerLoads());
        $this->assertSame('https://cdn.example.test/files/budget.pdf', $partial->url);
        $this->assertStringContainsString('/member/media/', $match->member_url);
        $this->assertStringContainsString('/member/media/', $match->getMemberLocalizedUrl());
    }

    #[Test]
    public function it_loads_and_caches_image_dimensions_when_missing(): void
    {
        $image = Mockery::mock();
        $image->shouldReceive('getWidth')->once()->andReturn(640);
        $image->shouldReceive('getHeight')->once()->andReturn(480);

        Mockery::mock('alias:Spatie\Image\Image')
            ->shouldReceive('load')
            ->once()
            ->andReturn($image);

        $media = Mockery::mock(Media::class)->makePartial();
        $media->shouldReceive('getUrl')->once()->andReturn('https://cdn.example.test/files/picture.png');
        $media->shouldReceive('hasCustomProperty')->with('width')->once()->andReturn(false);
        $media->shouldReceive('setCustomProperty')->with('width', 640)->once();
        $media->shouldReceive('setCustomProperty')->with('height', 480)->once();
        $media->shouldReceive('save')->once()->andReturn(true);
        $media->shouldReceive('getCustomProperty')->with('width')->once()->andReturn(640);

        $this->assertSame(640, $media->getWidthAttribute());
    }

    #[Test]
    public function it_loads_and_caches_image_height_when_missing(): void
    {
        $image = Mockery::mock();
        $image->shouldReceive('getWidth')->once()->andReturn(640);
        $image->shouldReceive('getHeight')->once()->andReturn(480);

        Mockery::mock('alias:Spatie\Image\Image')
            ->shouldReceive('load')
            ->once()
            ->andReturn($image);

        $media = Mockery::mock(Media::class)->makePartial();
        $media->shouldReceive('getUrl')->once()->andReturn('https://cdn.example.test/files/picture.png');
        $media->shouldReceive('hasCustomProperty')->with('height')->once()->andReturn(false);
        $media->shouldReceive('setCustomProperty')->with('width', 640)->once();
        $media->shouldReceive('setCustomProperty')->with('height', 480)->once();
        $media->shouldReceive('save')->once()->andReturn(true);
        $media->shouldReceive('getCustomProperty')->with('height')->once()->andReturn(480);

        $this->assertSame(480, $media->getHeightAttribute());
    }

    #[Test]
    public function it_uses_translation_and_tag_search_paths_for_non_sqlite_drivers(): void
    {
        $query = new class {
            public array $calls = [];

            public function translationsSearch($field, $search, $locale)
            {
                $this->calls[] = ['translationsSearch', $field, $search, $locale];
                return $this;
            }

            public function orWhere($field, $operator, $value)
            {
                $this->calls[] = ['orWhere', $field, $operator, $value];
                return $this;
            }

            public function orWhereHas($relation, $callback)
            {
                $this->calls[] = ['orWhereHas', $relation];
                $callback(new class($this) {
                    public function __construct(private object $owner) {}
                    public function search($search, $locale)
                    {
                        $this->owner->calls[] = ['tagSearch', $search, $locale];
                    }
                });
                return $this;
            }
        };

        \Illuminate\Support\Facades\DB::shouldReceive('connection->getDriverName')->once()->andReturn('mysql');

        $media = new class extends Media {
            public function tagWords()
            {
                return null;
            }
        };

        $media->scopeSearch($query, 'Budget', 'en');

        $this->assertSame(['translationsSearch', 'description', 'Budget', 'en'], $query->calls[0]);
        $this->assertSame(['orWhere', 'name', 'like', '%Budget%'], $query->calls[1]);
        $this->assertSame(['orWhereHas', 'tagWords'], $query->calls[2]);
        $this->assertSame(['tagSearch', 'Budget', 'en'], $query->calls[3]);
    }

    private function createMedia(array $attributes = []): Media
    {
        $media = new Media([
            'name' => $attributes['name'] ?? 'Default Media',
        ]);
        $media->file_name = $attributes['file_name'] ?? 'default.pdf';
        $media->mime_type = $attributes['mime_type'] ?? 'application/pdf';
        $media->disk = $attributes['disk'] ?? 'public';
        $media->conversions_disk = $attributes['conversions_disk'] ?? 'public';
        $media->size = $attributes['size'] ?? 1024;
        $media->manipulations = $attributes['manipulations'] ?? [];
        $media->custom_properties = $attributes['custom_properties'] ?? [];
        $media->generated_conversions = $attributes['generated_conversions'] ?? [];
        $media->responsive_images = $attributes['responsive_images'] ?? [];
        $media->collection_name = $attributes['collection_name'] ?? 'documents';

        $media->model_type = $attributes['model_type'] ?? MediaVisibilityUser::class;
        $media->model_id = $attributes['model_id'] ?? 1;
        $media->save();

        return $media;
    }
}

class MediaVisibilityUser extends User
{
    public function __construct(int $id = 0, private array $permissions = [])
    {
        parent::__construct();
        $this->id = $id;
    }

    public function can($abilities, $arguments = []): bool
    {
        return in_array($abilities, $this->permissions, true);
    }

    public function getMorphClass()
    {
        return static::class;
    }
}
