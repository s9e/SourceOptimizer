#!/bin/bash

cd $(dirname "$0")
cd ../..

if [ "$TRAVIS_PHP_VERSION" = "5.6" ]
then
	phpunit --coverage-clover /tmp/clover.xml
else
	phpunit
fi