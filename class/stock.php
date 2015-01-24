<?php
/**
 * 奖品库存管理
 * @author:jiangzaixing	 2015-01-23
 * **/
class stock{
	
	private $db = null;
	
	public function __construct( $db ){
		$this->db = $db;
	}
	public function checkStock( $key ){
		
		$num = MCache::get( $key );
		if( !isset( $num ) ){
			$num = $this->db->outdoor->weixin_prize->findOne(array('_id'=>$key));
		}
		return (int)$num;
	}
	
	//减库存
	public function decrStock( $key ){
		$isOk = true;
		try{
			$ret = $this->db->outdoor->weixin_prize->findAndModify( array('_id'=>$key) ,array('$inc'=>array('num'=>-1)) );
		}catch(Exception $e){
			$isOk = false;
		}
		if( $isOk ){
			MCache::getInstance()->decr( $key );
		}
		return $isOk;
	}
	
}


?>