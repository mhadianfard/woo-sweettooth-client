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
    const API_KEY_OPT_NAME = 'st_api_key';
    const API_SECRET_OPT_NAME = 'st_api_secret';

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
      # getApiClient will return false if it can't be created
      # or retrieved, eg if credentials aren't defined. In
      # that case we don't do any other setup, so that the plugin
      # esentially acts "deactivated" till an API key & secret
      # are input and saved.
        if ($this->getApiClient()) {
          $this->getActionListener()->setupActions();
          $this->getShortcodeClient()->setupShortcodes();
          # Required so that a redemption client exists, allowing
          # it to register it's ajax hooks. Without this ajax calls
          # break!
          $this->getRedemptionClient();
        }

        add_action( 'admin_menu', array($this, 'settings_menu'));

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
        $api_key = get_option(self::API_KEY_OPT_NAME);
        $api_secret = get_option(self::API_SECRET_OPT_NAME);

        if (!$api_key || !$api_secret) {
          return false;
        }
        if (!isset($this->_apiClient)){
            $this->_apiClient = new SweetTooth_ApiClient($api_key, $api_secret);
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
          if ($remote_id = get_user_meta(wp_get_current_user()->ID, self::REMOTE_ID_META_FIELD, true)) {
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

    public function settings_menu()
    {
      add_options_page( 'Sweet Tooth Loyalty Settings', 'Sweet Tooth Loyalty', 'manage_options', 'st-loyalty', array($this, 'st_loyalty_options'));
    }

    public function st_loyalty_options() 
    {
        //must check that the user has the required capability 
        if (!current_user_can('manage_options'))
        {
            wp_die( __('You do not have sufficient permissions to access this page.') );
        }

        // variables for the field and option names 
        $hidden_field_name = 'st_submit_hidden';
        $api_key_field_name = 'st_api_key';
        $api_secret_field_name = 'st_api_secret';
        $api_key_opt_name = self::API_KEY_OPT_NAME;
        $api_secret_opt_name = self::API_SECRET_OPT_NAME;

        // Read in existing option value from database
        $api_key = get_option( $api_key_opt_name );
        $api_secret = get_option( $api_secret_opt_name );

        // See if the user has posted us some information
        // If they did, this hidden field will be set to 'Y'
        if( isset($_POST[ $hidden_field_name ]) && $_POST[ $hidden_field_name ] == 'Y' ) {
            // Read their posted value
            $api_key = $_POST[ $api_key_field_name ];
            $api_secret = $_POST[ $api_secret_field_name ];

            // Save the posted value in the database
            update_option( $api_key_opt_name, $api_key );
            update_option( $api_secret_opt_name, $api_secret );

            // Put an settings updated message on the screen
            echo '<div class="updated"><p><strong>'. _e('settings saved.', 'st-loyalty' ).'</strong></p></div>';
        }

        // Now display the settings editing screen

        echo '<div class="wrap">';

        // header

        echo "<h2>" . __( 'Sweet Tooth Loyalty Settings', 'st-loyalty' ) . "</h2>";

        // settings form
    
        ?>

        <form name="form1" method="post" action="">
            <input type="hidden" name="<?php echo $hidden_field_name; ?>" value="Y">
            <p><?php _e("Channel API Key:", 'st-loyalty' ); ?> 
            <input type="text" name="<?php echo $api_key_field_name; ?>" value="<?php echo $api_key; ?>" size="20">
            </p>

            <p><?php _e("Channel API Secret:", 'st-loyalty' ); ?> 
            <input type="text" name="<?php echo $api_secret_field_name; ?>" value="<?php echo $api_secret; ?>" size="20">
            </p>
            <p class="submit">
            <input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e('Save Changes') ?>" />
            </p>

        </form>
        </div>
<?php
    }
}

?>
