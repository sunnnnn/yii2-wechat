<?php
namespace sunnnnn\wechat;

/**
 * @use: 微信通用助手类
 * @date: 2018/7/23 下午2:50
 * @author: sunnnnn [http://www.sunnnnn.com] [mrsunnnnn@qq.com]
 */
class Helper{

    public static function setConfig(&$config, $params){
        if(empty($params) || empty($params['config'])){
            $config = [];
        }else{
            if(empty($config) || !is_array($config)){
                $config = $params['config'];
            }else{
                foreach($config as $param => $value){
                    if(isset($params['config'][$param])){
                        $config[$param] = $params['config'][$param];
                    }else{
                        //*表示此项必填，不然报错
                        if('*' === $value){
                            Error::showError('Lost parameter : '.$param);
                            break;
                        }
                    }
                }
            }
        }
    }

    public static function isWeChatBrowser(){
        if ( strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false ) {
            return true;
        }
        return false;
    }
}