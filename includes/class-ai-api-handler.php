<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

abstract class AI_API_Handler {
    protected $api_key;

    public function __construct( $api_key ) {
        $this->api_key = $api_key;
    }

    abstract public function call_api( $prompt );
}