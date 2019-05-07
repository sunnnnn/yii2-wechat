<?php
namespace sunnnnn\wechat\mp;

use Yii;
use yii\base\Component;
use sunnnnn\wechat\Error;
use sunnnnn\wechat\Helper;
use sunnnnn\wechat\Curl;
/**
 * @use: 微信公众平台接口开发
 * @date: 2017-5-11 上午10:34:26
 * @author: sunnnnn [www.sunnnnn.com] [mrsunnnnn@qq.com]
 */
class MpWechat extends Component{

    public $config = [
        'appId'        => '*',
        'appSecret'    => '*',
        'mchId'        => '',
        'mchKey'       => '',
        'cert'         => '',
        'cache_token'  => 'mp_access_token_cache', //Access Token缓存索引
        'cache_js_ticket'  => 'mp_js_ticket_cache', //Js Ticket缓存索引
    ];

    const URL_GET_CODE           = 'https://open.weixin.qq.com/connect/oauth2/authorize?appid=%s&redirect_uri=%s&response_type=code&scope=%s&state=%s#wechat_redirect';
    const URL_GET_OPENID         = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid=%s&secret=%s&code=%s&grant_type=authorization_code';
    const URL_REFRESH_TOKEN      = 'https://api.weixin.qq.com/sns/oauth2/refresh_token?appid=%s&grant_type=refresh_token&refresh_token=%s';
    const URL_GET_USERINFO       = 'https://api.weixin.qq.com/sns/userinfo?access_token=%s&openid=%s&lang=zh_CN';

    const URL_GET_ACCESSTOKEN    = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=%s&secret=%s';
    const URL_SEND_TEMPLATEMESSAGE = 'https://api.weixin.qq.com/cgi-bin/message/template/send?access_token=%s';
    const URL_GET_JS_TICKET  = 'https://api.weixin.qq.com/cgi-bin/ticket/getticket?type=jsapi&access_token=%s';

    const WX_URL_QRTICKET        = 'https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token=%s';
    const WX_URL_QRCODE          = 'https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket=%s';

    const URL_CREATE_MENU        = 'https://api.weixin.qq.com/cgi-bin/menu/create?access_token=%s';

    const URL_SCAN_GET_MERCHANT_INFO = 'https://api.weixin.qq.com/scan/merchantinfo/get?access_token=%s';

    public function __construct($config = []){
        Helper::setConfig($this->config, $config);

        if(empty($this->config)){
            Error::showError('未配置参数', 'Unconfigured parameter');
        }

        if(!Helper::isWeChatBrowser()){
            //Error::showError('请在微信中打开', 'Please open in the WeChat');
        }
    }

    public function error($message, $title = '', $back = false){
        Error::showError($message, $title, $back);
    }

    /**=============================== 登陆授权 ================================*/
    /**
     * 获取url转为微信可获取code的url
     * @date: 2016-12-28 上午9:59:37
     * @author: sunnnnn
     * @param string $url 默认当前Url
     * @param boolen $userinfo 是否需要获取用户信息
     * @return string state
     */
    public function getCodeUrl($url = null, $userinfo = false, $state = null){
        $url   = $url === null ? Helper::getHost() : $url;
        $scope = $userinfo ? 'snsapi_userinfo' : 'snsapi_base';
        $state = $state === null ? Yii::$app->security->generateRandomString(8) : $state;
        return sprintf(self::URL_GET_CODE, $this->config['appId'], urlencode($url), $scope, $state);
    }

    /**
     * 获取openid
     * @date: 2016-12-28 上午10:04:06
     * @author: sunnnnn
     * @param unknown $code 微信服务器传回的code参数(与getCodeUrl配合使用)
     * @param string $getAll
     * @return mixed
     */
    public function getOpenid($code, $format = true){
        $url = sprintf(self::URL_GET_OPENID, $this->config['appId'], $this->config['appSecret'], $code);
        $resultJson = Curl::get($url);
        $result = json_decode($resultJson, true);
        if(isset($result['openid'])){
            return $format === true ? $result['openid'] : $result;
        }else{
            throw new \Exception(isset($result['errmsg']) ? $result['errmsg'] : 'Network Error');
        }
    }

