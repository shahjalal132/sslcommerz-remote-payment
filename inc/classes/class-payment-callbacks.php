<?php

/**
 * Payment Callbacks
 *
 * Handles SSLCommerz payment callbacks (success, fail, cancel, IPN)
 */

namespace BOILERPLATE\Inc;

use BOILERPLATE\Inc\Traits\Singleton;
use BOILERPLATE\Inc\Traits\Program_Logs;

class Payment_Callbacks {

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
        add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
    }

    /**
     * Register REST API routes
     */
    public function register_rest_routes() {
        register_rest_route(
            'api/v1',
            '/payment/success',
            array(
                'methods' => 'POST',
                'callback' => array( $this, 'handle_success_callback' ),
                'permission_callback' => '__return_true',
            )
        );

        register_rest_route(
            'api/v1',
            '/payment/fail',
            array(
                'methods' => 'POST',
                'callback' => array( $this, 'handle_fail_callback' ),
                'permission_callback' => '__return_true',
            )
        );

        register_rest_route(
            'api/v1',
            '/payment/cancel',
            array(
                'methods' => 'POST',
                'callback' => array( $this, 'handle_cancel_callback' ),
                'permission_callback' => '__return_true',
            )
        );

        register_rest_route(
            'api/v1',
            '/payment/ipn',
            array(
                'methods' => 'POST',
                'callback' => array( $this, 'handle_ipn_callback' ),
                'permission_callback' => '__return_true',
            )
        );
    }

    /**
     * Handle success callback
     *
     * @param \WP_REST_Request $request REST request object
     * @return \WP_REST_Response|\WP_Error
     */
    public function handle_success_callback( $request ) {
        $post_data = $request->get_body_params();

        if ( empty( $post_data['tran_id'] ) ) {
            return new \WP_Error( 'invalid_data', 'Missing transaction ID', array( 'status' => 400 ) );
        }

        $tran_id = sanitize_text_field( $post_data['tran_id'] );
        $amount = isset( $post_data['amount'] ) ? floatval( $post_data['amount'] ) : 0;
        $currency = isset( $post_data['currency'] ) ? sanitize_text_field( $post_data['currency'] ) : 'BDT';

        // Get transaction from database
        $db_handler = Database_Handler::get_instance();
        $transaction = $db_handler->get_transaction_by_tran_id( $tran_id );

        if ( ! $transaction ) {
            return new \WP_Error( 'transaction_not_found', 'Transaction not found', array( 'status' => 404 ) );
        }

        // Validate transaction if status is Pending or Processing
        if ( in_array( $transaction->status, array( 'Pending', 'Processing' ) ) ) {
            // Update config file
            $config_manager = SSLCommerz_Config::get_instance();
            $config_manager->update_config_file();

            // Include SSLCommerz library
            require_once PLUGIN_BASE_PATH . '/inc/libs/SSLCommerz-PHP-master/lib/SslCommerzNotification.php';

            try {
                $sslcz = new \SslCommerz\SslCommerzNotification();
                $validated = $sslcz->orderValidate( $post_data, $tran_id, $amount, $currency );

                if ( $validated ) {
                    // Update transaction status
                    $additional_data = array(
                        'sslcommerz_val_id' => $post_data['val_id'] ?? '',
                        'bank_tran_id' => $post_data['bank_tran_id'] ?? '',
                        'card_type' => $post_data['card_type'] ?? '',
                        'card_issuer' => $post_data['card_issuer'] ?? '',
                        'payment_date' => isset( $post_data['tran_date'] ) ? $post_data['tran_date'] : current_time( 'mysql' ),
                    );

                    $db_handler->update_transaction_status( $tran_id, 'Success', $additional_data );

                    // Redirect to Laravel success URL
                    $this->redirect_to_laravel_success( $transaction->order_id, $tran_id, $amount );
                } else {
                    $db_handler->update_transaction_status( $tran_id, 'Failed' );
                    $this->redirect_to_laravel_fail( $transaction->order_id, 'Payment validation failed' );
                }
            } catch ( \Exception $e ) {
                $this->put_program_logs( 'Success callback error: ' . $e->getMessage() );
                $db_handler->update_transaction_status( $tran_id, 'Failed' );
                $this->redirect_to_laravel_fail( $transaction->order_id, 'Payment validation error' );
            }
        } else {
            // Transaction already processed, redirect to success
            $this->redirect_to_laravel_success( $transaction->order_id, $tran_id, $amount );
        }

        // This should not be reached, but just in case
        return new \WP_REST_Response( array( 'status' => 'success' ), 200 );
    }

    /**
     * Handle fail callback
     *
     * @param \WP_REST_Request $request REST request object
     * @return \WP_REST_Response|\WP_Error
     */
    public function handle_fail_callback( $request ) {
        $post_data = $request->get_body_params();

        if ( empty( $post_data['tran_id'] ) ) {
            return new \WP_Error( 'invalid_data', 'Missing transaction ID', array( 'status' => 400 ) );
        }

        $tran_id = sanitize_text_field( $post_data['tran_id'] );
        $error = isset( $post_data['error'] ) ? sanitize_text_field( $post_data['error'] ) : 'Payment failed';

        // Get transaction from database
        $db_handler = Database_Handler::get_instance();
        $transaction = $db_handler->get_transaction_by_tran_id( $tran_id );

        if ( $transaction ) {
            // Update transaction status
            $db_handler->update_transaction_status( $tran_id, 'Failed' );

            // Redirect to Laravel fail URL
            $this->redirect_to_laravel_fail( $transaction->order_id, $error );
        } else {
            // Transaction not found, still redirect with error
            $this->redirect_to_laravel_fail( '', $error );
        }

        return new \WP_REST_Response( array( 'status' => 'failed' ), 200 );
    }

    /**
     * Handle cancel callback
     *
     * @param \WP_REST_Request $request REST request object
     * @return \WP_REST_Response|\WP_Error
     */
    public function handle_cancel_callback( $request ) {
        $post_data = $request->get_body_params();

        if ( empty( $post_data['tran_id'] ) ) {
            return new \WP_Error( 'invalid_data', 'Missing transaction ID', array( 'status' => 400 ) );
        }

        $tran_id = sanitize_text_field( $post_data['tran_id'] );

        // Get transaction from database
        $db_handler = Database_Handler::get_instance();
        $transaction = $db_handler->get_transaction_by_tran_id( $tran_id );

        if ( $transaction ) {
            // Update transaction status
            $db_handler->update_transaction_status( $tran_id, 'Cancelled' );

            // Redirect to Laravel fail URL
            $this->redirect_to_laravel_fail( $transaction->order_id, 'Payment cancelled', 'cancelled' );
        } else {
            // Transaction not found, still redirect
            $this->redirect_to_laravel_fail( '', 'Payment cancelled', 'cancelled' );
        }

        return new \WP_REST_Response( array( 'status' => 'cancelled' ), 200 );
    }

    /**
     * Handle IPN callback
     *
     * @param \WP_REST_Request $request REST request object
     * @return \WP_REST_Response|\WP_Error
     */
    public function handle_ipn_callback( $request ) {
        $post_data = $request->get_body_params();

        if ( empty( $post_data['tran_id'] ) || empty( $post_data['status'] ) ) {
            return new \WP_Error( 'invalid_data', 'Missing required data', array( 'status' => 400 ) );
        }

        $tran_id = sanitize_text_field( $post_data['tran_id'] );
        $status = sanitize_text_field( $post_data['status'] );

        // Get transaction from database
        $db_handler = Database_Handler::get_instance();
        $transaction = $db_handler->get_transaction_by_tran_id( $tran_id );

        if ( ! $transaction ) {
            return new \WP_Error( 'transaction_not_found', 'Transaction not found', array( 'status' => 404 ) );
        }

        // Update config file
        $config_manager = SSLCommerz_Config::get_instance();
        $config_manager->update_config_file();

        // Include SSLCommerz library
        require_once PLUGIN_BASE_PATH . '/inc/libs/SSLCommerz-PHP-master/lib/SslCommerzNotification.php';

        try {
            $sslcz = new \SslCommerz\SslCommerzNotification();

            switch ( $status ) {
                case 'VALID':
                    if ( $transaction->status === 'Pending' ) {
                        $amount = isset( $post_data['amount'] ) ? floatval( $post_data['amount'] ) : $transaction->amount;
                        $currency = isset( $post_data['currency'] ) ? sanitize_text_field( $post_data['currency'] ) : $transaction->currency;

                        $validation = $sslcz->orderValidate( $post_data, $tran_id, $amount, $currency );

                        if ( $validation ) {
                            $additional_data = array(
                                'sslcommerz_val_id' => $post_data['val_id'] ?? '',
                                'bank_tran_id' => $post_data['bank_tran_id'] ?? '',
                                'card_type' => $post_data['card_type'] ?? '',
                                'card_issuer' => $post_data['card_issuer'] ?? '',
                                'payment_date' => isset( $post_data['tran_date'] ) ? $post_data['tran_date'] : current_time( 'mysql' ),
                            );

                            $db_handler->update_transaction_status( $tran_id, 'Processing', $additional_data );
                            return new \WP_REST_Response( array( 'message' => 'Payment Record Updated Successfully' ), 200 );
                        } else {
                            $db_handler->update_transaction_status( $tran_id, 'Failed' );
                            return new \WP_REST_Response( array( 'message' => 'Payment was not valid' ), 200 );
                        }
                    } else {
                        return new \WP_REST_Response( array( 'message' => 'This order is already Successful' ), 200 );
                    }
                    break;

                case 'FAILED':
                    $db_handler->update_transaction_status( $tran_id, 'Failed' );
                    return new \WP_REST_Response( array( 'message' => 'Payment was failed' ), 200 );

                case 'CANCELLED':
                    $db_handler->update_transaction_status( $tran_id, 'Cancelled' );
                    return new \WP_REST_Response( array( 'message' => 'Payment was Cancelled' ), 200 );

                default:
                    return new \WP_Error( 'invalid_status', 'Invalid payment status', array( 'status' => 400 ) );
            }
        } catch ( \Exception $e ) {
            $this->put_program_logs( 'IPN callback error: ' . $e->getMessage() );
            return new \WP_Error( 'processing_error', 'Error processing IPN', array( 'status' => 500 ) );
        }
    }

    /**
     * Redirect to Laravel success URL
     *
     * @param string $order_id Order ID
     * @param string $tran_id Transaction ID
     * @param float $amount Amount
     */
    private function redirect_to_laravel_success( $order_id, $tran_id, $amount ) {
        $success_url = get_option( 'sslcommerz_laravel_success_url' );

        if ( empty( $success_url ) ) {
            wp_die( 'Payment successful but callback URL not configured.', 'Payment Success', array( 'response' => 500 ) );
        }

        $redirect_url = add_query_arg(
            array(
                'order_id' => $order_id,
                'status' => 'success',
                'transaction_id' => $tran_id,
                'amount' => $amount,
            ),
            $success_url
        );

        wp_redirect( $redirect_url );
        exit;
    }

    /**
     * Redirect to Laravel fail URL
     *
     * @param string $order_id Order ID
     * @param string $reason Failure reason
     * @param string $status Status (failed or cancelled)
     */
    private function redirect_to_laravel_fail( $order_id, $reason = '', $status = 'failed' ) {
        $fail_url = get_option( 'sslcommerz_laravel_fail_url' );

        if ( empty( $fail_url ) ) {
            wp_die( 'Payment failed. ' . $reason, 'Payment Error', array( 'response' => 500 ) );
        }

        $redirect_url = add_query_arg(
            array(
                'order_id' => $order_id,
                'status' => $status,
                'reason' => urlencode( $reason ),
            ),
            $fail_url
        );

        wp_redirect( $redirect_url );
        exit;
    }

}
