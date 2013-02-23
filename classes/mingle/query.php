<?php defined('SYSPATH') or die('No direct access allowed.');

class Mingle_Query{
	
	public $success = false;
	public $is_empty = true;
	public $not_empty_success = false;
	private $domain;
	private $wheres = array();
	private $order_by = array();
	private $limit = NULL;
	private $all = false;
	private $all_opts = NULL;
	private $cache = false;
	private $cache_expire = 1;
	public $items = array();
	private $pagination = false;
	private $page = 1;
	public $response;
	public $sql;
	public $consistent = false;
	public $profiler = array();
	public $fields;
	public $select = array();
	
	public function __construct($collection){
					
		$db = Mingle::instance();
				
		$this->collection_id = $collection;
		$this->collection = $db->{$collection};
		
	}
	
	
	public function cache($expire){
		
		$this->cache = true;
		
		if(is_numeric($expire) AND $expire > 0){
			$this->cache_expire = (int) $expire;
		}else{
			$this->cache_expire = 1;
		}
		
		return $this;
	}
	
	private function _operator($operator){
		
		switch($operator){
			case "<":
				return '$lt';
			break;
			case ">":
				return '$gt';
			break;
			case "<=":
				return '$lte';
			break;
			case ">=":
				return '$gte';
			break;
			case "!=":
				return '$ne';
			break;
			default:
				return $operator;
			break;
		}
		
	}
	
	public function where($field, $operator, $value){
		return $this->and_where($field, $operator, $value);
	}
	
	public function and_where($field, $operator, $value){
	    
		if($field == 'itemName()'){
			$field = 'itemName';
		}
		
	  	if($operator == '='){
			
			$this->wheres[$field]['$all'][] = $value;
			
		}else{
			
			$this->wheres[$field][$this->_operator($operator)] = $value;
		}
		
		// add fields to index
		$this->fields[$field] = 1;
		
		return $this;
	}
	
	public function or_where($field, $operator, $value){

		if($field == 'itemName()'){
			$field = 'itemName';
		}
		

	   	if($operator == '='){

			$this->wheres['$or']['in'][$field]['$in'][] = $value;

		}else{

			$this->wheres['$or'][][$field][$this->_operator($operator)] = $value;
		}
		
		// add fields to index
		$this->fields[$field] = 1;

		return $this;
		
	}
	
	
	public function or_is_null($field){
		
		return $this->or_where($field, '$exists', false);
	}
	
	public function or_is_not_null($field){
	
		return $this->or_where($field, '$exists', true);
	}
	
	
	public function and_is_null($field){
	
		return $this->and_where($field, '$exists', false);
	}

	public function and_is_not_null($field){

		return $this->and_where($field, '$exists', true);

	}
	
	public function and_wheres(){
		
		$wheres = func_get_args();
		
		if(count($wheres) > 0){
			
			foreach ($wheres as $where){
				
				if(is_bool($where['value'])){
					$where['value'] = intval($where['value']);
				}
				
				$this->or_where($where['field'], $where['operator'], $where['value']);
			}
			
		}
		
		return $this;
	}
	
	public function or_wheres(){
		
		$wheres = func_get_args();
		
		if(count($wheres) > 0){
			
			foreach ($wheres as $where){
				$this->and_where($where['field'], $where['operator'], $where['value']);
			}
			
		}
		
		return $this;
	}
	
	public function order_by($field, $order = 1){
		
		if(!is_numeric($order)){
			$order = strtolower($order) != 'desc' ? -1 : 1;
		}
		
		$this->order_by[$field] = $order;
		
		// add fields to index
		$this->fields[$field] = $order;
		
		return $this;
	}
	
	public function and_between($field, $min, $max){
		
		$this->and_where($field, '>=', $min);
		$this->and_where($field, '<=', $max);
		
		// add fields to index
		$this->fields[$field] = 1;
		
		return $this;
	}
	
