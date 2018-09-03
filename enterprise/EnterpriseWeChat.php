<?php
namespace sunnnnn\wechat\enterprise;

use Yii;
use yii\base\Component;
use sunnnnn\wechat\Error;
use sunnnnn\wechat\Helper;
use sunnnnn\wechat\Curl;
use sunnnnn\wechat\utils\WXBizMsgCrypt;

class EnterpriseWeChat extends Component{

    const URL_GET_OAuth_CODE = 'https://open.weixin.qq.com/connect/oauth2/authorize?appid=%s&redirect_uri=%s&response_type=code&scope=%s&agentid=%s&state=%s#wechat_redirect';
    const URL_GET_OAuth_USERID = 'https://qyapi.weixin.qq.com/cgi-bin/user/getuserinfo?access_token=%s&code=%s';
    const URL_GET_OAuth_USERINFO = 'https://qyapi.weixin.qq.com/cgi-bin/user/getuserdetail?access_token=%s';
    const URL_ACCESS_TOKEN = 'https://qyapi.weixin.qq.com/cgi-bin/gettoken?corpid=%s&corpsecret=%s';
    const URL_GET_DEPARTMENT = 'https://qyapi.weixin.qq.com/cgi-bin/department/list?access_token=%s&id=%s';
    const URL_CREATE_DEPARTMENT = 'https://qyapi.weixin.qq.com/cgi-bin/department/create?access_token=%s';
    const URL_UPDATE_DEPARTMENT = 'https://qyapi.weixin.qq.com/cgi-bin/department/update?access_token=%s';
    const URL_DELETE_DEPARTMENT = 'https://qyapi.weixin.qq.com/cgi-bin/department/delete?access_token=%s&id=%s';
    const URL_CREATE_USER = 'https://qyapi.weixin.qq.com/cgi-bin/user/create?access_token=%s';
    const URL_UPDATE_USER = 'https://qyapi.weixin.qq.com/cgi-bin/user/update?access_token=%s';
    const URL_DELETE_USER = 'https://qyapi.weixin.qq.com/cgi-bin/user/delete?access_token=%s&userid=%s';
    const URL_BATCH_DELETE_USER = 'https://qyapi.weixin.qq.com/cgi-bin/user/batchdelete?access_token=%s';
    const URL_GET_USER = 'https://qyapi.weixin.qq.com/cgi-bin/user/get?access_token=%s&userid=%s';
    const URL_GET_DEPARTMENT_USERS_LIST = 'https://qyapi.weixin.qq.com/cgi-bin/user/simplelist?access_token=%s&department_id=%s&fetch_child=%s';
    const URL_GET_DEPARTMENT_USERS_DETAIL = 'https://qyapi.weixin.qq.com/cgi-bin/user/list?access_token=%s&department_id=%s&fetch_child=%s';
    const URL_CREATE_TAG = 'https://qyapi.weixin.qq.com/cgi-bin/tag/create?access_token=%s';
    const URL_UPDATE_TAG = 'https://qyapi.weixin.qq.com/cgi-bin/tag/update?access_token=%s';
    const URL_DELETE_TAG = 'https://qyapi.weixin.qq.com/cgi-bin/tag/delete?access_token=%s&tagid=%s';
    const URL_GET_TAGS = 'https://qyapi.weixin.qq.com/cgi-bin/tag/list?access_token=%s';
    const URL_ADD_USERS_TO_TAG = 'https://qyapi.weixin.qq.com/cgi-bin/tag/addtagusers?access_token=%s';
    const URL_REMOVE_USERS_FROM_TAG = 'https://qyapi.weixin.qq.com/cgi-bin/tag/deltagusers?access_token=%s';
    const URL_GET_USERS_FROM_TAG = 'https://qyapi.weixin.qq.com/cgi-bin/tag/get?access_token=%s&tagid=%s';
    const URL_INVITE = 'https://qyapi.weixin.qq.com/cgi-bin/batch/invite?access_token=%s';
    const URL_UPLOAD_MEDIA = 'https://qyapi.weixin.qq.com/cgi-bin/media/upload?access_token=%s&type=%s';
    const URL_SEND_MESSAGE = 'https://qyapi.weixin.qq.com/cgi-bin/message/send?access_token=%s';
    const URL_JS_GET_TICKET = 'https://qyapi.weixin.qq.com/cgi-bin/get_jsapi_ticket?access_token=%s';
    const URL_APPROVAL = 'https://qyapi.weixin.qq.com/cgi-bin/corp/getapprovaldata?access_token=%s';

    public $config = [
        'corpid'           => '*',  //企业ID，必填
        'agentid'          => '',   //应用ID
        'contact_secret'   => '',   //通讯录密钥
        'e_contact_secret' => '',   //外部联系人密钥
        'cache_prefix'     => 'access_token_cache_', //Access Token缓存索引
        'cache_js_prefix'  => 'js_ticket_cache_', //缓存索引
        'verify_url_token' => '', //接收事件服务器 Token
        'verify_url_key'   => '', //接收事件服务器 EncodingAESKey
    ];

    public function __construct($config = []){
        Helper::setConfig($this->config, $config);

        if(empty($this->config)){
            Error::showError('未配置参数', 'Unconfigured parameter');
        }

        //if(!Helper::isWeChatBrowser()){
        //Error::showError('请在企业微信中打开', 'Please open in the Enterprise WeChat');
        //}
    }

    public function error($message, $title = '', $back = false){
        Error::showError($message, $title, $back);
    }

