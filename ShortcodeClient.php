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
               'no_login'         => 'Please login to see see your points balance.',
               'zero_points'      => '',
               'label'            => "Points",
               'wrapper_class'    => "st_points_balance" // HTML class for the <span> element to wrap this block in. No wrapper if none specified.
      ), $atts ) );
       
      $customer = $this->_getSweetToothClient()->getRemoteCustomerData();
      if (!$customer) {
        $this->_getSweetToothClient()->createCurrentCustomer();
      }

      $balance = $this->_getSweetToothClient()->getCustomerBalance();
      
      if ($balance === false) {
          return $no_login;         
      }
      
      if ($balance == 0 && !empty($zero_points)){
          return $zero_points;
      }
      
      if (empty($wrapper_class)){
          return "{$balance} {$label}";
      }
      
      return "<span class='{$wrapper_class}'>
                  <span class='{$wrapper_class}_amount'>{$balance}</span>
                  <span class='{$wrapper_class}_label'>{$label}</span>
              </span>";
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
               'balance_class'    => "st_points_balance"  // HTML class to update new points balance with after a redemption               
       ), $atts ) );
       
       if ( !is_user_logged_in() ) {
           return $no_login;
       }

       $redemptionOptions = $this->_getSweetToothClient()->getRedemptionClient()->getEligibleRedemptionOptions();
       
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
                       'options_name'  => $options_name,
                       'balance_class' => $balance_class         
               ));
           }
           
           // Build the options HTML
           $optionsHtml = "<form id='{$form_id}' onsubmit='submitRedemption(); return false;'>";
           foreach ($redemptionOptions as $index => $option){
               $optionsHtml .= "<label>
                                   <input type='radio' name='{$options_name}' class='{$options_class}' value=\"{$option['id']}\" />
                                   &nbsp;{$option['name']}
                               </label>
                               <br />";
           }
           $optionsHtml .= "   <br />
                               <input type=\"submit\" value=\"Redeem Now\" />
                           </form>";
           
           if (empty($wrapper_id)){
               return $optionsHtml;
           }
           
           return "<div id='{$wrapper_id}'>{$optionsHtml}</div>";           
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
