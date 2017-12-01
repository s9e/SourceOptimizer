#!/bin/bash

cd $(dirname "$0")
cd ../..

if [ "$TRAVIS_PHP_VERSION" = "7.1" ]
then
	./vendor/bin/phpunit --coverage-clover /tmp/clover.xml
else
	./vendor/bin/phpunit
fi