    /**
     * @use: OAuth2.0验证接口获取code的url
     * @date: 2018/7/27 上午9:54
     * @author: sunnnnn [http://www.sunnnnn.com] [mrsunnnnn@qq.com]
     * @return: string
     * @param $url 回调url，会带上code
     * @param null $agentId 应用ID
     * @param string $scope snsapi_base, snsapi_userinfo, snsapi_privateinfo
     * @param string $state
     */
    public function getCodeUrl($url, $agentId = null, $scope = 'snsapi_base', $state = 'enterprise'){
        return sprintf(self::URL_GET_OAuth_CODE, $this->config['corpid'], urlencode($url), $scope, $agentId ?? $this->config['agentid'], $state);
    }

    /**
     * @use: OAuth2.0验证接口获取UserID或者openID
     * @date: 2018/7/27 上午10:39
     * @author: sunnnnn [http://www.sunnnnn.com] [mrsunnnnn@qq.com]
     * @return: mixed|string
     * @param $code
     * @param null $secret
     */
    public function getOAuthUserId($code, $secret = null){
        $token = $this->getAccessToken($secret);

        $url = sprintf(self::URL_GET_OAuth_USERID, $token, $code);
        $result = Curl::get($url);
        $result = json_decode($result, true);

        if(!empty($result) && $result['errcode'] == 0) {
            return $result;
        }else{
            throw new \Exception(isset($result['errmsg']) ? $result['errmsg'] : 'Network Error');
        }
    }

    /**
     * @use: OAuth2.0验证接口获取用户信息
     * @date: 2018/7/27 上午10:43
     * @author: sunnnnn [http://www.sunnnnn.com] [mrsunnnnn@qq.com]
     * @return: mixed|string
     * @param $ticket getOAuthUserId获取的user_ticket
     * @param null $secret
     */
    public function getOAuthUserInfo($ticket, $secret = null){
        $token = $this->getAccessToken($secret);

        $url = sprintf(self::URL_GET_OAuth_USERINFO, $token);
        $data = ['user_ticket' => $ticket];
        $result = Curl::post($url, json_encode($data));
        $result = json_decode($result, true);

        if(!empty($result) && $result['errcode'] == 0) {
            return $result;
        }else{
            throw new \Exception(isset($result['errmsg']) ? $result['errmsg'] : 'Network Error');
        }
    }

    /**
     * @use: 验证URL有效性
     * @date: 2018/7/24 下午4:25
     * @author: sunnnnn [http://www.sunnnnn.com] [mrsunnnnn@qq.com]
     * @return: string
     * @param $params
     * @throws \Exception
     */
    public function verifyUrl($throw = false){
        $sEchoStr = '';
        $sVerifyMsgSig = Yii::$app->request->get('msg_signature', '');
        $sVerifyTimeStamp = Yii::$app->request->get('timestamp', '');
        $sVerifyNonce = Yii::$app->request->get('nonce', '');
        $sVerifyEchoStr = Yii::$app->request->get('echostr', '');

        $crypt = new WXBizMsgCrypt($this->config['verify_url_token'], $this->config['verify_url_key'], $this->config['corpid']);
        $errCode = $crypt->VerifyURL($sVerifyMsgSig, $sVerifyTimeStamp, $sVerifyNonce, $sVerifyEchoStr, $sEchoStr);
        if($errCode == 0){
            return $sEchoStr;
        }else{
            if($throw === true){
                throw new \Exception("ERR: " . $errCode);
            }else{
                return false;
            }
        }
    }

    /**
     * @use: 获取微信发来的消息，解密数据包，得到明文消息结构体
     * @date: 2018/7/25 下午1:14
     * @author: sunnnnn [http://www.sunnnnn.com] [mrsunnnnn@qq.com]
     * @return: boolen
     */
    public function getMessage(&$message, $format = true){
        $signature = Yii::$app->request->get('msg_signature');
        $timestamp = Yii::$app->request->get('timestamp');
        $nonce     = Yii::$app->request->get('nonce');
        $xml       = file_get_contents("php://input");

        $crypt     = new WXBizMsgCrypt($this->config['verify_url_token'], $this->config['verify_url_key'], $this->config['corpid']);
        $errorCode = $crypt->DecryptMsg($signature, $timestamp, $nonce, $xml, $message);

        if(!empty($message) && $format === true){
            $message = Helper::xmlToArray($message);
        }

        return $errorCode == 0 ? true : $errorCode;
    }

    /**
     * @use: 获取Access Token
     * @date: 2018/7/23 下午3:59
     * @author: sunnnnn [http://www.sunnnnn.com] [mrsunnnnn@qq.com]
     * @return: mixed
     * @param null $secret 应用密钥， 默认通讯录密钥，需要配置config
     * @param bool $format 为true只返回token， false返回全部信息
     * @param bool $refresh 为true强制刷新
     * @throws \Exception
     */
    public function getAccessToken($secret = null, $format = true, $refresh = false){
        $secret = $secret === null ? $this->config['contact_secret'] : $secret;

        if($refresh === false && Yii::$app->cache->exists($this->config['cache_prefix'].$secret)){
            return Yii::$app->cache->get($this->config['cache_prefix'].$secret);
        }

        $url = sprintf(self::URL_ACCESS_TOKEN, $this->config['corpid'], $secret);
        $result = Curl::get($url);
        $result = json_decode($result, true);

        if(!empty($result) && $result['errcode'] == 0) {
            $expiresIn = isset($result['expires_in']) ? $result['expires_in'] : 7200;
            Yii::$app->cache->set($this->config['cache_prefix'].$secret, $result['access_token'], $expiresIn - 180);
            return $format === true ? $result['access_token'] : $result;
        }else{
            throw new \Exception(isset($result['errmsg']) ? $result['errmsg'] : 'Network Error');
        }
    }

