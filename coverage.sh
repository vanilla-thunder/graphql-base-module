#!/bin/bash

# Full installation of the shop
composer update $DEFAULT_COMPOSER_FLAGS # TODO: This won't be needed on Travis. It is used for local testing.

mkdir ../shop-graphql
cd ../shop-graphql
cp -r ../graphql-base/vendor/oxid-esales/oxideshop-ce/* ./ # TODO: Use `cp -r $TRAVIS_BUILD_DIR/vendor/oxid-esales/oxideshop-ce/* ./`
composer update $DEFAULT_COMPOSER_FLAGS

composer require oxid-esales/graphql-base:dev-coverage # TODO: Use `composer require oxid-esales/graphql-base:dev-$TRAVIS_BRANCH` on Travis

# For remote code coverage report to work we need to install `c3.php` and require it in the eShops `bootstrap.php` file
composer require codeception/module-phpbrowser codeception/c3 --dev

sed -i 's/<?php/<?php\n\nrequire(__DIR__ . "\/..\/c3.php");/' source/bootstrap.php

# Try to run tests and then coverage
vendor/bin/codecept -c source/modules/oe/graphql-base/tests/ run
#vendor/bin/codecept -c source/modules/oe/graphql-base/tests/ run --coverage --coverage-html
