# Thermal API Unit Tests

## Overview

The test suite for Thermal API is built using the `WP_UnitTestCase` class created for testing core WordPress functionality.

You can read more about unit testing WordPress here: [http://make.wordpress.org/core/handbook/automated-testing/](http://make.wordpress.org/core/handbook/automated-testing/)

## Configuration

Since we need to spin up a temporary WordPress installation to test against, configuring the test suite is much like configuring a new WordPress site.

Set up a **fresh database** just for running these tests.  **All data in the test suite database will be destroyed.**

Rename the `wp-tests-config-sample.php` file to `wp-tests-config.php` and enter the appropriate database information.

## Running the Tests

From the plugin root:

    $ phpunit tests