    /**
     * @use: 获取部门信息
     * @date: 2018/7/23 下午4:37
     * @author: sunnnnn [http://www.sunnnnn.com] [mrsunnnnn@qq.com]
     * @return: mixed
     * @param null $secret 应用密钥， 默认通讯录密钥，需要配置config
     * @param int $id  部门ID，默认应用权限下的所有部门
     * @param bool $format 格式化返回信息
     * @throws \Exception
     */
    public function getDepartments($id = 0, $secret = null, $format = true){
        $token = $this->getAccessToken($secret);

        $url = sprintf(self::URL_GET_DEPARTMENT, $token, $id);
        $result = Curl::get($url);
        $result = json_decode($result, true);

        if(!empty($result) && $result['errcode'] == 0) {
            return $format === true ? $result['department'] : $result;
        }else{
            throw new \Exception(isset($result['errmsg']) ? $result['errmsg'] : 'Network Error');
        }
    }

    /**
     * @use: 创建新的部门
     * @date: 2018/7/23 下午4:49
     * @author: sunnnnn [http://www.sunnnnn.com] [mrsunnnnn@qq.com]
     * @return: mixed
     * @param $params 部门数据 array => id, parentid, name, order
     * @param null $secret 应用密钥， 默认通讯录密钥，需要配置config
     * @param bool $format 格式化返回信息
     * @throws \Exception
     */
    public function createDepartment($params, $secret = null, $format = true){
        $token = $this->getAccessToken($secret);

        $url = sprintf(self::URL_CREATE_DEPARTMENT, $token);
        $data = [
            'id'       => isset($params['id']) ? $params['id'] : null,
            'parentid' => isset($params['parentid']) ? $params['parentid'] : 0,
            'name'     => isset($params['name']) ? $params['name'] : '',
            'order'    => isset($params['order']) ? $params['order'] : 10000,
        ];
        $result = Curl::post($url, json_encode($data));
        $result = json_decode($result, true);

        if(!empty($result) && $result['errcode'] == 0) {
            return $format === true ? $result['id'] : $result;
        }else{
            throw new \Exception(isset($result['errmsg']) ? $result['errmsg'] : 'Network Error');
        }
    }

    /**
     * @use: 更新部门数据
     * @date: 2018/7/23 下午5:03
     * @author: sunnnnn [http://www.sunnnnn.com] [mrsunnnnn@qq.com]
     * @return: mixed
     * @param $params 需要更新的数据，其中id是必须的
     * @param null $secret 应用密钥， 默认通讯录密钥，需要配置config
     * @param bool $format 格式化返回信息
     * @throws \Exception
     */
    public function updateDepartment($params, $secret = null){
        $token = $this->getAccessToken($secret);

        $url = sprintf(self::URL_UPDATE_DEPARTMENT, $token);
        $data = [
            'id'       => isset($params['id']) ? $params['id'] : null,
            'parentid' => isset($params['parentid']) ? $params['parentid'] : null,
            'name'     => isset($params['name']) ? $params['name'] : null,
            'order'    => isset($params['order']) ? $params['order'] : null,
        ];
        $result = Curl::post($url, json_encode($data));
        $result = json_decode($result, true);

        if(!empty($result) && $result['errcode'] == 0) {
            return true;
        }else{
            throw new \Exception(isset($result['errmsg']) ? $result['errmsg'] : 'Network Error');
        }
    }

    /**
     * @use: 删除部门
     * @date: 2018/7/24 下午1:43
     * @author: sunnnnn [http://www.sunnnnn.com] [mrsunnnnn@qq.com]
     * @return: bool
     * @param $id 删除的部门ID
     * @param null $secret 应用密钥， 默认通讯录密钥，需要配置config
     * @throws \Exception
     */
    public function deleteDepartment($id, $secret = null){
        $token = $this->getAccessToken($secret);

        $url = sprintf(self::URL_DELETE_DEPARTMENT, $token, $id);
        $result = Curl::get($url);
        $result = json_decode($result, true);

        if(!empty($result) && $result['errcode'] == 0) {
            return true;
        }else{
            throw new \Exception(isset($result['errmsg']) ? $result['errmsg'] : 'Network Error');
        }
    }

    /**
     * @use: 创建成员
     * @date: 2018/7/24 下午2:06
     * @author: sunnnnn [http://www.sunnnnn.com] [mrsunnnnn@qq.com]
     * @return: mixed
     * @param $params
     * [
     *  'userid' => '员工号', //唯一，必填
     *  'name' => '姓名', //必填
     *  'department' => [], //部门，数组，必填
     *  'mobile' => '手机号', //手机号和邮箱不能同时为空
     *  'email' => '电子邮箱', //手机号和邮箱不能同时为空
     *  'english_name' => '英文名',
     *  'order' => [], //在对应部门内的排序，与department对应
     *  'position' => '职位信息',
     *  'gender' => '性别。1表示男性，2表示女性',
     *  'telephone' => '座机',
     *  'isleader' => '标识是否为上级',
     *  'avatar_mediaid' => '成员头像的mediaid，通过素材管理接口上传图片获得的mediaid',
     *  'enable' => '1表示启用成员，0表示禁用成员',
     *  'extattr' => '自定义字段',
     *  'to_invite' => '是否邀请该成员使用企业微信',
     *  'external_profile' => '成员对外属性',
     * ]
     * @param null $secret 应用密钥， 默认通讯录密钥，需要配置config
     * @throws \Exception
     */
    public function createUser($params, $secret = null){
        $token = $this->getAccessToken($secret);

        $url = sprintf(self::URL_CREATE_USER, $token);
        $data = [
            'userid'           => isset($params['userid']) ? $params['userid'] : null,
            'name'             => isset($params['name']) ? $params['name'] : null,
            'department'       => isset($params['department']) ? $params['department'] : [],
            'mobile'           => isset($params['mobile']) ? $params['mobile'] : '',
            'email'            => isset($params['email']) ? $params['email'] : '',
            'english_name'     => isset($params['english_name']) ? $params['english_name'] : '',
            'order'            => isset($params['order']) ? $params['order'] : [],
            'position'         => isset($params['position']) ? $params['position'] :'',
            'gender'           => isset($params['gender']) ? $params['gender'] : 1,
            'telephone'        => isset($params['telephone']) ? $params['telephone'] : '',
            'isleader'         => isset($params['isleader']) ? $params['isleader'] : false,
            'avatar_mediaid'   => isset($params['avatar_mediaid']) ? $params['avatar_mediaid'] : '',
            'enable'           => isset($params['enable']) ? $params['enable'] : 1,
            'extattr'          => isset($params['extattr']) ? $params['extattr'] : null,
            'to_invite'        => isset($params['to_invite']) ? $params['to_invite'] : false,
            'external_profile' => isset($params['external_profile']) ? $params['external_profile'] : null,
        ];
        $result = Curl::post($url, json_encode($data));
        $result = json_decode($result, true);

        if(!empty($result) && $result['errcode'] == 0) {
            return true;
        }else{
            throw new \Exception(isset($result['errmsg']) ? $result['errmsg'] : 'Network Error');
        }
    }

