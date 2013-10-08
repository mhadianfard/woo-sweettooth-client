<?php
/**
 * Plugin Name: Sweet Tooth Client for WooCommerce
 * Plugin URI: http://www.sweettoothloyalty.com
 * Description: A sample Sweet Tooth Client built for WooCommerce
 * Version: 1.0.0
 * Author: Sweet Tooth Inc.
 * Author URI: http://www.sweettoothhq.com
 * License: GPL2 
 */


/**
 * Check if WooCommerce is active
 **/
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
    
    /**
     * Check if Sweet Tooth class has already been decleared
     */
    if ( !class_exists('SweetTooth') ) {
        
        /**
         * Include SweetTooth classes and Init SweetTooth
         */        
        include_once ( 'SweetTooth.php' );
        include_once ( 'ApiClient.php' );
        include_once ( 'ActionListener.php' );
                
        // Additional reference to SweetTooth singleton object on the GLOBALS array.  
        $GLOBALS['sweettooth'] = SweetTooth::getInstance();                
    }
}

?>