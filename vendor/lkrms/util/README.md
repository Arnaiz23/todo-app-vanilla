# lkrms/util

> A lightweight PHP toolkit for expressive backend/CLI apps. Opinionated but
> adaptable. Negligible dependencies. May contain traces of Laravel.

<p>
  <a href="https://packagist.org/packages/lkrms/util"><img src="https://poser.pugx.org/lkrms/util/v" alt="Latest Stable Version" /></a>
  <a href="https://packagist.org/packages/lkrms/util"><img src="https://poser.pugx.org/lkrms/util/license" alt="License" /></a>
  <a href="https://github.com/lkrms/php-util/actions"><img src="https://github.com/lkrms/php-util/actions/workflows/ci.yml/badge.svg" alt="CI Status" /></a>
  <a href="https://codecov.io/gh/lkrms/php-util"><img src="https://codecov.io/gh/lkrms/php-util/graph/badge.svg?token=5Z8MBDXO7R" alt="Code Coverage" /></a>
</p>

----

## Installation

Install the latest version with [Composer](https://getcomposer.org/):

```shell
composer require lkrms/util
```

## Documentation

API documentation for `lkrms/util` is [available online][api-docs]. It tracks
the `main` branch of the project's [GitHub repository][repo] and is generated by
[ApiGen][].

You can build the API documentation locally by running the following commands in
the top-level directory. It should appear in `docs/api` after a few seconds.

```shell
composer -d tools/apigen install
```

```shell
tools/apigen/vendor/bin/apigen -c tools/apigen/apigen.neon
```

Other documentation is available [here][docs] and in the source code.


[api-docs]: https://lkrms.github.io/php-util/
[ApiGen]: https://github.com/ApiGen/ApiGen
[docs]: docs/
[repo]: https://github.com/lkrms/php-util
