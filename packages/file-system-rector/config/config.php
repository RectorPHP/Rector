<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $parameters = $containerConfigurator->parameters();

    $parameters->set('project_directory', null);

    $services = $containerConfigurator->services();

    $services->defaults()
        ->public()
        ->autowire();

    $services->load('Rector\FileSystemRector\\', __DIR__ . '/../src')
        ->exclude([__DIR__ . '/../src/Rector/**/*Rector.php']);
};
