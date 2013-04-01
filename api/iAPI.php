<?php
namespace WP_JSON_API;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

interface iAPI {
	/**
	 * Returns int the API version number
	 */
	public function getVersion();
	
	public function handleRequest(Request $request, Response $response);
}
