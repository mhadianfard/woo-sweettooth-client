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
     * @todo Move these to Options
     */
    protected $apiUrl = 'https://dev-api.sweettoothloyalty.com/';
    protected $apiKey = '233f162ac5bce350e934dfefd87200df';
    
    /**
     * Instance of RESTful Client (cURL wrapper)
     * @var PestJSON
     */
    protected $_restClient = null;
    
    /**
     * Overwrites PestJSON Constructor 
     * @param unknown_type $base_url
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
     * @param string $event_type. Represents the type of event expected on the server.
     * @param array $customer {
     *     Array representing the customer this event affects.
     *         @type type $key 'external_id'. Accepts string. WooCommerce Id of the customer.
     *         @type type $key 'first_name'. Accepts string.
     *         @type type $key 'last_name'. Accepts string.
     *         @type type $key 'email'. Accepts string.     
     *     }      
     * @param array|int|string $data (optional). Generic data to send with the event.
     * @param string $external_id (optional). The WooCommerce Id of the event or dataset to be sent. 
     * @param array $sources (optional) {
     *     Array containing tags for the various sources of this event.
     *         @type type $value. Accepts string.        
     * }
     * 
     * @return SweetTooth_ApiClient
     */
    public function sendEvent($event_type, $customer = null, $data = null, $external_id = null, $sources = null)
    {
        if (empty($event_type)){
            throw Exception ('Missing event type');
        }

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

        // Always send full customer details.
        if (empty($customer)){
            /**
             * @todo. Build customer array.
             */
            throw Exception ('Missing customer');
        }
        
        // Prepare event with whatever we do have.
        $event = array (
                    'event_type'   => $event_type,
                    'data'         => $data,
                    'customer'     => $customer
                );
        
        if (!empty($external_id)){
            $event['external_id'] = $external_id;
        }
        
        if (!empty($sources)) {
            if (!is_array($sources)){
                $sources = array($sources);
            }
            $event['sources'] = $sources;
        }
        
        $this->getRestClient()->post('/events', $event);
        
        return $this;
    }
}
?>