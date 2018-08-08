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

    public static function xmlToArray($xml){
        //禁止引用外部xml实体
        libxml_disable_entity_loader(true);
        return json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
    }

    public static function arrayToXml($array){
        $xml = "<xml>";
        foreach ($array as $key => $val){
            if (is_numeric($val)){
                $xml.="<".$key.">".$val."</".$key.">";
            }else{
                $xml.="<".$key."><![CDATA[".$val."]]></".$key.">";
            }
        }
        $xml.="</xml>";

        return $xml;
    }

    public static function getArrayDepth($array) {
        if(!is_array($array)) return 0;

        $maxDepth = 1;
        foreach ($array as $value) {
            if (is_array($value)) {
                $depth = self::getArrayDepth($value) + 1;

                if ($depth > $maxDepth) {
                    $maxDepth = $depth;
                }
            }
        }

        return $maxDepth;
    }
}