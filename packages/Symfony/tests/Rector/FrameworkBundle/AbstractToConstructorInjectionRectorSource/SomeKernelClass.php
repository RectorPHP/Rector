<?php declare(strict_types=1);

namespace Rector\Symfony\Tests\FrameworkBundle\AbstractToConstructorInjectionRectorSource;

use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\HttpKernel\Kernel;

final class SomeKernelClass extends Kernel
{
    public function registerBundles(): iterable
    {
        return [];
    }

    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
    }

    protected function build(ContainerBuilder $containerBuilder): void
    {
        $containerBuilder->register('some_service', 'stdClass');
        //Symfony\Component\Translation\TranslatorInterface  alias for "translator.data_collector"
        //translator                                         alias for "translator.data_collector"
        //translator.data_collector                          Symfony\Component\Translation\DataCollectorTranslator
    }

    public function getCacheDir()
    {
        return sys_get_temp_dir() . '/_tmp';
    }

    public function getLogDir()
    {
        return sys_get_temp_dir() . '/_tmp';
    }
}
