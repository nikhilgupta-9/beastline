<?php
// iThink Logistics API Configuration
class iThinkConfig {
    const API_URL = 'https://dev.ithinklogistics.com/api_v3/';
    const ACCESS_TOKEN = '5a7b40197cd919337501dd6e9a3aad9a';
    const SECRET_KEY = '2b54c373427be180d1899400eeb21aab';
    const PICKUP_ADDRESS_ID = 1293;
    
    // Available couriers for selection
    public static $availableCouriers = [
        'bluedart' => 'Blue Dart',
        'delhivery' => 'Delhivery',
        'xpressbees' => 'XpressBees',
        'shadowfax' => 'Shadowfax',
        'ekart' => 'Ekart',
        'dtdc' => 'DTDC',
        'fedex' => 'FedEx',
        'pickrr' => 'Pickrr',
        'gati' => 'Gati'
    ];
}
?>