<?php

declare(strict_types=1);

namespace ParcelTrap\PKGE\Tests;

use ParcelTrap\ParcelTrapServiceProvider;
use ParcelTrap\PKGE\PKGEServiceProvider;

class TestCase extends \Orchestra\Testbench\TestCase
{
    protected function getPackageProviders($app): array
    {
        return [ParcelTrapServiceProvider::class, PKGEServiceProvider::class];
    }
}
