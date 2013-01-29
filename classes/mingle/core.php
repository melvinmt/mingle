<?php

class Mingle_Core{
	
	protected static $instance = NULL;
	
	public static function instance(){
		// debug('mingle!');
		
		if(self::$instance === NULL){
		
			$config = Kohana::config('mingle');
		
			$opt = array();

			
			// check if connection should be to a replica set
			if(isset($config['replica_set']) AND !empty($config['replica_set'])){
				$opt['replicaSet'] = $config['replica_set'];
			}
			
			// check if specific database is defined
			if(isset($config['db']) AND !empty($config['db'])){
				$db = '/'.$config['db'];
			}else{
				$db = '';
			}
			
			// backwards compatibility: if just a single host is defined, convert it to hosts array
			if(isset($config['host']) AND !isset($config['hosts'])){
				$config['hosts'] = array($config['hosts']);
			}
		
			// check to see if authentication is needed
			if(isset($config['username']) AND !empty($config['username']) AND isset($config['password']) AND !empty($config['password'])){
				
				// shuffle($config['hosts']);

				$uri = "mongodb://{$config['username']}:{$config['password']}@".implode(',', $config['hosts']).$db;

			}else{
				
				$uri = "mongodb://".implode(',', $config['hosts']).$db;
			}
			
			// connect to Mongo
			$mongo = new Mongo($uri, $opt);
			
			$db = $mongo->{$config['db']};
		
			return self::$instance = $db;
			
		}else{
			
			return self::$instance;
		}
	
	}
	
	public static function get($collection){
		
		return new Mingle_Query($collection);
	}	
	
	public static function delete($collection, $args, $justOne = true){
		
		$db = Mingle::instance();

		$collection_db = $db->{$collection};
		
		if(is_string($args)){
			$args = array('itemName' => $args);
		}
		
		return $collection_db->remove($args, array('safe' => true, 'justOne' => $justOne));
		
	}
	
	public static function where($field, $operator, $value){
		
		return array('field' => $field, 'operator' => $operator, 'value' => $value);
		
	}
	
}