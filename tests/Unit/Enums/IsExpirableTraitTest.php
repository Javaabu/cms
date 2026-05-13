<?php

namespace Javaabu\Cms\Tests\Unit\Enums;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Javaabu\Cms\Enums\IsExpirable;
use Javaabu\Cms\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class IsExpirableTraitTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_handles_expiration_attributes_and_status_helpers(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-01-10 10:00:00'));

        $model = new ExpirableTestModel();
        $model->setExpireAtAttribute('2026-01-09 10:00:00');

        $this->assertTrue($model->getIsExpiredAttribute());
        $this->assertFalse($model->getNeverExpireAttribute());
        $this->assertSame('Expired', $model->getFormattedExpireAtAttribute());

        $model->setExpireAtAttribute(null);
        $this->assertFalse($model->getIsExpiredAttribute());
        $this->assertTrue($model->getNeverExpireAttribute());
        $this->assertSame('Does not expire', $model->expiredAtDiff());
    }

    #[Test]
    public function it_applies_expired_and_not_expired_scopes(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-01-10 10:00:00'));

        ExpirableTestModel::query()->create([
            'type' => 'alerts',
            'title' => 'Expired post',
            'slug' => 'expired-post',
            'lang' => 'en',
            'status' => 'published',
            'published_at' => '2026-01-09 10:00:00',
            'expire_at' => '2026-01-09 10:00:00',
        ]);

        ExpirableTestModel::query()->create([
            'type' => 'alerts',
            'title' => 'Active post',
            'slug' => 'active-post',
            'lang' => 'en',
            'status' => 'published',
            'published_at' => '2026-01-09 10:00:00',
            'expire_at' => '2026-01-12 10:00:00',
        ]);

        ExpirableTestModel::query()->create([
            'type' => 'alerts',
            'title' => 'No expiry draft',
            'slug' => 'no-expiry-draft',
            'lang' => 'en',
            'status' => 'draft',
            'published_at' => '2026-01-09 10:00:00',
            'expire_at' => null,
        ]);

        $this->assertSame(1, ExpirableTestModel::query()->expired()->count());
        $this->assertSame(2, ExpirableTestModel::query()->notExpired()->count());
        $this->assertSame(1, ExpirableTestModel::query()->hasStatus('expired')->count());
        $this->assertSame(2, ExpirableTestModel::query()->hasStatus('not_expired')->count());
        $this->assertSame(2, ExpirableTestModel::query()->hasStatus('published')->count());
        $this->assertSame(2, ExpirableTestModel::query()->isActive()->count());
        $this->assertSame(1, ExpirableTestModel::query()->isActive(false)->count());
    }
}

class ExpirableTestModel extends Model
{
    use IsExpirable;

    protected $table = 'posts';

    protected $guarded = [];

    public $timestamps = false;
}
