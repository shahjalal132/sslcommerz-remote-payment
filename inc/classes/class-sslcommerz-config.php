<?php

/**
 * SSLCommerz Configuration Manager
 *
 * Manages SSLCommerz library configuration
 */

namespace BOILERPLATE\Inc;

use BOILERPLATE\Inc\Traits\Singleton;
use BOILERPLATE\Inc\Traits\Program_Logs;

class SSLCommerz_Config {

    use Singleton;
    use Program_Logs;

    /**
     * Get SSLCommerz configuration array
     *
     * @return array Configuration array compatible with SSLCommerz library
     */
    public function get_config() {
        $store_id = get_option( 'sslcommerz_store_id', '' );
        $store_password = get_option( 'sslcommerz_store_password', '' );
        $is_sandbox = get_option( 'sslcommerz_is_sandbox', true );
        $laravel_success_url = get_option( 'sslcommerz_laravel_success_url', '' );
        $laravel_fail_url = get_option( 'sslcommerz_laravel_fail_url', '' );

        $config_details = sprintf(
            'Store ID: %s, Store Password: %s, Is Sandbox: %s, Laravel Success URL: %s, Laravel Fail URL: %s',
            $store_id,
            $store_password,
            $is_sandbox ? 'true' : 'false',
            $laravel_success_url,
            $laravel_fail_url
        );

        $this->put_program_logs( 'SSLCommerz config details: ' . $config_details );

        // Get site URL for callbacks
        $site_url = site_url();

        $config = array(
            'success_url' => $site_url . '/wp-json/api/v1/payment/success',
            'failed_url' => $site_url . '/wp-json/api/v1/payment/fail',
            'cancel_url' => $site_url . '/wp-json/api/v1/payment/cancel',
            'ipn_url' => $site_url . '/wp-json/api/v1/payment/ipn',
            'projectPath' => $site_url,
            'apiDomain' => $is_sandbox ? 'https://sandbox.sslcommerz.com' : 'https://securepay.sslcommerz.com',
            'apiCredentials' => array(
                'store_id' => $store_id,
                'store_password' => $store_password,
            ),
            'apiUrl' => array(
                'make_payment' => '/gwprocess/v4/api.php',
                'order_validate' => '/validator/api/validationserverAPI.php',
            ),
            'connect_from_localhost' => false,
            'verify_hash' => true,
        );

        $this->put_program_logs( 'SSLCommerz config: ' . json_encode( $config ) );

        return $config;
    }

    /**
     * Update SSLCommerz config file dynamically
     * This method can be used to update the library's config file if needed
     *
     * @return bool True on success, false on failure
     */
    public function update_config_file() {
        $config = $this->get_config();
        $config_file = PLUGIN_BASE_PATH . '/inc/libs/SSLCommerz-PHP-master/config/config.php';

        if ( ! file_exists( $config_file ) ) {
            return false;
        }

        // Read current config file
        $config_content = file_get_contents( $config_file );

        // Update STORE_ID
        $config_content = preg_replace(
            "/define\s*\(\s*['\"]STORE_ID['\"].*?\);/",
            "define('STORE_ID', '" . addslashes( $config['apiCredentials']['store_id'] ) . "');",
            $config_content
        );

        // Update STORE_PASSWORD
        $config_content = preg_replace(
            "/define\s*\(\s*['\"]STORE_PASSWORD['\"].*?\);/",
            "define('STORE_PASSWORD', '" . addslashes( $config['apiCredentials']['store_password'] ) . "');",
            $config_content
        );

        // Update IS_SANDBOX
        $is_sandbox_value = $config['apiDomain'] === 'https://sandbox.sslcommerz.com' ? 'true' : 'false';
        $config_content = preg_replace(
            "/define\s*\(\s*['\"]IS_SANDBOX['\"].*?\);/",
            "define('IS_SANDBOX', " . $is_sandbox_value . ");",
            $config_content
        );

        // Update PROJECT_PATH
        $config_content = preg_replace(
            "/define\s*\(\s*['\"]PROJECT_PATH['\"].*?\);/",
            "define('PROJECT_PATH', '" . addslashes( $config['projectPath'] ) . "');",
            $config_content
        );

        // Extract relative paths from full URLs for callback URLs
        // The library will combine projectPath + '/' + callback_url
        $site_url = rtrim( site_url(), '/' );
        $success_path = str_replace( $site_url, '', $config['success_url'] );
        $failed_path = str_replace( $site_url, '', $config['failed_url'] );
        $cancel_path = str_replace( $site_url, '', $config['cancel_url'] );
        $ipn_path = str_replace( $site_url, '', $config['ipn_url'] );
        
        // Remove leading slash if present (library will add it)
        $success_path = ltrim( $success_path, '/' );
        $failed_path = ltrim( $failed_path, '/' );
        $cancel_path = ltrim( $cancel_path, '/' );
        $ipn_path = ltrim( $ipn_path, '/' );

        // Update success_url
        $config_content = preg_replace(
            "/['\"]success_url['\"]\s*=>\s*['\"].*?['\"]/",
            "'success_url' => '" . addslashes( $success_path ) . "'",
            $config_content
        );

        // Update failed_url
        $config_content = preg_replace(
            "/['\"]failed_url['\"]\s*=>\s*['\"].*?['\"]/",
            "'failed_url' => '" . addslashes( $failed_path ) . "'",
            $config_content
        );

        // Update cancel_url
        $config_content = preg_replace(
            "/['\"]cancel_url['\"]\s*=>\s*['\"].*?['\"]/",
            "'cancel_url' => '" . addslashes( $cancel_path ) . "'",
            $config_content
        );

        // Update ipn_url
        $config_content = preg_replace(
            "/['\"]ipn_url['\"]\s*=>\s*['\"].*?['\"]/",
            "'ipn_url' => '" . addslashes( $ipn_path ) . "'",
            $config_content
        );

        // Write updated config
        return file_put_contents( $config_file, $config_content ) !== false;
    }

    /**
     * Get callback URLs
     *
     * @return array Array of callback URLs
     */
    public function get_callback_urls() {
        $site_url = site_url();
        return array(
            'success_url' => $site_url . '/wp-json/api/v1/payment/success',
            'failed_url' => $site_url . '/wp-json/api/v1/payment/fail',
            'cancel_url' => $site_url . '/wp-json/api/v1/payment/cancel',
            'ipn_url' => $site_url . '/wp-json/api/v1/payment/ipn',
        );
    }

}
