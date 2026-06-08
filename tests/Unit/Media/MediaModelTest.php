<?php

namespace Javaabu\Cms\Tests\Unit\Media;

use Illuminate\Foundation\Auth\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
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
