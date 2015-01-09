<?php

/*
  Plugin Name: Thermal API
  Version:     0.13.4
  Plugin URI:  http://thermal-api.com/
  Description: The power of WP_Query in a RESTful API.
  Author:      Voce Platforms
  Author URI:  http://voceplatforms.com/
 */

define( "THERMAL_API_MIN_PHP_VER", '5.3.0' );

register_activation_hook( __FILE__, 'thermal_activation' );

function thermal_activation() {
	if ( version_compare( phpversion(), THERMAL_API_MIN_PHP_VER, '<' ) ) {
		die( sprintf( "The minimum PHP version required for Thermal API is %s", THERMAL_API_MIN_PHP_VER ) );
	}
}

if ( version_compare( phpversion(), THERMAL_API_MIN_PHP_VER, '>=' ) ) {
  @include(__DIR__ . '/vendor/autoload.php');
	require(__DIR__ . '/dispatcher.php');
	new Voce\Thermal\API_Dispatcher();
}
