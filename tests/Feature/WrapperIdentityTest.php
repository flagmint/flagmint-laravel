<?php

declare(strict_types=1);

namespace Flagmint\Laravel\Tests\Feature;

use Flagmint\FlagmintClient;
use Flagmint\Laravel\Package;
use Flagmint\Laravel\Tests\TestCase;

final class WrapperIdentityTest extends TestCase
{
    public function testProviderPassesLaravelWrapperInfo(): void
    {
        /** @var FlagmintClient $client */
        $client = $this->app->make(FlagmintClient::class);
        $params = $client->getIdentity()->toQueryParams();

        $this->assertSame(Package::NAME, $params['wrapperName']);
        $this->assertSame(Package::VERSION, $params['wrapperVersion']);
        $this->assertSame('php', $params['platform']);
    }
}
