<?php
/**
 * 奖品库存初始化
 * key 生成规则 ： prefix.奖品id_当前日期_num 例：mmbang_weixin_prize_1_0130_num
 * **/
include_once('../../../../include/common.php');
include_once('./config.php');
class initPrize{
	
	private $db = null;
	private $pConf = array();		 //奖品配置
	private $activeDate = array(); //活动日期时间
	public function __construct($db, $pConf, $activeDate){
		$this->db = $db;
		$this->pConf = $pConf;
		$this->activeDate = $activeDate;
	}
	
	public function init(){
		$pMap = array();
		$time = time();
		foreach($this->activeDate as $k => $day){
			foreach( $this->pConf as $_k =>$prize ){
				$key = sprintf( "%s_%d_%s_num", PRIZE_PREFIX, $prize['id'], $day );
				$num = isset( $prize['num'][$k] ) ? $prize['num'][$k] : 0;
				MCache::set( $key, $num, 0, 3600);
				$this->db->outdoor->weixin_prize->update( array( '_id'=>$key ),array(
					'_id' => $key,
					'num' => (int)$num,
					'add_time'=> $time
				), array('upsert'=>1) );
				//$pMap[$key] = $num;
			}
		}
		//print_r( $pMap );
	}
	
}
$initObj = new initPrize( $mongo ,$CONF_PRIZE,$ACTIVE_DATE);
$initObj->init();
?>