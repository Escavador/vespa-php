#!/bin/bash
./vendor/squizlabs/php_codesniffer/bin/phpcs --standard=PSR12 -s -p --colors src/
./vendor/squizlabs/php_codesniffer/bin/phpcbf --standard=PSR12 src/
