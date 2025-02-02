<?php
namespace Model;
use HY\Model;
!defined('HY_PATH') && exit('HY_PATH not defined.');
class Friend extends Model {

	//获取两者关系
	public function get_state($uid1,$uid2){
		//{hook m_friend_get_state_1}
		$state = $this->find('*',array('AND'=>array('uid1'=>$uid1,'uid2'=>$uid2)));
		//{hook m_friend_get_state_2}
		return empty($state) ? false : $state['state'];
	}
	public function set_state($uid1,$uid2,$s){
		//{hook m_friend_set_state_1}
		return $this->update(array('state'=>$s),array('AND'=>array('uid1'=>$uid1,'uid2'=>$uid2)));
	}
	//添加关注关系
	public function add_friend($uid1,$uid2){
		//{hook m_friend_add_friend_1}
		if(!$this->has(array('AND'=>array('uid1'=>$uid1,'uid2'=>$uid2)))){ //$uid1 未关注 $uid2
			//{hook m_friend_add_friend_2}
			if($this->has(array('AND'=>array('uid1'=>$uid2,'uid2'=>$uid1)))){ // $uid2 关注了 $uid1
				//{hook m_friend_add_friend_3}
				if($this->get_state($uid2,$uid1)!= 0){
					$this->set_state($uid2,$uid1,2);
					return $this->insert(array('uid1'=>$uid1,'uid2'=>$uid2,'state'=>2));
				}
				//{hook m_friend_add_friend_4}
				return $this->insert(array('uid1'=>$uid1,'uid2'=>$uid2,'state'=>1));
				
				
			}else{
				//{hook m_friend_add_friend_5}
				return $this->insert(array('uid1'=>$uid1,'uid2'=>$uid2,'state'=>1));
			}
		}else{
			//{hook m_friend_add_friend_6}
			$this->set_state($uid1,$uid2,1);
		}
		//{hook m_friend_add_friend_7}
		//已存在朋友关系
		return false;
	}
	//删除朋友关系
	public function rm_friend($uid1,$uid2){
		//{hook m_friend_rm_friend_1}
		if($this->has(array('AND'=>array('uid1'=>$uid1,'uid2'=>$uid2)))){ //$uid1 关注 $uid2
			//{hook m_friend_rm_friend_2}
			if($this->has(array('AND'=>array('uid1'=>$uid1,'uid2'=>$uid2)))){ // $uid2 关注了 $uid1
				//{hook m_friend_rm_friend_3}
				if($this->get_state($uid2,$uid1)!= 0)
					$this->set_state($uid2,$uid1,1);
			}
			//{hook m_friend_rm_friend_4}
			$this->delete(array('AND'=>array('uid1'=>$uid1,'uid2'=>$uid2)));
		}
		//{hook m_friend_rm_friend_5}
	}

	public function update_int($uid1,$uid2,$type="+",$size=1){
		//{hook m_friend_update_int_1}
		if($this->has(array('AND'=>array('uid1'=>$uid1,'uid2'=>$uid2)))){
			//{hook m_friend_update_int_2}
			if($type==="+")
				return $this->update(array("c[{$type}]"=>$size,'atime'=>NOW_TIME),array('AND'=>array('uid1'=>$uid1,'uid2'=>$uid2)));
			return $this->update(array("c[{$type}]"=>$size),array('AND'=>array('uid1'=>$uid1,'uid2'=>$uid2)));
		}
		//{hook m_friend_update_int_3}
		$this->insert(array('uid1'=>$uid1,'uid2'=>$uid2,'c'=>1,'atime'=>NOW_TIME,'state'=>0));
		//{hook m_friend_update_int_4}
		//陌生人=1
	}
	public function get_c($uid1,$uid2){
		//{hook m_friend_get_c_1}
		if(!$this->has(array('AND'=>array('uid1'=>$uid1,'uid2'=>$uid2))))
			return 0;
		//{hook m_friend_get_c_2}
		return $this->find('c',array('AND'=>array('uid1'=>$uid1,'uid2'=>$uid2)));
	}
	//{hook m_friend_fun}
}