    /**
     * @use: 更新成员
     * @date: 2018/7/24 下午2:30
     * @author: sunnnnn [http://www.sunnnnn.com] [mrsunnnnn@qq.com]
     * @return: bool
     * @param $params userid必填，其他同createUser
     * @param null $secret 应用密钥， 默认通讯录密钥，需要配置config
     * @throws \Exception
     */
    public function updateUser($params, $secret = null){
        $token = $this->getAccessToken($secret);

        $url = sprintf(self::URL_UPDATE_USER, $token);
        $data = [
            'userid'           => isset($params['userid']) ? $params['userid'] : null,
            'name'             => isset($params['name']) ? $params['name'] : null,
            'department'       => isset($params['department']) ? $params['department'] : null,
            'mobile'           => isset($params['mobile']) ? $params['mobile'] : null,
            'email'            => isset($params['email']) ? $params['email'] : null,
            'english_name'     => isset($params['english_name']) ? $params['english_name'] : null,
            'order'            => isset($params['order']) ? $params['order'] : null,
            'position'         => isset($params['position']) ? $params['position'] : null,
            'gender'           => isset($params['gender']) ? $params['gender'] : null,
            'telephone'        => isset($params['telephone']) ? $params['telephone'] : null,
            'isleader'         => isset($params['isleader']) ? $params['isleader'] : null,
            'avatar_mediaid'   => isset($params['avatar_mediaid']) ? $params['avatar_mediaid'] : null,
            'enable'           => isset($params['enable']) ? $params['enable'] : null,
            'extattr'          => isset($params['extattr']) ? $params['extattr'] : null,
            'to_invite'        => isset($params['to_invite']) ? $params['to_invite'] : null,
            'external_profile' => isset($params['external_profile']) ? $params['external_profile'] : null,
        ];
        $result = Curl::post($url, json_encode($data));
        $result = json_decode($result, true);

        if(!empty($result) && $result['errcode'] == 0) {
            return true;
        }else{
            throw new \Exception(isset($result['errmsg']) ? $result['errmsg'] : 'Network Error');
        }
    }

    /**
     * @use: 删除成员
     * @date: 2018/7/24 下午2:33
     * @author: sunnnnn [http://www.sunnnnn.com] [mrsunnnnn@qq.com]
     * @return: bool
     * @param $userId 员工号
     * @param null $secret 应用密钥， 默认通讯录密钥，需要配置config
     * @throws \Exception
     */
    public function deleteUser($userId, $secret = null){
        $token = $this->getAccessToken($secret);

        $url = sprintf(self::URL_DELETE_USER, $token, $userId);
        $result = Curl::get($url);
        $result = json_decode($result, true);

        if(!empty($result) && $result['errcode'] == 0) {
            return true;
        }else{
            throw new \Exception(isset($result['errmsg']) ? $result['errmsg'] : 'Network Error');
        }
    }

    /**
     * @use: 批量删除成员
     * @date: 2018/7/24 下午2:37
     * @author: sunnnnn [http://www.sunnnnn.com] [mrsunnnnn@qq.com]
     * @return: bool
     * @param $users 员工号数组，或者字符串用,隔开
     * @param null $secret
     * @throws \Exception
     */
    public function batchDeleteUsers($users, $secret = null){
        $token = $this->getAccessToken($secret);

        $url = sprintf(self::URL_BATCH_DELETE_USER, $token);
        $data = !empty($users) ? (!is_array($users) ? explode(",", $users) : $users) : [];
        $result = Curl::post($url, json_encode($data));
        $result = json_decode($result, true);

        if(!empty($result) && $result['errcode'] == 0) {
            return true;
        }else{
            throw new \Exception(isset($result['errmsg']) ? $result['errmsg'] : 'Network Error');
        }
    }

    /**
     * @use: 获取指定成员详情
     * @date: 2018/7/24 下午2:42
     * @author: sunnnnn [http://www.sunnnnn.com] [mrsunnnnn@qq.com]
     * @return: mixed|string
     * @param $userId 员工号
     * @param null $secret
     * @throws \Exception
     */
    public function getUser($userId, $secret = null){
        $token = $this->getAccessToken($secret);

        $url = sprintf(self::URL_GET_USER, $token, $userId);
        $result = Curl::get($url);
        $result = json_decode($result, true);

        if(!empty($result) && $result['errcode'] == 0) {
            return $result;
        }else{
            throw new \Exception(isset($result['errmsg']) ? $result['errmsg'] : 'Network Error');
        }
    }

