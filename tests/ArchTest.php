<?php

declare(strict_types=1);

arch('no debugging helpers are left behind')
    ->expect(['dd', 'dump', 'ray', 'var_dump', 'dexit'])
    ->not->toBeUsed();

arch('the whole package declares strict types')
    ->expect('BBSLab\FilamentOkta')
    ->toUseStrictTypes();

arch('no class in the package is declared final')
    ->expect('BBSLab\FilamentOkta')
    ->not->toBeFinal();

arch('the package never depends on test or workbench code')
    ->expect('BBSLab\FilamentOkta')
    ->not->toUse(['Workbench', 'BBSLab\FilamentOkta\Tests']);