    /**
     * @use: 刷新 access token
     * @date: 2019/4/30 2:10 PM
     * @author: sunnnnn [http://www.sunnnnn.com] [mrsunnnnn@qq.com]
     * @return: mixed
     * @param $refreshToken
     * @param bool $format
     */
    public function refreshToken($refreshToken, $format = true){
        $url = sprintf(self::URL_REFRESH_TOKEN, $this->config['appId'], $refreshToken);
        $resultJson = Curl::get($url);
        $result = json_decode($resultJson, true);
        if(isset($result['access_token'])){
            return $format === true ? $result['access_token'] : $result;
        }else{
            throw new \Exception(isset($result['errmsg']) ? $result['errmsg'] : 'Network Error');
        }
    }

    /**
     * 获取用户信息
     * @date: 2016-12-28 上午10:04:47
     * @author: sunnnnn
     * @param unknown $access_token
     * @param unknown $openid
     * @return mixed
     */
    public function getUserinfo($access_token, $openid){
        $url = sprintf(self::URL_GET_USERINFO, $access_token, $openid);
        $resultJson = Curl::get($url);
        $result = json_decode($resultJson, true);
        if(isset($result['openid'])){
            return $result;
        }else{
            throw new \Exception(isset($result['errmsg']) ? $result['errmsg'] : 'Network Error');
        }
    }


    /**=============================== 通用功能 ================================*/
    /**
     * 获取AccessToken（通用型）
     * @date: 2016-12-28 上午10:05:03
     * @author: sunnnnn
     * @param string $refresh 是否强制刷新access token
     * @return Ambigous <unknown, mixed>
     */
    public function getAccessToken($refresh = false){

        if($refresh === false && Yii::$app->cache->exists($this->config['cache_token'])){
            return Yii::$app->cache->get($this->config['cache_token']);
        }

        $url = sprintf(self::URL_GET_ACCESSTOKEN, $this->config['appId'], $this->config['appSecret']);
        $resultJson = Curl::get($url);
        $result = json_decode($resultJson, true);
        if(isset($result['access_token'])){
            $expiresIn = isset($result['expires_in']) ? $result['expires_in'] : 7200;
            Yii::$app->cache->set($this->config['cache_token'], $result['access_token'], $expiresIn - 180);
            return $result['access_token'];
        }else{
            throw new \Exception(isset($result['errmsg']) ? $result['errmsg'] : 'Network Error');
        }
    }

    /**=============================== 自定义菜单（待） ================================*/
    /**
     * 自定义菜单
     * @date: 2016-12-28 上午10:12:42
     * @author: sunnnnn
     * @param unknown $menuData
     * @param string $accessToken
     * @return boolean
     */
    public function setMenu($menuData, $accessToken = ''){
        $access_token = !empty($accessToken) ? $accessToken : $this->getAccessToken();
        $url = sprintf(self::URL_CREATE_MENU, $access_token);
        $json_menu = json_encode($menuData);
        $json_menu = preg_replace("#\\\u([0-9a-f]+)#ie", "iconv('UCS-2BE', 'UTF-8', pack('H4', '\\1'))", $menuData);
        $result = Curl::post($url, $json_menu);
        if($result->errcode === 0){
            return true;
        }else{
            return false;
        }
    }


    /**=============================== JS-SDK ================================*/
    /**
     * 获取JsSdk Ticket
     * @date: 2016-12-28 上午10:06:35
     * @author: sunnnnn
     * @param $refresh 是否强制刷新
     * @return Ambigous <unknown, mixed>
     */
    public function getJsTicket($refresh = false){
        if($refresh === false && Yii::$app->cache->exists($this->config['cache_js_ticket'])){
            return Yii::$app->cache->get($this->config['cache_js_ticket']);
        }

        $token = $this->getAccessToken();
        $url = sprintf(self::URL_GET_JS_TICKET, $token);
        $resultJson = Curl::get($url);
        $result = json_decode($resultJson, true);

        if(isset($result['ticket'])) {
            $expiresIn = isset($result['expires_in']) ? $result['expires_in'] : 7200;
            Yii::$app->cache->set($this->config['cache_js_ticket'], $result['ticket'], $expiresIn - 180);
            return $result['ticket'];
        }else{
            throw new \Exception(isset($result['errmsg']) ? $result['errmsg'] : 'Network Error');
        }
    }

