<?php

namespace classes;

class Mongo {

    public $host;
    public $port;
    public $db;

    public function __construct($host, $port) {
        $this->host = $host;
        $this->port = $port;
        $mongo = new \MongoClient("mongodb://" . $this->host . ':' . $this->port);
        $this->db = $mongo->selectDB('apk_service');
    }

    public function reportPosition($data) {
        $data['createtime'] = time();
        $data['createdatetime'] = date('Y-m-d H:i:s', $data['createtime']);
        $this->db->selectCollection('puhai_home_location')->insert($data);
    }

    public function getInfoByIds($identity_ids) {
        $data = [];
        foreach ($identity_ids as $key => $ids) {
            $data[$key]['identity_id'] = $ids;
            $data[$key]['status'] = $this->getStatusHome($ids);
            $data[$key]['nickname'] = $this->getNicknameHome($ids);
            $data[$key]['alarmCount'] = $this->getAlarmCountHome($ids);
            $data[$key]['posinfo'] = $this->getPosinfo($ids);
        }
        return $data;
    }

    public function getStatusHome($identity_id) {
        $condition = array(
            'createtime' => array('$gte' => time() - 180),
            'identity_id' => $identity_id,
            'cmdinfo' => array('$ne' => array()),
        );
        $alarm = $this->db->selectCollection('puhai_home_report')->findOne($condition);
        if ($alarm) {
            return 'alarm';
        }

        $condition = array(
            'createtime' => array('$gte' => time() - 2 * 3600),
            'identity_id' => $identity_id,
        );
        $exist = $this->db->selectCollection('puhai_home_report')->findOne($condition);

        return $exist ? 'online' : 'offline';
    }

    public function getNicknameHome($identity_id) {
        $setup = $this->db->selectCollection('puhai_home_setup')->findOne(array('identity_id' => $identity_id));
        return isset($setup['nickname']) ? $setup['nickname'] : '';
    }

    public function getAlarmCountHome($identity_id) {
        $condition = array(
            'identity_id' => $identity_id,
            'cmdinfo' => array('$ne' => array()),
        );

        return $this->db->selectCollection('puhai_home_report')->find($condition)->sort(array('createtime' => -1))->count();
    }

    public function getPosinfo($identity_id) {
        $setup = $this->db->selectCollection('puhai_home_setup')->findOne(array('identity_id' => $identity_id));
        $phones = isset($setup['phone']) && !empty($setup['phone']) ? $setup['phone'] : array();
        $location = array();
        foreach ($phones as $phone) {
            if (empty($phone)) {
                continue;
            }
            $latest = iterator_to_array($this->db->selectCollection('puhai_home_location')->find(array('phone' => $phone), array('posinfo' => true))->sort(array('createtime' => -1))->limit(1), false);
            $latest = array_shift($latest);
            if (empty($latest['posinfo'])) {
                continue;
            }
            $latest['posinfo']['phone'] = $phone;
            $location[] = $latest['posinfo'];
        }

        return $location;
    }

    public function getAccountByIds($identity_id) {
        $ret = $this->db->selectCollection('puhai_home_setup')->findOne(['identity_id' => $identity_id]);
        return $ret['phone'];
    }

}
