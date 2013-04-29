<?php

namespace WP_JSON_API;

class API implements iAPI {

	public function getVersion() {
		return 1;
	}

	/**
	 * Routes the request to the correct handler and fills the response accordingly
	 * 
	 */
	public function handleRequest( Request $request, Response $response ) {
		throw new Exception( "Method Not Implemented" );
	}

}
