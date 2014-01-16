<?php

namespace Voce\Thermal\v1\Controllers;

class RewriteRules {

	public static function find( $app ) {
		global $wp_rewrite;
		$base_url = home_url( '/' );
		$rewrite_rules = array( );

		$rules = $wp_rewrite->wp_rewrite_rules();
		if(  is_array( $rules )) {
		foreach ( $rules as $regex => $query ) {
			$patterns = array( '|index\.php\?&?|', '|\$matches\[(\d+)\]|' );
			$replacements = array( '', '\$$1' );

			$rewrite_rules[] = array(
				'regex' => $regex,
				'query_expression' => preg_replace( $patterns, $replacements, $query ),
			);
		}
		} else {
			$app->halt('404', 'Rewrite Rules are not setup on this site.');
		}

		return compact( 'base_url', 'rewrite_rules' );
	}

}

?>
