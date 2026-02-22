<?php
// config/ithink-api.php
class iThinkConfig {
    // Use correct API URL
    const API_URL = 'https://my.ithinklogistics.com/api_v3/';
    const ACCESS_TOKEN = '06ee35c96720585aea7929c67c9d7500';
    const SECRET_KEY = '781406cce6e792df7e862581e952f0af';
    const PICKUP_ADDRESS_ID = 112475;
    const STORE_ID = 1;
    
    // Pickup location details (UPDATE THESE WITH YOUR ACTUAL VALUES)
    const PICKUP_NAME = 'Beastline Warehouse';
    const PICKUP_COMPANY = 'Beastline';
    const PICKUP_ADDRESS = 'Flat No 103, Plot No. 15B, Jain Colony, Part - 3, Uttam Nagar, New Delhi, South West Delhi, Delhi, 110059';
    const PICKUP_ADDRESS2 = '';
    const PICKUP_CITY = 'New Delhi';
    const PICKUP_STATE = 'Delhi';
    const PICKUP_PIN = '110059';
    const PICKUP_PHONE = '9540086304';
    
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