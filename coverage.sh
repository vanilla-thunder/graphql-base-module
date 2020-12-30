#!/bin/bash

# Full installation of the shop
#composer update $DEFAULT_COMPOSER_FLAGS # TODO: This won't be needed on Travis. It is used for local testing.

mkdir ../shop-graphql
cd ../shop-graphql
#cp -r ../graphql-base/vendor/oxid-esales/oxideshop-ce/* ./ # TODO: Use `cp -r $TRAVIS_BUILD_DIR/vendor/oxid-esales/oxideshop-ce/* ./`
cp -r $TRAVIS_BUILD_DIR/vendor/oxid-esales/oxideshop-ce/* ./
composer update $DEFAULT_COMPOSER_FLAGS

#composer require oxid-esales/graphql-base:dev-coverage # TODO: Use `composer require oxid-esales/graphql-base:dev-$TRAVIS_BRANCH` on Travis
composer require oxid-esales/graphql-base:dev-$TRAVIS_BRANCH

# For remote code coverage report to work we need to install `c3.php` and require it in the eShops `bootstrap.php` file
composer require codeception/module-phpbrowser codeception/c3 --dev

#sed -i 's/<?php/<?php\n\nrequire(__DIR__ . "\/..\/c3.php");/' source/bootstrap.php

mkdir source/modules/oe/graphql-base/tests
cp -r ../graphql-base-module/tests/* source/modules/oe/graphql-base/tests/
cp ../graphql-base-module/source/config.inc.php source/config.inc.php

composer require codeception/module-rest --dev
composer require codeception/module-phpbrowser --dev

# prepare shop
mkdir -p source/tmp/
mkdir -p var/configuration

# prepare configuration

sed -i 's|<dbHost>|127.0.0.1|' source/config.inc.php
sed -i 's|<dbName>|oxideshop|' source/config.inc.php
sed -i 's|<dbUser>|root|' source/config.inc.php
sed -i 's|<dbPwd>||' source/config.inc.php
sed -i 's|<sShopURL>|http://127.0.0.1:8080|' source/config.inc.php
sed -i "s|'<sShopDir>'|__DIR__|" source/config.inc.php
sed -i "s|'<sCompileDir>'|__DIR__ . '/tmp'|" source/config.inc.php
sed -i "s|blSkipViewUsage = false|blSkipViewUsage = true|" source/config.inc.php

# start mysql and import
sudo sed -e 's|utf8_unicode_ci|latin1_general_ci|g; s|utf8|latin1|g' --in-place /etc/mysql/my.cnf
sudo service mysql restart

# start php built-in webserver
php -S 127.0.0.1:8080 -t ./ &

# wait for it ;-)
sleep 2;

vendor/bin/reset-shop
composer clearcache
composer update

php -S 127.0.0.1:8080 -t ./ &
sudo service mysql restart

echo '---------install config---------'
bin/oe-console oe:module:install-configuration source/modules/oe/graphql-base/
bin/oe-console oe:module:activate oe_graphql_base
echo '---------end install config---------'

sudo chmod +775 c3.php

# Try to run tests and then coverage
vendor/bin/codecept -c source/modules/oe/graphql-base/tests/ run
# vendor/bin/codecept -c source/modules/oe/graphql-base/tests/ run --coverage --coverage-html
# vendor/bin/runtests-codeception

echo '---- output of more ----'
more /home/travis/build/OXID-eSales/shop-graphql/source/modules/oe/graphql-base/tests/Codeception/_output/OxidEsales.GraphQL.Base.Tests.Codeception.Acceptance.GraphQLCest.testOpenShop.fail.html
