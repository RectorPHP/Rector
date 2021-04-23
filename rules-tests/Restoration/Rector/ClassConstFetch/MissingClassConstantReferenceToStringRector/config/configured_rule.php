<?php

declare(strict_types=1);

use Rector\Restoration\Rector\ClassConstFetch\MissingClassConstantReferenceToStringRector;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();
    $services->set(MissingClassConstantReferenceToStringRector::class);
};
