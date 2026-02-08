<?php

/**
 * SSLCommerz Wrapper
 *
 * Wrapper class to properly initialize and use SSLCommerz library
 */

namespace BOILERPLATE\Inc;

use BOILERPLATE\Inc\Traits\Singleton;
use BOILERPLATE\Inc\Traits\Program_Logs;

class SSLCommerz_Wrapper {

    use Singleton;
    use Program_Logs;

    /**
     * Initialize SSLCommerz payment
     *
     * @param array $paymentData Payment data array
     * @return mixed Response from SSLCommerz or false on failure
     */
    public function init_payment( $paymentData ) {
        // Update config file with current settings
        $config_manager = SSLCommerz_Config::get_instance();
        $config_manager->update_config_file();

        // Include SSLCommerz library
        require_once PLUGIN_BASE_PATH . '/inc/libs/SSLCommerz-PHP-master/lib/SslCommerzNotification.php';

        // Get callback URLs
        $callback_urls = $config_manager->get_callback_urls();

        // Add callback URLs to payment data
        $paymentData['success_url'] = $callback_urls['success_url'];
        $paymentData['fail_url'] = $callback_urls['failed_url'];
        $paymentData['cancel_url'] = $callback_urls['cancel_url'];
        $paymentData['ipn_url'] = $callback_urls['ipn_url'];

        try {
            $sslcz = new \SslCommerz\SslCommerzNotification();
            $response = $sslcz->makePayment( $paymentData, 'hosted' );

            $this->put_program_logs( 'SSLCommerz response: ' . json_encode( $response ) );

            return $response;
        } catch ( \Exception $e ) {
            $this->put_program_logs( 'SSLCommerz error: ' . $e->getMessage() );
            return false;
        }
    }

}
