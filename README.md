# vespa-php
[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE.md)
[![Build Status][ico-build-status]][link-travis]
[![Total Downloads][ico-downloads]][link-downloads]

## Introduction
PHP low-level client for Vespa. https://vespa.ai/

## Install

Via Composer
``` bash
composer require escavador/vespa-php
```
#### Config
To adjust the library, you can publish the config file to your project using:
```
php artisan vendor:publish --provider="Escavador\Vespa\VespaServiceProvider"
```
Configure variables in your .env file:
```
VESPA_HOST=localhost:8080
VESPA_BASIC_TOKEN=
```
## Change log

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.


[ico-version]: https://img.shields.io/packagist/v/escavador/vespa-php.svg?style=flat-square
[ico-license]: https://img.shields.io/badge/license-GPL3-brightgreen.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/escavador/vespa-php.svg?style=flat-square
[ico-build-status]: https://travis-ci.org/Escavador/vespa-php.svg?branch=master

[link-packagist]: https://packagist.org/packages/escavador/vespa-php
[link-downloads]: https://poser.pugx.org/escavador/vespa-php/downloads
[link-author]: https://github.com/escavador
[link-travis]: https://travis-ci.org/Escavador/vespa-php
