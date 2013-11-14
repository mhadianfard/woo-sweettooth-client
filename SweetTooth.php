<?php

/**
 * The SweetTooth Client class holds instances of the ActionListener and ApiClient.
 * This is the main hub for SweetTooth functionality.
 * 
 * @see SweetTooth_ApiClient
 * @see SweetTooth_ActionListener
 *
 */
class SweetTooth
{
    
    const REMOTE_ID_META_FIELD = 'st_loyalty_remote_customer_id';

    /**
     * Stores a singleton reference of this class.
     * 
     * @var SweetTooth
     * @see SweetTooth::getInstance()
     */
    protected static $_instance = null;
    
    /**
     * Stores a singleton reference of the api client object
     * 
     * @var SweetTooth_ApiClient
     * @see SweetTooth::getApiClient()
     */
    protected $_apiClient = null;

    /**
     * Stores a singleton refrence of the action listener object
     * 
     * @var SweetTooth_ActionListener
     * @see SweetTooth::getActionListener()
     */
    protected $_actionListener = null;
    
    /**
     * Stores a singleton refrence to the shortcode client object
     * 
     * @var SweetTooth_ShortcodeClient
     * @see SweetTooth::getShortcodeClient()
     */
    protected $_shortcodeClient = null;
    
    /**
     * Stores a singleton refrence to the redemption client object
     * @var SweetTooth_RedemptionClient
     * @see SweetTooth::getRedemptionClient()
     */
    protected $_redemptionClient = null;
    
    /**
     * Stores the remote customer data if available
     * @var null|boolean|array
     * @see SweetTooth::getCustomerBalance()
     */
    protected $_remoteCustomerData = null;
    
    
    /**
     * Protected constructor to enforce singleton pattern for this class.
     * 
     * @see SweetTooth::getInstance()
     * @access protected
     */
    protected function __construct() {}
    
    /**
     * Makes sure all components of the Sweet Tooth client are initialized and ready to go.
     * 
     * @return SweetTooth
     */
    protected function _setupSweetToothClient()
    {
        $this->getActionListener()->setupActions();
        $this->getShortcodeClient()->setupShortcodes();

        return $this;
    }
        
    /**
     * Get the singleton Api Client object.
     * 
     * Sever calls are made through the Api Client.
     * @return SweetTooth_ApiClient
     */
    public function getApiClient()
    {
        if (!isset($this->_apiClient)){
            $this->_apiClient = new SweetTooth_ApiClient();
        }
        
        return $this->_apiClient;
    }

    /**
     * Get the singleton Action Listener object.
     * The Action Listener is responsible for handling WooCommerce actions.
     * 
     * @return SweetTooth_ActionListener
     */
    public function getActionListener()
    {
        if (!isset($this->_actionListener)){
            $this->_actionListener = new SweetTooth_ActionListener();
        }
        
        return $this->_actionListener;
    }
    
    /**
     * Get the singleton Shortcode Client.
     * The Shortcode Client is responsible for providing Sweet Tooth data
     * in the form of shortcodes for use inside templates and custom content in WP
     * 
     * @return SweetTooth_ShortcodeClient
     */
    public function getShortcodeClient()
    {
        if (!isset($this->_shortcodeClient)){
            $this->_shortcodeClient = new SweetTooth_ShortcodeClient();
        }
        
        return $this->_shortcodeClient;
    }

    /**
     * Get the singleton Redemption Client.
     * The Redemption Client is responsible for setting up and providing redemption options
     * based on available features supported in WooCommerce and it's coupon system.    
     *
     * @return SweetTooth_RedemptionClient
     */
    public function getRedemptionClient()
    {
        if (!isset($this->_redemptionClient)){
            $this->_redemptionClient = new SweetTooth_RedemptionClient();
        }
    
        return $this->_redemptionClient;
    }
    
    
    /**
     * Get's the singleton instance of the SweetTooth object.
     * 
     * @return SweetTooth
     */
    public static function getInstance()
    {
        if (!isset(SweetTooth::$_instance)){
            SweetTooth::$_instance = new SweetTooth();
            SweetTooth::$_instance->_setupSweetToothClient();
        }
        
        return SweetTooth::$_instance;
    }
    
