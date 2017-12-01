#!/bin/bash

cd $(dirname "$0")

echo "Installing Composer"
./installComposer.sh

if [ "$TRAVIS_PHP_VERSION" = "7.1" ]
then
	echo "Installing Scrutinizer"
	./installScrutinizer.sh
elif [ "$TRAVIS_PHP_VERSION" != "nightly" ]
then
	echo "Removing XDebug"
	phpenv config-rm xdebug.ini
fi