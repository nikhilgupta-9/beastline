<?php
// iThink Logistics API Configuration
class iThinkConfig {
    // Use correct API URL (remove trailing slash if present)
    const API_URL = 'https://my.ithinklogistics.com/api_v3/';
    const ACCESS_TOKEN = '06ee35c96720585aea7929c67c9d7500';
    const SECRET_KEY = '781406cce6e792df7e862581e952f0af';
    const PICKUP_ADDRESS_ID = 112475;
    const STORE_ID = 1; // Contact iThink support if you don't have this
    
    // Available couriers for selection
    public static $availableCouriers = [
        'delhivery' => 'Delhivery',
        'fedex' => 'FedEx',
        'xpressbees' => 'XpressBees',
        'bluedart' => 'Blue Dart',
        'shadowfax' => 'Shadowfax',
        'ekart' => 'Ekart',
        'dtdc' => 'DTDC',
        'pickrr' => 'Pickrr',
        'gati' => 'Gati'
    ];
}
?>