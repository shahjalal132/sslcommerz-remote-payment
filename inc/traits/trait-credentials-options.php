<?php

namespace BOILERPLATE\Inc\Traits;

trait Credentials_Options {

    private $api_url;
    private $api_key;
    private $option1;
    private $option2;

    private function load_credentials_options() {
        // api credentials
        $this->api_url = get_option( 'api_url' );
        $this->api_key = get_option( 'api_key' );

        // options
        $this->option1 = get_option( 'option1' );
        $this->option2 = get_option( 'option2' );
    }

}