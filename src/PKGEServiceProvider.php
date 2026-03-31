<?php

declare(strict_types=1);

namespace ParcelTrap\PKGE;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\ServiceProvider;
use ParcelTrap\Contracts\Factory;
use ParcelTrap\ParcelTrap;

class PKGEServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        /** @var ParcelTrap $factory */
        $factory = $this->app->make(Factory::class);

        $factory->extend(PKGE::IDENTIFIER, function (Container $container) {
            /** @var ConfigRepository $config */
            $config = $container->make(ConfigRepository::class);

            return new PKGE(
                /** @phpstan-ignore-next-line */
                apiKey: (string) $config->get('parceltrap.drivers.pkge.api_key'),
            );
        });
    }
}
