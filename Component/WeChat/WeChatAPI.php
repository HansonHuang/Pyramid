<?php

/**
 * @file
 *
 * WechatAPI
 * @see http://mp.weixin.qq.com/wiki/home/index.html
 */

namespace Pyramid\Component\WeChat;

use Exception;

/**
 * @todo 客服 群发 永久素材
 *
 */
class WeChatAPI {
    
    /**
     * 发送客服消息
     *
     * @param {
          touser  : OPENID,
          msgtype : text | image | voice | video | music | news | wxcard
          ...
       }
     *
     * @return boolean | array
     */
    public function sendCustomMessage($data) {
        static $url = 'https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token=%s';
        $token = $this->getAccessToken();
        $body  = Utility::http(sprintf($url, $token), Utility::json_encode($data));
        $json  = json_decode($body, true);
        if (!$json || !empty($json['errcode'])) {
            return false;
        } else {
            return $json;
        }
    }
   
    /**
     * 发送模板消息
     *
     * @param {
          touser      : OPENID,
          template_id : TEMPLATE,
          url         :
          topcolor    : '#FFFFFF'
          data        : [
              first : {
                 value :
                 color :
              }
              ...
              remark : {
                 value :
                 color :
              }
          ]
       }
     *
     * @return boolean | array
     */
    public function sendTemplateMessage($data) {
        static $url = 'https://api.weixin.qq.com/cgi-bin/message/template/send?access_token=%s';
        $token = $this->getAccessToken();
        $body  = Utility::http(sprintf($url, $token), Utility::json_encode($data));
        $json  = json_decode($body, true);
        if (!$json || !empty($json['errcode'])) {
            return false;
        } else {
            return $json;
        }
    }
    
    /**
     * 上传临时素材
     *
     * image 1M, voice 2M, video 10M, thumb 64K     
     * @return boolean | array(type => , media_id => , created_at => )
     */
    public function uploadFile($type, $file) {
        static $url = 'http://file.api.weixin.qq.com/cgi-bin/media/upload?access_token=%s&type=%s';
        $token = $this->getAccessToken();
        $body  = Utility::http(sprintf($url, $token, $type), array('media' => '@'.realpath($file)));
        $json  = json_decode($body, true);
        if (!$json || !empty($json['errcode'])) {
            return false;
        } else {
            return $json;
        }
    }
    
    /**
     * 下载临时素材
     *
     * @return boolean | binary
     */
    public function downloadFile($media_id) {
        static $url = 'http://file.api.weixin.qq.com/cgi-bin/media/get?access_token=%s&media_id=%s';
        $token = $this->getAccessToken();
        $body  = Utility::http(sprintf($url, $token, $media_id));
        if (substr($body,0,1) == '{') {
            return false;
        } else {
            return $body;
        }
    }

    /**
     * 获取单个用户信息
     *
     * @return boolean | array(
            subscribe      =>,
            openid         =>,
            nickname       =>,
            sex            =>,
            language       =>,
            city           =>,
            province       =>,
            country        =>,
            headimgurl     =>,
            subscribe_time =>,
            unionid        =>,
            remark         =>,
            groupid        => 0,
        )
     */
    public function getUserInfo($openid) {
        static $url = 'https://api.weixin.qq.com/cgi-bin/user/info?access_token=%s&openid=%s&lang=zh_CN';
        $token = $this->getAccessToken();
        $body  = Utility::http(sprintf($url, $token, $openid));
        $json  = json_decode($body, true);
        if (!$json || !empty($json['errcode'])) {
            return false;
        } else {
            return $json;
        }
    }

