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
     * Protected constructor to enforce singleton pattern for this class.
     * 
     * @see SweetTooth::getInstance()
     * @access protected
     */
    protected function __construct()
    {
        /**
         * Setup Actions,
         */
        $this->getActionListener()->setupActions();
        $this->getShortcodeClient()->setupShortcodes();
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
     * Get's the singleton instance of the SweetTooth object.
     * 
     * @return SweetTooth
     */
    public static function getInstance()
    {
        if (!isset(SweetTooth::$_instance)){
            SweetTooth::$_instance = new SweetTooth();
        }
        
        return SweetTooth::$_instance;
    }
    
    /**
     * Accesses the Sweet Tooth server to get a points balance for the logged in customer.
     *
     * Note that polling the server for a points balance too frequently is not very efficient.
     * If you're expecting to do this often, you should implement a caching system
     * to reduce the number of calls to the server.
     * 
     * @return null|int. Customer's points balance if one is available. Null otherwise.
     */
    public function getCustomerBalance()
    {   
        // Make sure we call the server only once during the current http request.
        if (!isset($this->_customerPointsBalance)){
            
            /**
             * Debug statement to keep track of calls to the server.
             * @todo Take out
             */
            error_log("Getting points balance from server...");
            
            $this->_customerPointsBalance = null;            
            try {
                $currentUser = wp_get_current_user();
                $userId = $currentUser->ID;
                if (!empty( $currentUser->ID )){
                    // If there's someone logged in right now
        
                    $email = $currentUser->user_email;
                    if (empty($email)){
                        throw new Exception("No email address for current customer.");
                    }
        
                    $customerData = $this->getApiClient()->getCustomerByEmail($email);        
                    if (isset($customerData['points_balance'])){
                        $this->_customerPointsBalance = intval($customerData['points_balance']);
                    }
                }
        
            } catch (Exception $e){
                error_log("Problem loading customer balance. " . $e->getMessage());
            }
        }
    
        return $this->_customerPointsBalance;
    }
}

?>