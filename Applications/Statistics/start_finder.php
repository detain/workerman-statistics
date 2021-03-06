<?php 
/**
 * This file is part of workerman.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the MIT-LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @author walkor<walkor@workerman.net>
 * @copyright walkor<walkor@workerman.net>
 * @link http://www.workerman.net/
 * @license http://www.opensource.org/licenses/mit-license.php MIT License
 */
require_once __DIR__ . '/loader.php';

use Bootstrap\StatisticProvider;
use Bootstrap\StatisticWorker;
use \Workerman\Worker;

// recv udp broadcast
$udp_finder = new Worker("Text://0.0.0.0:55858");
$udp_finder->name = 'StatisticFinder';
$udp_finder->transport = 'udp';
$udp_finder->onMessage = function ($connection, $data)
{
	$data = json_decode($data, true);
	if(empty($data))
	{
		return false;
	}

	// 无法解析的包
	if(empty($data['cmd']) || $data['cmd'] != 'REPORT_IP' )
	{
		return false;
	}

	// response
	return $connection->send(json_encode(array('result'=>'ok')));
};

// Run the runAll method if it is not started in the root directory
if(!defined('GLOBAL_START'))
{
	Worker::runAll();
}
