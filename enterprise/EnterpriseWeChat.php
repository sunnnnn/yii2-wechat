<?php
namespace sunnnnn\wechat\enterprise;

use Yii;
use yii\base\Component;
use sunnnnn\wechat\Error;
use sunnnnn\wechat\Helper;
use sunnnnn\wechat\Curl;
use sunnnnn\wechat\utils\WXBizMsgCrypt;

class EnterpriseWeChat extends Component{

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

    public $config = [
        'corpid'           => '*',  //企业ID，必填
        'agentid'          => '',   //应用ID
        'contact_secret'   => '',   //通讯录密钥
        'e_contact_secret' => '',   //外部联系人密钥
        'cache'            => true, //是否缓存Token
        'cache_prefix'     => 'access_token_cache_', //缓存索引
        'verify_url_token' => '', //接收事件服务器 Token
        'verify_url_key'   => '', //接收事件服务器 EncodingAESKey
    ];

    public function __construct($config = []){
        Helper::setConfig($this->config, $config);

        if(empty($this->config)){
            Error::showError('Please set the configuration file <param: wechat>!');
        }

        if(!Helper::isWeChatBrowser()){
            //Error::showError('please open this in wechat app !');
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
     * @use: 获取Access Token
     * @date: 2018/7/23 下午3:59
     * @author: sunnnnn [http://www.sunnnnn.com] [mrsunnnnn@qq.com]
     * @return: mixed
     * @param null $secret 应用密钥， 默认通讯录密钥，需要配置config
     * @param bool $format 为true只返回token， false返回全部信息
     * @throws \Exception
     */
    public function getAccessToken($secret = null, $format = true){
        $secret = $secret === null ? $this->config['contact_secret'] : $secret;

        if($this->config['cache'] === true && Yii::$app->cache->exists($this->config['cache_prefix'].$secret)){
            return Yii::$app->cache->get($this->config['cache_prefix'].$secret);
        }

        $url = sprintf(self::URL_ACCESS_TOKEN, $this->config['corpid'], $secret);
        $result = Curl::get($url);
        $result = json_decode($result, true);

        if(!empty($result) && $result['errcode'] == 0) {
            if($this->config['cache'] === true){
                $expiresIn = isset($result['expires_in']) ? $result['expires_in'] : 7200;
                Yii::$app->cache->set($this->config['cache_prefix'].$secret, $result['access_token'], $expiresIn - 180);
            }
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
            return true;
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
}