    /**
     * Accesses the Sweet Tooth server to get a points balance for the logged in customer.
     * @see SweetTooth::getRemoteCustomerData()
     *
     * @return boolean|int. Customer's points balance if one is available. Boolean false otherwise.
     */    
    public function getCustomerBalance()
    {
        $customerData = $this->getRemoteCustomerData();
        if ($customerData === false || !isset($customerData['points_balance'])){
            return false;
        }     

        return intval($customerData['points_balance']);            
    }
    
    /**
     * Accesses the Sweet Tooth server to get a remtote customer ID for the logged in customer.
     * @see SweetTooth::getRemoteCustomerData()
     *
     * @return boolean|string. Customer's remote ID one is available. Boolean false otherwise.
     */
    public function getCustomerRemoteId()
    {
        if (is_user_logged_in()) {
          if ($remote_id = get_user_meta(wp_get_current_user()->ID, self::REMOTE_ID_META_FIELD)) {
            return $remote_id;
          }
        }

        $customerData = $this->getRemoteCustomerData();
        if ($customerData === false || !isset($customerData['id'])){
            return false;
        }
    
        return $customerData['id'];
    }    

    /**
     * Access the Sweet Tooth server to create a customer corresponding to the currently
     * logged in WP User.
     *
     * @return boolean, true if a customer was created.
     */
    public function createCurrentCustomer()
    {
      // Bail early if no user is logged in (to WP) or we are able to retrieve
      // customer data from Sweet Tooth Loyalty for the logged in user (exists).
      if (!is_user_logged_in() || $this->getRemoteCustomerData()) {
        return false;
      }

      $current_user = wp_get_current_user();
      $customer = array(
        'first_name' => $current_user->user_firstname,
        'last_name' => $current_user->user_lastname,
        'email' => $current_user->user_email
      );

      try {
        $this->_remoteCustomerData = $this->getApiClient()->createCustomer($customer);
        update_user_meta($current_user->ID, self::REMOTE_ID_META_FIELD, $this->_remoteCustomerData['id']);
        return true;
      } catch (Exception $e) {
        error_log("Problem createing customer. " . $e->getMessage());
      } 
    }

    /**
     * Accesses the Sweet Tooth server to get any information about the customer
     *
     * Note that polling the server for customer data too frequently is not very efficient.
     * If you're expecting to do this often, you should implement a caching system
     * to reduce the number of calls to the server.
     *
     * @return boolean|array. Customer data if one is available. Boolean false otherwise.
     */
    public function getRemoteCustomerData()
    {   
        // Make sure we call the server only once during the current http request.
        if (is_null($this->_remoteCustomerData)){            
            /**
             * Debug statement to keep track of calls to the server.
             * @todo Take out
             */
            error_log("Gettting customer data from server...");
            
            $this->_remoteCustomerData = false;            
            try {
                $currentUser = wp_get_current_user();
                $currentUserId = $currentUser->ID;
                
                // If there's someone logged in right now
                if (!empty( $currentUserId )){        
                    if ($remoteId = get_user_meta($currentUserId, SweetTooth::REMOTE_ID_META_FIELD, true)) {
                      $this->_remoteCustomerData = $this->getApiClient()->getCustomerByRemoteId($remoteId);
                    } else {
                      $email = $currentUser->user_email;
                      if (empty($email)){
                          throw new Exception("No email address for current customer.");
                      }
                              
                      $this->_remoteCustomerData = $this->getApiClient()->getCustomerByEmail($email);                    
                    }
                }
        
            } catch (Exception $e){
                error_log("Problem loading customer data. " . $e->getMessage());
            }
        }
    
        return $this->_remoteCustomerData;
    }
}

?>
