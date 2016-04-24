<?php
require 'JsonDb.php';
use Nahid\JsonDb;

$json=new JsonDb('erp');

// if($json->node('items')->save('monitor')){
// 	echo "successful";
// }

$items=[
	['id'=>4, 'cat'=>'pant', 'name'=>'Shirt'],
	['id'=>5, 'cat'=>'pant', 'name'=>'Pant'],
	['id'=>6, 'cat'=>'t-shirt', 'name'=>'Bra']
];
//
//if($json->node('product:code')->save("70423589")){
//	echo 'Saved';
//}

//var_dump(json_decode(json_encode(reset($items))));

$data = $json->node('product:items')->where('id', '=', 4)->first();

echo $data->cat;

// var_dump($json->node('products')->where('name', '=', 'Keyboard')->fetch());