    /**
     * 获取多个用户信息 最多100个
     *
     * @param {
          user_list : [
            { openid =>, lang => zh_CN }
            { openid =>, lang => zh_CN }
          ]
        }
     *
     * @return boolean | array(
          user_info_list : [
             {USERINFO}
             {USERINFO}
          ]
        )
     */
    public function getUserInfoBatch($openids) {
        static $url = 'https://api.weixin.qq.com/cgi-bin/user/info/batchget?access_token=%s';
        $token = $this->getAccessToken();
        foreach ($openids as $openid) {
            $data['user_list'][] = array(
                'openid' => $openid,
                'lang'   => 'zh_CN',
            );
        }
        $body  = Utility::http(sprintf($url, $token), Utility::json_encode($data));
        $json  = json_decode($body, true);
        if (!$json || !empty($json['errcode'])) {
            return false;
        } else {
            return $json;
        }
    }
    
    /**
     * 设置用户备注
     *
     * @return boolean
     */
    public function setUserRemark($openid, $remark) {
        static $url = 'https://api.weixin.qq.com/cgi-bin/user/info/updateremark?access_token=%s';
        $token = $this->getAccessToken();
        $body  = Utility::http(sprintf($url, $token), Utility::json_encode(array(
                    'openid' => $openid, 'remark' => $remark,
                 )));
        $json  = json_decode($body, true);
        if (!$json || !empty($json['errcode'])) {
            return false;
        } else {
            return true;
        }
    }
    
    /**
     * 获取用户列表
     *
     * @return boolean | array(
            total
            count
            next_openid
            data : {
                openid : [OPENID, OPENID, ...]
            }
       )
     */
    public function getUserList($next_openid = '') {
        static $url = 'https://api.weixin.qq.com/cgi-bin/user/get?access_token=%s&next_openid=%s';
        $token = $this->getAccessToken();
        $body  = Utility::http(sprintf($url, $token, $next_openid));
        $json  = json_decode($body, true);
        if (!$json || !empty($json['errcode'])) {
            return false;
        } else {
            return $json;
        }
    }

    /**
     * 创建用户分组
     *
     * @param {
          group ：{
             name => ,
          }
        }
     *
     * @return boolean | array(
          group : {
             id   =>,
             name =>,
          }
       )
     */
    public function addGroup($name) {
        static $url = 'https://api.weixin.qq.com/cgi-bin/groups/create?access_token=%s';
        $token = $this->getAccessToken();
        $body  = Utility::http(sprintf($url, $token), Utility::json_encode(array(
                    'group' => array('name' => $name)
                 )));
        $json  = json_decode($body, true);
        if (!$json || !empty($json['errcode'])) {
            return false;
        } else {
            return $json;
        }
    }

    /**
     * 查询所有分组
     *
     * @return boolean | array(
          groups : [
             {
                id
                name
                count
             },
             ...
          ]
       )
     */
    public function getGroups() {
        static $url = 'https://api.weixin.qq.com/cgi-bin/groups/get?access_token=%s';
        $token = $this->getAccessToken();
        $body  = Utility::http(sprintf($url, $token));
        $json  = json_decode($body, true);
        if (!$json || !empty($json['errcode'])) {
            return false;
        } else {
            return $json;
        }
    }

    /**
     * 修改分组
     *
     * @param {
          group ：{
             id   => ,
             name => ,
          }
        }
     *
     * @return boolean
     */
    public function setGroup($groupid, $name) {
        static $url = 'https://api.weixin.qq.com/cgi-bin/groups/update?access_token=%s';
        $token = $this->getAccessToken();
        $body  = Utility::http(sprintf($url, $token), Utility::json_encode(array(
                    'group' => array('id' => $groupid, 'name' => $name)
                 )));
        $json  = json_decode($body, true);
        if (!$json || !empty($json['errcode'])) {
            return false;
        } else {
            return true;
        }
    }
    
    /**
     * 删除分组
     *
     * @param {
          group ：{
             id   => ,
          }
        }
     *
     * @return boolean
     */
    public function deleteGroup($groupid) {
        static $url = 'https://api.weixin.qq.com/cgi-bin/groups/delete?access_token=%s';
        $token = $this->getAccessToken();
        $body  = Utility::http(sprintf($url, $token), Utility::json_encode(array(
                    'group' => array('id' => $groupid)
                 )));
        $json  = json_decode($body, true);
        if (!$json || !empty($json['errcode'])) {
            return false;
        } else {
            return true;
        }
    }
    
