#!/bin/bash

cd $(dirname "$0")
cd ../..

if [ "$TRAVIS_PHP_VERSION" = "7.1" ]
then
	composer require -q --no-interaction "satooshi/php-coveralls:*"
fi

composer install --no-interaction