    /**
     * 获取JsSdk配置数据
     * @date: 2016-12-28 上午10:05:49
     * @author: sunnnnn
     * @param string $ticket
     * @param string $url
     * @param string $noncestr
     * @return Ambigous <string, \sunnnnn\wechat\Ambigous, unknown, mixed>
     */
    public function getJsConfig($url = null, $noncestr = null){
        $data['jsapi_ticket'] = $this->getJsTicket();
        $data['noncestr'] = $noncestr === null ? Yii::$app->security->generateRandomString(8) : $noncestr;
        $data['timestamp'] = strval(time());
        $data['url'] = url === null ? Helper::getHost() : $url;
        $data['sign'] = $this->getJsSign($data);
        $data['appid'] = $this->config['appId'];
        return $data;
    }

    /**
     * 生成签名
     * @date: 2016-12-28 上午10:13:11
     * @author: sunnnnn
     * @param unknown $data
     * @return string
     */
    private function getJsSign($data){
        if(empty($data) || !is_array($data)){
            return '';
        }
        ksort($data);
        $str = '';
        foreach($data as $key => $val){
            if(empty($str)){
                $str = strtolower($key).'='.$val;
            }else{
                $str .= '&'.strtolower($key).'='.$val;
            }
        }
        $sign = sha1($str);
        return $sign;
    }


    /**=============================== 二维码 ================================*/
    /**
     * 创建临时二维码ticket
     * @date: 2016-12-28 上午10:09:31
     * @author: sunnnnn
     * @param unknown $scene_id
     * @param string $accessToken
     * @param number $expire_seconds
     * @return mixed
     */
    public function getQRLimitTicket($scene_id, $accessToken = '', $expire_seconds = 604800){
        $access_token = !empty($accessToken) ? $accessToken : $this->getAccessToken();
        $url = sprintf(self::WX_URL_QRTICKET, $access_token);
        $data = [
            'expire_seconds' => $expire_seconds,
            'action_name'    => 'QR_SCENE',
            'action_info'    => [
                'scene' => ['scene_id' => $scene_id]
            ],
        ];
        $result = Curl::post($url, json_encode($data));
        return json_decode($result,true);
    }

    /**
     * 创建永久二维码ticket
     * @date: 2016-12-28 上午10:11:10
     * @author: sunnnnn
     * @param unknown $scene_str
     * @param string $accessToken
     * @return mixed
     */
    public function getQRTicket($scene_str, $accessToken = ''){
        $access_token = !empty($accessToken) ? $accessToken : $this->getAccessToken();
        $url = sprintf(self::WX_URL_QRTICKET, $access_token);
        $data = [
            'action_name'    => 'QR_LIMIT_SCENE',
            'action_info'    => [
                'scene' => ['scene_str' => $scene_str]
            ],
        ];
        $result = Curl::post($url, json_encode($data));
        return json_decode($result,true);
    }

    /**
     * 通过ticket换取二维码
     * @date: 2016-12-28 上午10:12:05
     * @author: sunnnnn
     * @param unknown $ticket
     * @return mixed
     */
    public function getQRCode($ticket){
        $url = sprintf(self::WX_URL_QRCODE, urlencode($ticket));
        return Curl::get($url);
    }

    /**
     * 通过ticket换取二维码URL
     * @date: 2016-12-28 上午10:12:25
     * @author: sunnnnn
     * @param unknown $ticket
     * @return string
     */
    public function getQRCodeUrl($ticket){
        return sprintf(self::WX_URL_QRCODE, urlencode($ticket));
    }


    /**=============================== 微信扫一扫 ================================*/
    public function getScanMerchant(){
        $access_token = $this->getAccessToken();
        $url = sprintf(self::URL_SCAN_GET_MERCHANT_INFO, $access_token);
        $resultJson = Curl::get($url);
        $result = json_decode($resultJson, true);
        if(isset($result['errcode']) && $result['errcode'] == 0){
            return $result;
        }else{
            throw new \Exception(isset($result['errmsg']) ? $result['errmsg'] : 'Network Error');
        }
    }
}