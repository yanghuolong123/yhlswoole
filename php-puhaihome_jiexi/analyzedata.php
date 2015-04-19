<?php

// 命令解析
// 状态位
$analyzeData['cmd']['status_bit']['bit3'][1]['name'] = '发生报警';     // 报警位
$analyzeData['cmd']['status_bit']['bit2'][1]['name'] = '自检';
$analyzeData['cmd']['status_bit']['bit1'][1]['name'] = '发生故障';     // 故障位
$analyzeData['cmd']['status_bit']['bit0'][1]['name'] = '上电';

// 子状态位
$analyzeData['cmd']['status_bit']['bit3'][1]['sub_status_bit']['bit0'][1] = 'CH4报警';
$analyzeData['cmd']['status_bit']['bit3'][1]['sub_status_bit']['bit2'][1] = 'CO报警';
$analyzeData['cmd']['status_bit']['bit3'][1]['sub_status_bit']['bit4'][1] = '温度报警';

return $analyzeData;
