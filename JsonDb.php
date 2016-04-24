<?php
namespace Nahid;
require_once 'DBHandler.php';

use Nahid\DBHandler;

class JsonDb
{

	use DBHandler;
	protected $_file;
	protected $_node='';
	protected $_data=array();
	
	/**
	 * Stores where conditions
	 * @var array
	 */
	protected $_andConditions = [];

	/**
	 * Stores orWhere conditions
	 * @var array
	 */
	protected $_orConditions = [];

	protected $_calculatedData = null;

	protected $_conditions = [
		'>'=>'greater',
		'<'=>'less',
		'='=>'equal',
		'!='=>'notequal',
		'>='=>'greaterequal',
		'<='=>'lessequal',
		];

	/*
		this constructor set main json file path
		otherwise create it and read file contents
		and decode as an array and store it in $this->_data
	*/
	function __construct($database)
	{
		$this->connect($database);
		$this->_file = $this->_path;
	}

	public function node($node=null)
	{
		if(is_null($node) || $node=='') return false;

		$this->_node=explode(':', $node);
		return $this;
	}

	private function getData()
	{
		if($this->_node) {
			$terminate=false;
			$map = $this->_map['__map'];
			$path=$this->_node;

			foreach($path as $val){

				if(!isset($map[$val])){
					$terminate=true;
					break;
				}

				$map = &$map[$val];
			}

			$map = $map['__map'];
			if($terminate) return false;
			return $this->_data = $this->getNodeData($map);
		}
		return false;
	}
	
	private function runFilter($data, $key, $condition, $value)
	{
	    $func ='where'. ucfirst($this->_conditions[$condition]);
	    return $this->$func($data, $key, $value);
	}

	private function makeWhere($rule, $key=null, $condition=null, $value=null)
	{
		$data = $this->getData();
		$calculatedData = $this->runFilter($data, $key, $condition, $value);
		if(!is_null($this->_calculatedData)) {
			if($rule=='and')
				$calculatedData = array_intersect(array_keys($this->_calculatedData), array_keys($calculatedData));	

			if($rule=='or')
				$calculatedData = array_merge(array_keys($this->_calculatedData), array_keys($calculatedData));

			$this->_calculatedData='';

			foreach ($calculatedData as $value) {
				$this->_calculatedData[$value]= $data[$value];
			}
			return true;
		}
		$this->_calculatedData = $calculatedData;
		return true;
	}

	public function where($key=null, $condition=null, $value=null)
	{
		$this->makeWhere('and', $key, $condition, $value);
		return $this;
	}


	public function orWhere($key=null, $condition=null, $value=null)
	{
		$this->makeWhere('or', $key, $condition, $value);
		return $this;
	}

	public function fetch()
	{
		if(is_null($this->_calculatedData)) {
			return $this->getData();
		}

		return $this->_calculatedData;
	}

	public function first()
	{
		if(is_null($this->_calculatedData)) {
			$data = $this->getData();
			if(is_array($data)) {
				return json_decode(json_encode(reset($data)));
			}

			return $data;

		}

		return json_decode(json_encode(reset($this->_calculatedData)));
	}
	/*
	saveData()

	This function helps to you to save or update data or value in specific node

	@param 		:	string $node // ':' colon separeted string
	@param 		: 	string/int $value
	@param 		: 	boolean $array

	@return 	: 	json otherwise false
	*/
    public function save($value, $array=false)
    {
		$json='';
		$nodeData='';
		$mapName = '';
		$file = '';
		$node=$this->_node;
		$data = &$this->_map['__map'];
		$finalKey = array_pop($node);
		foreach ($node as $key) {
			$data = &$data[$key];
		}


		//var_dump($this->_map);

        if($array==true){
            $value = [$value];
        }else{
            $value = $value;
        }


        if(isset($data[$finalKey])) {
            $mapName = $data[$finalKey]['__map'];
			$nodeData = $this->getDataFromFile('nodes/'.$mapName);
			if(is_array($nodeData['__data'])) {
				array_push($nodeData['__data'], $value);
			}else{
				$nodeData['__data'] = $value;
				$this->saveNodeData($mapName, $nodeData);
			}

        }else{
            $mapName = $this->makeUniqueName().'.jnode';
            $data[$finalKey]=["__map"=>$mapName];

            $jsonData ='{
            "name":"'. $mapName .'",
            "parent":"db",
            "key":"'. $finalKey .'",
            "__data":'. json_encode($value). '}';
            $file = $this->makeJsonFile('nodes/'.$mapName, $jsonData);
        }

        $json=json_encode($this->_map);
        if(file_put_contents('database/erp/erp.jdb', $json)){
            return $json;
        }
        return false;


    }


    /*
    getNodeValue()

    This method helps to you to find or get specific node value.

    @param 		: 	string $node // ':' colon separeted string

    @return 	: 	string/false
    */

	public function delete()
	{
		$json='';
		$node=$this->_node;

		$data = &$this->_data;
	    $finalKey = array_pop($node);
	    foreach ($node as $key) {
	        $data = &$data[$key];
	    }

	    if(isset($data[$finalKey])){
	    	unset($data[$finalKey]);
	    }else{
	    	return false;
	    }


		$json=json_encode($this->_data);

	    if(file_put_contents($this->_file, $json)){
	    	return $json;
	    }

	    return false;

	}



	protected function whereGreater($data, $key, $value)
	{
		return array_filter($data, function($var) use($key, $value){
			if(isset($var[$key]))
			if($var[$key]>$value){
				return $var;
			}
		});
	}

	protected function whereLess($data, $key, $value)
	{
		return array_filter($data, function($var) use($key, $value){
			if(isset($var[$key]))
			if($var[$key]<$value){
				return $var;
			}
		});
	}

	protected function whereEqual($data, $key, $value)
	{
		return array_filter($data, function($var) use($key, $value){
			if(isset($var[$key]))
			if($var[$key]==$value){
				return $var;
			}
		});
	}

	protected function whereGreaterequal($data, $key, $value)
	{
		return array_filter($data, function($var) use($key, $value){
			if(isset($var[$key]))
			if($var[$key]>=$value){
				return $var;
			}
		});
	}
	protected function whereLessequal($data, $key, $value)
	{
		return array_filter($data, function($var) use($key, $value){
			if(isset($var[$key]))
			if($var[$key]<=$value){
				return $var;
			}
		});
	}

	protected function whereNotequal($data, $key, $value)
	{
		return array_filter($data, function($var) use($key, $value){
			if(isset($var[$key]))
			if($var[$key]!=$value){
				return $var;
			}
		});
	}
}
