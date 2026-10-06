<?php

declare(strict_types=1);

namespace Flagmint\Laravel\Tests\Feature;

use Flagmint\Laravel\Facades\Flagmint;
use Flagmint\Laravel\Tests\TestCase;
use Illuminate\Support\Facades\Blade;

final class FacadeAndBladeTest extends TestCase
{
    public function testFacadeBool(): void
    {
        $this->bindMockedClient();
        $this->assertTrue(Flagmint::bool('new-checkout', false, ['kind' => 'user', 'key' => 'u1']));
        $this->assertTrue(Flagmint::isEnabled('new-checkout', ['kind' => 'user', 'key' => 'u1']));
    }

    public function testBladeFeatureDirective(): void
    {
        $this->bindMockedClient();
        $html = Blade::render(
            <<<'BLADE'
@feature('new-checkout')
YES
@else
NO
@endfeature
BLADE
        );
        $this->assertStringContainsString('YES', $html);
    }

    public function testConfigDefaultsPublishedShape(): void
    {
        $this->assertSame('fm_test_key', config('flagmint.api_key'));
        $this->assertSame('memory', config('flagmint.cache.driver'));
    }
}
