<?php
ini_set("display_errors","on");
error_reporting(E_ALL);
//一等奖中奖时间
define("FIRST_PRIZE_TIME",'01-30');
//总抽奖次数
define("MAX_CHANGES",4);
//总抽奖次数
define("PRIZE_PREFIX", 'mmbang_weixin_prize');

//活动日期
$ACTIVE_DATE = array(
	1=>'0130','0131','0201','0202','0203','0204','0205',
);
//活动奖品
$CONF_PRIZE = array(
	array('id'=>1,'name'=>'一等奖','change'=>0.01,'num'=>array(
			1=>0,0,0,1,0,0,0
	)),
	array('id'=>2,'name'=>'二等奖','change'=>0.02,'num'=>array(
			1=>140,140,210,105,35,35,35
	)),
	array('id'=>3,'name'=>'三等奖','change'=>99,'num'=>array(
			1=>40,40,60,30,10,10,10
	)),
	array('id'=>4,'name'=>'四等奖','change'=>0.04, 'num'=>array(
			1=>20,20,30,15,5,5,5
	)),
	array('id'=>5,'name'=>'未中奖','change'=>0.02 ,'num'=>0)
);