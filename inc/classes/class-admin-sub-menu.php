<?php

namespace BOILERPLATE\Inc;

use BOILERPLATE\Inc\Traits\Program_Logs;
use BOILERPLATE\Inc\Traits\Singleton;

class Admin_Sub_Menu {

    use Singleton;
    use Program_Logs;

    public function __construct() {
        $this->setup_hooks();
    }

    public function setup_hooks() {
        add_action( 'admin_menu', [ $this, 'register_admin_sub_menu' ] );
        add_filter( 'plugin_action_links_' . PLUGIN_BASE_NAME, [ $this, 'add_plugin_action_links' ] );

        // save api credentials
        add_action( 'wp_ajax_save_credentials', [ $this, 'save_api_credentials' ] );
        add_action( 'wp_ajax_save_options', [ $this, 'save_options' ] );
        add_action( 'wp_ajax_save_sslcommerz_settings', [ $this, 'save_sslcommerz_settings' ] );
    }

    public function save_api_credentials() {

        $api_url = sanitize_text_field( $_POST['api_url'] );
        $api_key = sanitize_text_field( $_POST['api_key'] );

        if ( empty( $api_url ) || empty( $api_key ) ) {
            wp_send_json_error( 'An error occurred! Please fill all the fields.' );
        }

        update_option( 'api_url', $api_url );
        update_option( 'api_key', $api_key );

        wp_send_json_success( 'Credentials saved successfully!' );
        die();
    }

    public function save_options() {

        $option1 = sanitize_text_field( $_POST['option1'] );
        $option2 = sanitize_text_field( $_POST['option2'] );

        update_option( 'option1', $option1 );
        update_option( 'option2', $option2 );

        wp_send_json_success( 'Options saved successfully!' );
        die();
    }

    function add_plugin_action_links( $links ) {
        $settings_link = '<a href="admin.php?page=' . PAGE_SLUG . '">' . __( 'Settings', 'wp-plugin-boilerplate' ) . '</a>';
        array_unshift( $links, $settings_link );
        return $links;
    }

    public function register_admin_sub_menu() {
        add_submenu_page(
            'options-general.php',
            'SSLCommerz Remote Payment',
            'SSLCommerz Remote Payment',
            'manage_options',
            PAGE_SLUG,
            [ $this, 'menu_callback_html' ],
        );
    }

    public function menu_callback_html() {
        include_once PLUGIN_BASE_PATH . '/templates/template-admin-sub-menu.php';
    }

    public function save_sslcommerz_settings() {
        // Verify nonce for security
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'save_sslcommerz_settings' ) ) {
            wp_send_json_error( 'Security check failed. Please refresh the page and try again.' );
        }

        // Check user permissions
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'You do not have permission to perform this action.' );
        }

        // Sanitize and get form data
        $store_id = sanitize_text_field( $_POST['sslcommerz_store_id'] ?? '' );
        $store_password = sanitize_text_field( $_POST['sslcommerz_store_password'] ?? '' );
        $is_sandbox = isset( $_POST['sslcommerz_is_sandbox'] ) ? true : false;
        $encryption_key = sanitize_text_field( $_POST['sslcommerz_encryption_key'] ?? '' );
        $laravel_success_url = esc_url_raw( $_POST['sslcommerz_laravel_success_url'] ?? '' );
        $laravel_fail_url = esc_url_raw( $_POST['sslcommerz_laravel_fail_url'] ?? '' );

        // Validate required fields
        if ( empty( $store_id ) || empty( $store_password ) || empty( $encryption_key ) ) {
            wp_send_json_error( 'Please fill in all required fields (Store ID, Store Password, and Encryption Key).' );
        }

        // Validate URLs if provided
        if ( ! empty( $laravel_success_url ) && ! filter_var( $laravel_success_url, FILTER_VALIDATE_URL ) ) {
            wp_send_json_error( 'Invalid Laravel Success URL format.' );
        }

        if ( ! empty( $laravel_fail_url ) && ! filter_var( $laravel_fail_url, FILTER_VALIDATE_URL ) ) {
            wp_send_json_error( 'Invalid Laravel Fail URL format.' );
        }

        // Save options
        update_option( 'sslcommerz_store_id', $store_id );
        update_option( 'sslcommerz_store_password', $store_password );
        update_option( 'sslcommerz_is_sandbox', $is_sandbox );
        update_option( 'sslcommerz_encryption_key', $encryption_key );
        update_option( 'sslcommerz_laravel_success_url', $laravel_success_url );
        update_option( 'sslcommerz_laravel_fail_url', $laravel_fail_url );

        wp_send_json_success( 'SSLCommerz settings saved successfully!' );
    }

}