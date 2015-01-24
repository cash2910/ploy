<?php
/**
* 好玩er新年福袋微信互动
* 抽奖规则：
 * 1、每个用户只能抽中一次
 * 2、每个奖项最多中一次
 * 3、若剩余奖品不足则默认不中奖
 * 是否考虑令牌的引入
* @author:jiangzaixing  2014-01-20
* @todo: 验证用户客户端
*/
include('../../../../include/common.php');
include('./config.php');
include('./class/weixin.php');
include('./class/ploy.php');
include('./class/stock.php');

$methodList = array(
		'index', 		 //游戏中心页面
		'rolling', 		 //进行抽奖
		'getUserInfo', 	 //获取 用户信息
		'saveAddress',	 //保存用户收货地址
		'addChange'		 //添加用户抽奖机会
);


$act = isset($_REQUEST['act']) ? $_REQUEST['act'] : 'index';
if(!in_array($act , $methodList))
	die('access denied');

$controller = new Rcon( $smarty , $mongo , $CONF_PRIZE);
call_user_func_array( array($controller, $act), array($_REQUEST) );
//对应控制器
class Rcon{

	public $sm = null;     //smarty对象
	public $psev = null;    //抽奖逻辑对象
	public $wsev = null;    //微信逻辑对象
	private $stockObj = null; //库存判断
	public $uid = '';

	function __construct( $sObj, $db ,$pconf){

		$this->sm = $sObj;
		$this->db = $db;
		$this->pconf = $pconf;
		$this->psev = new ploy( $db );
		$this->wsev = new weixin( $db );
		$this->stockObj = new stock( $db );
		$this->isNew = false;  //判断当前用户是否为第一次登陆用户
		//$this->dateTime = date( 'md', $_SERVER['REQUEST_TIME'] ); //获取当期前日期时间
		
		//$_SESSION['openId'] = !isset( $_SESSION['openId'] ) ? $this->wsev->getOpenId() : "";
		//$this->uid = $_SESSION['openId'];
		$this->uid = 'ttta213';
		$this->dateTime = '0130';
		$userInfo = $this->getUser( $this->uid );
		if(!isset( $userInfo )){
			$this->addUser( $this->uid );
			$this->isNew = true;
		}
		if( empty( $this->uid ) ){
			$this->error();
		}
		
	}
	
	

	public function index(){
		//$this->sm->display('report_topic.tpl');
	}
	
	private function checkRoll(){
		
		//判断当前次数 和获奖信息 若没有抽奖机会或者已经获奖则返回未中奖
		$uInfo = $this->getUser( $this->uid );
		if( isset($uInfo['prize']) && $uInfo['prize'] > 0 )
			die('参数错误');
		if( !isset( $uInfo['has_share'] ) && 1 == $uInfo['change'] )
			die('抽奖次数不足！');
		//当抽奖次数大于4次的时候则不允许抽奖
		if( $uInfo['change'] >= MAX_CHANGES )
			die('抽奖次数不足！');
	}
	/**
	 * 抽奖调用方法
	 * */
	public function rolling(){
		
		//验证剩余次数
		//$this->checkRoll();
		$ret = 0;
		//请求抽奖方法
		$conf = $this->getConf();
		foreach ($conf as $k =>$p){
			$ps[] = new Prize($p);
		}
		$this->psev->setPrizes( $ps );
		$prize = $this->psev->doRoll();
		switch( $prize->id ){
			case 1:
				//当前时间不等于1等奖中奖时间则设为未来中奖
				if( FIRST_PRIZE_TIME != date('m-d') ){
					$ret = 0;
				}else{ 
					$ret = 1;
				}
				break;
			case 2:
			case 3:
			case 4:
				$ret = $prize->id;
				break;
			case 5:
			default:
				$ret = 0;
				break;
		}
		//判断奖品数量 若不足则默认未中奖 否则扣减奖品
		if( $ret > 0 ){
			if( $this->checkStock( $prize ) && !$this->checkHasPrize( $this->uid, $ret) ){
				$this->addPrize( $this->uid, $prize );
				$this->decStock( $prize );
			}else{
				$ret = 0;
			}
		}
		//更新用户抽奖次数
		$this->db->outdoor->weixin_userinfo->update(array('_id'=>$this->uid), array('$inc'=>array('change'=>1),'$set'=>array('update_time'=>$_SERVER['REQUEST_TIME'])));
		die( json_encode( array('prizeType'=>$ret) ) );
	}
	
	
	/**
	 * 判断奖品库存
	 * key 生成规则 ： prefix.奖品id_当前日期_num 例：mmbang_weixin_prize_1_0130_num
	 * **/
	private function checkStock( $prize ){

		$key = sprintf( "%s_%d_%s_num", PRIZE_PREFIX, $prize->id, $this->dateTime );
		return $this->stockObj->checkStock( $key );
	}
	/**
	 * 判断用户是否中奖
	 * */
	private function checkHasPrize( $uid, $pid ){
		//新用户则无需判断
		if( $this->isNew )
			return false;
		$uInfo = $this->getUser($uid);
		if( isset( $uInfo['prize'] ) && $uInfo['prize'] > 0)
			return true;
		else 
			return false;	
	}
	
