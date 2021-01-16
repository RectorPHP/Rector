<?php

declare(strict_types=1);

use Rector\Composer\Rector\ChangePackageVersionRector;
use Rector\Composer\Rector\RemovePackageRector;
use Rector\Composer\ValueObject\PackageAndVersion;
use Rector\Renaming\Rector\MethodCall\RenameMethodRector;
use Rector\Renaming\Rector\Name\RenameClassRector;
use Rector\Renaming\Rector\StaticCall\RenameStaticMethodRector;
use Rector\Renaming\ValueObject\MethodCallRename;
use Rector\Renaming\ValueObject\RenameStaticMethod;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symplify\SymfonyPhpConfig\ValueObjectInliner;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();

    $services->set(RenameClassRector::class)->call('configure', [[
        RenameClassRector::OLD_TO_NEW_CLASSES => [
            // https://github.com/nette/application/compare/v3.0.7...v3.1.0
            'Nette\Application\IRouter' => 'Nette\Routing\Router',  // TODO not sure about this, it is not simple rename, Nette\Routing\Router already exists in nette/routing
            'Nette\Application\IResponse' => 'Nette\Application\Response',
            'Nette\Application\UI\IRenderable' => 'Nette\Application\UI\Renderable',
            'Nette\Application\UI\ISignalReceiver' => 'Nette\Application\UI\SignalReceiver',
            'Nette\Application\UI\IStatePersistent' => 'Nette\Application\UI\StatePersistent',
            'Nette\Application\UI\ITemplate' => 'Nette\Application\UI\Template',
            'Nette\Application\UI\ITemplateFactory' => 'Nette\Application\UI\TemplateFactory',
            'Nette\Bridges\ApplicationLatte\ILatteFactory' => 'Nette\Bridges\ApplicationLatte\LatteFactory',

            // https://github.com/nette/bootstrap/compare/v3.0.2...v3.1.0
            'Nette\Configurator' => 'Nette\Bootstrap\Configurator',

            // https://github.com/nette/caching/compare/v3.0.2...v3.1.0
            'Nette\Caching\IBulkReader' => 'Nette\Caching\BulkReader',
            'Nette\Caching\IStorage' => 'Nette\Caching\Storage',
            'Nette\Caching\Storages\IJournal' => 'Nette\Caching\Storages\Journal',

            // https://github.com/nette/database/compare/v3.0.7...v3.1.1
            'Nette\Database\ISupplementalDriver' => 'Nette\Database\Driver',
            'Nette\Database\IConventions' => 'Nette\Database\Conventions',
            'Nette\Database\Context' => 'Nette\Database\Explorer',
            'Nette\Database\IRow' => 'Nette\Database\Row',
            'Nette\Database\IRowContainer' => 'Nette\Database\ResultSet',
            'Nette\Database\Table\IRow' => 'Nette\Database\Table\ActiveRow',
            'Nette\Database\Table\IRowContainer' => 'Nette\Database\Table\Selection',

            // https://github.com/nette/forms/compare/v3.0.7...v3.1.0-RC2
            'Nette\Forms\IControl' => 'Nette\Forms\Control',
            'Nette\Forms\IFormRenderer' => 'Nette\Forms\FormRenderer',
            'Nette\Forms\ISubmitterControl' => 'Nette\Forms\SubmitterControl',

            // https://github.com/nette/mail/compare/v3.0.1...v3.1.5
            'Nette\Mail\IMailer' => 'Nette\Mail\Mailer',

            // https://github.com/nette/security/compare/v3.0.5...v3.1.2
            'Nette\Security\IAuthorizator' => 'Nette\Security\Authorizator',
            'Nette\Security\Identity' => 'Nette\Security\SimpleIdentity',
            'Nette\Security\IResource' => 'Nette\Security\Resource',
            'Nette\Security\IRole' => 'Nette\Security\Role',

            // https://github.com/nette/utils/compare/v3.1.4...v3.2.1
            'Nette\Utils\IHtmlString' => 'Nette\HtmlStringable',
            'Nette\Localization\ITranslator' => 'Nette\Localization\Translator',

            // https://github.com/nette/latte/compare/v2.5.5...v2.9.2
            'Latte\ILoader' => 'Latte\Loader',
            'Latte\IMacro' => 'Latte\Macro',
            'Latte\Runtime\IHtmlString' => 'Latte\Runtime\HtmlStringable',
            'Latte\Runtime\ISnippetBridge' => 'Latte\Runtime\SnippetBridge',
        ],
    ]]);

    $services->set(RenameMethodRector::class)->call('configure', [[
        RenameMethodRector::METHOD_CALL_RENAMES => ValueObjectInliner::inline([
            // https://github.com/nette/caching/commit/60281abf366c4ab76e9436dc1bfe2e402db18b67
            new MethodCallRename('Nette\Caching\Cache', 'start', 'capture'),
            // https://github.com/nette/forms/commit/faaaf8b8fd3408a274a9de7ca3f342091010ad5d
            new MethodCallRename('Nette\Forms\Container', 'addImage', 'addImageButton'),
            // https://github.com/nette/utils/commit/d0427c1811462dbb6c503143eabe5478b26685f7
            new MethodCallRename('Nette\Utils\Arrays', 'searchKey', 'getKeyOffset'),
        ]),
    ]]);

    $services->set(RenameStaticMethodRector::class)
        ->call('configure', [[
            RenameStaticMethodRector::OLD_TO_NEW_METHODS_BY_CLASSES => ValueObjectInliner::inline([
                // https://github.com/nette/utils/commit/8a4b795acd00f3f6754c28a73a7e776b60350c34
                new RenameStaticMethod('Nette\Utils\Callback', 'closure', 'Closure', 'fromCallable'),
            ]),
        ]]);

    // TODO change $router[] = new Router() to $router->addRoute() because of deprecated flags

    // TODO Presenter->getContext() and Presenter->context is deprecated

    $services->set(ChangePackageVersionRector::class)
        ->call('configure', [[
            ChangePackageVersionRector::PACKAGES_AND_VERSIONS => ValueObjectInliner::inline([
                // meta package
                new PackageAndVersion('nette/nette', '^3.1'),
                // https://github.com/nette/nette/blob/v3.0.0/composer.json vs https://github.com/nette/nette/blob/v3.1.0/composer.json
                new PackageAndVersion('nette/application', '^3.1'),
                new PackageAndVersion('nette/bootstrap', '^3.1'),
                new PackageAndVersion('nette/caching', '^3.1'),
                new PackageAndVersion('nette/database', '^3.1'),
                new PackageAndVersion('nette/di', '^3.0'),
                new PackageAndVersion('nette/finder', '^2.5'),
                new PackageAndVersion('nette/forms', '3.1.0-RC2'),  // TODO change when 3.1 will be released
                new PackageAndVersion('nette/http', '^3.1'),
                new PackageAndVersion('nette/mail', '^3.1'),
                new PackageAndVersion('nette/php-generator', '^3.5'),
                new PackageAndVersion('nette/robot-loader', '^3.3'),
                new PackageAndVersion('nette/safe-stream', '^2.4'),
                new PackageAndVersion('nette/security', '^3.1'),
                new PackageAndVersion('nette/tokenizer', '^3.0'),
                new PackageAndVersion('nette/utils', '^3.2'),
                new PackageAndVersion('latte/latte', '^2.9'),
                new PackageAndVersion('tracy/tracy', '^2.8'),
            ]),
        ]]);

    $services->set(RemovePackageRector::class)
        ->call('configure', [[
            RemovePackageRector::PACKAGE_NAMES => [
                'nette/component-model',
                'nette/neon'
            ],
        ]]);
};
