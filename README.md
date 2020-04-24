# phly/phly-opcache-preload

[![Build Status](https://travis-ci.com/phly/phly-opcache-preload.svg?branch=master)](https://travis-ci.com/phly/phly-opcache-preload)
[![Coverage Status](https://coveralls.io/repos/github/laminas/laminas-opcache-preload/badge.svg?branch=master)](https://coveralls.io/github/laminas/laminas-opcache-preload?branch=master)

This library provides CLI tooling for generating an opcache preload file.

> ## Proof of Concept
>
> This library is a proof of concept, based on the Laminas [Opcache
> Preloading for Mezzio and MVC RFC](https://discourse.laminas.dev/t/rfc-opache-preloading-for-mezzio-and-mvc/1442).
> It will eventually live in the Laminas organization. As such, use this for
> testing purposes only.

## Installation

Run the following to install this library:

```bash
$ composer config repositories.opcache vcs https://github.com/phly/phly-opcache-preload.git
$ composer require "phly/phly-opcache-preload:dev-master@dev"
```

## Usage

Get usage information after installation via the following commands:

```bash
$ ./vendor/bin/laminas help opcache:preload-generate
$ ./vendor/bin/laminas help opcache:preload-ini
```

Generally speaking, use this command to generate the preload file:

```bash
$ ./vendor/bin/laminas opcache:preload-generate
```

and this one to add it to a php.ini configuration file:

```bash
$ ./vendor/bin/laminas opcache:preload-ini > $PHP_INI_DIR/conf.d/999-preload.ini
```

<!--
## Documentation

Browse the documentation online at https://docs.laminas.dev/laminas-{component}/

-->

## Support

* [Issues](https://github.com/phly/phly-opcache-preload/issues/)
* [Forum](https://discourse.laminas.dev/t/rfc-opcache-preloading-for-mezzio-and-mvc/1442)
