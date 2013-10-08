<?php

/**
 * This class is responsible for setting up and listening in for WordPress and WooCommerce actions.
 * We can access the singleton instance of the Sweet Tooth Client from here and therefore get a hold
 * of the Api Client to send out any events we need to.
 */
class SweetTooth_ActionListener
{
    /**
     * Sets up all necessary listeners for WP / WC actions.
     * Entry point into the action listener.
     * 
     * @return SweetTooth_ActionListener
     */
    public function setupActions()
    {
        // This is called once the order is marked as complete
        add_action( 'woocommerce_order_status_completed', array($this, 'onOrderStatusComplete') );
        
        return $this;
    }
    
    /**
     * Prepares an order event and sends it off to the ST server.
     * This function is called when a WC order has been marked complete
     * and a "woocommerce_order_status_completed" action is triggered.
     * 
     * @param string $order_id. WooCommerce order_id
     * @return SweetTooth_ActionListener
     */
    public function onOrderStatusComplete($order_id)
    {
        $order = new WC_Order($order_id);
        $customer_id = $order->user_id;
        $customer = array();
        
        if (empty($customer_id)){
            /**
             * This was as Guest checkout.
             * We'll use billing info to identify the customer.
             */
             $customer['first_name'] = $order->billing_first_name;
             $customer['last_name'] = $order->billing_last_name;
             $customer['email'] = $order->billing_email;

        } else {
            /**
             * The customer has an account with us.
             */
             $user = get_user_by('id', $customer_id);
             $customer['external_id'] = $customer_id;
             $customer['first_name'] = $user->first_name;
             $customer['last_name'] = $user->last_name;
             $customer['email'] = $user->user_email;
        }

        $this->_getApiClient()->sendEvent('order', $customer, $order, $order_id);
        error_log("Order event sent!");
        
        return $this;
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
    
    /**
     * Returns singleton refrence to the Sweet Tooth Api client object
     * @return SweetTooth_ApiClient
     */
    protected function _getApiClient()
    {
        return $this->_getSweetToothClient()->getApiClient();
    }
}
?>
