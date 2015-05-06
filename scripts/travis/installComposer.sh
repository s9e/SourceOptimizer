#!/bin/bash

cd $(dirname "$0")
cd ../..

if [ "$TRAVIS_PHP_VERSION" = "5.6" ]
then
	composer require -q --no-interaction "satooshi/php-coveralls:*"
fi

composer install --no-interaction