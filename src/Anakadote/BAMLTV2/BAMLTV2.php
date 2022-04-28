<?php

namespace Anakadote\BAMLTV2;

/**
 * Send a lead to the default BAM LeadTracker v2 web service.
 */
class BAMLTV2
{
    /**
     * The allowed inputs to be sent to the BAMLT.
     *
     * @var array
     */
    private $allowedInputs = [
        'name', 'firstName', 'lastName', 'email', 'phone', 'address', 'address2', 'city', 'state', 'postalCode', 'country', 
        'businessName', 'businessAddress', 'businessAddress2', 'businessCity', 'businessState', 'businessPostalCode', 'businessCountry', 
        'comments', 'leadSource','mediaType',
    ];

    /**
     * The BAMLT v2 API key.
     * 
     * @var string
     */
    private $apiKey;

    /**
     * Data array to send to the BAMLT.
     *
     * @var array
     */
    private $data = [];


    /**
     * Constructor
     */
    public function __construct($apiKey)
    {
        $this->apiKey = $apiKey;

        // Fill data array with allowed input keys
        $this->data = array_fill_keys($this->allowedInputs, null);
    }

    /**
     * Send a lead to the BAMLT.
     *
     * @param  array   $input
     * @return string  The UUID of the lead on BAMLT
     */
    public function send(array $input)
    {
        // Allow a "name" input
        if (isset($input['name'])) {
            $name = explode(' ', preg_replace('/\s+/', ' ', (trim($input['name']))));
            $input['firstName'] = isset($name[0]) ? $name[0] : '';
            $input['lastName']  = count($name) > 1 ? array_pop($name) : '';
        }

        // Loop through supplied data and take allowed values
        foreach ($input as $key => $value) {
            if (in_array($key, $this->allowedInputs)) {
                $this->setData($key, $value);
            }
        }

        // Build the source url / http referrer, and append any UTM params if provided.
        $httpReferrer = null;
        if ($serverHttpReferrer = parse_url(($_SERVER['HTTP_REFERER'] ?? ''))) {
            $httpReferrer = sprintf('%s://%s%s', $serverHttpReferrer['scheme'], $serverHttpReferrer['host'], $serverHttpReferrer['path']);
        }

        if (! empty($input['utm'])) {
            $httpReferrer .= '?' . http_build_query($input['utm']);
        }

        // The JSON payload.
        $payload = [];
        $payload['httpReferrer'] = $httpReferrer;

        foreach ($this->data as $key => $value) {
            $payload[ $key ] = $value;
        }

        $ch = curl_init('https://bamlt.com/api/lead');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
          'Authorization: Bearer ' . $this->apiKey,
          'Accept: application/json',
          'Content-Type: application/json',
        ]);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($ch);
        curl_close($ch);

        if (! empty($output['uuid'])) {
            return $output['uuid'];
        }

        return false;
    }
    
    /**
     * Set a piece of data.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return void
     */
    private function setData($key, $value)
    {
        $this->data[ $key ] = $value;
    }
}
