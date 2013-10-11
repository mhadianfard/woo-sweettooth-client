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
     * Multi-dimensional array used to store eligible redemption options
     * for different point balances as needed.  
     * @var unknown_type
     */
    protected $_eligibleOptions = array();
    
    /**
     * Used to define the various redemption options available.
     * This function is called when we're setting up the Sweet Tooth Client.
     * 
     * @return SweetTooth_RedemptionClient
     */
    public function setupRedemptionOptions()
    {
        // Option to spend 1000 points for 100% off and free shipping
        $this->addRedemptionOption(100, 1000, "Spend 1000 Points and pay nothing!", 'percent', array('free_shipping' => 'yes'));
        
        // Option to spend 10 points for a $5 coupon
        $this->addRedemptionOption(5.00, 10, "Get $5 off by spending 10 Points.");
        
        // Option to spend 15 points for a 20% cart discount coupon
        $this->addRedemptionOption(20, 15, null, 'percent');
        
        // Option to spend 25 points for a $10 coupon with free shipping
        $this->addRedemptionOption(10, 25, "Get $10 off with free shipping for 25 Points!", null, array('free_shipping' => 'yes'));        
        
        // Mark the setup ready. No more redemption options should be introduced after this. 
        $this->_isSetupReady = true;

        return $this;
    }
        
    /**
     * Public constructor
     */
    public function __construct()
    {
        // Setup Ajax Action
        add_action('wp_ajax_sweettooth_customer_coupon_redemption', array($this, 'redeemAction'));
    }
    
    /**
     * Ajax Entery Point...
     */
    public function redeemAction()
    {
        echo "Ajax Response";
        die();
    }
        
    /**
     * Based on the $availablePointsToSpend, return an array of redemption options which we have enough points to spend towards.    
     * @return array of redemption options.
     */
    public function getEligibleRedemptionOptions($availablePointsToSpend)
    {
        if (empty($availablePointsToSpend) || !is_int($availablePointsToSpend)  || $availablePointsToSpend <= 0){
            error_log("Problem retrieving eligible redemption options. {$availablePointsToSpend} must be a positive integer.");
            return array();
        }
        
        // If we've figured this out once already, don't do it again. 
        if (!isset($this->_eligibleOptions[$availablePointsToSpend])){
            $eligibleOptions = array();
            /*
             * Loop through all our redemption options, pick the ones we have enough points to spend on, throw them into a new array
             * with an index identical to the original index.
             * 
             * To avoid this overhead more than once during an http request, we'll save the result around in case we need it again.
             * @todo This would be much better done using MySQL queries if the redemption options were stored in the Database.
             */         
            foreach ($this->_options as $originalIndex => $redemptionOption){
                if ($redemptionOption['points_redemption'] <= $availablePointsToSpend){
                    $eligibleOptions[$originalIndex] = $redemptionOption;
                }
            }
            
            $this->_eligibleOptions[$availablePointsToSpend] = $eligibleOptions;
        }
        
        return $this->_eligibleOptions[$availablePointsToSpend];
    }
    
    /**
     * Builds and stores a redemption option array with the following indices: 
     *     coupon_amount, points_redemption, discount_type, option_label, coupon_options          
     * You can only call addRedemptionOptions from the setupRedemptionOptions() function. 
     * You can retreive this and other options by calling getEligibleRedemptionOptions().
     * 
     * @param number $coupon_amount. Fixed positive dollar or percentage amount for the discount depending on other options.
     * @param int $points_redemption. A positive whole number representing the number of points to deduct for this redemption. 
     * @param string $option_label (optional). A sentence or label used to identify this redemption option. If not supplied we'll generate one.
     * @param string $discount_type (optional). One of the following four: "fixed_cart" (default), "percent", "fixed_product", "percent_product"
     * @param array $coupon_options (optional). Array of extra options for generating a coupon in WooCommerce.
     *     Anything specified here will overwrite other coupon settings.
     * 
     * @return boolean|array. The redemption option which was created or boolean false if there was a problem with the input. 
     */
    public function addRedemptionOption($coupon_amount, $points_redemption, $option_label = null, $discount_type = null, $coupon_options = null)
    {
        try {
            if (isset($this->_isSetupReady) && $this->_isSetupReady){
                throw new Exception("You can only add redemption options within the setupRedemptionOptions() function.");
            }
            
            $redemption = array();
            
            if (empty($coupon_amount) || !is_numeric($coupon_amount) || $coupon_amount <= 0){
                throw new Exception("Discount amount must be a number greater than zero.");
            }
            $redemption['coupon_amount'] = $coupon_amount;
            
            
            if (empty($points_redemption) || !is_int($points_redemption)  || $points_redemption <= 0){
                throw new Exception("Points to deduct must be a whole number greater than zero.");
            }
            $redemption['points_redemption'] = $points_redemption;
            
            
            if (empty($discount_type)){
                $discount_type = 'fixed_cart';
                
            } else {
                $allowedTypes = array('fixed_cart', 'percent', 'fixed_product', 'percent_product');
                if (!in_array($discount_type, $allowedTypes)){
                    throw new Exception("{$discount_type} is not a supported discount type within WooCommerce.");
                }    
            }
            $redemption['discount_type'] = $discount_type;
            
            
            if (empty($option_label)){
                $option_label = $this->_getGenericOptionLabel($coupon_amount, $points_redemption, $discount_type);
            }
            $redemption['option_label'] = $option_label;
            
            
            if (empty($coupon_options)){
                $coupon_options = array();
                 
            } elseif (!is_array($coupon_options)){
                throw new Exception("Options must be an array if one is specified.");
            }
            $redemption['coupon_options'] = $coupon_options;
    
            $this->_options[] = $redemption;            
            return $redemption;
            
        } catch(Exception $e) {
            error_log("Problem creating a redemption option. " . $e->getMessage());
            return false;            
        }
    }
    
    /**
     * Based on arguments passed in, this will generate a generic description for a redemption option.
     * 
     * @param number $coupon_amount. Fixed positive dollar or percentage amount for the discount depending on other options.
     * @param int $points_redemption. A positive whole number representing the number of points to deduct for this redemption.
     * @param string $discount_type. One of the following four: "fixed_cart", "percent", "fixed_product", "percent_product".
     * @return string 
     */
    protected function _getGenericOptionLabel($coupon_amount, $points_redemption, $discount_type)
    {
        $discountString = "";
        
        switch ($discount_type) {
            case 'fixed_cart':
                $discountString = "\${$coupon_amount} on your total order";
                break;
            case 'percent':
                $discountString = "{$coupon_amount}% off of your total order";
                break;
            case 'fixed_product':
                $discountString = "\${$coupon_amount} on selected products";
                break;
            case 'percent_product':
                $discountString = "{$coupon_amount}% off of selected products";
                break;
        }
        
        return "Deduct {$points_redemption} Points for a discount of {$discountString}.";        
    }
}