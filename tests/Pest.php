<?php

declare(strict_types=1);

use BBSLab\FilamentOkta\Tests\Boot\BarePanelTestCase;
use BBSLab\FilamentOkta\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(TestCase::class)->in('Feature', 'Unit');

// Boot-phase tests register their own panels, so they bind a dedicated TestCase
// (a subclass adding a plugin-less panel) — kept out of Feature to avoid two
// TestCases claiming the same folder.
uses(BarePanelTestCase::class, RefreshDatabase::class)->in('Boot');

// Browser tests (Pest v4 + Playwright) need a browser, so they are grouped and
// excluded from the default suite — run them with `composer test:browser`.
uses(TestCase::class)->group('browser')->in('Browser');
