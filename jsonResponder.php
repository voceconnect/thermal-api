<?php

require_once('lib/Slim/Slim/Middleware.php');

class JsonResponder extends \Slim\Middleware {
	function call() {
		$app = $this->getApplication();
		$this->next->call();

		$env = $app->environment();

		if ( isset($env['slim.response_object']) ) {
			$res = $app->response();
			$app->contentType('application/json');
			$res->write(json_encode($env['slim.response_object']), true);
		}

	}
}