<?php
namespace Action;
use HY\Action;
!defined('HY_PATH') && exit('HY_PATH not defined.');
class Friend extends HYBBS {
    //设置状态
	public function friend_state(){
        //{hook a_friend_friend_state_1}
        if(!IS_LOGIN)
            return $this->json(array('error'=>false,'info'=>'请登录后操作!'));
        //{hook a_friend_friend_state_2}
        $uid = intval(X("post.uid"));
        if(NOW_UID == $uid){
            return $this->json(array('error'=>false,'info'=>'无法添加自己!'));
        }
        //{hook a_friend_friend_state_3}
        $User = M("User");
        if(!$User->is_id($uid))
            return $this->json(array('error'=>false,'info'=>'你玩的挺嗨的!'));
        $Friend = M("Friend");
        //{hook a_friend_friend_state_4}
        $state = $Friend->get_state(NOW_UID,$uid);

        //陌生人
        //{hook a_friend_friend_state_5}
        if($state == 0){ //添加好友
            //{hook a_friend_friend_state_6}
            $Friend->add_friend(NOW_UID,$uid);
     
            
            //更新关注数量
            
            $count1 = $Friend->count(array('AND'=>array('uid1'=>NOW_UID,'OR'=>array('state'=>array(1,2)))));
            $count2 = $Friend->count(array('AND'=>array('uid2'=>$uid,'OR'=>array('state'=>array(1,2)))));
            
            //{hook a_friend_friend_state_7}
            $User->update(array('follow'=>$count1),array('id'=>NOW_UID));
            $User->update(array('fans'=>$count2),array('id'=>$uid));

            return $this->json(array('error'=>true,'info'=>'添加成功!','id'=>1));
        }
        elseif($state == 1 || $state == 2){ //删除好友
            //{hook a_friend_friend_state_8}
            $Friend->rm_friend(NOW_UID,$uid);
        
            $count1 = $Friend->count(array('AND'=>array('uid1'=>NOW_UID,'OR'=>array('state'=>array(1,2)))));
            $count2 = $Friend->count(array('AND'=>array('uid2'=>$uid,'OR'=>array('state'=>array(1,2)))));
            //{hook a_friend_friend_state_9}

            $User->update(array('follow'=>$count1),array('id'=>NOW_UID));
            $User->update(array('fans'=>$count2),array('id'=>$uid));
            //{hook a_friend_friend_state_10}

            return $this->json(array('error'=>true,'info'=>'删除成功!','id'=>0));
        }
        return $this->json(array('error'=>false,'info'=>'没有返回值!'));
        //{hook a_friend_friend_state_11}

    }
    //发送聊天信息
    public function send_chat(){
        //{hook a_friend_send_chat_1}
        if(!IS_LOGIN)
            return $this->json(array('error'=>false,'info'=>'你需要重新登录!'));
        //{hook a_friend_send_chat_2}
        if(IS_POST){
            if($this->_user['chat_size'] >= $this->_usergroup[NOW_GROUP]['chat_size'])
                return $this->json(array("error"=>false,'info'=>"你已经没有聊天记录储存空间,需要提升用户组或者到个人中心清空你的聊天记录!"));
            //{hook a_friend_send_chat_3}
            //发送给ID
            $uid = intval(X("post.uid"));
            $content = htmlspecialchars(strip_tags(X("post.content")));
            $content = str_replace('&nbsp;','',$content);
            $content = trim($content);
            if(empty($content))
                return $this->json(array('error'=>false,'info'=>'内容不能为空!'));

            //{hook a_friend_send_chat_4}
            //不能发送给自己
            if($uid == NOW_UID)
                return $this->json(array('error'=>false,'info'=>'你玩的挺嗨的!'));
            $User = M("User");
            //{hook a_friend_send_chat_5}
            if(!$User->is_id($uid))
                return $this->json(array('error'=>false,'info'=>'该用户不存在!'));
            //{hook a_friend_send_chat_6}
            M("Chat")->send($uid,NOW_UID,$content);
            M("User")->update_int(NOW_UID,'chat_size','+',strlen($content));
            return $this->json(array('error'=>true,'info'=>'!'));
        }
        //{hook a_friend_send_chat_7}
    }
    //朋友列表
    public function friend_list(){
        //{hook a_friend_friend_list_1}
        if(!IS_LOGIN)
            return $this->json(array('error'=>false,'info'=>'你需要重新登录!'));
        //{hook a_friend_friend_list_2}
        if(IS_POST){
            //{hook a_friend_friend_list_3}
            $Friend = S("Friend");
            //获取我关注的
            $list = $Friend->select('*',array('uid1'=>NOW_UID));
            //获取我的粉丝
            $list1 = $Friend->select("*",array('AND'=>array('uid2'=>NOW_UID,'state'=>1)));

            //{hook a_friend_friend_list_4}
            foreach ($list as $k=> &$v) {
                //{hook a_friend_friend_list_5}
                foreach ($list1 as &$vv) {
                    //{hook a_friend_friend_list_6}
                    if($v['state']== 0 && $v['uid1'] == $vv['uid2'] && $v['uid2'] == $vv['uid1']){
                        $vv['c'] = $v['c'];
                        unset($list[$k]);

                    }
                }
             
             //{hook a_friend_friend_list_7}
            }
            //{hook a_friend_friend_list_8}
            foreach ($list1 as &$v) {
                //{hook a_friend_friend_list_9}
                $v['state'] = 3;
                $v['uid2'] = $v['uid1'];
             
            }
            //{hook a_friend_friend_list_10}
            
            $list = array_merge($list,$list1);
            $User= M("User");
            
            $user_tmp = array();
            $ol = S("ol");
            //{hook a_friend_friend_list_11}
            
            foreach ($list as $key => &$v) {
                if(!isset($user_tmp[$v['uid2']]))
                    $user_tmp[$v['uid2']] = $User->id_to_user($v['uid2']);
                //{hook a_friend_friend_list_12}
                $v['uid'] = $v['uid2'];
                $v['user'] = $user_tmp[$v['uid2']];
                $v['ps'] = $User->find('ps',array('id'=>$v['uid']));
                $v['avatar'] = $this->avatar($v['user']);
                $v['ol']=$ol->has(array('uid'=>$v['uid']));
                unset($v['uid2']);
                unset($v['uid1']);
                if($v['uid'] <= 0)
                    unset($list[$key]);
            }
            //{hook a_friend_friend_list_13}
            $this->json($list);

        }
    }
    //获取历史聊天记录
    public function get_old_chat(){
        //{hook a_friend_get_old_chat_1}
        if(!IS_LOGIN)
            return $this->json(array('error'=>false,'info'=>'你需要重新登录!'));
        //{hook a_friend_get_old_chat_2}
        if(IS_POST){
            //{hook a_friend_get_old_chat_3}
            $uid1 = intval(X("post.uid"));
            $uid2 = NOW_UID;
            $Chat = S("Chat");
            $Friend = M("Friend");
            //{hook a_friend_get_old_chat_4}
            $size = $size1 = $Friend->get_c($uid2,$uid1);

            //echo $size;
            if(!$size)
                $size = 10;
            //{hook a_friend_get_old_chat_5}
            $data = array();
            if($size == 10){
                //{hook a_friend_get_old_chat_6}
                $data = $Chat->select('*',
                     array(
                        "OR" => array(
                            "AND" => array(
                                "uid1" => $uid1,
                                "uid2" => $uid2
                            ),
                            "AND #" => array(
                                "uid1" => $uid2,
                                "uid2" => $uid1
                            )
                        ),
                        'LIMIT' => $size,
                        'ORDER' => 'atime DESC'
                    )
                );
                //{hook a_friend_get_old_chat_7}
            }else{
                //{hook a_friend_get_old_chat_8}
                $data = $Chat->select('*',
                     array(
                        "AND" => array(
                            "uid1" => $uid2,
                            "uid2" => $uid1
                            
                        ),
                        'LIMIT' => $size,
                        'ORDER' => 'atime DESC'
                    )
                );
                //{hook a_friend_get_old_chat_9}
            }
            //{hook a_friend_get_old_chat_10}

            

            $Friend->update_int($uid2,$uid1,'-',$size);
            if($size1 != 0){
                $Chat_count = M("Chat_count");
                $Chat_count->update_int(NOW_UID,'-',$size1);
            }
            //{hook a_friend_get_old_chat_11}
            foreach ($data as &$v) {
                //{hook a_friend_get_old_chat_12}
                $v['time'] = humandate($v['atime']);
                
            }
            //{hook a_friend_get_old_chat_13}
            $this->json($data);
        }
    }

