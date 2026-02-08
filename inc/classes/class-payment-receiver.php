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

        // // Check if data parameter exists
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
            // Get JSON data from URL (it's URL-encoded, so decode it first)
            $url_encoded_data = sanitize_text_field( $_GET['data'] );
            $json_data = urldecode( $url_encoded_data );
            
            // Remove any slashes that might have been added by WordPress/PHP
            $json_data = wp_unslash( $json_data );

            // $this->put_program_logs( 'Payment data (after URL decode and unslash): ' . $json_data );
            
            // Decode JSON payment data
            $payment_data = json_decode( $json_data, true );

            // Validate JSON decoding
            if ( ! $payment_data || json_last_error() !== JSON_ERROR_NONE ) {
                $this->put_program_logs( 'JSON decode error: ' . json_last_error_msg() );
                $this->put_program_logs( 'Raw JSON string length: ' . strlen( $json_data ) );
                $this->redirect_to_laravel_fail( 'Invalid payment data: ' . json_last_error_msg() );
                return;
            }

            // Add static data to init payment.
            // $payment_data = [
            //     // Payment Information
            //     'total_amount' => '500',
            //     'currency' => 'BDT',
            //     'tran_id' => 'TXN-' . time() . '-' . '1234567890',

            //     // Customer Information
            //     'cus_name' => 'John Doe',
            //     'cus_email' => 'john.doe@example.com',
            //     'cus_add1' => 'Dhaka',
            //     'cus_add2' => '',
            //     'cus_city' => 'Dhaka',
            //     'cus_state' => 'Dhaka',
            //     'cus_postcode' => '1000',
            //     'cus_country' => 'Bangladesh',
            //     'cus_phone' => '01711111111',
            //     'cus_fax' => '',
            //     // Shipment Information
            //     'ship_name' => 'John Doe',
            //     'ship_add1' => 'Dhaka',
            //     'ship_add2' => '',
            //     'ship_city' => 'Dhaka',
            //     'ship_state' => 'Dhaka',
            //     'ship_postcode' => '1000',
            //     'ship_phone' => '01711111111',
            //     'ship_country' => 'Bangladesh',

            //     // Product Information
            //     'shipping_method' => 'NO',
            //     'num_of_item' => '1',
            //     'product_name' => 'Course Enrollment',
            //     'product_category' => 'Education',
            //     'product_profile' => 'general',

            //     // Optional Parameters (for callbacks)
            //     'value_a' => '1234567890',
            //     'value_b' => '1234567890',
            //     'value_c' => '',
            //     'value_d' => '',
            // ];

            // Validate required fields
            $required_fields = array( 'total_amount', 'currency', 'tran_id', 'cus_name', 'cus_email', 'cus_phone', 'value_a' );
            foreach ( $required_fields as $field ) {
                if ( empty( $payment_data[ $field ] ) ) {
                    $this->redirect_to_laravel_fail( 'Missing required payment field: ' . $field, $payment_data );
                    return;
                }
            }

            // Save transaction to database
            $db_handler = Database_Handler::get_instance();
            $transaction_id = $db_handler->save_transaction( $payment_data );

            if ( ! $transaction_id ) {
                $this->redirect_to_laravel_fail( 'Failed to save transaction', $payment_data );
                return;
            }

            // Initialize SSLCommerz payment
            $sslcommerz_wrapper = SSLCommerz_Wrapper::get_instance();
            $response = $sslcommerz_wrapper->init_payment( $payment_data );

            $this->put_program_logs( 'Payment response: ' . json_encode( $response ) );

            if ( $response === false ) {
                // Update transaction status to failed
                $db_handler->update_transaction_status( $payment_data['tran_id'], 'Failed' );
                $this->redirect_to_laravel_fail( 'Failed to initialize payment', $payment_data );
                return;
            }

            // Payment initialization successful, SSLCommerz will redirect user
            // The makePayment method with 'hosted' type will handle the redirect

        } catch ( \Exception $e ) {
            $this->put_program_logs( 'Payment request error: ' . $e->getMessage() );
            $payment_data = isset( $payment_data ) ? $payment_data : array();
            $this->redirect_to_laravel_fail( 'Payment processing error: ' . $e->getMessage(), $payment_data );
        }
    }

    /**
     * Redirect to Laravel fail URL
     *
     * @param string $reason Failure reason
     * @param array $payment_data Payment data array (optional)
     */
    private function redirect_to_laravel_fail( $reason = '', $payment_data = array() ) {
        $fail_url = get_option( 'sslcommerz_laravel_fail_url' );

        if ( empty( $fail_url ) ) {
            wp_die( 'Payment processing failed. ' . $reason, 'Payment Error', array( 'response' => 500 ) );
        }

        // Try to get order_id from payment data if available
        $order_id = $payment_data['value_a'] ?? '';

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
