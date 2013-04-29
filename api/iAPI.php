<?php
namespace WP_JSON_API;

interface iAPI {
	/**
	 * Returns int the API version number
	 */
	public function getVersion();
	
	public function handleRequest(Request $request, Response $response);
}
