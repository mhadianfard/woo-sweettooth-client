<?php

/**
 * This class uses WooCommerce's Coupon System to offer redemptions.
 * You can setup redemption options in the setupRedemptionOptions() function.
 * 
 * @todo The ideal way of implementing most functions of this class is to
 * store redemption options in the database.
 * 
 * @see SweetTooth_RedemptionClient::setupRedemptionOptions
 * @see SweetTooth_RedemptionClient::addRedemptionOption
 * @link http://docs.woothemes.com/document/create-a-coupon-programatically/
 */
class SweetTooth_RedemptionClient
{
    /**
     * Stores array of coupon redemption options
     * @see SweetTooth_RedemptionClient::getRedemptionOptions()
     * @var array
     */
    protected $_options = array();
    
    /**
     * Public constructor
     */
    public function __construct()
    {
        // Setup Ajax Action
        add_action('wp_ajax_sweettooth_customer_coupon_redemption', array($this, 'redeemAction'));
    }
    
    /**
     * Ajax entery point to redeem a selected option coming from the http param "selected".
     * This will first double check the customer's balance and validate the selected redemption option.
     * Then we deduct points from their account,
     * once that is done, we create a copuon code and send it back in the response.
     * 
     * @return string http response.
     */
    public function redeemAction()
    {
        $response = array();
        
        try {
            if (!isset($_REQUEST['selected'])){
                throw new Exception("Please select a redemption option first.");
            }
            
            $sweettooth = SweetTooth::getInstance();
            $redemptionOptions = $this->getEligibleRedemptionOptions();
            $selectedOptionId = $_REQUEST['selected'];
            
            if (!array_key_exists($selectedOptionId, $redemptionOptions)){
                throw new Exception("You are not elligble for the selected option.");
            }
            
            $remoteCustomerId = $sweettooth->getCustomerRemoteId();

            $selectedOption = $redemptionOptions[$selectedOptionId];
            /**
             * Right now this plugin only understands 'fixed points' type redemption
             * options, where the customer exchanges a fixed number of points for
             * a fixed reward. At the time of this writing this is the only type of
             * exchange supported by the Sweet Tooth Loyalty API, however it's likely
             * other exchange types will be added over time. In that case this code
             * will need to be adjusted accordingly.
             */
            if ($selectedOption['points_exchange']['type'] != 'fixed_points') {
              error_log("Customer selected redemption option " . $redemptionOptionId . " which has a points_exchange type of " . $selectedOption['points_exchange']['type'] . ". Unfortunately this plugin doesn't know how to handle this type of redemption :(");
              throw new Exception("You are not elligible for the selected option.");
            }

            $pointsToDeduct = (-1) * intval($redemptionOption['points_exchange']['points_amount']);

            try {
                $reponse = $sweettooth->getApiClient()->createRedemption($remoteCustomerId,  $pointsToDeduct, $selectedOptionId);
                
            } catch (Exception $e){
                // Original exception probably contains data we can't share with the customer. Throw another one.
                error_log("Couldn't create a points transfer. " . $e->getMessage());
                throw new Exception("Unable to deduct points from your Sweet Tooth account.");
            }                    
            
            $couponCode = $this->createCopoun($response);
            
            $response['success'] = true;
            $response['coupon_code'] = $couponCode;
            $response['new_balance'] = $sweettooth->getCustomerBalance() + $pointsToDeduct;
            
        } catch (Exception $e){
            $response['success'] = false;
            $response['message'] = $e->getMessage();
        }

        echo json_encode($response);
        
        die();
    }
        
    /**
     * Based on the $availablePointsToSpend, return an array of redemption options which we have enough points to spend towards.    
     * @return array of redemption options.
     */
    public function getEligibleRedemptionOptions()
    {
        $sweettooth = SweetTooth::getInstance();
        return $sweettooth->getApiClient()->getRedemptionOptions($sweettooth->getCustomerRemoteId());
    }
    
    public function createCopoun($redemptionOption)
    {
        // Coupon Code is a function of the current system time
        $coupon_code = base64_encode(microtime());
        
        $amount = $redemptionOption['coupon_amount'];
        $discount_type = $redemptionOption['discount_type'];        
        $coupon = array(
                'post_title'     => $coupon_code,
                'post_content'   => $redemptionOption['option_label'],
                'post_status'    => 'publish',
                'post_author'    => 1,
                'post_type'		 => 'shop_coupon'
        );
        
        $new_coupon_id = wp_insert_post( $coupon );
        
        // Add meta
        update_post_meta( $new_coupon_id, 'discount_type', $discount_type );
        update_post_meta( $new_coupon_id, 'coupon_amount', $amount );
        update_post_meta( $new_coupon_id, 'individual_use', 'no' );
        update_post_meta( $new_coupon_id, 'usage_limit', 1 );        
        update_post_meta( $new_coupon_id, 'product_ids', '' );
        update_post_meta( $new_coupon_id, 'exclude_product_ids', '' );
        update_post_meta( $new_coupon_id, 'expiry_date', '' );
        update_post_meta( $new_coupon_id, 'apply_before_tax', 'yes' );
        update_post_meta( $new_coupon_id, 'free_shipping', 'no' );
        
        // Override options with anything else which was explicitly mentioned
        foreach ($redemptionOption['coupon_options'] as $option => $value){
            update_post_meta( $new_coupon_id, $option, $value );
        }
        
        return $coupon_code;
    }
}
