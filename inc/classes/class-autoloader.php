<?php
/**
 * Bootstraps the plugin. load class.
 */

namespace BOILERPLATE\Inc;

use BOILERPLATE\Inc\Traits\Singleton;

class Autoloader {
    use Singleton;

    protected function __construct() {

        // load class.
        I18n::get_instance();
        Enqueue_Assets::get_instance();
        // Admin_Top_Menu::get_instance();
        Admin_Sub_Menu::get_instance();
        APIS::get_instance();
        
        // Load SSLCommerz payment classes
        Payment_Decryption::get_instance();
        Database_Handler::get_instance();
        SSLCommerz_Config::get_instance();
        SSLCommerz_Wrapper::get_instance();
        Payment_Receiver::get_instance();
        Payment_Callbacks::get_instance();
    }
}