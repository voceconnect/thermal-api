<?php

class API_Test_v1 extends \Voce\Thermal\API_Base {

	protected $version = '1';

	public function __construct( \Slim\Slim $slim ) {
		parent::__construct( $slim );
	}

}