# Rector - Upgrade your Legacy App to Modern Codebase
[![All Contributors](https://img.shields.io/badge/all_contributors-0-orange.svg?style=flat-square)](#contributors)

Rector is a **rec**onstruc**tor** tool - it does **instant upgrades** and **instant refactoring** of your code.
Why doing it manually if 80% Rector can handle for you?

[![Build Status](https://img.shields.io/travis/rectorphp/rector/master.svg?style=flat-square)](https://travis-ci.org/rectorphp/rector)
[![Coverage Status](https://img.shields.io/coveralls/rectorphp/rector/master.svg?style=flat-square)](https://coveralls.io/github/rectorphp/rector?branch=master)
[![Downloads](https://img.shields.io/packagist/dt/rector/rector.svg?style=flat-square)](https://packagist.org/packages/rector/rector)


![Rector-showcase](docs/images/rector-showcase.gif)

<br>

## Sponsors

Rector grows faster with your help, the more you help the more work it saves you.
Check out [Rector's Patreon](https://www.patreon.com/rectorphp). One-time donation is welcomed [trough PayPal](https://www.paypal.me/rectorphp).

<br>

## Open-Source First

Rector **instantly upgrades and instantly refactors PHP code of your application**. It covers many open-source projects and PHP changes itself:

<br>

<p align="center">
    <a href="/config/level/php"><img src="/docs/images/php.png"></a>
    <img src="/docs/images/space.png" width=20>
    <a href="/config/level/cakephp"><img src="/docs/images/cakephp.png"></a>
    <img src="/docs/images/space.png" width=20>
    <a href="/config/level/symfony"><img src="/docs/images/symfony.png"></a>
    <img src="/docs/images/space.png" width=20>
    <a href="/config/level/symfony"><img src="/docs/images/easy-admin.png"></a>
    <img src="/docs/images/space.png" width=20>
    <a href="/config/level/sylius"><img src="/docs/images/sylius.png"></a>
    <img src="/docs/images/space.png" width=20>
    <a href="/config/level/phpunit"><img src="/docs/images/phpunit.jpg"></a>
    <img src="/docs/images/space.png" width=20>
    <a href="/config/level/twig"><img src="/docs/images/twig.png"></a>
    <img src="/docs/images/space.png" width=20>
    <a href="/config/level/laravel"><img src="/docs/images/laravel.png"></a>
    <img src="/docs/images/space.png" width=20>
    <a href="/config/level/silverstripe"><img src="/docs/images/silverstripe.jpg"></a>
</p>

<br>

## What can Rector do for You?

- Rename classes, methods, properties, namespaces or constants
- Complete [parameter, var or return type declarations](https://www.tomasvotruba.cz/blog/2019/01/03/how-to-complete-type-declarations-without-docblocks-with-rector/) based on static analysis of your code
- Upgrade your code from PHP 5.3 to PHP 7.4
- [Migrate your project from Nette to Symfony](https://www.tomasvotruba.cz/blog/2019/02/21/how-we-migrated-from-nette-to-symfony-in-3-weeks-part-1/)
- [Complete PHP 7.4 property type declarations](https://www.tomasvotruba.cz/blog/2018/11/15/how-to-get-php-74-typed-properties-to-your-code-in-few-seconds/)
- [Turn Laravel static to Dependency Injection](https://www.tomasvotruba.cz/blog/2019/03/04/how-to-turn-laravel-from-static-to-dependency-injection-in-one-day/)
- And much more...

...**look at overview of [all available Rectors](/docs/AllRectorsOverview.md)** with before/after diffs and configuration examples. You can use them to build your own sets.

## Install

```bash
composer require rector/rector --dev
```

**Do you have conflicts on `composer require` or on run?**

- use [Docker image](#run-rector-in-docker) or
- install [prefixed version](https://github.com/rectorphp/rector-prefixed) with isolated dependencies (currently [looking for maintainer](https://github.com/rectorphp/prefixer/issues/1))

### Extra Autoloading

Rector relies on project and autoloading of its classes. To specify own autoload file, use `--autoload-file` option:

```bash
vendor/bin/rector process ../project --autoload-file ../project/vendor/autoload.php
```

Or make use of `rector.yaml` config:

```yaml
# rector.yaml
parameters:
    autoload_paths:
        - 'vendor/squizlabs/php_codesniffer/autoload.php'
        - 'vendor/project-without-composer'
```

## Exclude Paths and Rectors

You can also **exclude files or directories** (with regex or [fnmatch](http://php.net/manual/en/function.fnmatch.php)):

```yaml
# rector.yaml
parameters:
    exclude_paths:
        - '*/src/*/Tests/*'
```

Do you want to use whole set, except that one rule? Exclude it:

```yaml
# rector.yaml
parameters:
    exclude_rectors:
        - 'Rector\CodeQuality\Rector\If_\SimplifyIfReturnBoolRector'
```

By default Rector uses language features of your PHP version. If you you want to use different PHP version than your system, put it in config:

```yaml
parameters:
    php_version_features: '7.2' # your version 7.3
```

## Running Rector

### A. Prepared Sets

Featured open-source projects have **prepared sets**. You'll find them in [`/config/level`](/config/level) or by calling:

```bash
vendor/bin/rector levels
```

Let's say you pick `symfony40` level and you want to upgrade your `/src` directory:

```bash
# show known changes in Symfony 4.0
vendor/bin/rector process src --level symfony40 --dry-run
```

```bash
# apply
vendor/bin/rector process src --level symfony40
```

### B. Custom Sets

1. Create `rector.yaml` with desired Rectors:

    ```yaml
    services:
        Rector\Rector\Architecture\DependencyInjection\AnnotatedPropertyInjectToConstructorInjectionRector:
            $annotation: "inject"
    ```

2. Run on your `/src` directory:

    ```bash
    vendor/bin/rector process src --dry-run
    # apply
    vendor/bin/rector process src
    ```

## How to Apply Coding Standards?

AST that Rector uses doesn't deal with coding standards very well, so it's better to let coding standard tools do that. Your project doesn't have one? Rector ships with [EasyCodingStandard](https://github.com/Symplify/EasyCodingStandard) set that covers namespaces import, 1 empty line between class elements etc.

Just use `--with-style` option to handle these basic cases:

```bash
vendor/bin/rector process src --with-style
```

## 3 Steps to Create Own Rector

First, make sure it's not covered by [any existing Rectors yet](/docs/AllRectorsOverview.md).

Let's say we want to **change method calls from `set*` to `change*`**.

```diff
 $user = new User();
-$user->setPassword('123456');
+$user->changePassword('123456');
```

### 1. Create New Rector and Implement Methods

Create class that extends [`Rector\Rector\AbstractRector`](/src/Rector/AbstractRector.php). It has useful methods like checking node type and name. Just run `$this->` and let PHPStorm show you all possible methods.

```php
<?php declare(strict_types=1);

namespace App\Rector;

use Nette\Utils\Strings;
use PhpParser\Node;
use PhpParser\Node\Identifier;
use PhpParser\Node\Expr\MethodCall;
use Rector\Rector\AbstractRector;
use Rector\RectorDefinition\CodeSample;
use Rector\RectorDefinition\RectorDefinition;

final class MyFirstRector extends AbstractRector
{
    public function getDefinition(): RectorDefinition
    {
        // what does this do?
        // minimalistic before/after sample - to explain in code
        return new RectorDefinition('Change method calls from set* to change*.', [
            new CodeSample('$user->setPassword("123456");', '$user->changePassword("123456");')
        ]);
    }

    /**
     * @return string[]
     */
    public function getNodeTypes(): array
    {
        // what node types we look for?
        // pick any node from https://github.com/rectorphp/rector/blob/master/docs/NodesOverview.md
        return [MethodCall::class];
    }

    /**
     * @param MethodCall $node - we can add "MethodCall" type here, because only this node is in "getNodeTypes()"
     */
    public function refactor(Node $node): ?Node
    {
        // we only care about "set*" method names
        if (! $this->isName($node, 'set*')) {
            // return null to skip it
            return null;
        }

        $methodCallName = $this->getName($node);
        $newMethodCallName = Strings::replace($methodCallName, '#^set#', 'change');

        $node->name = new Identifier($newMethodCallName);

        // return $node if you modified it
        return $node;
    }
}
```

### 2. Register it

```yaml
# rector.yaml
services:
    App\Rector\MyFirstRector: ~
```

### 3. Let Rector Refactor Your Code

```bash
# see the diff first
vendor/bin/rector process src --dry-run

# if it's ok, apply
vendor/bin/rector process src
```

That's it!

## More Detailed Documentation

- **[All Rectors Overview](/docs/AllRectorsOverview.md)**
- [How Rector Works?](/docs/HowItWorks.md)
- [Nodes Overview](/docs/NodesOverview.md)

## How to Contribute

Just follow 3 rules:

- **1 feature per pull-request**
- **New feature needs tests**
- Tests, coding standards and PHPStan **checks must pass**:

    ```bash
    composer complete-check
    ```

    Don you need to fix coding standards? Run:

    ```bash
    composer fix-cs
    ```

We would be happy to merge your feature then.

## Run Rector in Docker

With this command, you can process your project with rector from docker:

```bash
docker run -v $(pwd):/project rector/rector:latest process /project/src --level symfony40 --dry-run

# Note that a volume is mounted from `pwd` into `/project` which can be accessed later.
```

Using `rector.yaml`:

```bash
docker run -v $(pwd):/project rector/rector:latest process /project/app --config /project/rector.yaml --autoload-file /project/vendor/autoload.php --dry-run
```

### Community Packages

Do you use Rector to upgrade your code? Share it here:

- [drupal8-rector/drupal8-rector](https://github.com/drupal8-rector/drupal8-rector) by @mxr576

## Contributors ✨

Thanks goes to these wonderful people ([emoji key](https://allcontributors.org/docs/en/emoji-key)):

<!-- ALL-CONTRIBUTORS-LIST:START - Do not remove or modify this section -->
<!-- prettier-ignore -->
<!-- ALL-CONTRIBUTORS-LIST:END -->

This project follows the [all-contributors](https://github.com/all-contributors/all-contributors) specification. Contributions of any kind welcome!