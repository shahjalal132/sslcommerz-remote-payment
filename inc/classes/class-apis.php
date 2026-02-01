<?php

namespace BOILERPLATE\Inc;

use BOILERPLATE\Inc\Traits\Credentials_Options;
use BOILERPLATE\Inc\Traits\Program_Logs;
use BOILERPLATE\Inc\Traits\Singleton;

class APIS {

    use Singleton;
    use Program_Logs;
    use Credentials_Options;

    public function __construct() {
        // $this->load_credentials_options();
        $this->setup_hooks();
    }

    public function setup_hooks() {
        // Register custom REST API routes
        add_action( 'rest_api_init', [ $this, 'register_apis_routes' ] );
    }

    public function register_apis_routes() {

        // register health check route
        register_rest_route( 'api/v1', '/health', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'health_check_callback' ],
            'permission_callback' => '__return_true', // Allow public access
        ] );

    }

    public function health_check_callback() {
        return new \WP_REST_Response( [
            'status'    => 'success',
            'message'   => 'API is healthy and running',
            'timestamp' => current_time( 'mysql' ),
        ], 200 );
    }

}