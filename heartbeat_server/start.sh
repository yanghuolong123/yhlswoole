#!/bin/bash

sudo kill -s 9 `ps -aux | grep heartbeat_server.php | grep -v grep | awk '{print $2}'`

php heartbeat_server.php
