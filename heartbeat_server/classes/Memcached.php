<?php

namespace classes;

class Memcached {

    protected $cache;

    public function __construct(array $config) {
        $this->cache = new \Memcached;
        foreach ($config['servers'] as $conf) {
            $this->cache->addServer($conf['host'], $conf['port'], $conf['weight']);
        }
    }

    public function set($key, $value, $expire = 0) {
        return $this->cache->set($key, $value, $expire);
    }

    public function get($key) {
        return $this->cache->get($key);
    }

}
