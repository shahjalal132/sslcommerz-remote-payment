<?php

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 */

class Plugin_Activator {

    public static function activate() {
        self::create_database_table();
        self::create_payment_page();
        self::set_default_options();
    }

    /**
     * Create database table for SSLCommerz transactions
     */
    private static function create_database_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'sslcommerz_transactions';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            order_id VARCHAR(255) NOT NULL,
            order_number VARCHAR(255),
            transaction_id VARCHAR(255) NOT NULL,
            customer_name VARCHAR(255),
            customer_email VARCHAR(255),
            customer_phone VARCHAR(255),
            amount DECIMAL(10,2) NOT NULL,
            currency VARCHAR(10) DEFAULT 'BDT',
            status VARCHAR(20) DEFAULT 'Pending',
            sslcommerz_val_id VARCHAR(255),
            bank_tran_id VARCHAR(255),
            card_type VARCHAR(50),
            card_issuer VARCHAR(100),
            payment_date DATETIME,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY transaction_id (transaction_id),
            KEY order_id (order_id),
            KEY status (status)
        ) $charset_collate;";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );
    }

    /**
     * Create payment page
     */
    private static function create_payment_page() {
        $page_title = 'Payment';
        $page_slug = 'payment';
        
        // Check if page already exists
        $page = get_page_by_path( $page_slug );
        
        if ( ! $page ) {
            $page_data = array(
                'post_title'    => $page_title,
                'post_name'     => $page_slug,
                'post_content'  => '',
                'post_status'   => 'publish',
                'post_type'     => 'page',
                'post_author'   => 1,
            );
            
            $page_id = wp_insert_post( $page_data );
            
            // Store page ID in options for reference
            update_option( 'sslcommerz_payment_page_id', $page_id );
        } else {
            update_option( 'sslcommerz_payment_page_id', $page->ID );
        }
    }

    /**
     * Set default plugin options
     */
    private static function set_default_options() {
        $default_options = array(
            'sslcommerz_store_id' => '',
            'sslcommerz_store_password' => '',
            'sslcommerz_is_sandbox' => true,
            'sslcommerz_encryption_key' => '',
            'sslcommerz_laravel_success_url' => '',
            'sslcommerz_laravel_fail_url' => '',
        );

        foreach ( $default_options as $option_name => $default_value ) {
            if ( get_option( $option_name ) === false ) {
                update_option( $option_name, $default_value );
            }
        }
    }

}