<?php

namespace App;

class Mongo {

    public $db;

    public function __construct($host) {
        $mongo = new \MongoClient("mongodb://" . $host);
        $this->db = $mongo->selectDB('im_service');
    }

    public function login($uid, $info) {
        $this->db->selectCollection('im_online')->update(['uid' => $uid], [
            'uid' => $uid,
            'info' => $info,
            'createtime' => time(),
            'createdatetime' => date('Y-m-d H:i:s'),
                ], ['upsert' => true]);
    }

    public function getOnlineUsers() {
        $users = [];
        $onlineusers = iterator_to_array($this->db->selectCollection('im_online')->find(array(), array('uid' => TRUE)), false);
        foreach ($onlineusers as $online) {
            $users[] = $online['uid'];
        }
        
        return $users;
    }

    public function getUsers($users = []) {
        $ret = [];
        $users = iterator_to_array($this->db->selectCollection('im_online')->find(array('uid' => array('$in' => $users))), false);
        foreach ($users as $user) {
            $ret[] = $user['info'];
        }
        
        return $ret;
    }
    
    public function getUser($userid) {
        $user = $this->db->selectCollection('im_online')->findOne(array('uid'=>$userid));
        
        return $user['info'];
    }

    public function saveMsg($data) {
        $this->db->selectCollection('im_history')->insert($data);
    }

    public function getHistory() {
        $historys = iterator_to_array($this->db->selectCollection('im_history')->find(array('msg.channal' => 0))->sort(array('time' => -1))->limit(100), false);       
        foreach ($historys as &$history) {
            unset($history['_id']);
        }

        return array_reverse($historys);
    }

}
