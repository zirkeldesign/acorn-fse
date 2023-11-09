# Acorn FSE

**🔥 This package is a work in progress. 🔥**

This [`roots/acorn`](https://roots.io/acorn/) package provides a way to use
the [WordPress Full Site Editing](https://make.wordpress.org/core/2020/11/18/full-site-editing-in-wordpress-5-6/)
features in a [Sage 10](https://roots.io/sage/). Inspired
by [`strarsis/sage10-fse`](https://github.com/strarsis/sage10-fse).

## Requirements

* Acorn
* PHP >= 8.1

## Installation

Install this package with Composer:

```bash
composer require zirkeldesign/acorn-fse
```

Add the package to the cached package manifest.

```bash
wp acorn package:discover
```

You can publish the config file with:

```shell
$ wp acorn vendor:publish --tag="fse-config"
```

## Usage

*Needs to be documented.*
