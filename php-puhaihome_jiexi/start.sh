#!/bin/bash

sudo kill -s 9 `ps -aux | grep puhai_home_server.php | awk '{print $2}'`

php puhai_home_server.php
