<?php

/**
 * Database Handler
 *
 * Handles all database operations for SSLCommerz transactions
 */

namespace BOILERPLATE\Inc;

use BOILERPLATE\Inc\Traits\Singleton;

class Database_Handler {

    use Singleton;

    /**
     * Get table name
     */
    private function get_table_name() {
        global $wpdb;
        return $wpdb->prefix . 'sslcommerz_transactions';
    }

    /**
     * Save new transaction
     *
     * @param array $paymentData Payment data array
     * @return int|false Transaction ID on success, false on failure
     */
    public function save_transaction( $paymentData ) {
        global $wpdb;

        $table_name = $this->get_table_name();

        $data = array(
            'order_id' => sanitize_text_field( $paymentData['value_a'] ?? '' ),
            'order_number' => sanitize_text_field( $paymentData['value_b'] ?? '' ),
            'transaction_id' => sanitize_text_field( $paymentData['tran_id'] ?? '' ),
            'customer_name' => sanitize_text_field( $paymentData['cus_name'] ?? '' ),
            'customer_email' => sanitize_email( $paymentData['cus_email'] ?? '' ),
            'customer_phone' => sanitize_text_field( $paymentData['cus_phone'] ?? '' ),
            'amount' => floatval( $paymentData['total_amount'] ?? 0 ),
            'currency' => sanitize_text_field( $paymentData['currency'] ?? 'BDT' ),
            'status' => 'Pending',
        );

        $result = $wpdb->insert( $table_name, $data );

        if ( $result ) {
            return $wpdb->insert_id;
        }

        return false;
    }

    /**
     * Get transaction by SSLCommerz transaction ID
     *
     * @param string $tranId Transaction ID
     * @return object|null Transaction object or null if not found
     */
    public function get_transaction_by_tran_id( $tranId ) {
        global $wpdb;

        $table_name = $this->get_table_name();
        $tranId = sanitize_text_field( $tranId );

        $result = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table_name WHERE transaction_id = %s",
                $tranId
            )
        );

        return $result;
    }

    /**
     * Get transaction by Laravel order ID
     *
     * @param string $orderId Order ID
     * @return object|null Transaction object or null if not found
     */
    public function get_transaction_by_order_id( $orderId ) {
        global $wpdb;

        $table_name = $this->get_table_name();
        $orderId = sanitize_text_field( $orderId );

        $result = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table_name WHERE order_id = %s ORDER BY created_at DESC LIMIT 1",
                $orderId
            )
        );

        return $result;
    }

    /**
     * Update transaction status and additional data
     *
     * @param string $tranId Transaction ID
     * @param string $status New status
     * @param array $additionalData Additional data to update
     * @return bool True on success, false on failure
     */
    public function update_transaction_status( $tranId, $status, $additionalData = array() ) {
        global $wpdb;

        $table_name = $this->get_table_name();
        $tranId = sanitize_text_field( $tranId );
        $status = sanitize_text_field( $status );

        $data = array(
            'status' => $status,
            'updated_at' => current_time( 'mysql' ),
        );

        // Add additional data if provided
        if ( isset( $additionalData['sslcommerz_val_id'] ) ) {
            $data['sslcommerz_val_id'] = sanitize_text_field( $additionalData['sslcommerz_val_id'] );
        }

        if ( isset( $additionalData['bank_tran_id'] ) ) {
            $data['bank_tran_id'] = sanitize_text_field( $additionalData['bank_tran_id'] );
        }

        if ( isset( $additionalData['card_type'] ) ) {
            $data['card_type'] = sanitize_text_field( $additionalData['card_type'] );
        }

        if ( isset( $additionalData['card_issuer'] ) ) {
            $data['card_issuer'] = sanitize_text_field( $additionalData['card_issuer'] );
        }

        if ( isset( $additionalData['payment_date'] ) ) {
            $data['payment_date'] = sanitize_text_field( $additionalData['payment_date'] );
        }

        $result = $wpdb->update(
            $table_name,
            $data,
            array( 'transaction_id' => $tranId ),
            array( '%s', '%s', '%s', '%s', '%s', '%s', '%s' ),
            array( '%s' )
        );

        return $result !== false;
    }

}
