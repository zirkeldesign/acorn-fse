# Acorn FSE Package

This repo can be used to scaffold an Acorn package. See the [Acorn Package Development](https://roots.io/acorn/docs/package-development/) docs for further information.

## Installation

You can install this package with Composer:

```bash
composer require zirkeldesign/acorn-fse
```

You can publish the config file with:

```shell
$ wp acorn vendor:publish --provider="Zirkeldesign\AcornFSE\Providers\AcornFSEServiceProvider"
```

## Usage

From a Blade template:

```blade
@include('AcornFSE::example')
```

From WP-CLI:

```shell
$ wp acorn fse
```
