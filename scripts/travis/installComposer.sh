#!/bin/bash

cd $(dirname "$0")
cd ../..

composer install --dev --no-interaction

if [ "$TRAVIS_PHP_VERSION" = "5.6" ]
then
	composer require --dev -q --no-interaction "satooshi/php-coveralls:*"
fi