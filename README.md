# Composer template for a Drupal 7 project with Symfony

The goal here is to kickstart a pre-configured project with Drupal 7 & Symfony
bound together with the [drupal-sf-dic](https://github.com/makinacorpus/drupal-sf-dic/)
module.

Based on : [drupal-project](https://github.com/drupal-composer/drupal-project/)

## Usage

First you need to [install composer](https://getcomposer.org/doc/00-intro.md#globally).

After that you can launch :

```
composer create-project makinacorpus/drupal-template-sf some-dir --stability dev
```
And follow instructions.

## What does the template do?

* Installs Drupal 7
* Initializes settings.php
* Configures [drupal-sf-dic](https://github.com/makinacorpus/drupal-sf-dic/)
* Creates a Symfony-like directory organization with default config files
* Configures Drupal for using Twig
* Initializes a new profile
* Initializes new Bootstrap based frontend theme using Twig (if desired)
* Initializes new [drupal-badm](https://github.com/makinacorpus/drupal-badm/) based backend theme (if desired)
* Installs [drupal-usync](https://github.com/makinacorpus/drupal-usync/)

## Todo

* Add weback (+babel) integration
* Patchs gestion