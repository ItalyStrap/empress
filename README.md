# ItalyStrap Empress API

[![Build Status](https://travis-ci.org/ItalyStrap/empress.svg?branch=master)](https://travis-ci.org/ItalyStrap/empress)
[![Latest Stable Version](https://img.shields.io/packagist/v/italystrap/empress.svg)](https://packagist.org/packages/italystrap/empress)
[![Total Downloads](https://img.shields.io/packagist/dt/italystrap/empress.svg)](https://packagist.org/packages/italystrap/empress)
[![Latest Unstable Version](https://img.shields.io/packagist/vpre/italystrap/empress.svg)](https://packagist.org/packages/italystrap/empress)
[![License](https://img.shields.io/packagist/l/italystrap/empress.svg)](https://packagist.org/packages/italystrap/empress)
![PHP from Packagist](https://img.shields.io/packagist/php-v/italystrap/empress)

Config driven for [Auryn Injector](https://github.com/rdlowrey/auryn) the OOP way

In this library it is also included a bridge for the [Auryn\Injector](https://github.com/rdlowrey/auryn) and [ProxyManager](https://github.com/Ocramius/ProxyManager) for lazily initializes a "real" instance of the proxied class.

## Table Of Contents

* [Installation](#installation)
* [Basic Usage](#basic-usage)
* [Advanced Usage](#advanced-usage)
* [Contributing](#contributing)
* [License](#license)
* [Notes](#notes)
* [Credits](#credits)

## Installation

The best way to use this package is through Composer:

```CMD
composer require italystrap/empress
```
This package adheres to the [SemVer](http://semver.org/) specification and will be fully backward compatible between minor versions.

## Basic Usage

> For more information on how to use Auryn\Injector see the [Auryn README](https://github.com/rdlowrey/auryn/blob/master/README.md)

Keep in mind that `$injector->(SomeClass::class)` hides the word `new` and does the instantiation for you.

Do not use Injector as a service locator or your server will blown up.

```php

use ItalyStrap\Config\Config;
use ItalyStrap\Config\ConfigFactory;
use ItalyStrap\Config\ConfigInterface;
use ItalyStrap\Empress\AurynResolverInterface;
use ItalyStrap\Empress\AurynResolver;
use ItalyStrap\Empress\Extension;
use ItalyStrap\Empress\Injector;


```

## Advanced Usage

> TODO

## Contributing

All feedback / bug reports / pull requests are welcome.

## License

Copyright (c) 2019 Enea Overclokk, ItalyStrap

This code is licensed under the [MIT](LICENSE).

## Notes

*  Maintained under the [Semantic Versioning Guide](http://semver.org)

## Credits

* [`rdlowrey/auryn`](https://github.com/rdlowrey/auryn)
* [`brightnucleus/injector`](https://github.com/brightnucleus/injector)