<?php
/**
 * 微信开发相关接口
 * @author:jiangzaixing	 2015-01-20
 * **/ 
class weixin{
	
	const APPID = 'wx7a48e9a111e952a0' ;
	const APPSECRET = 'ade06c5e51f8726abc1239c92da8fbf8';
	const GET_TOKEN_URL = 'https://api.weixin.qq.com/cgi-bin/token';
	
	//获取token存入缓存。。微信token每天最多获取2000次  每个token过期时间为2小时
	public function getAccessToken(){
		
		$token = $this->_getToken();
		if( !$token ){
			$ret = $this->get( self::GET_TOKEN_URL ,array(
				'grant_type'=> 'client_credential',
				'appid'     => self::APPID,
				'secret'	=> self::APPSECRET
			));
			$res = json_decode( $ret , true );
			$token = $res['access_token'];
			$this->_setToken( $token );
		}
		return $token;
	}
	
	public function getOpenId( $retUrl ='mmb.com/app/weixin/fudai/index.php' ){
		$token = $this->getAccessToken();
		$appid = self::APPID;
		$retUrl = urlencode( $retUrl );
		$url = "https://open.weixin.qq.com/connect/oauth2/authorize?appid={$appid}&redirect_uri={$retUrl}&response_type=code&scope=snsapi_userinfo&state=STATE#wechat_redirect";
		die( $url );
		header("Location:".$url);
	}
	
	
	private function _getToken(){
		return "";	
	}
	
	private function _setToken(  $token){
		
	}
	
	private function get( $url , $param , $method = 'get' ){
		
		$url = ( $method == 'get' )  ? $url.'?'.http_build_query($param) : $url;
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL , $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER , 1);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
		$ret = curl_exec($ch);
		return $ret;
	}
	
	
}
?>