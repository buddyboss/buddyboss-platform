# buddypress/wp-cli-buddypress

WP-CLI commands for use with BuddyPress.

[![Build Status](https://travis-ci.org/buddypress/wp-cli-buddypress.svg?branch=master)](https://travis-ci.org/buddypress/wp-cli-buddypress)

These commands may be in flux, as we work toward a potential merge into BuddyPress. See https://buddypress.trac.wordpress.org/ticket/7604.

Please use and provide feedback!

## System Requirements

* PHP >= 5.4
* wp-cli >= 0.23.0
* BuddyPress trunk (development version).

## Setup

* Install [wp-cli](https://wp-cli.org)
* Install wp-cli-buddypress. Manuall installation is recommended, though Composer installation should work too. See https://wp-cli.org/package-index/ for information.
* Inside of a WP installation, type `wp bp`. You should see a list of available commands.

## Changelog

### 1.8.0

* `wp-cli-buddypress` requires PHP 5.4
* `bp notification` commands introduced

### 1.7.0

* Updated `bp` and `bp xprofile` commands PHPDoc info
* Fixed `component list` commands output
* Check if the `component` exists first before using it
* Fixed `component` Behat tests
* Removed PHP 5.3 support from Travis

### 1.6.0

* `bp email` commands introduced
* With PSR-4 support for the classes

### 1.5.0

* CRUD commands introduced to the main BuddyPress components
* Behat tests added for all commands
* Codebase fixed for WPCS

### 1.4.0

* New commands: `bp xprofile list_fields`, `bp xprofile delete_field`
* Added the ability to pass multiple comma-separated values when updating xprofile fields
* Fixed bug in component activation

### 1.3.1

* Improved logic for user-id parsing

### 1.3.0

* New commands: `bp group get_members`, `bp group update`
* Ability to pass 'content' when using `bp activity generate`
* When using `bp activity generate` with type=activity_update and component=groups, format the activity action properly

### 1.2.0

* Use wp-cli's new fourth-level commands
* New commands: xprofile create_group, xprofile create_field, xprofile set_data

### 1.1.1

* Ensure that components have their install routine run after activation

### 1.1

* New commands: activate, deactivate, activity_create, activity_generate
* Improved documentation
* Added support for installation via Composer

### 1.0

* Initial release
