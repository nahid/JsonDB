<?php
namespace Nahid;

trait DBHandler
{
	protected $_db;
	protected $_node;
	protected $_map;
	protected $_path = 'database';


	public function connect($database=null)
	{
		if(!is_null($database)) {
			$this->_db = $database;
			$this->_path .= '/'.$database;

			$data = '{"name":"'. $database. '",
					"driver":"jsondb",
					"extention":"jdb",
					"type":"database",
					"version": "1.0.1",
					"__map":{}
				}';
            if(!file_exists($this->_path)) {
                mkdir($this->_path.'/nodes', 0755, true);
            }
			$file = $this->makeJsonFile($database.'.jdb', $data);
            if($file) {
                 $this->_map = $this->getDataFromFile($file);
            }

            return false;

		}
	}

	protected function isMultiArray( $arr ) {
	    rsort( $arr );
	    return isset( $arr[0] ) && is_array( $arr[0] );
	}

	public function isJson($string, $return_map = false) {
	     $data = json_decode($string, true);
	     return (json_last_error() == JSON_ERROR_NONE) ? ($return_map ? $data : true) : false;
	}

	protected function makeNodeFile($nodeName = null)
	{
		if(!is_null($nodeName)) {
			$path = $this->_path.'/data/_'. $nodeName;
			$this->_node = $this->getDataFromFile($this->makeJsonFile($path, 'node'));
			return $this->_node;
			
		}
	}

	protected function makeJsonFile($fileName, $data='{}')
	{
		$path = $this->_path.'/'.$fileName;
		if(!file_exists($path)) {
				$file=fopen($path, 'w+') or die("Unable to open file!");

				fwrite($file, $data);
				fclose($file);

		}
		return $fileName;
	}

	protected function getDataFromFile($file, $type = 'application/json')
	{
		$file = $this->_path.'/'.$file;
		if(file_exists($file)) {
			$opts = [
				'http'=>[
					'header' => 'Content-Type: '.$type.'; charset=utf-8'
				]
			];

			$context = stream_context_create($opts); 

			$data=file_get_contents($file, 0, $context);

			
			return $this->isJson($data, true);
		}	
	}

    protected function getNodeData($file, $type = 'application/json')
    {
        $data = $this->getDataFromFile('nodes/'.$file, $type);

        return $data['__data'];
    }

    protected function saveNodeData($file, $data)
    {
        $file = $this->_path.'/nodes/'.$file;
        $data = json_encode($data);

        if(file_exists($file)) {
            if(file_put_contents($file, $data)) {
                return true;
            }

        }

        return false;
    }



	public function isStrStartWith($string, $like = ':map>>>')
	{
		$pattern = '/^'. $like. '/';
		if(preg_match($pattern, $string)) {
			return true;
		}

		return false;
	}

	public function makeUniqueName($prefix='node_', $hash=false)
	{
		$name = uniqid();
		if($hash) {
			return $prefix.md5($name);
		}
		return $prefix.$name;
	}

}
