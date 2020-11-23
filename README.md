Teknoo Software - Composer Intsall
==================================

[![Build Status](https://travis-ci.com/TeknooSoftware/composer-install.svg?branch=master)](https://travis-ci.com/TeknooSoftware/composer-install)
[![Latest Stable Version](https://poser.pugx.org/teknoo/composer-install/v/stable)](https://packagist.org/packages/teknoo/composer-install)
[![Latest Unstable Version](https://poser.pugx.org/teknoo/composer-install/v/unstable)](https://packagist.org/packages/teknoo/composer-install)
[![Total Downloads](https://poser.pugx.org/teknoo/composer-install/downloads)](https://packagist.org/packages/teknoo/composer-install)
[![License](https://poser.pugx.org/teknoo/composer-install/license)](https://packagist.org/packages/teknoo/composer-install)
[![PHPStan](https://img.shields.io/badge/PHPStan-enabled-brightgreen.svg?style=flat)](https://github.com/phpstan/phpstan)

Composer plugin allowing the creation of a functional package "out of the box" as well as a cleaning of the project 
when it is uninstalled.

This performs some automated install/update or uninstall operations when a package is installed, updated or uninstalled 
via Composer. Operations are managed by `Action` classes, implementing `Teknoo\Composer\Action\ActionInterface`,
provided by this plugin or your packages. 

Arguments or configuration to pass to actions must be defined as extra, under the key `Teknoo\\Composer\\Installer` like 
the following example. It comes with actions dedicated to install bundles for Symfony 4.0+ :
 
* updating the `bundles.php` file
* copy/clean some configuration files into `config/packages` folder.
* copy/clean routes files into `config/routes` foler.

Quick Example
-------------

    {
        "name": "your-company/your-package",
        [...]
        "extra": {
            "Teknoo\\Composer\\Installer": {
                "config": {
                    #To configure Teknoo\\Composer\\Installer
                    #disabled: true #to disabled installer in the current composer project
                },
                #To add some bundle in bundles.php
                "Teknoo\\Composer\\Action\\SymfonyBundle": {
                    "Your\\Company\\Bundle": {"all":  true},
                    "Your\\Another\\Company\\Bundle": {"dev": true }
                },
                #To add/update some file in config/packages
                "Teknoo\\Composer\\Action\\SymfonyPackages": {
                    "bundle_config.yaml": [
                        "# Read the documentation:",
                        "my_bundle:",
                        "  foo:",
                        "    - 'bar'",
                        "    - '%kernel.project_dir%/vendor/foo/bar.php'",
                        "  bar:",
                        "    foo: 42"
                    ],
                    "bundle_another_config.yaml": "foo\\nbar",,
                    "bundle_another_config.yaml": {
                        "base64": "aGVsbG8gd29ybGQ="
                    },
                }
            }
        }
    }

Support this project
---------------------

This project is free and will remain free, but it is developed on my personal time. 
If you like it and help me maintain it and evolve it, don't hesitate to support me on [Patreon](https://patreon.com/teknoo_software).
Thanks :) Richard. 

Installation & Requirements
---------------------------
To install this library with composer, run this command :

    composer require teknoo/composer-install

This library requires :

    * PHP 7.4+
    * Composer 1.10 or 2.0+

Credits
-------
Richard Déloge - <richarddeloge@gmail.com> - Lead developer.
Teknoo Software - <https://teknoo.software>

About Teknoo Software
---------------------
**Teknoo Software** is a PHP software editor, founded by Richard Déloge.
Teknoo Software's goals : Provide to our partners and to the community a set of high quality services or software,
 sharing knowledge and skills.

License
-------
composer-install is licensed under the MIT License - see the licenses folder for details

Contribute :)
-------------

You are welcome to contribute to this project. [Fork it on Github](CONTRIBUTING.md)