	/**
	 *	获取用户抽奖剩余次数
	 *  当用户为新用户默认抽奖次数为1
	 *  否则若用户分享过则设置为总抽奖次数减已抽奖次数
	 */
	function getUserInfo(){
		$left = 0;
		if( $this->isNew  ){
			$left = 1;
		}else{
			$ret = $this->getUser( $this->uid );
			if( !isset( $ret['has_share'] )  ){
				$left = $ret['change'] >=1 ? 0 : 1;
			}else{
				$left = $ret['change'] >= MAX_CHANGES ? 0 : (MAX_CHANGES - $ret['change']);
			}
		}
		die( json_encode( array( 'leftNum'=>$left  ) ) );
	}
	
	function saveAddress(){
		//验证中奖信息
		$ret = $this->getUser( $this->uid );
		if(!isset($ret['prize']))
			die( json_encode( array( 'isOk' => 0,'msg'=>'用户未中奖') ) );
		//保存收货地址
		$this->db->outdoor->weixin_userinfo->save( array('_id'=>$uid) , array('$set'=>array('addressInfo'=>array(
				'consignor' =>$_GET['consignor'],
				'mobile'    =>$_GET['mobile'],
				'address'	=>$_GET['address']
		))));
		die( json_encode( array( 'isOk' => 1,'msg'=>'保存成功') ) );
	}
	
	//更新是否分享
	function hasShare(){
		
		$this->db->outdoor->weixin_userinfo->update( array('_id'=>$this->uid), array('$set'=>array(
			'has_share'=>1,
			'update_time'=>time()
		)));
		die( json_encode( array( 'isOk' => 1,'msg'=>'保存成功') ) );
	}
	
	//扣减库存
	private function decStock( Prize $prize ){
		$key = sprintf( "%s_%d_%s_num", PRIZE_PREFIX, $prize->id, $this->dateTime );
		return $this->stockObj->decrStock( $key );
	}
	
	private function getUserPrize( $uid ){
		$ret = $this->getUser($uid);
		return isset($ret['prize']) ? $ret['prize'] : "";
	}
	
	//修改成缓存处理
	private function getConf(){
		return $this->pconf;
	}
	
	//添加奖品
	private function addPrize(  $uid , Prize $p){
		return $this->db->outdoor->weixin_userinfo->update(array('_id'=>$uid),array('$set'=>array(
			'prize'=>$p->id,
			'update_time'=> time()
		)));
	}
	
	//添加用户
	private function addUser( $uid ){
		$this->db->outdoor->weixin_userinfo->insert(array(
			'_id'=>$uid,
			'add_time'=>$_SERVER['REQUEST_TIME'],
			'change' => 0,  //已抽奖次数
			'add_time_str'=>date('Y-m-d H:i:s', $_SERVER['REQUEST_TIME'] )
		));
	}
	
	private function getUser( $uid ){
		return $this->db->outdoor->weixin_userinfo->findOne(array('_id'=>$uid));
	}

	private function error( $msg = '参数错误' ){
		die( json_encode( array( 'isOk'=>0,'msg'=>'参数错误'  ) ) );
	}

	private function retJson( array $ret ){
		die( json_encode( array('root'=>$data['list'] , 'totalProperty'=> $data['count'] ) ) );
	}

}

?>