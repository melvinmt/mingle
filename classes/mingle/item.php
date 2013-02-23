<?php

class Mingle_Item implements ArrayAccess{

	protected $collection_id;
	protected $data = array();
	protected $collection;
	protected $profiler = array();

	public function __construct($collection, $id = NULL, array $values = NULL){

		$db = Mingle::instance();

		$this->collection_id = $collection;
		$this->collection = $db->{$collection};

		if($id !== NULL){

			// try to retrieve item from database
			$from_db = $this->collection->findOne(array("id" => $id));

			if(!empty($from_db)){
				$this->values($from_db);
			}else{
				$this->data['id'] = $id;
			}

			$this->data['itemName'] = $id;

		}

		if($values !== NULL){
			$this->values($values);
		}

	}

	public function values($values){

		$this->data = array_merge($this->data, $values);

	}

	public function save(){

		$db = Mingle::instance();

		$this->data['_collection'] = $this->collection_id;

		try{

			$this->collection->save($this->data, array("safe" => true));

		}catch(Exception $e){

			$code = $e->getCode();

			if($code == 11000 OR $code == 11001){ // duplicate

				$from_db = $this->collection->findOne(array("id" => $this->id));

				if(!empty($from_db)){

					$this->data['_id'] = $from_db['_id'];

					$this->collection->save($this->data, array("safe" => true));
				}

			}

		}

	}

	public function id(){

		if(isset($this->data['_id'])){

			return $this->data['_id'];

		}

	}

	public function itemName(){

		return $this->id();

	}

	public function delete(){

		if(isset($this->data['_id'])){
			return $this->collection->remove(array('_id' => $this->data['_id']), array('safe' => true, 'justOne' => true));
		}

	}

	public function as_array(){

		return $this->data;

	}

	public function __set($name, $value){

		if(is_bool($value)){
			$value = intval($value);
		}

		return $this->data[$name] = $value;
	}

	public function __get($name){

		return $this->data[$name];
	}

	public function __isset($name){

		return isset($this->data[$name]);
	}

	public function __unset($name){

		unset($this->data[$name]);
	}

	public function offsetSet($name, $value){

        $this->{$name} = $value;
    }

    public function offsetExists($name){

        return isset($this->{$name});
    }

	public function offsetUnset($name){

        unset($this->{$name});
    }

    public function offsetGet($name){

        return $this->{$name};
    }



}