    /**
     * @use: 获取指定部门下的成员列表
     * @date: 2018/7/24 下午2:46
     * @author: sunnnnn [http://www.sunnnnn.com] [mrsunnnnn@qq.com]
     * @return: mixed
     * @param $departmentId 部门ID
     * @param string $fetch 是否递归到子部门
     * @param null $secret
     * @param bool $format
     * @throws \Exception
     */
    public function getDepartmentUsersList($departmentId, $fetch = '0', $secret = null, $format = true){
        $token = $this->getAccessToken($secret);

        $url = sprintf(self::URL_GET_DEPARTMENT_USERS_LIST, $token, $departmentId, $fetch);
        $result = Curl::get($url);
        $result = json_decode($result, true);

        if(!empty($result) && $result['errcode'] == 0) {
            return $format === true ? $result['userlist'] : $result;
        }else{
            throw new \Exception(isset($result['errmsg']) ? $result['errmsg'] : 'Network Error');
        }
    }

    /**
     * @use: 获取指定部门下的成员列表(详细信息)
     * @date: 2018/7/24 下午2:46
     * @author: sunnnnn [http://www.sunnnnn.com] [mrsunnnnn@qq.com]
     * @return: mixed
     * @param $departmentId 部门ID
     * @param string $fetch 是否递归到子部门
     * @param null $secret
     * @param bool $format
     * @throws \Exception
     */
    public function getDepartmentUsersDetail($departmentId, $fetch = '0', $secret = null, $format = true){
        $token = $this->getAccessToken($secret);

        $url = sprintf(self::URL_GET_DEPARTMENT_USERS_DETAIL, $token, $departmentId, $fetch);
        $result = Curl::get($url);
        $result = json_decode($result, true);

        if(!empty($result) && $result['errcode'] == 0) {
            return $format === true ? $result['userlist'] : $result;
        }else{
            throw new \Exception(isset($result['errmsg']) ? $result['errmsg'] : 'Network Error');
        }
    }

    /**
     * @use: 创建标签
     * @date: 2018/7/24 下午3:33
     * @author: sunnnnn [http://www.sunnnnn.com] [mrsunnnnn@qq.com]
     * @return: mixed
     * @param $params array => id, name
     * @param null $secret
     * @param bool $format
     * @throws \Exception
     */
    public function createTag($params, $secret = null, $format = true){
        $token = $this->getAccessToken($secret);

        $url = sprintf(self::URL_CREATE_TAGm, $token);
        $data = [
            'tagid'   => isset($params['id']) ? $params['id'] : null,
            'tagname' => isset($params['name']) ? $params['name'] : '',
        ];
        $result = Curl::post($url, json_encode($data));
        $result = json_decode($result, true);

        if(!empty($result) && $result['errcode'] == 0) {
            return $format === true ? $result['tagid'] : $result;
        }else{
            throw new \Exception(isset($result['errmsg']) ? $result['errmsg'] : 'Network Error');
        }
    }

    /**
     * @use: 更新标签的名称
     * @date: 2018/7/24 下午3:35
     * @author: sunnnnn [http://www.sunnnnn.com] [mrsunnnnn@qq.com]
     * @return: bool
     * @param $params array => id , name
     * @param null $secret
     * @throws \Exception
     */
    public function updateTag($params, $secret = null){
        $token = $this->getAccessToken($secret);

        $url = sprintf(self::URL_UPDATE_TAG, $token);
        $data = [
            'tagid'   => isset($params['id']) ? $params['id'] : null,
            'tagname' => isset($params['name']) ? $params['name'] : null
        ];
        $result = Curl::post($url, json_encode($data));
        $result = json_decode($result, true);

        if(!empty($result) && $result['errcode'] == 0) {
            return true;
        }else{
            throw new \Exception(isset($result['errmsg']) ? $result['errmsg'] : 'Network Error');
        }
    }

    /**
     * @use: 删除标签
     * @date: 2018/7/24 下午3:36
     * @author: sunnnnn [http://www.sunnnnn.com] [mrsunnnnn@qq.com]
     * @return: bool
     * @param $id
     * @param null $secret
     * @throws \Exception
     */
    public function deleteTag($id, $secret = null){
        $token = $this->getAccessToken($secret);

        $url = sprintf(self::URL_DELETE_TAG, $token, $id);
        $result = Curl::get($url);
        $result = json_decode($result, true);

        if(!empty($result) && $result['errcode'] == 0) {
            return true;
        }else{
            throw new \Exception(isset($result['errmsg']) ? $result['errmsg'] : 'Network Error');
        }
    }

    /**
     * @use: 获取标签列表
     * @date: 2018/7/24 下午3:37
     * @author: sunnnnn [http://www.sunnnnn.com] [mrsunnnnn@qq.com]
     * @return: mixed
     * @param null $secret
     * @param bool $format
     * @throws \Exception
     */
    public function getTags($secret = null, $format = true){
        $token = $this->getAccessToken($secret);

        $url = sprintf(self::URL_GET_TAGS, $token);
        $result = Curl::get($url);
        $result = json_decode($result, true);

        if(!empty($result) && $result['errcode'] == 0) {
            return $format === true ? $result['taglist'] : $result;
        }else{
            throw new \Exception(isset($result['errmsg']) ? $result['errmsg'] : 'Network Error');
        }
    }

