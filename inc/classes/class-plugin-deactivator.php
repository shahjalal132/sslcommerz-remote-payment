<?php

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 */

class Plugin_Deactivator {

    public static function deactivate() {
        self::delete_database_table();
    }

    private static function delete_database_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'sslcommerz_transactions';
        $wpdb->query( "DROP TABLE IF EXISTS $table_name" );
    }

}