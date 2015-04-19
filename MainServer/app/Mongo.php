<?php

namespace App;

class Mongo {

    public $db;

    public function __construct($host) {
        $mongo = new \MongoClient("mongodb://" . $host);
        $this->db = $mongo->selectDB('im_service');
    }

    public function login($client_id, $info) {
        $this->db->selectCollection('im_online')->update(['client_id' => $client_id], [
            'client_id' => $client_id,
            'info' => $info,
            'createtime' => time(),
            'createdatetime' => date('Y-m-d H:i:s'),
                ], ['upsert' => true]);
    }

    public function getOnlineUsers() {
        $users = [];
        $onlineusers = iterator_to_array($this->db->selectCollection('im_online')->find(array(), array('client_id' => TRUE)), false);
        foreach ($onlineusers as $online) {
            $users[] = $online['client_id'];
        }
        
        return $users;
    }

    public function getUsers($users = []) {
        $ret = [];
        $users = iterator_to_array($this->db->selectCollection('im_online')->find(array('client_id' => array('$in' => $users))), false);
        foreach ($users as $user) {
            $ret[] = $user['info'];
        }
        
        return $ret;
    }
    
    public function getUser($userid) {
        $user = $this->db->selectCollection('im_online')->findOne(array('client_id'=>$userid));
        
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