    /**
     * @use: 添加成员到指定标签
     * @date: 2018/7/24 下午3:44
     * @author: sunnnnn [http://www.sunnnnn.com] [mrsunnnnn@qq.com]
     * @return: bool
     * @param $params array => tagid, users[员工号数组]， departments[部门数组]
     * @param null $secret
     * @param bool $format
     * @throws \Exception
     */
    public function addUsersToTag($params, $secret = null){
        $token = $this->getAccessToken($secret);

        $url = sprintf(self::URL_ADD_USERS_TO_TAG, $token);
        $data = [
            'tagid'   => isset($params['id']) ? $params['id'] : null,
            'userlist' => isset($params['users']) ? $params['users'] : null,
            'partylist' => isset($params['departments']) ? $params['departments'] : null,
        ];
        $result = Curl::post($url, json_encode($data));
        $result = json_decode($result, true);

        if(!empty($result) && $result['errcode'] == 0) {
            if(!empty($result['invalidlist'])){
                return $result['invalidlist'];
            }elseif(!empty($result['invalidparty'])){
                return $result['invalidparty'];
            }else{
                return true;
            }
        }else{
            throw new \Exception(isset($result['errmsg']) ? $result['errmsg'] : 'Network Error');
        }
    }

    /**
     * @use: 在指定标签中移除员工
     * @date: 2018/7/24 下午3:47
     * @author: sunnnnn [http://www.sunnnnn.com] [mrsunnnnn@qq.com]
     * @return: bool
     * @param $params array => tagid, users[员工号数组], departments[部门数组]
     * @param null $secret
     * @param bool $format
     * @throws \Exception
     */
    public function removeUsersFromTag($params, $secret = null){
        $token = $this->getAccessToken($secret);

        $url = sprintf(self::URL_REMOVE_USERS_FROM_TAG, $token);
        $data = [
            'tagid'   => isset($params['id']) ? $params['id'] : null,
            'userlist' => isset($params['users']) ? $params['users'] : null,
            'partylist' => isset($params['departments']) ? $params['departments'] : null,
        ];
        $result = Curl::post($url, json_encode($data));
        $result = json_decode($result, true);

        if(!empty($result) && $result['errcode'] == 0) {
            if(!empty($result['invalidlist'])){
                return $result['invalidlist'];
            }elseif(!empty($result['invalidparty'])){
                return $result['invalidparty'];
            }else{
                return true;
            }
        }else{
            throw new \Exception(isset($result['errmsg']) ? $result['errmsg'] : 'Network Error');
        }
    }

    /**
     * @use: 获取标签成员
     * @date: 2018/7/24 下午3:49
     * @author: sunnnnn [http://www.sunnnnn.com] [mrsunnnnn@qq.com]
     * @return: bool
     * @param $id TagID
     * @param null $secret
     * @throws \Exception
     */
    public function getUsersFromTags($id, $secret = null){
        $token = $this->getAccessToken($secret);

        $url = sprintf(self::URL_GET_USERS_FROM_TAG, $token, $id);
        $result = Curl::get($url);
        $result = json_decode($result, true);

        if(!empty($result) && $result['errcode'] == 0) {
            return $result;
        }else{
            throw new \Exception(isset($result['errmsg']) ? $result['errmsg'] : 'Network Error');
        }
    }

    /**
     * @use: 邀请成员
     * @date: 2018/7/24 下午3:55
     * @author: sunnnnn [http://www.sunnnnn.com] [mrsunnnnn@qq.com]
     * @return: bool
     * @param $params array => users[员工号数组], departments[部门数组], tags[标签是数组]
     * @param null $secret
     * @throws \Exception
     */
    public function invite($params, $secret = null){
        $token = $this->getAccessToken($secret);

        $url = sprintf(self::URL_INVITE, $token);
        $data = [
            'user'   => isset($params['users']) ? $params['users'] : null,
            'party' => isset($params['departments']) ? $params['departments'] : null,
            'tag' => isset($params['tags']) ? $params['tags'] : null,
        ];
        $result = Curl::post($url, json_encode($data));
        $result = json_decode($result, true);

        if(!empty($result) && $result['errcode'] == 0) {
            if(!empty($result['invaliduser'])){
                return $result['invaliduser'];
            }elseif(!empty($result['invalidparty'])){
                return $result['invalidparty'];
            }elseif(!empty($result['invalidtag'])){
                return $result['invalidtag'];
            }else{
                return true;
            }
        }else{
            throw new \Exception(isset($result['errmsg']) ? $result['errmsg'] : 'Network Error');
        }
    }

    /**
     * @use: 上传临时素材
     * @date: 2018/8/8 下午4:35
     * @author: sunnnnn [http://www.sunnnnn.com] [mrsunnnnn@qq.com]
     * @return: mixed
     * @param null $secret
     * @param $type 媒体文件类型，所有文件size必须大于5个字节
     *      图片（image）：2MB，支持JPG,PNG格式
     *      语音（voice） ：2MB，播放长度不超过60s，仅支持AMR格式
     *      视频（video） ：10MB，支持MP4格式
     *      普通文件（file）：20MB
     * @param $filePath
     * @param bool $format
     */
    public function uploadMedia($secret = null, $filePath, $type = 'image', $format = true){
        $token = $this->getAccessToken($secret);

        $url = sprintf(self::URL_UPLOAD_MEDIA, $token, $type);
        if(class_exists('\CURLFile')) {
            $data = [
                'media' => new \CURLFile(realpath($filePath), 'application/octet-stream', basename($filePath))
            ];
        } else {
            $data = ['media' => '@' . realpath($filePath)];
        }

        $result = Curl::post($url, $data);
        $result = json_decode($result, true);

        if(!empty($result) && $result['errcode'] == 0) {
            return $format === true ? $result['media_id'] : $result;
        }else{
            throw new \Exception(isset($result['errmsg']) ? $result['errmsg'] : 'Network Error');
        }
    }

    /**====================发送应用消息=====================*/

