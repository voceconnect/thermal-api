<?php

// This file is used to configure the test suite. Modify the following values and save as "wp-tests-config.php"


// Path to the WordPress codebase. Add a forward slash in the end.
// Current value is the WordPress default, implying that this file is in "/wp-content/plugins/wp-json-api/tests/"
// If this is not correct for your installation, modify the following path:
define( 'ABSPATH', __DIR__ . '/../../../../' );


// ** MySQL settings ** //

// This configuration file will be used by the test suite. /wordpress/wp-config.php will be ignored.

/** WARNING WARNING WARNING! **/
// These tests will DROP ALL TABLES in the database with the prefix named below.
// DO NOT use a production database or one that is shared with something else.

define( 'DB_NAME', 'yourdbnamehere' );
define( 'DB_USER', 'yourusernamehere' );
define( 'DB_PASSWORD', 'yourpasswordhere' );
define( 'DB_HOST', 'localhost' );
define( 'DB_CHARSET', 'utf8' );
define( 'DB_COLLATE', '' );

$table_prefix  = 'wptests_';   // Only numbers, letters, and underscores please!

define( 'WP_TESTS_DOMAIN', 'example.org' );
define( 'WP_TESTS_EMAIL', 'admin@example.org' );
define( 'WP_TESTS_TITLE', 'Test Blog' );

define( 'WP_PHP_BINARY', 'php' );

define( 'WPLANG', '' );
