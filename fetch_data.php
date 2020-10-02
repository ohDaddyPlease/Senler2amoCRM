<?php

$data = json_decode(file_get_contents('php://input'), 1);
//file_put_contents('senler_response_example.txt', print_r($data, 1));
$phone = $object = $type = $fio = $vk_user_id = null;
if (!empty($data)) {
    $object = $data['object'];
    $type = $data['type'];
    $fio = $object['first_name'] . ' ' . $object['last_name'];
    $vk_user_id = $object['vk_user_id'];
    $phone = $object['variables']['phone'];
    $age = $object['variables']['age'];
}
