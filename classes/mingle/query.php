<?php defined('SYSPATH') or die('No direct access allowed.');

class Mingle_Query{
	
	public $success = false;
	public $is_empty = true;
	public $not_empty_success = false;
	private $domain;
	private $wheres = array();
	private $order_by = array();
	// private $select = '*';
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
	
		$this->profiler[] = Profiler::start(__CLASS__, 'TOTAL');
				
		$db = Mingle::instance();
		
		// $db->setProfilingLevel(1);
		
		$this->collection_id = $collection;
		$this->collection = $db->{$collection};
		
	}
	
	public function __destruct(){
		
	}	
	
	public function consistent($consistent = true){
		
		// $this->consistent = (bool) $consistent;
		
		return $this;
		
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
	
	public function all(){
		
		// $this->all = true;
		
		return $this;
	}
	
	private function repeat($times = 1){
		
		// $this->all = true;
		// $this->all_opts = (int) $times;
		
		return $this;
	}
	
	private function NextToken($next_token){
		
		// $this->all = true;
		// $this->all_opts = strval($next_token);
		
		return $this;	
	}
	
	public function from($domain){
		
		// $this->domain = $domain;
		
		return $this;
	}
	
	private function add_where($logical, $clause){
		
		// strip slashes if clause contains itemName();
		// $clause = str_replace('`itemName()`', 'itemName()', $clause);
		
		// $this->wheres[] = array('logical' => $logical, 'clause' => $clause);
		
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
	
	public function and_where($field, $operator, $value){
	    
		if($field == 'itemName()'){
			$field = 'itemName';
		}
		
		if(is_bool($value)){
			$value = intval($value);
		}
	
	   if($operator == '='){
			
			$this->wheres[$field]['$all'][] = $value;
			// $this->wheres[$field] = $value;
			
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
		
		if(is_bool($value)){
			$value = intval($value);
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
		
		
		$profiler1 = Profiler::start(__CLASS__, 'execute');
		
		
		// $profiler = Profiler::start(__CLASS__, 'ensureIndex');
		
		// debug($this->fields);
		
		// $this->collection->ensureIndex($this->fields, array('background' => true));
		
		// Profiler::stop($profiler);
		
		
		// $indexes = $this->collection->getIndexInfo();
		// debug($indexes);
				
		if(isset($this->wheres['$or']['in'])){
			
			$ors = $this->wheres['$or']['in'];
			
			$this->wheres['$or'] = array();
			
			foreach($ors as $key => $value){
				$this->wheres['$or'][][$key] = $value;
			}
			
			// $this->wheres['$or'] = array_values($this->wheres['$or']);
		}
		
		// debug($this->wheres);
		
		$profiler = Profiler::start(__CLASS__, 'find');
		
		if(empty($this->select)){
			$cursor = $this->collection->find($this->wheres);
		}else{
			$cursor = $this->collection->find($this->wheres, $this->select);
		}
		Profiler::stop($profiler);
	
		$this->success = true;
		
		// add skip
		if($this->pagination){
			
			$this->limit = $this->limit != NULL ? $this->limit : 10;
			$profiler = Profiler::start(__CLASS__, 'skip');
			
			$cursor->skip( ($this->page - 1) * $this->limit);
			Profiler::stop($profiler);
			
		}
		
		// add limit
		if($this->limit != NULL AND $this->limit > 0){
			$profiler = Profiler::start(__CLASS__, 'limit');
			
			$cursor->limit(intval($this->limit));
			Profiler::stop($profiler);
			
		}
		
		// add sorting
		if(!empty($this->order_by)){
			$profiler = Profiler::start(__CLASS__, 'sort');
			
			$cursor->sort($this->order_by);
			Profiler::stop($profiler);
			
		}
			$profiler = Profiler::start(__CLASS__, 'rewind');
			
		$cursor->reset();
		
		Profiler::stop($profiler);
		
		$profiler = Profiler::start(__CLASS__, 'iterate');
		
		$this->items = array();
		foreach ($cursor as $obj) {
		  $this->items[] = $obj;
		}		
		
		Profiler::stop($profiler);
	
		$this->count = $cursor->count();
	
		$this->is_empty = ($this->count == 0) ? true : false;

	
		$this->not_empty_success = ($this->success AND !$this->is_empty) ?: false;
		
		// debug($this);
		
		Profiler::stop($profiler1);
		
			if(isset($this->profiler) AND !empty($this->profiler) AND is_array($this->profiler)){

				foreach ($this->profiler as $profiler){
					Profiler::stop($profiler);
				}
			}
		
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

	
	public function response(){
	
		// return $this->response;
	}
}