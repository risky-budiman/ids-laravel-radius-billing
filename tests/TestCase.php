<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Cache statis hak akses (aturan rute & pengecualian per role) tidak boleh terbawa antar tes
        \App\Support\RouteAccess::flush();
    }
}
