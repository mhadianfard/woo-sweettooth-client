<?php

require_once ( 'lib/pest/PestJSON.php' );

/**
 * The SweetTooth Api Client used to communicate with the RESTful Api.
 * 
 * Wraps Pest, a RESTful client library which essentially wraps cURL.
 * @link https://github.com/educoder/pest
 */
class SweetTooth_ApiClient
{    
    /**
     * @var string     
     * @example "https://api.sweettoothloyalty.com/"
     * @todo Move to Options Interface
     */
    protected $apiUrl = '';
    
    /**
     * @var string
     * @example "233f162ac5bce350e934dfefd87200df"
     * @todo Move to Options Interface
     */
    protected $apiKey = '';
    
    
    
    /**
     * Instance of RESTful Client (cURL wrapper)
     * @var PestJSON
     */
    protected $_restClient = null;
    
    /**
     * Overwrites PestJSON Constructor 
     */
    public function __construct()
    {        
        $restClient = $this->getRestClient();
        $restClient->setupAuth($this->apiKey, "");
    }
    
    /**
     * Get's singleton instance of the RESTful Client (cURL wrapper)
     * 
     * @see https://github.com/educoder/pest
     * @return PestJSON
     */
    public function getRestClient()
    {
        if (!isset($this->_restClient)){
            $this->_restClient = new PestJSON($this->apiUrl);
            
            // Disable JSON Exceptions in Pest since we might get empty responses.
            $this->_restClient->throwJsonExceptions = false;
        }
        
        return $this->_restClient;
    } 

    /**
     * Sends an event of a specific type to the Sweet Tooth Api
     * 
     * @throws Exception. If there's a problem with the transmission.
     * @param string $event_type. Represents the type of event expected on the server.
     * @param array|int $customer. Either remote customer id or customer array {
     *     Array representing the customer this event affects.
     *         @type type $key 'first_name' (optional). Accepts string.
     *         @type type $key 'last_name' (optional). Accepts string.
     *         @type type $key 'email' (optional). Accepts string.
     *     }      
     * @param array|int|string $data (optional). Generic data to send with the event.
     * @param string $external_id (optional). The WooCommerce Id of the event or dataset to be sent. 
     * @param array $sources (optional) {
     *     Array containing tags for the various sources of this event.
     *         @type type $value. Accepts string.        
     * }
     * 
     * @return array containing server response
     */
    public function sendEvent($event_type, $customer, $data = null, $external_id = null, $sources = null)
    {
        if (empty($event_type)){
            throw Exception ('Missing event type');
        }

        // Start building out our event array
        $event = array('event_type'   => $event_type);

        
        // Data must be an array, even if it's empty.
        if (empty($data)){
            $data = array();
            
        } elseif (is_object($data)) {
            if (method_exists($data, 'toArray')){
                $data = $data->toArray();
                
            } else {
                $data = (array) $data;
            }
            
        } else {
            $data = array($data);
        }
        
        $event['data'] = $data;
        
        
        // Send full customer details or remote customer id
        if (empty($customer)){
            /**
             * @todo. Build customer array.
             */
            throw Exception ('Missing customer');
            
        } elseif (is_object($customer)){
            if (method_exists($customer, 'toArray')){
                $customer = $customer->toArray();
            
            } else {
                $customer = (array) $customer;
            }
        }

        if (is_array($customer)){
            $event['customer'] = $customer;
        } else {
            $event['customer_id'] = $customer;
        }

       
        if (!empty($external_id)){
            $event['external_id'] = $external_id;
        }
                
        if (!empty($sources)) {
            if (!is_array($sources)){
                $sources = array($sources);
            }
            $event['sources'] = $sources;
        }
        
        return $this->getRestClient()->post('/events', $event);        
    }
    
    /**
     * Accesses the server to get customer data for the specified email address.
     * Note that accessing customer information by email is not very safe since the customer
     * can change their email address at any time. The best way to do this is to keep a
     * remote customer_id available locally for each customer.
     * Looking up customers by their ID on the Sweet Tooth server is the safest route to take.
     *  
     * @throws Exception. If there's a problem with the transmission. 
     * @param string $email
     * @return array response
     */
    public function getCustomerByEmail($email)
    {
        return $this->getRestClient()->get("/customers/{$email}"); 
    }
    
    /**
     * Accesses the server to add a points transaction to the specified customer.
     * @param string $remote_customer_id. The ID of the customer on the Sweet Tooth server. 
     * @param int $points_change. Positive or negative whole number to change the customer's points balance by.
     * @param string $status (optional). "completed" (default), or "pending". Positive pending points don't affect the customer's balance. 
     * @param array $data (optional), any additional data to send along with the request.
     * 
     * @return array response.
     * @throws Exception if there's a problem creating this transaction.
     */
    public function addPointsTransaction($remote_customer_id, $points_change, $status = "completed", $data = array())
    {
        return $this->getRestClient()->post("/customers/{$remote_customer_id}/points_transactions", array(
                    'points_change'   => $points_change,
                    'status'          => $status,
                    'data'            => $data
        ));
    }
}
?>
