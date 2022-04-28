<?php

namespace Anakadote\BAMLTV2;

/**
 * Send a lead to the BAM LeadTracker v2 API.
 */
class BAMLTV2
{
    /**
     * The allowed inputs to be sent to the BAMLT.
     *
     * @var array
     */
    private $allowedInputs = [
        'firstName', 'lastName', 'email', 'phone', 'address', 'address2', 'city', 'state', 'postalCode', 'country', 
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
        // Allow a "name" input.
        if (! empty($input['name'])) {
            $name = explode(' ', preg_replace('/\s+/', ' ', (trim($input['name']))));
            $input['firstName'] = isset($name[0]) ? $name[0] : '';
            $input['lastName']  = count($name) > 1 ? array_pop($name) : '';
        }

        // Allow a "zip" input.
        if (! empty($input['zip'])) {
            $input['postalCode'] = $input['zip'];
        }

        // Loop through the supplied data and take allowed values.
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

        $payload = array_filter($payload);

        $ch = curl_init('https://bamlt.com/api/lead');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
          'Authorization: Bearer ' . $this->apiKey,
          'Accept: application/json',
          'Content-Type: application/json',
        ]);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode == 201) {
            $response = json_decode($output, true);

            if (! empty($response['uuid'])) {
                return $response['uuid'];
            }

            return true;
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