    /**
     * @use: 发送应用消息
     * @date: 2018/8/8 下午3:15
     * @author: sunnnnn [http://www.sunnnnn.com] [mrsunnnnn@qq.com]
     * @return: void
     * @param array $to 发送目标
     * @param string $type 消息类别，test/image/voice/video/file/textcard/news/mpnews
     * @param array $extData 扩展数据，对应各消息类别
     * @param null $secret
     * @param null $agentId
     * @param null $safe 表示是否是保密消息，0表示否，1表示是，默认0
     */
    public function sendMessage($to = [], $type = '', $extData = [], $secret = null, $agentId = null, $safe = null){
        $token = $this->getAccessToken($secret);

        $url = sprintf(self::URL_SEND_MESSAGE, $token);
        $commonData = [
            'touser'  => isset($to['users']) && is_array($to['users']) ? implode('|', $to['users']) : '',
            'toparty' => isset($to['departments']) && is_array($to['departments']) ? implode('|', $to['departments']) : '',
            'totag'   => isset($to['tags']) && is_array($to['tags']) ? implode('|', $to['tags']) : '',
            'msgtype' => empty($type) ? 'text' : $type,
            'agentid' => $agentId === null ? $this->config['agentid'] : $agentId,
            'safe'    => $safe
        ];
        $data = array_merge($commonData, $extData);
        $result = Curl::post($url, json_encode($data));
        $result = json_decode($result, true);

        if(!empty($result) && $result['errcode'] == 0) {
            return $result;
        }else{
            throw new \Exception(isset($result['errmsg']) ? $result['errmsg'] : 'Network Error');
        }
    }

    /**
     * @use: 发送文本消息
     * @date: 2018/8/8 下午3:21
     * @author: sunnnnn [http://www.sunnnnn.com] [mrsunnnnn@qq.com]
     * @return: void
     * @param $to
     * @param $content 消息内容，最大2048字节
     * @param null $agentId
     * @param null $secret
     * @param int $safe
     */
    public function sendTextMessage($to, $content, $agentId = null, $secret = null, $safe = 0){
        $this->sendMessage($to, 'text', ['text' => ['content' => $content]], $secret, $agentId, $safe);
    }

    /**
     * @use: 图片消息
     * @date: 2018/8/8 下午3:27
     * @author: sunnnnn [http://www.sunnnnn.com] [mrsunnnnn@qq.com]
     * @return: void
     * @param $to
     * @param $mediaId 图片媒体文件id，可以调用上传临时素材接口获取
     * @param null $agentId
     * @param null $secret
     * @param int $safe
     */
    public function sendImageMessage($to, $mediaId, $agentId = null, $secret = null, $safe = 0){
        $this->sendMessage($to, 'image', ['image' => ['media_id' => $mediaId]], $secret, $agentId, $safe);
    }

    /**
     * @use: 音频文件
     * @date: 2018/8/8 下午3:35
     * @author: sunnnnn [http://www.sunnnnn.com] [mrsunnnnn@qq.com]
     * @return: void
     * @param $to
     * @param $mediaId 图片媒体文件id，可以调用上传临时素材接口获取
     * @param null $agentId
     * @param null $secret
     */
    public function sendVoiceMessage($to, $mediaId, $agentId = null, $secret = null){
        $this->sendMessage($to, 'voice', ['voice' => ['media_id' => $mediaId]], $secret, $agentId);
    }

    /**
     * @use: 视频消息
     * @date: 2018/8/8 下午3:34
     * @author: sunnnnn [http://www.sunnnnn.com] [mrsunnnnn@qq.com]
     * @return: void
     * @param $to
     * @param $data Array
     *          mediaId: 视频媒体文件id，可以调用上传临时素材接口获取
     *          title: 视频消息的标题，不超过128个字节
     *          description: 视频消息的描述，不超过512个字节
     * @param null $agentId
     * @param null $secret
     * @param int $safe
     */
    public function sendVideoMessage($to, $data = [], $agentId = null, $secret = null, $safe = 0){
        $video = [
            'media_id' => isset($data['mediaId']) ? $data['mediaId'] : '',
            'title' => isset($data['title']) ? $data['title'] : '',
            'description' => isset($data['description']) ? $data['description'] : '',
        ];
        $this->sendMessage($to, 'video', ['video' => $video], $secret, $agentId, $safe);
    }

    /**
     * @use: 文件消息
     * @date: 2018/8/8 下午3:36
     * @author: sunnnnn [http://www.sunnnnn.com] [mrsunnnnn@qq.com]
     * @return: void
     * @param $to
     * @param $mediaId
     * @param null $agentId
     * @param null $secret
     * @param int $safe
     */
    public function sendFileMessage($to, $mediaId, $agentId = null, $secret = null, $safe = 0){
        $this->sendMessage($to, 'file', ['file' => ['media_id' => $mediaId]], $secret, $agentId, $safe);
    }

    /**
     * @use: 文本卡消息
     * @date: 2018/8/8 下午3:39
     * @author: sunnnnn [http://www.sunnnnn.com] [mrsunnnnn@qq.com]
     * @return: void
     * @param $to
     * @param $data array
     *          title: 标题，不超过128个字节
     *          description: 描述，不超过512个字节
     *          url: 点击后跳转的链接
     *          btntxt: 按钮文字。 默认为“详情”， 不超过4个文字
     * @param null $agentId
     * @param null $secret
     */
    public function sendCardMessage($to, $data = [], $agentId = null, $secret = null){
        $card = [
            'title' => isset($data['title']) ? $data['title'] : '',
            'description' => isset($data['description']) ? $data['description'] : '',
            'url' => isset($data['url']) ? $data['url'] : '',
            'btntxt' => isset($data['btntxt']) ? $data['btntxt'] : '详情',
        ];
        $this->sendMessage($to, 'textcard', ['textcard' => $data], $secret, $agentId);
    }

