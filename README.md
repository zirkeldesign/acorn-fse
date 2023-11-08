# Acorn FSE

**This package is a work in progress.**

This [`roots/acorn`](https://roots.io/acorn/) package provides a way to use
the [WordPress Full Site Editing](https://make.wordpress.org/core/2020/11/18/full-site-editing-in-wordpress-5-6/)
features in a [Sage 10](https://roots.io/sage/).

## Installation

You can install this package with Composer:

```bash
composer config repositories.acorn-fse vcs https://github.com/zirkeldesign/acorn-fse
composer require zirkeldesign/acorn-fse
```

You can publish the config file with:

```shell
$ wp acorn vendor:publish --provider="Zirkeldesign\AcornFSE\Providers\AcornFSEServiceProvider"
```

## Usage

*Needs to be documented.*
