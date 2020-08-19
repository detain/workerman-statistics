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

use Workerman\Worker;
use Workerman\WebServer;
use Workerman\Protocols\Http\Request;
use Workerman\Protocols\Http\Response;
use Workerman\Connection\TcpConnection;

// WebServer
$web = new Worker('http://0.0.0.0:55757');
$web->name = 'StatisticWeb';

define('WEBROOT', realpath(__DIR__ . '/Web'));

$web->onMessage = function (TcpConnection $connection, Request $request) {
	$addr = explode(':', $connection->getRemoteAddress());
	$_SERVER['REMOTE_ADDR'] = $addr[0];
	$_GET = $request->get();
	$_POST = $request->post();
	$path = $request->path();
	if ($path === '/') {
		$connection->send(exec_php_file(WEBROOT.'/index.php'));
		return;
	}
	$file = realpath(WEBROOT. $path);
	if (false === $file) {
		$connection->send(new Response(404, array(), '<h3>404 Not Found</h3>'));
		return;
	}
	// Security check! Very important!!!
	if (strpos($file, WEBROOT) !== 0) {
		$connection->send(new Response(400));
		return;
	}
	if (\pathinfo($file, PATHINFO_EXTENSION) === 'php') {
		$connection->send(exec_php_file($file));
		return;
	}

	if (!empty($if_modified_since = $request->header('if-modified-since'))) {
		// Check 304.
		$info = \stat($file);
		$modified_time = $info ? \date('D, d M Y H:i:s', $info['mtime']) . ' ' . \date_default_timezone_get() : '';
		if ($modified_time === $if_modified_since) {
			$connection->send(new Response(304));
			return;
		}
	}
	$connection->send((new Response())->withFile($file));
};

function exec_php_file($file) {
	\ob_start();
	// Try to include php file.
	try {
		include $file;
	} catch (\Exception $e) {
		echo $e;
	}
	return \ob_get_clean();
}

// Run the runAll method if it is not started in the root directory
if(!defined('GLOBAL_START'))
{
	Worker::runAll();
}
