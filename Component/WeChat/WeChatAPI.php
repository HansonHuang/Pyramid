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
        $body  = Utility::http(sprintf($url, $token, $type), array('media' => CURLFile::realpath($file)));
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
    public function downloadFile($media_id, $filename='') {
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
            subscribe      => 0:未关注 1:关注,
            openid         =>,
            nickname       =>,
            sex            => 0:未知 1:男 2:女,
            language       => zh_CN,
            city           =>,
            province       =>,
            country        =>,
            headimgurl     =>,
            subscribe_time =>,
            unionid        =>,
            remark         =>,
            groupid        => 0,
            tagid_list     => [2,168]
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
     * 新增或修改用户标签
     * @see http://mp.weixin.qq.com/wiki?t=resource/res_main&id=mp1421140837
     * @param {
         tag : {
             id 修改的时候用到
             name: 30字以内
         }
       }
     * @return {id name} | true | false
     */
    public function setTag($tag) {
        static $urlCreate = 'https://api.weixin.qq.com/cgi-bin/tags/create?access_token=%s';
        static $urlUpdate = 'https://api.weixin.qq.com/cgi-bin/tags/update?access_token=%s';
        $token = $this->getAccessToken();
        $type = empty($tag['tag']['id']) ? 'create' : 'update';
        $body = Utility::http(sprintf($type=='update'?$urlUpdate:$urlCreate, $token), Utility::json_encode($tag));
        $json = json_decode($body, true);
        if (!$json || !empty($json['errcode'])) {
            return false;
        } else {
            return $type == 'update' ? true : $json['tag'];
        }
    }

    /**
     * 获取公众号已创建的标签
     * @return array
     */
    public function getTags() {
        static $url = 'https://api.weixin.qq.com/cgi-bin/tags/get?access_token=%s';
        $token = $this->getAccessToken();
        $body = Utility::http(sprintf($url,$token));
        $json = json_decode($body, true);
        if (!$json || !empty($json['errcode'])) {
            return false;
        } else {
            return $json['tags'];
        }
    }

    /**
     * 删除用户标签
     * @return boolean
     */
    public function deleteTag($tagid) {
        static $url = 'https://api.weixin.qq.com/cgi-bin/tags/delete?access_token=%s';
        $token = $this->getAccessToken();
        $body = Utility::http(sprintf($url,$token), '{"tag":{"id":'.$tagid.'}}');
        $json = json_decode($body, true);
        if (!$json || !empty($json['errcode'])) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * 批量给用户打标签
     * @param openids [openid,openid] 每次不超过50个
     * @param tagid 标签号
     */
    public function setUsersTag($openids, $tagid) {
        static $url = 'https://api.weixin.qq.com/cgi-bin/tags/members/batchtagging?access_token=%s';
        $token = $this->getAccessToken();
        $body  = Utility::http(sprintf($url, $token), Utility::json_encode(array(
                    'openid_list' => $openids, 'tagid' => $tagid,
                 )));
        $json  = json_decode($body, true);
        if (!$json || !empty($json['errcode'])) {
            return false;
        } else {
            return true;
        }
    }
    
    /**
     * 批量取消用户标签
     * @param openids [openid,openid] 每次不超过50个
     * @param tagid 标签号
     */
    public function deleteUsersTag($openids, $tagid) {
        static $url = 'https://api.weixin.qq.com/cgi-bin/tags/members/batchuntagging?access_token=%s';
        $token = $this->getAccessToken();
        $body  = Utility::http(sprintf($url, $token), Utility::json_encode(array(
                    'openid_list' => $openids, 'tagid' => $tagid,
                 )));
        $json  = json_decode($body, true);
        if (!$json || !empty($json['errcode'])) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * 获取标签下的用户列表
     * @param tagid
     * @param next_openid
     * @return boolean | array(
            count
            next_openid
            data : {
                openid : [OPENID, OPENID, ...]
            }
       )
     */
    public function getTagedUserList($tagid, $next_openid = '') {
        static $url = 'https://api.weixin.qq.com/cgi-bin/user/tag/get?access_token=%s';
        $token = $this->getAccessToken();
        $body  = Utility::http(sprintf($url, $token), Utility::json_encode(array(
                    'tagid' => $tagid, 'next_openid' => $next_openid,
                 )));
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
     * 添加个性化菜单
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
          ],
          
          matchrule {  //至少1项
              tag_id   //用户标签的id
              sex      //0:未知 1:男 2:女,
              country 
              province
              city
              client_platform_type //1:iOS 2:Android 3:Other
              language  //zh_CN zh_TW zh_HK en id ms es ja it pl pt ru th vi ar hi tr he de fr
          }

        }
     * @return menuid | false
     */
    public function setConditionalMenu($menu) {
        static $url = 'https://api.weixin.qq.com/cgi-bin/menu/addconditional?access_token=%s';
        $token = $this->getAccessToken();
        $body  = Utility::http(sprintf($url, $token), Utility::json_encode($menu));
        $json  = json_decode($body, true);
        if (!$json || !empty($json['errcode'])) {
            return false;
        } else {
            return $json['menuid'];
        }
    }
    
    /**
     * 删除个性化菜单
     *
     * @return boolean
     */
    public function deleteConditionalMenu($menuid) {
        static $url = 'https://api.weixin.qq.com/cgi-bin/menu/delconditional?access_token=%s';
        $token = $this->getAccessToken();
        $body  = Utility::http(sprintf($url, $token), Utility::json_encode(array('menuid'=>$menuid)));
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
          scope        => snsapi_base | snsapi_userinfo,
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
            'scope'         => !empty($param['scope']) ? $param['scope'] : 'snsapi_base',
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
     * 获取JS API Ticket
     *
     * @return {
            "errcode":0,
            "errmsg":"ok",
            "ticket":"bxLdikRXVbTPdHSM05e5u5sUoXNKdvsdshFKA",
            "expires_in":7200
        }
     */
    public function getJsTicket() {
        static $url = "https://api.weixin.qq.com/cgi-bin/ticket/getticket?type=jsapi&access_token=%s";
        $ticket = Utility::getJsTicket($this);
        if (empty($ticket) || time() > $ticket['expired']) {
            $token = $this->getAccessToken();
            $body  = Utility::http(sprintf($url, $token));
            $json  = json_decode($body, true);
            if (!$json || !empty($json['errcode'])) {
                throw new Exception('Error - WeChat Can not get JsTicket.');
            } else {
                $ticket['ticket']   = $json['ticket'];
                $ticket['expired'] = time() + $json['expires_in'] - 120;
                Utility::setJsTicket($ticket, $this);
            }
        }
        
        return $ticket['ticket'];
    }
    
    /**
     * 获取wx.config需要的数据
     *
     * @param string uri 当前网址(#前的所有字符)
     */
    public function wxConfig($uri = '') {
        $timestamp  = time();
        $nonceStr   = md5(microtime(true));
        $ticket     = $this->getJsTicket();
        $string     = "jsapi_ticket={$ticket}&noncestr={$nonceStr}&timestamp={$timestamp}&url=" . $uri;
        $signature  = sha1($string);
        return array(
            'appId'     => $this->getConfig('appid'),
            'timestamp' => $timestamp,
            'nonceStr'  => $nonceStr,
            'signature' => $signature,
        );
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

    /**
     * 验证配置
     */
    public function validateConfig() {
        return $this->getTags() === false ? false : true;
    }


    /**
     * 发送现金红包
     * @see https://pay.weixin.qq.com/wiki/doc/api/cash_coupon.php?chapter=13_5
     *
     * data {
            'mch_billno'   : 商户订单号,唯一
            'send_name'    : 商户名称
            're_openid'    : 用户openid,
            'total_amount' : 金额 单位:分,
            'total_num'    : 红包发放总人数,写1就可,
            'wishing'      : 红包祝福语,
            'act_name'     : 活动名称,
            'remark'       : 备注,
            
            'nonce_str'    : [可选] 随机字符串,
            'mch_id'       : [可选] 商户号,
            'client_ip'    : [可选] 客户端IP
            'wxappid'      : [可选] 公众号appid,
        }
     *@return true|false
     */
    public function sendRedpack(array $data) {
        static $url = 'https://api.mch.weixin.qq.com/mmpaymkttransfers/sendredpack';
        $data += array(
            'nonce_str' => md5(microtime(true)),
            'client_ip' => $this->getConfig('client_ip'),
            'mch_id'    => $this->getConfig('mch_id'),
            'wxappid'   => $this->getConfig('appid'),
        );
        $data = Utility::makeSign($data, $this->getConfig('pay_key'));
        $xml  = Utility::buildXML($data);
        $body = Utility::http($url, $xml, $this->getConfig('certs'));
        try {
            $obj = @simplexml_load_string($body, 'SimpleXMLElement', LIBXML_NOCDATA | LIBXML_NOBLANKS);
            $res = Utility::extractXML($obj);
            if ($res['return_code'] == 'SUCCESS' && $res['result_code'] == 'SUCCESS') {
                return $res;
            } else {
                logger()->error('sendRedpack(Error): ' . $res['return_msg'] . '|' . $res['err_code_des']);
                return false;
            }
        } catch (Exception $e) {
            logger()->error('sendRedpack(Exception): ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 微信统一下单
     * @see https://pay.weixin.qq.com/wiki/doc/api/jsapi.php?chapter=9_1
     * @see https://pay.weixin.qq.com/wiki/doc/api/jsapi.php?chapter=9_7 下单回调结果
     * data {
            body        : 商品描述(128)
            detail      : [可选] 商品详情(8192)
            attach      : [可选] 附加信息(127)
            out_trade_no: 商户订单号(32)
            total_fee   : 总额(单位为分)
            time_start  : [可选] 交易起始时间 yyyyMMddHHmmss
            time_expire : [可选] 交易结束时间 yyyyMMddHHmmss
            goods_tag   : [可选] 商品标记(32)
            notify_url  : 回调地址
            trade_type  : 交易类型 JSAPI:公众号支付 NATIVE:原生扫码支付 APP:app支付
            product_id  : [可选] 商品ID(32)
            limit_pay   : [可选] 指定支付方式 no_credit
            openid      : OPENID (trade_type=JSAPI 此参数必须)
            
            appid       : [可选] 公众号appid
            mch_id      : [可选] 商户号
            device_info : [可选] WEB
            nonce_str   : [可选] 随机字符
            fee_type    : [可选] CNY
            spbill_create_ip: IP
        }
     *@return array(
                trade_type JSAPI NATIVE APP
                prepay_id  预支付交易会话,2小时有效
                code_url   trade_type为NATIVE是有返回
              )  | false
     */
    public function sendOrder(array $data) {
        static $url = 'https://api.mch.weixin.qq.com/pay/unifiedorder';
        $data += array(
            'appid'       => $this->getConfig('appid'),
            'mch_id'      => $this->getConfig('mch_id'),
            'device_info' => 'WEB',
            'nonce_str'   => md5(microtime(true)),
            'fee_type'    => 'CNY',
            'spbill_create_ip' => $this->getConfig('client_ip'),
            'trade_type'  => 'JSAPI',
        );
        $data = Utility::makeSign($data, $this->getConfig('pay_key'));
        $xml  = Utility::buildXML($data);
        $body = Utility::http($url, $xml);
        try {
            $obj = @simplexml_load_string($body, 'SimpleXMLElement', LIBXML_NOCDATA | LIBXML_NOBLANKS);
            $res = Utility::extractXML($obj);
            if ($res['return_code'] == 'SUCCESS' && $res['result_code'] == 'SUCCESS') {
                return $res;
            } else {
                logger()->error('sendOrder(Error): ' . $res['return_msg'] . '|' . $res['err_code_des']);
                return false;
            }
        } catch (Exception $e) {
            logger()->error('sendOrder(Exception): ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 微信JSAPI支付js数据
     * @parameter $res JSAPI下单返回的数组
     */
    public function jsPayConfig($res) {
        $data = array(
            'appId'     => !empty($res['appid']) ? $res['appid'] : $this->getConfig('appid'),
            'timeStamp' => time(),
            'nonceStr'  => md5(microtime(true)),
            'package'   => 'prepay_id=' . $res['prepay_id'],
            'signType'  => 'MD5',            
        );
        $data['paySign'] = Utility::getSign($data, $this->getConfig('pay_key'));
        return $data;
    }

}