	public function or_between($field, $min, $max){
		
		$this->or_where($field, '>=', $min);
		$this->or_where($field, '<=', $max);
		
		return $this;
	}
	
	public function and_in($field, array $values){
		
		if(!isset($this->wheres[$field]['$in'])){
			$this->wheres[$field]['$in'] = array();
		}
		
		$this->wheres[$field]['$in'] = array_merge($this->wheres[$field]['$in'], $values);
		
		return $this;
	}
	
	public function or_in($field, array $values){
		
		if(!isset($this->wheres['$or'])){
			$this->wheres['$or']['in'][$field]['$in'] = array();
		}
		
		$this->wheres['$or']['in'][$field]['$in'] = array_merge($this->wheres['$or']['in'][$field]['$in'], $values);
		
		return $this;
	}
	
	public function and_every($field, $operator, $value){
	    
		if(is_bool($value)){
	        	$value = intval($value);
        	}	    
		
		if(is_numeric($value)){
			$value = sprintf('%016.6f', $value);
		}
		
		$this->add_where('AND', "(EVERY(`{$field}`) ".strtoupper($operator)." '{$value}')");
		
		return $this;
	}
	
	
	public function or_every($field, $operator, $value){
	    
		if(is_bool($value)){
			$value = intval($value);
        	}	    
		
		if(is_numeric($value)){
			$value = sprintf('%016.6f', $value);
		}
		
		$this->add_where('OR', "(EVERY(`{$field}`) ".strtoupper($operator)." '{$value}')");
		
		return $this;
	}
	
	public function select(){
	
		$fields = func_get_args();
		
		if(count($fields) > 0){
		
			foreach ($fields as $field){
		
				$this->select[$field] = 1;
				
			}
		}
		
		return $this;
	}
	
	public function limit($int){
		
		$this->limit = $int;
		
		return $this;
	}
	
	public function page($int){
		
		if((int) $int < 1){
			$int = 1;
		}
		
		$this->pagination = true;
		$this->page = (int) $int;
		
		return $this;
		
	}
	
	public function execute(){
				
		if(isset($this->wheres['$or']['in'])){
			
			$ors = $this->wheres['$or']['in'];
			
			$this->wheres['$or'] = array();
			
			foreach($ors as $key => $value){
				$this->wheres['$or'][][$key] = $value;
			}
			
		}
		
		if(empty($this->select)){
			$cursor = $this->collection->find($this->wheres);
		}else{
			$cursor = $this->collection->find($this->wheres, $this->select);
		}
	
		$this->success = true;
		
		// add skip
		if($this->pagination){
			
			$this->limit = $this->limit != NULL ? $this->limit : 10;
			$profiler = Profiler::start(__CLASS__, 'skip');
			
			$cursor->skip( ($this->page - 1) * $this->limit);
		}
		
		// add limit
		if($this->limit != NULL AND $this->limit > 0){
			$profiler = Profiler::start(__CLASS__, 'limit');
			
			$cursor->limit(intval($this->limit));
		}
		
		// add sorting
		if(!empty($this->order_by)){
			
			$cursor->sort($this->order_by);
			
		}
			
		$cursor->reset();

		$this->items = array();
		foreach ($cursor as $obj) {
		  $this->items[] = $obj;
		}		
	
		$this->count = $cursor->count();
	
		$this->is_empty = ($this->count == 0) ? true : false;

		$this->not_empty_success = ($this->success AND !$this->is_empty) ?: false;
		
		return $this;
	}
	
	public function items(){
		
		return new Mingle_Items($this->collection_id, $this->items);	
		
	}
	
	public function item(){
		
		if(isset($this->items[0])){
			
			$item = $this->items[0];
			
			if(isset($item['id'])){
				$id = strval($item['id']);
			}else{
				$id = NULL;
			}
					
			return new Mingle_Item($this->collection_id, $id, $item);
			
		}
		
	}

}
