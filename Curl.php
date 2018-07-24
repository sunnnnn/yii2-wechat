<?php
namespace sunnnnn\wechat;

class Curl{

    public static function get($url, $options = []){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        if(!empty($options) && is_array($options)){
            foreach($options as $curlopt => $value){
                curl_setopt($ch, $curlopt, $value);
            }
        }

        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            return json_encode(['error' => curl_error($ch)]);
        }
        curl_close($ch);
        return $result;
    }

    public static function post($url, $data, $options= []){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

        if(!empty($options) && is_array($options)){
            foreach($options as $curlopt => $value){
                curl_setopt($ch, $curlopt, $value);
            }
        }

        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            return json_encode(['error' => curl_error($ch)]);
        }
        curl_close($ch);
        return $result;
    }

    public static function put($url, $data, $options= []){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["X-HTTP-Method-Override: put"]);

        if(!empty($options) && is_array($options)){
            foreach($options as $curlopt => $value){
                curl_setopt($ch, $curlopt, $value);
            }
        }

        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            return json_encode(['error' => curl_error($ch)]);
        }
        curl_close($ch);
        return $result;
    }

    public static function delete($url, $data, $options = []){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["X-HTTP-Method-Override: delete"]);

        if(!empty($options) && is_array($options)){
            foreach($options as $curlopt => $value){
                curl_setopt($ch, $curlopt, $value);
            }
        }

        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            return json_encode(['error' => curl_error($ch)]);
        }
        curl_close($ch);
        return $result;
    }

    public static function request($url, $method = 'get', $data = [], $options = []){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT,30);
        switch($method){
            case 'post':
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
                break;
            case 'put':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
                curl_setopt($ch, CURLOPT_HTTPHEADER, ["X-HTTP-Method-Override: put"]);
                break;
            case 'delete':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
                curl_setopt($ch, CURLOPT_HTTPHEADER, ["X-HTTP-Method-Override: delete"]);
                break;
            default: break;
        }

        if(!empty($options) && is_array($options)){
            foreach($options as $curlopt => $value){
                curl_setopt($ch, $curlopt, $value);
            }
        }

        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            return json_encode(['error' => curl_error($ch)]);
        }
        curl_close($ch);
        return $result;
    }

}