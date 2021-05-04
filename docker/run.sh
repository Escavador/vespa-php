#!/bin/bash
# halt on any error
set -e

php-fpm

exec "$@"
