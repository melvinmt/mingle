<?php defined('SYSPATH') or die('No direct access allowed.');

class Mingle_Items implements Iterator, ArrayAccess{
	
	private $position = 0;
    public $array = array();
	public $items = array();
	public $empty = true;
	
	public function __construct($collection, array $items){
		
		$this->position = 0;
		
		if(count($items) > 0){
			
			foreach ($items as $item){
				
				if(isset($item['id'])){
					$id = $item['id'];
					// unset($item['_id']);
					
					$this->items[] = new Mingle_Item($collection, $id, $item);
				}else{

					$mingle = new Mingle_Item($collection);
					$mingle->values($item);
					
					$this->items[] = $mingle;
				}
			}
			
			$this->array = array_values($items);
			$this->empty = false;
			
		}else{
			
			$this->empty = true;
		}
	}	

    function rewind(){
	 	reset($this->items);
		$this->has_next = count($this->items) > 0 ? true : false;
    }

    function current(){
        return current($this->items);
    }

    function key() {
        return $this->items[key($this->items)]['id'];
    }

    function next() {
        $has_next = next($this->items);		
		$this->has_next = (!$has_next) ? false : true;
    }

    function valid() {
        return $this->has_next;
    }

	public function offsetSet($name, $value){
		
        $this->items[$name] = $value;
    }

    public function offsetExists($name){
	
        return isset($this->items[$name]);
    }
    
	public function offsetUnset($name){
		
        unset($this->items[$name]);
    }

    public function offsetGet($name){
	
        return $this->items[$name];
    }


}