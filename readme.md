# BAM LeadTracker v2 Service Class

Send a lead to the BAM LeadTracker v2 API.

Begin by installing this package through Composer via the terminal.

    composer require anakadote/bamlt-v2


## Usage

There is one public method available, **send(array $input)**
    
    $bamlt = new BAMLTV2(BAMLT_API_KEY);
    $bamlt->send([
        'firstName' => 'Taylor',
        'lastName' => 'Collins',
        'postalCode' => 15243,
        'phone' => '5551212',
    ]);
