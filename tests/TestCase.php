<?php

namespace Tests;

use App\Modules\Shipping\Application\Port\ShippingQuoteGateway;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Tests\Fakes\FakeShippingQuoteGateway;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->app->bind(ShippingQuoteGateway::class, FakeShippingQuoteGateway::class);
    }
}
