#!/bin/sh -l

# local-prefix test

# show errors
set -e

# script fails if trying to access to an undefined variable
set -u


# functions
note()
{
    MESSAGE=$1;

    printf "\n";
    echo "[NOTE] $MESSAGE";
    printf "\n";
}


note "Starts"

# configure here
NESTED_DIRECTORY="rector-nested"
SCOPED_DIRECTORY="rector-scoped"



# ---------------------------



note "Coping root files to $NESTED_DIRECTORY directory"
rsync -av * "$NESTED_DIRECTORY" --quiet

# repace phpstan/phpstan with phpstan/phpstan-src
note "Replacing phpstan with phpstan-src"
composer remove phpstan/phpstan --no-update --working-dir "$NESTED_DIRECTORY"
composer config repositories.repo-name vcs https://github.com/phpstan/phpstan-src.git --working-dir "$NESTED_DIRECTORY"
composer require phpstan/phpstan-src:dev-master --no-update --working-dir "$NESTED_DIRECTORY"

note "Adding PHPStan dev dependencies"
composer require "phpdocumentor/reflection-docblock:dev-master#5.2 as 4.3.4" --no-update --working-dir "$NESTED_DIRECTORY"
composer require jetbrains/phpstorm-stubs:dev-master#b2402e4a525593f68ff46303dcc6bc625437276a --no-update --working-dir "$NESTED_DIRECTORY"

# phpdocumentor/reflection-docblock 4.3.4 is blocker on PHP 8, so we have to ignore php version
note "Running composer update without dev"
composer update --no-dev --no-progress --ansi --working-dir "$NESTED_DIRECTORY" --ignore-platform-req php

# downgrade phpstan code to from PHP 7.4 to 7.3
note "Downgrading PHPStan code from PHP 7.4 to 7.3"
# this will remove dependency on dev packages that are imported in phpstan.neon
rm "$NESTED_DIRECTORY/phpstan.neon"
# cd NESTED_DIRETORY is needed for phpstan-src required packages to be autoloaded
cd "$NESTED_DIRECTORY" && bin/rector process vendor/phpstan/phpstan-src/src --config ci/downgrade-phpstan-php74-rector.php --ansi

# Avoid Composer v2 platform checks (composer.json requires PHP 7.4+, but below we are running 7.3)
note "Disabling platform check"
composer config platform-check false


# composer update --no-dev --prefer-dist --ansi --working-dir rector-nested

# 2. scope it
# @todo temporary only no net + is already locally insatlled
note "Running scoper to $SCOPED_DIRECTORY"
wget https://github.com/humbug/php-scoper/releases/download/0.14.0/php-scoper.phar
cd "$NESTED_DIRECTORY" && php ../php-scoper.phar add-prefix bin config packages rules src templates vendor composer.json --output-dir "../$SCOPED_DIRECTORY" --config scoper.inc.php --force --ansi -vvv
cd ..

composer dump-autoload --working-dir "$SCOPED_DIRECTORY" --ansi --optimize --classmap-authoritative --no-dev

vendor/bin/package-scoper scope-composer-json "$SCOPED_DIRECTORY/composer.json" --ansi

# clean up
rm -rf "$NESTED_DIRECTORY"