    /**
     * 查询用户所在分组
     *
     * @return boolean | array(
          groupid =>,
       )
     */
    public function getUserGroup($openid) {
        static $url = 'https://api.weixin.qq.com/cgi-bin/groups/getid?access_token=%s';
        $token = $this->getAccessToken();
        $body  = Utility::http(sprintf($url, $token), Utility::json_encode(array(
                    'openid' => $openid,
                 )));
        $json  = json_decode($body, true);
        if (!$json || !empty($json['errcode'])) {
            return false;
        } else {
            return $json;
        }
    }
    
    /**
     * 修改用户所在分组
     *
     * @return boolean
     */
    public function setUserGroup($openid, $groupid) {
        static $url = 'https://api.weixin.qq.com/cgi-bin/groups/members/update?access_token=%s';
        $token = $this->getAccessToken();
        $body  = Utility::http(sprintf($url, $token), Utility::json_encode(array(
                    'openid'     => $openid,
                    'to_groupid' => $groupid,
                 )));
        $json  = json_decode($body, true);
        if (!$json || !empty($json['errcode'])) {
            return false;
        } else {
            return true;
        }
    }
    
    /**
     * 批量移动用户分组
     *
     * @param {
          to_groupid : ,
          openid_list ：[
             OPENID, OPENID, ...
          ]
        }
     *
     * @return boolean
     */
    public function setUsersGroup($openids, $groupid) {
        static $url = 'https://api.weixin.qq.com/cgi-bin/groups/members/update?access_token=%s';
        $token = $this->getAccessToken();
        $body  = Utility::http(sprintf($url, $token), Utility::json_encode(array(
                    'openid_list' => $openids,
                    'to_groupid'  => $groupid,
                 )));
        $json  = json_decode($body, true);
        if (!$json || !empty($json['errcode'])) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * 创建自定义菜单
     *
     * @param {
          button : [
              {
                type : click
                name :
                key  :
              },
              {
                name :
                sub_button : [
                    type: view
                    name:
                    url :
                ]
              }
          ]
        }
     *
     * @return boolean
     */
    public function setMenu($menu) {
        static $url = 'https://api.weixin.qq.com/cgi-bin/menu/create?access_token=%s';
        $token = $this->getAccessToken();
        $body  = Utility::http(sprintf($url, $token), Utility::json_encode($menu));
        $json  = json_decode($body, true);
        if (!$json || !empty($json['errcode'])) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * 查询自定义菜单
     *
     * @retrun boolean | array(
            menu : {
                button : ...
            }
       )
     */
    public function getMenu() {
        static $url = 'https://api.weixin.qq.com/cgi-bin/menu/get?access_token=%s';
        $token = $this->getAccessToken();
        $body  = Utility::http(sprintf($url, $token));
        $json  = json_decode($body, true);
        if (!$json || !empty($json['errcode'])) {
            return false;
        } else {
            return $json;
        }
    }

    /**
     * 删除自定义菜单
     *
     * @return boolean
     */
    public function deleteMenu() {
        static $url = 'https://api.weixin.qq.com/cgi-bin/menu/delete?access_token=%s';
        $token = $this->getAccessToken();
        $body  = Utility::http(sprintf($url, $token));
        $json  = json_decode($body, true);
        if (!$json || !empty($json['errcode'])) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * 用户同意授权，获取code
     *
     * @param array(
          redirect_uri =>,
          scope        =>,
          state        =>,
       )
     *
     * @return string URL
     */
    public function getWebCodeUrl($param){
        static $url = 'https://open.weixin.qq.com/connect/oauth2/authorize?appid=%s&redirect_uri=%s&response_type=%s&scope=%s&state=%s#wechat_redirect';
        $args = array(
            'appid'         => $this->getConfig('appid'),
            'redirect_uri'  => $param['redirect_uri'],
            'response_type' => 'code',
            'scope'         => $param['scope'],
            'state'         => $param['state'],
        );
        return vsprintf($url, $args);
    }

    /**
     * 通过code换取网页授权WebAccessToken
     *
     * @return boolean | array
     */
    public function getWebToken($code) {
        static $url = 'https://api.weixin.qq.com/sns/oauth2/access_token?%s';
        $args = array(
            'appid'      => $this->getConfig('appid'),
            'secret'     => $this->getConfig('appsecret'),
            'code'       => $code,
            'grant_type' => 'authorization_code',
        );
        $body = Utility::http(sprintf($url, http_build_query($args)));
        $json = json_decode($body, true);
        if (!$json || !empty($json['errcode'])) {
            return false;
        } else {
            return $json;
        }
    }

    /**
     * 刷新WebAccessToken
     *
     * @return boolean | array
     */
    public function refreshWebToken($token) {
        static $url = 'https://api.weixin.qq.com/sns/oauth2/refresh_token?%s';
        $args = array(
            'appid'         => $this->getConfig('appid'),
            'grant_type'    => 'refresh_token',
            'refresh_token' => $token,
        );
        $body = Utility::http(sprintf($url, http_build_query($args)));
        $json = json_decode($body, true);
        if (!$json || !empty($json['errcode'])) {
            return false;
        } else {
            return $json;
        }
    }
    
    /**
     * 验证WebAccessToken
     *
     * @return boolean
     */
    public function authWebToken($openid, $token) {
        static $url = 'https://api.weixin.qq.com/sns/auth?access_token=%s&openid=%s';
        $body = Utility::http(sprintf($url, $token, $openid));
        $json = json_decode($body, true);
        if (!$json || !empty($json['errcode'])) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * 获取用户信息(需scope为 snsapi_userinfo)
     *
     * @return boolean | array(
            openid     =>,
            nickname   =>,
            sex        =>,
            province   =>,
            city       =>,
            country    =>,
            headimgurl =>,
            privilege  => []
        )
     */
    public function getWebUserInfo($openid, $token) {
        static $url = 'https://api.weixin.qq.com/sns/userinfo?access_token=%s&openid=%s&lang=zh_CN';
        $body = Utility::http(sprintf($url, $token, $openid));
        $json = json_decode($body, true);
        if (!$json || !empty($json['errcode'])) {
            return false;
        } else {
            return $json;
        }
    }
    
    /**
     * 二维码申请
     *
     * 临时二维码
     * @param {
            expire_seconds: 604800
            action_name   : QR_SCENE
            action_info : {
                scene : {
                    scene_id : 小于100000
                }
            }
        }
     * 永久二维码
     * @param {
            action_name : QR_LIMIT_SCENE | QR_LIMIT_STR_SCENE
            action_info : {
                scene : {
                    scene_id :小于100000
                    <scene_str:长度小于64>
                }
            }
        }
     *
     * @return boolean | array(
            ticket         =>,
            expire_seconds =>,
            url            =>,
        )
     * https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket=TICKET
     */
    public function getQrcodeTicket($data) {
        static $url = 'https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token=%s';
        $token = $this->getAccessToken();
        $body  = Utility::http(sprintf($url, $token), Utility::json_encode($data));
        $json  = json_decode($body, true);
        if (!$json || !empty($json['errcode'])) {
            return false;
        } else {
            return $json;
        }
    }
    
    /**
     * 获取access_token
     */
    public function getAccessToken() {
        static $url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=%s&secret=%s';
        $token = Utility::getAccessToken($this);
        if (empty($token) || time() > $token['expired']) {
            $body = Utility::http(sprintf($url, $this->getConfig('appid'), $this->getConfig('appsecret')));
            $json = json_decode($body, true);
            if (!$json || !empty($json['errcode'])) {
                throw new Exception('Error - WeChat Can not get AccessToken.');            
            } else {
                $token['token']   = $json['access_token'];
                $token['expired'] = time() + $json['expires_in'] - 120;
                Utility::setAccessToken($token, $this);
            }
        }

        return $token['token'];
    }

}
