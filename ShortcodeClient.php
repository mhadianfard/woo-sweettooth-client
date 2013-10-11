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
       add_shortcode( 'sweettooth_customer_coupon_redemption', array($this, 'getCouponRedemptionBlock') );
              
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
               'no_login'     => 'Please login to see see your points balance.',
               'zero_points'  => '',
               'label'        => "Points"
      ), $atts ) );
       
      $balance = $this->_getSweetToothClient()->getCustomerBalance();
      
      if ($balance === false) {
          return $no_login;         
      }
      
      if ($balance == 0 && !empty($zero_points)){
          return $zero_points;
      }
      
      return "{$balance} {$label}";
   }
   
   public function getCouponRedemptionBlock($atts)
   {
       extract( shortcode_atts( array(
               'no_login'     => 'Please login to see some redemption options.',
               'no_options'   => "You don't have any redemption options at this point."
       ), $atts ) );
       
       $balance = $this->_getSweetToothClient()->getCustomerBalance();
       if ($balance === false){
           return $no_login;
           
       } else {
           $redemptionOptions = $this->_getSweetToothClient()->getRedemptionClient()->getEligibleRedemptionOptions($balance);
       }
       
       if (empty($redemptionOptions)){
           return $no_options;
           
       } else {
           $optionsHtml = "";
           foreach ($redemptionOptions as $index => $option){
               $optionsHtml .= "[{$index}] {$option['option_label']} <i>(costs {$option['points_redemption']} Points)</i><br/>";
           }
           
           return $optionsHtml;
       }
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