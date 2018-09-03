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
        if (isset($_SERVER['HTTP_USER_AGENT']) && strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false) {
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

    /**
     * @use: 获取当前URL
     * @date: 2018/8/13 下午1:48
     * @author: sunnnnn [http://www.sunnnnn.com] [mrsunnnnn@qq.com]
     * @return: string
     * @param bool $proto 是否需要 http:// 或 https://
     * @param bool $uri 是否需要请求参数
     */
    public static function getHost($proto = true, $uri = true){
        $result = $_SERVER['HTTP_HOST'];

        if($proto === true){
            $httpType = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';
            $result = $httpType.$result;
        }

        $uri === true && $result = $result.$_SERVER['REQUEST_URI'];

        return $result;
    }
}