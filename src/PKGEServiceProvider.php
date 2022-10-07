<?php

declare(strict_types=1);

namespace ParcelTrap\PKGE;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Support\ServiceProvider;
use ParcelTrap\Contracts\Factory;
use ParcelTrap\ParcelTrap;

class PKGEServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        /** @var ParcelTrap $factory */
        $factory = $this->app->make(Factory::class);

        $factory->extend(PKGE::IDENTIFIER, function () {
            /** @var Repository $config */
            $config = $this->app->make(Repository::class);

            return new PKGE(
                /** @phpstan-ignore-next-line */
                apiKey: (string) $config->get('parceltrap.drivers.pkge.api_key'),
            );
        });
    }
}
