<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /** Tests render pages without needing `npm run build` first. */
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }
}
