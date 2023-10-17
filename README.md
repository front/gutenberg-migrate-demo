# Gutenberg migrate example project

## Pre-requisites

- [ddev](https://ddev.readthedocs.io/en/stable/)

## Installation

- Clone this repository
- Run `ddev start`
- Run `ddev composer install`
- Run `ddev drush site-install --existing-config -y`

This should give you a working Drupal 10 site with the Gutenberg module installed and the migrations should be in place.

## Test the migrations

### Migrate Layout builder nodes

- Create some content with the content type `layout_builder_page`
- Run the migration: `drush mim layout_builder_example`

### Migrate articles

- Create some content with the content type `article_type`
- Run the migration: `drush mim article_example`
