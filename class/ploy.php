<?php
/**
 * 抽奖活动
 * @author:jiangzaixing	 2015-01-20
 * **/ 
class ploy{
	
	public $prizes = array();
	
	public function setPrizes( array $ps ){
		 foreach ( $ps as $p){
		 	$this->setPrize( $p );
		 }
	}
	
	public function setPrize( Prize $p ){
		$this->prizes[$p->id] = $p ;
	}
	
	public function doRoll(){
		
		$ruleInfo = $this->getRule();
		$ch = mt_rand(1,$ruleInfo['t']); $j = 0; $ret = '';
		foreach( $ruleInfo['pool'] as $k => $p ){
			if( $ch > $j && $ch < ($j+$p) ){
				$ret = $this->prizes[$k];
				return $ret;
			}else{
				$j += $p;
			}
		}
	}
	
	//分区间
	private function getRule(){
		$sum = array(); $pool = array();
		foreach($this->prizes as $k=> $p){
			$t = $p->change*100;
			$sum[] = $t;
			$pool[$k] = $t;
		}
		return array( 't'=>array_sum( $sum ), 'pool'=>$pool );
	}
	
}
class Prize{
	public $id;
	public $name;
	public $change;
	public function __construct( $conf ){
		$this->id = $conf['id'];
		$this->name = $conf['name'];
		$this->change = $conf['change'];
	}
}

?>