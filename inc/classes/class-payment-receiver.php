<?php

/**
 * Payment Receiver
 *
 * Handles incoming payment requests from Laravel
 */

namespace BOILERPLATE\Inc;

use BOILERPLATE\Inc\Traits\Singleton;
use BOILERPLATE\Inc\Traits\Program_Logs;

class Payment_Receiver {

    use Singleton;
    use Program_Logs;

    /**
     * Constructor
     */
    protected function __construct() {
        $this->setup_hooks();
    }

    /**
     * Setup hooks
     */
    private function setup_hooks() {
        add_action( 'template_redirect', array( $this, 'handle_payment_page' ) );
    }

    /**
     * Handle payment page request
     */
    public function handle_payment_page() {
        // Check if we're on the payment page
        $payment_page_id = get_option( 'sslcommerz_payment_page_id' );
        if ( ! $payment_page_id || ! is_page( $payment_page_id ) ) {
            return;
        }

        // Check if data parameter exists
        if ( ! isset( $_GET['data'] ) || empty( $_GET['data'] ) ) {
            wp_die( 'Missing payment data. Please contact support.', 'Payment Error', array( 'response' => 400 ) );
        }

        // Handle payment request
        $this->handle_payment_request();
    }

    /**
     * Handle payment request
     */
    public function handle_payment_request() {
        try {
            // Get encrypted data from URL
            $encrypted_data = sanitize_text_field( $_GET['data'] );

            // Get encryption key from options
            $encryption_key = get_option( 'sslcommerz_encryption_key' );
            if ( empty( $encryption_key ) ) {
                $this->redirect_to_laravel_fail( 'Encryption key not configured' );
                return;
            }

            // Decrypt payment data
            $decryption = Payment_Decryption::get_instance();
            $payment_data = $decryption->decrypt_payment_data( $encrypted_data, $encryption_key );

            // Validate required fields
            $required_fields = array( 'total_amount', 'currency', 'tran_id', 'cus_name', 'cus_email', 'cus_phone', 'value_a' );
            foreach ( $required_fields as $field ) {
                if ( empty( $payment_data[ $field ] ) ) {
                    $this->redirect_to_laravel_fail( 'Missing required payment field: ' . $field );
                    return;
                }
            }

            // Save transaction to database
            $db_handler = Database_Handler::get_instance();
            $transaction_id = $db_handler->save_transaction( $payment_data );

            if ( ! $transaction_id ) {
                $this->redirect_to_laravel_fail( 'Failed to save transaction' );
                return;
            }

            // Initialize SSLCommerz payment
            $sslcommerz_wrapper = SSLCommerz_Wrapper::get_instance();
            $response = $sslcommerz_wrapper->init_payment( $payment_data );

            if ( $response === false ) {
                // Update transaction status to failed
                $db_handler->update_transaction_status( $payment_data['tran_id'], 'Failed' );
                $this->redirect_to_laravel_fail( 'Failed to initialize payment' );
                return;
            }

            // Payment initialization successful, SSLCommerz will redirect user
            // The makePayment method with 'hosted' type will handle the redirect

        } catch ( \Exception $e ) {
            $this->put_program_logs( 'Payment request error: ' . $e->getMessage() );
            $this->redirect_to_laravel_fail( 'Payment processing error: ' . $e->getMessage() );
        }
    }

    /**
     * Redirect to Laravel fail URL
     *
     * @param string $reason Failure reason
     */
    private function redirect_to_laravel_fail( $reason = '' ) {
        $fail_url = get_option( 'sslcommerz_laravel_fail_url' );

        if ( empty( $fail_url ) ) {
            wp_die( 'Payment processing failed. ' . $reason, 'Payment Error', array( 'response' => 500 ) );
        }

        // Try to get order_id from payment data if available
        $order_id = '';
        if ( isset( $_GET['data'] ) ) {
            try {
                $encryption_key = get_option( 'sslcommerz_encryption_key' );
                if ( ! empty( $encryption_key ) ) {
                    $decryption = Payment_Decryption::get_instance();
                    $payment_data = $decryption->decrypt_payment_data( sanitize_text_field( $_GET['data'] ), $encryption_key );
                    $order_id = $payment_data['value_a'] ?? '';
                }
            } catch ( \Exception $e ) {
                // Ignore decryption errors for redirect
            }
        }

        $redirect_url = add_query_arg(
            array(
                'order_id' => $order_id,
                'status' => 'failed',
                'reason' => urlencode( $reason ),
            ),
            $fail_url
        );

        wp_redirect( $redirect_url );
        exit;
    }

}
