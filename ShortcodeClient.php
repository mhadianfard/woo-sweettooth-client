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
       // Default rendering paramaters:
       extract( shortcode_atts( array(
               'no_login'         => "Please login to see some redemption options.",
               'no_options'       => "You don't have any redemption options at this point.",
               'form_id'          => "sweettooth_customer_coupon_redemption",
               'wrapper_id'       => "",    // ID of <div> element to wrap this block in. No wrapper if none specified.
               'options_class'    => "st_redemption_options",
               'options_name'     => "st_redemption_options",               
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
           if (!is_admin()) {
               /**
                * Exclude JavaScript from any ajax requests (or admin requests).
                * Add the RedemptionClient JavaScript to the page and
                * make some local variables available in it.
                */
               wp_enqueue_script( 'st-redemption-script', plugins_url( 'woo-sweettooth-client/js/RedemptionClient.js'), array('jquery'));               
               wp_localize_script( 'st-redemption-script', 'st_redemption_ajax_object', array(
                       'ajax_url'      => admin_url( 'admin-ajax.php' ),
                       'ajax_action'   => 'sweettooth_customer_coupon_redemption',
                       'form_id'       => $form_id,
                       'wrapper_id'    => $wrapper_id,
                       'options_class' => $options_class,
                       'options_name'  => $options_name                       
               ));
           }
           
           // Build the options HTML
           $optionsHtml = "<form onsubmit='submitRedemption(); return false;'>";
           foreach ($redemptionOptions as $index => $option){
               $optionsHtml .= "<label>
                                   <input type='radio' name='{$options_name}' class='{$options_class}' value=\"{$index}\" data-points-redemption=\"{$option['points_redemption']}\" />
                                   &nbsp;{$option['option_label']}
                               </label>
                               <br />";
           }
           $optionsHtml .= "   <br />
                               <input type=\"submit\" value=\"Redeem Now\" />
                           </form>";
           
           if (empty($wrapper_id)){
               return $optionsHtml;
           }
           
           return "<div id=\"{$wrapper_id}\">{$optionsHtml}</div>";           
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