    /**
     * @use: 图文消息
     * @date: 2018/8/8 下午3:56
     * @author: sunnnnn [http://www.sunnnnn.com] [mrsunnnnn@qq.com]
     * @return: void
     * @param $to
     * @param array $data Array 支持最多8条数据
     *          title: 标题，不超过128个字节
     *          description: 描述，不超过512个字节
     *          url: 点击后跳转的链接
     *          picurl: 图文消息的图片链接，支持JPG、PNG格式，较好的效果为大图 640x320，小图80x80
     *          btntxt: 按钮文字，仅在图文数为1条时才生效。 默认为“阅读全文”， 不超过4个文字
     * @param null $agentId
     * @param null $secret
     */
    public function sendNewsMessage($to, $data = [], $agentId = null, $secret = null){
        if(Helper::getArrayDepth($data) == 2){
            foreach($data as $key => $val){
                $articles[] = [
                    'title' => isset($val['title']) ? $val['title'] : '',
                    'description' => isset($val['description']) ? $val['description'] : '',
                    'url' => isset($val['url']) ? $val['url'] : '',
                    'picurl' => isset($val['picurl']) ? $val['picurl'] : '',
                    'btntxt' => isset($val['btntxt']) ? $val['btntxt'] : '阅读全文',
                ];

            }
        }else{
            $articles[] = [
                'title' => isset($data['title']) ? $data['title'] : '',
                'description' => isset($data['description']) ? $data['description'] : '',
                'url' => isset($data['url']) ? $data['url'] : '',
                'picurl' => isset($data['picurl']) ? $data['picurl'] : '',
                'btntxt' => isset($data['btntxt']) ? $data['btntxt'] : '阅读全文',
            ];
        }

        $this->sendMessage($to, 'news', ['news' => ['articles' => $articles]], $secret, $agentId);
    }

    /**=====================JS-SDK========================*/
    /**
     * @use: 获取JS-SDK的ticket
     * @date: 2018/8/6 上午11:50
     * @author: sunnnnn [http://www.sunnnnn.com] [mrsunnnnn@qq.com]
     * @return: mixed
     * @param null $secret
     * @param bool $format
     * @param bool $refresh 为true则强制刷新
     */
    public function getJsTicket($secret = null, $format = true, $refresh = false){
        if($refresh === false && Yii::$app->cache->exists($this->config['cache_js_prefix'].$secret)){
            return Yii::$app->cache->get($this->config['cache_js_prefix'].$secret);
        }

        $token = $this->getAccessToken($secret);
        $url = sprintf(self::URL_JS_GET_TICKET, $token);
        $result = Curl::get($url);
        $result = json_decode($result, true);

        if(!empty($result) && $result['errcode'] == 0) {
            $expiresIn = isset($result['expires_in']) ? $result['expires_in'] : 7200;
            Yii::$app->cache->set($this->config['cache_js_prefix'].$secret, $result['ticket'], $expiresIn - 180);
            return $format === true ? $result['ticket'] : $result;
        }else{
            throw new \Exception(isset($result['errmsg']) ? $result['errmsg'] : 'Network Error');
        }
    }

    /**
     * @use: 获取使用JS-SDK的页面的配置数据
     * @date: 2018/8/6 上午11:55
     * @author: sunnnnn [http://www.sunnnnn.com] [mrsunnnnn@qq.com]
     * @return: string
     * @param array $jsApiList 需要使用的JS接口列表，https://work.weixin.qq.com/api/doc#10029/附录2-所有JS接口列表
     * @param null $secret
     * @param bool $url 需要用到JS_SDK的URL，默认当前页面
     * @param bool $debug 调试模式
     * @param string $noncestr
     */
    public function getJsConfig($jsApiList = [], $secret = null, $url = null, $debug = false, $noncestr = 'EnterpriseWechat'){
        $data['jsapi_ticket'] = $this->getJsTicket($secret);
        $data['nonceStr'] = $noncestr;
        $data['timestamp'] = strval(time());
        $data['url'] = empty($url) ? Helper::getHost() : $url;
        $data['signature'] = $this->getJsSignature($data);
        $data['appId'] = $this->config['corpid'];
        $data['beta'] = true;
        $data['debug'] = $debug;
        $data['jsApiList'] = $jsApiList;
        return json_encode($data);
    }

    /** 获取JS_SDK的配置签名
     * @use:
     * @date: 2018/8/6 下午1:16
     * @author: sunnnnn [http://www.sunnnnn.com] [mrsunnnnn@qq.com]
     * @return: string
     * @param $config
     */
    public function getJsSignature($config){
        if(empty($config) || !is_array($config)){
            return '';
        }
        ksort($config);
        $signature = '';
        foreach($config as $key => $val){
            if(empty($signature)){
                $signature = strtolower($key).'='.$val;
            }else{
                $signature .= '&'.strtolower($key).'='.$val;
            }
        }

        return sha1($signature);
    }


    /**===================Approval审批=====================*/
    /**
     * @use:
     * @date: 2018/8/31 上午10:35
     * @author: sunnnnn [http://www.sunnnnn.com] [mrsunnnnn@qq.com]
     * @return: mixed|string
     * @param null $secret
     * @param string $start 开始时间 时间戳
     * @param string $end 结束时间 时间戳
     * @param null $number 从这个审批单号开始抓取
     * @throws \Exception
     */
    public function getApprovalData($secret = null, $start = '', $end = '', $number = null){
        $token = $this->getAccessToken($secret);

        $url = sprintf(self::URL_APPROVAL, $token);
        $data = [
            'starttime' => empty($start) ? mktime(0,0,0,date('m'),date('d'),date('Y')) : $start,
            'endtime'   => empty($end) ? time() : $end,
            'next_spnum' => empty($number) ? null : $number,
        ];
        $result = Curl::post($url, json_encode($data));
        $result = json_decode($result, true);

        if(!empty($result) && $result['errcode'] == 0) {
            return $result;
        }else{
            throw new \Exception(isset($result['errmsg']) ? $result['errmsg'] : 'Network Error');
        }
    }
}