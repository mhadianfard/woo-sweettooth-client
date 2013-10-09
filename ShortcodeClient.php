<?php

/**
 * The Shortcode Client is responsible for providing Sweet Tooth data
 * in the form of shortcodes for use inside templates and custom content in WP
 * 
 * @link http://codex.wordpress.org/Shortcode_API
 *
 */
class SweetTooth_ShortcodeClient
{
   /**
    * Function to setup shortcodes within WordPress
    */ 
   public function setupShortcodes()
   {
       // Use "[sweettooth_customer_points_balance]" shortcode to generate the points balance
       add_shortcode( 'sweettooth_customer_points_balance', array($this, 'getCustomerBalance'));
              
       return $this;
   }

   /**
    * Accesses the Sweet Tooth server to get a points balance for the logged in customer.
    * 
    * Note that polling the server for a points balance too frequently is not very efficient.
    * If you're expecting to do this often, you should implement a caching system
    * to reduce the number of calls to the server.
    * 
    * @see SweetTooth::getCustomerBalance()
    * @return string
    */
   public function getCustomerBalance($atts)
   {
      extract( shortcode_atts( array(
               'default' => 'N/A',
               'label' => "Points"
      ), $atts ) );
       
      $balance = $this->_getSweetToothClient()->getCustomerBalance();
      if (!is_null($balance)){
          return "{$balance} {$label}";    
      }
      
      return $default;
   }
      
   /**
    * Returns singleton reference to the Sweet Tooth client object
    *
    * @return SweetTooth
    */
   protected function _getSweetToothClient()
   {
       return SweetTooth::getInstance();
   }  
}
?>