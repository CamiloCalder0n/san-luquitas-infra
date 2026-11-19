<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Las pruebas no necesitan los CSS/JS compilados (npm run build).
        $this->withoutVite();
    }
}
