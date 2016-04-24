<?php
require_once 'jsondb.php';
use Nahid\JsonDb;
$json=new JsonDb('erp');

//to save data in data.json file

$product=[
  ['id'=>1, 'name'=>'Nokia'],
  ['id'=>2, 'name'=>'iPhone'],
  ['id'=>3, 'name'=>'Samsung']
];

echo $json->node('home:title')->save($product);


