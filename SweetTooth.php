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
}

?>