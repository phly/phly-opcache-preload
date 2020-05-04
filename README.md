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

This package may be installed globally, or locally. We recommend installing
globally, as it will generally be used exactly once within an application to
generate the preload file, and then is no longer required.

To install globally:

```bash
$ composer global config repositories.opcache vcs https://github.com/phly/phly-opcache-preload.git
$ composer global require "phly/phly-opcache-preload:dev-master@dev"
```

> ### Add Composer to your $PATH
>
> To add the Composer global vendor binary directory to your path on Linux, Mac,
> and other *nix variants:
>
> ```bash
> export PATH=$(composer global config home)/vendor/bin:$PATH
> ```
>
> On Windows, follow [this tutorial](https://www.architectryan.com/2018/03/17/add-to-the-path-on-windows-10/).

To install locally:

```bash
$ composer config repositories.opcache vcs https://github.com/phly/phly-opcache-preload.git
$ composer require "phly/phly-opcache-preload:dev-master@dev"
```

## Usage

Get usage information after installation via the following commands:

```bash
$ phly-opcache-preload help generate:preload-file
$ phly-opcache-preload help generate:ini
```

Generally speaking, use this command to generate the preload file:

```bash
$ phly-opcache-preload generate:preload-file
```

and this one to add it to a php.ini configuration file:

```bash
$ phly-opcache-preload generate:ini > $PHP_INI_DIR/conf.d/999-preload.ini
```

> ### Local usage
>
> If you installed locally, use `./vendor/bin/phly-opcache-preload` in the above
> examples.

## Configuring preloading rules

`generate:preload-file` generates a file containing:

- The class `Phly\OpcachePreload\Preloader`.
- Creation of an instance of that class.
- Configuration declarations.
- A method call to start preloading.

When it comes to configuring the preloader, you may call any of the following
methods on the `Preloader` instance:

- **`paths(string ...$paths): Preloader`**: Add one or more paths to preload.
  These may be individual files, or entire subdirectory trees. When the file is
  generated, the commandline tooling attempts to determine if you are preloading
  for a Laminas MVC, Laminas API Tools, or Mezzio application, and will define
  some initial paths for you accordingly. Otherwise, this will be empty.

- **`ignorePaths(string ...$paths): Preloader`**: Add one or more paths to
  ignore when preloading. As with `paths()`, these may be individual files or
  subdirectory trees.

- **`ignoreClasses(string ...$names): Preloader`**: Add one or more class names
  to never preload. The tooling uses the composer `autoload_classmap.php` file
  to determine if a class matches a given file, and, if so, it will skip
  preloading that file.

Each of the above may be called more than once, or with more than one argument.

The last line of the file **MUST** be `$preloader->load();` as that line
performs the actual preloading operations.

<!--
## Documentation

Browse the documentation online at https://docs.laminas.dev/laminas-{component}/

-->

## Support

* [Issues](https://github.com/phly/phly-opcache-preload/issues/)
* [Forum](https://discourse.laminas.dev/t/rfc-opcache-preloading-for-mezzio-and-mvc/1442)
