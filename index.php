<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'vendor/autoload.php';
require_once 'functions.php';
require_once 'fetch_data.php';

if ($type !== 'action') exit(1);

$phone_id = 134587; //amoCRM phone field id
$phone_enum = 195205; //amoCRM phone field enum
$vk_filed_id = 135939; //amoCRM vk field id
$age_field_id = 200749; //amoCRM age field id

$base_domain = 'myamo.amocrm.ru'; //amoCRM account domain
$user_email = 'e@mail.ru'; //amoCRM user email
$api_key = ''; //user api key from amoCRM
$callback_key = ''; //vk callback key from senler settings


$params = [
    'callback_key' => '',
    'senler_api_url' => 'https://senler.ru/api/vars/get',
];

$params['curl_params'] = [ 
    'vk_group_id' => 1, 
    'vk_user_id' => 1, 
    'name' => 'phone',
];
$params['curl_params']['hash'] = GetHash($params['curl_params'], $params['callback_key']); 

try {
    $amo = new \AmoCRM\Client($base_domain, $user_email, $api_key);
    $contact = $amo->contact;
    $lead = $amo->lead;
    $link = $amo->links;

    /*echo '<pre>';
    // для теста найти  контакт, чтобы увидеть энум и ид поля телефона
    $client = $contact->apiList(['query'=>'88005553535']);
    var_dump($client);
    echo '</pre>';
    exit;*/

    $client = $contact->apiList(['query'=>'id' . $vk_user_id]);

    if ($client == null) {
        $contact['name'] = $fio;
        $contact['tags'] = ['vk', 'senler'];
        $contact->addCustomField($vk_filed_id, 'id' . $vk_user_id);
        $client = $contact->apiAdd();

        $lead['name'] = $fio;
        $lead['tags'] = ['vk', 'senler'];
        $lead_id = $lead->apiAdd();

        $link['from'] = 'leads';
        $link['from_id'] = $lead_id;
        $link['to'] = 'contacts';
        $link['to_id'] = $client;
        $link->apiLink();
    }

    //клиен указал телефон, дополним его в контакте црм
    if (isset($phone)) {

        $client_id = $client[0]['id'];
        
        $contact->addCustomField($phone_id, $phone, $phone_enum);
        $contact->apiUpdate($client_id);
    }
    
        //клиен указал возраст, дополним его в контакте црм
    if (isset($age)) {

        $client_id = $client[0]['id'];
        
        $contact->addCustomField($age_field_id, $age);
        $contact->apiUpdate($client_id);
    }
    
    if (isset($object['utms'])) {
		//$object['utms'][0]['utm_id']
		$utms = array_map(static function ($utm) use($callback_key, $object) {
			$params = [ 
			'vk_group_id' => $object['vk_group_id'], 
			'utm_id' => $utm['utm_id']
			];  
			$params['hash'] = GetHash($params, $callback_key); 
			$myCurl = curl_init(); 
			curl_setopt_array($myCurl, [ 
				CURLOPT_URL => 'https://senler.ru/api/utms/Get', 
				CURLOPT_RETURNTRANSFER => true, 
				CURLOPT_POST => true, 
				CURLOPT_POSTFIELDS => http_build_query($params) 
			]); 
			$response = curl_exec($myCurl); 
			$response = json_decode($response, 1);
			$metka = $response['items'][0]['name'];
			curl_close($myCurl);
			return $metka;
		}, $object['utms']);	
		file_put_contents('logs.txt', print_r($utms, 1));
		
		$client = $client[0];
		$client_id = $client['id'];
		//var_dump($client); exit();
		//	$client['linked_leads_id'][0]
		$tags = array_map(static function ($tag) {
			return $tag['name'];
		}, $client['tags']);
		$tags = array_merge($tags, is_array($utms) ? $utms : [$utms]);
		
        $contact['tags'] = $tags;
        $contact->apiUpdate($client_id);
        
        $lead['tags'] = $tags;
        $lead->apiUpdate($client['linked_leads_id'][0]);
        
	}

} catch (\AmoCRM\Exception $e) {
	file_put_contents('errors.txt', print_r($e, 1));

    printf('Error (%d): %s' . PHP_EOL, $e->getCode(), $e->getMessage());
}

exit(1);
