<?php

namespace Javaabu\Cms\Tests\Unit\Enums;

use Javaabu\Cms\Enums\Flags;
use Javaabu\Cms\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class FlagsTest extends TestCase
{
    #[Test]
    public function it_builds_labels_for_all_flag_cases(): void
    {
        $labels = Flags::labels();

        $this->assertCount(count(Flags::cases()), $labels);
        $this->assertArrayHasKey(Flags::US->value, $labels);
        $this->assertArrayHasKey(Flags::MV->value, $labels);
        $this->assertSame('United States', $labels[Flags::US->value]);
        $this->assertSame('Maldives', $labels[Flags::MV->value]);
    }

    #[Test]
    public function it_generates_flag_asset_urls_from_case_values(): void
    {
        $this->assertStringEndsWith('/us.svg', Flags::getFlagUrl('us'));
        $this->assertStringEndsWith('/mv.svg', Flags::getFlagUrl(Flags::MV->value));
    }
}