    public function pm(){
        //{hook a_friend_pm_1}
        if(!IS_LOGIN)
            return $this->json(array('error'=>false,'info'=>'你需要重新登录!','error_id'=>1));
        //{hook a_friend_pm_2}
        if(IS_POST && IS_AJAX){
            //{hook a_friend_pm_3}
            $time = X("post.time");
            $Chat_count = S("Chat_count");
            $c = $Chat_count->find('*',array('uid'=>NOW_UID));
            //{hook a_friend_pm_4}
            //没有好友
            if(empty($c))
                return $this->json(array('error'=>false,'info'=>array(),'atime'=>$c['atime'],'ex'=>'no','error_id'=>2));
            //{hook a_friend_pm_5}
 
            if($time == $c['atime'] || $c['c'] == 0)
                return $this->json(array('error'=>false,'info'=>array(),'atime'=>$c['atime'],'error_id'=>3));
            $Friend = S("Friend");
            $data = $Friend->select(array('uid2','c'),array('AND'=>array('uid1'=>NOW_UID,'c[!]'=>0)));
            //{hook a_friend_pm6}
            $this->json(array('error'=>true,'info'=>$data,'atime'=>$c['atime']));
            //var_dump($c);
        }
    }
    public function user_info(){
        //{hook a_friend_user_info_1}
        if(!IS_LOGIN)
            return $this->json(array('error'=>false,'info'=>'你需要重新登录!'));
        //{hook a_friend_user_info_2}
        if(IS_POST && IS_AJAX){
            //{hook a_friend_user_info_3}
            $uid = intval(X("post.uid"));
            $User = M("User");
            if(!$User->is_id($uid))
                return $this->json(array('error'=>false,'info'=>'没有这个用户!'));
            //{hook a_friend_user_info_4}
            $user = $User->id_to_user($uid);
            $avatar = $this->avatar($user);
            //{hook a_friend_user_info_5}
            return $this->json(array('error'=>true,'info'=>array('user'=>$user,'avatar'=>$avatar)));;
        }
    }
    //{hook a_friend_fun}
}