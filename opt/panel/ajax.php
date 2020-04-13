<?php
require_once 'inc/lib.php';
if(isset($_GET['username']) && isset($_GET['password']) && !isset($_GET['dns'])) {
    $user = user_info($_GET['username']);
    if($user['pass'] != $_GET['password']) { exit("wrong password"); } else {
	unlink('server'.'/ngrok.log');
    	server_start($user['user']);
    	die("success");
    }
}
if(isset($_GET['username']) && isset($_GET['password']) && isset($_GET['dns'])) {
    $user = user_info($_GET['username']);
    if($user['pass'] != $_GET['password']) { exit("wrong password"); } else {
    	$dns = ngrok_stat($user['user']);
	$dns_fix = str_replace(' ','',$dns);
	list($dns_host,$dns_port) = explode(':',$dns_fix);
    	echo $dns_port;
    }
}

session_start();
if(isset($_GET['user']) && isset($_GET['pass']) && isset($_GET['req'])) {
    $user = user_info($_GET['user']);
    if($user['pass'] != $_GET['pass']) { exit("wrong password"); } else {
	switch ($_GET['req']) {
		case 'dir':
			// Initial vars
			$dirs = array();
			$files = array();

			// Get directory contents
			$h = opendir('server' . $_GET['dir']);
			while (false !== ($f = readdir($h)))
				if ($f != '.' && $f != '..')
					if (is_dir('server' . $_GET['dir'] . '/' . $f))
						$dirs[] = $f;
					elseif (is_file('server' . $_GET['dir'] . '/' . $f))
						$files[] = $f;
			closedir($h);
			unset($f);

			// Sort data
			sort($dirs);
			sort($files);

			// Get file sizes
			$sizes = array();
			foreach ($files as $f)
				$sizes[] = filesize('server' . $_GET['dir'] . '/' . $f);

			// Output data
			echo json_encode(array(
				'dirs' => $dirs,
				'files' => $files,
				'sizes' => $sizes
			));

			break;
		case 'file_get':
			if (is_file('server' . $_GET['file']))
				echo file_get_contents('server' . $_GET['file']);
			break;
		case 'file_put':
			if (is_file('server' . $_GET['file']))
				file_put_contents('server' . $_GET['file'], $_GET['data']);
			break;
		case 'delete':
			foreach ($_GET['files'] as $f)
				if (is_file('server' . $f))
					unlink('server' . $f);
			break;
		case 'rename':
			file_rename($_GET['path'], $_GET['newname'], 'server');
			break;
		case 'cron_exists':
			header('Content-type: application/json');
			echo json_encode(check_cron_exists($_GET['user']));
			break;
		case 'get_cron':
			header('Content-type: application/json');
			echo json_encode(get_cron($_GET['user']));
			break;
		case 'server_start':
			echo server_start($user['user']);
			user_modify($user['user'],$user['pass'],$user['role'],'server',$user['ram'],$user['port'],$user['jar'],$user['key'],time());
			break;
		case 'server_cmd':
			server_cmd($user['user'], $_GET['cmd']);
			break;
		case 'server_stop':
			server_stop($user['user']);
			user_modify($user['user'],$user['pass'],$user['role'],'server',$user['ram'],$user['port'],$user['jar'],$user['key'],'null');
			break;
		case 'server_kill':
			server_kill($user['user']);
			break;
		case 'server_running':
			echo json_encode(server_running($user['user']));
			break;
		case 'server_log':
			/*if($files = glob('server' . "screenlog.?*")) {
				// Prefer GNU screen log
				echo mclogparse2(file_backread('server'));
			} else*/
			if(is_file('server' . "/logs/latest.log")) {
				// 1.7 logs
				echo mclogparse2(file_backread('server' . '/logs/latest.log', 64));
			} elseif(is_file('server' . "/server.log")) {
				// 1.6 and earlier
				echo mclogparse2(file_backread('server' . '/server.log', 64));
			} elseif(is_file('server' . "/proxy.log.0")) {
				// BungeeCord
				echo mclogparse2(file_backread('server' . '/proxy.log.0', 64));
			} else {
				echo "No log file found.";
			}
			break;
		case 'server_log_bytes':
			header('Content-type: application/json');

			// Find log file
			if(is_file('server' . '/logs/latest.log')) {
				$file = 'server' . '/logs/latest.log';
			} elseif(is_file('server' . '/server.log')) {
				$file = 'server' . '/server.log';
			} elseif(is_file('server' . '/proxy.log.0')) {
				$file = 'server' . '/proxy.log.0';
			} else {
				exit(json_encode(array('error' => 1, 'msg' => 'No log file found.')));
			}

			// Get requested byte range
			$start = isset($_REQUEST['start']) ? intval($_REQUEST['start']) : 0;
			$end = isset($_REQUEST['end']) ? intval($_REQUEST['end']) : null;

			$data = @file_get_contents($file, false, null, $start, $end);

			$return = array(
				'start' => $start,
				'end' => $start + strlen($data),
				'data' => $data,
			);

			if($data === false) {
				$data = file_get_contents($file, false, null, 0, 30*1024);
				$return = array(
					'error' => 2,
					'msg' => 'Failed to requested bytes from the log file. Returned first 30 KB.',
					'start' => 0,
					'end' => strlen($data),
					'data' => $data,
				);
			}

			echo json_encode($return);

		case 'players':
			require_once 'inc/MinecraftQuery.class.php';
			$mq = new MinecraftQuery();
			try {
				$mq->Connect(KT_LOCAL_IP, $user['port'], 2); // 2 second timeout
			} catch (MinecraftQueryException $ex) {
				echo json_encode(array('error' => 1, 'msg' => $ex->getMessage()));
				die();
			}

			$data = array(
				'info' => $mq->GetInfo(),
				'players' => $mq->GetPlayers()
			);

			echo json_encode($data);
			break;
		case 'set_jar':
			$result = user_modify($user['user'], $user['pass'], $user['role'], 'server', $user['ram'], $user['port'], $_GET['jar'], $user['key']);
			echo json_encode($result);
			break;
	}
    }
}
if (!$user = user_info($_SESSION['user']))
	exit();

if(isset($_GET['ngrok'])) {
    $user = user_info($_GET['ngrok']);
    $arr = array('key' => $user['key']);
    echo '['.json_encode($arr).']';
}
if(isset($_GET['timer_start'])) {
user_modify($user['user'],$user['pass'],$user['role'],'server',$user['ram'],$user['port'],$user['jar'],$user['key'],time());
}

if(isset($_POST['key'])) { 
	set_key($user['user'],'server'.'/ngrok.yml',$user['key'],$_POST['key']);
	user_modify($user['user'],$user['pass'],$user['role'],'server',$user['ram'],$user['port'],$user['jar'],$_POST['key'],$user['active']);
	set_key($user['user'],'server'.'/ngrok.yml',$user['key'],$_POST['key']);
	user_modify($user['user'],$user['pass'],$user['role'],'server',$user['ram'],$user['port'],$user['jar'],$_POST['key'],$user['active']);
} 
switch ($_POST['req']) {
	case 'dir':
		// Initial vars
		$dirs = array();
		$files = array();

		// Get directory contents
		$h = opendir('server' . $_POST['dir']);
		while (false !== ($f = readdir($h)))
			if ($f != '.' && $f != '..')
				if (is_dir('server' . $_POST['dir'] . '/' . $f))
					$dirs[] = $f;
				elseif (is_file('server' . $_POST['dir'] . '/' . $f))
					$files[] = $f;
		closedir($h);
		unset($f);

		// Sort data
		sort($dirs);
		sort($files);

		// Get file sizes
		$sizes = array();
		foreach ($files as $f)
			$sizes[] = filesize('server' . $_POST['dir'] . '/' . $f);

		// Output data
		echo json_encode(array(
			'dirs' => $dirs,
			'files' => $files,
			'sizes' => $sizes
		));

		break;
	case 'file_get':
		if (is_file('server' . $_POST['file']))
			echo file_get_contents('server' . $_POST['file']);
		break;
	case 'file_put':
		if (is_file('server' . $_POST['file']))
			file_put_contents('server' . $_POST['file'], $_POST['data']);
		break;
	case 'delete':
		foreach ($_POST['files'] as $f)
			if (is_file('server' . $f))
				unlink('server' . $f);
		break;
	case 'rename':
		file_rename($_POST['path'], $_POST['newname'], 'server');
		break;
	case 'cron_exists':
		header('Content-type: application/json');
		echo json_encode(check_cron_exists($_POST['user']));
		break;
	case 'get_cron':
		header('Content-type: application/json');
		echo json_encode(get_cron($_POST['user']));
		break;
	case 'server_start':
		echo server_start($user['user']);
		user_modify($user['user'],$user['pass'],$user['role'],'server',$user['ram'],$user['port'],$user['jar'],$user['key'],time());
		break;
	case 'server_cmd':
		server_cmd($user['user'], $_POST['cmd']);
		break;
	case 'server_stop':
		server_stop($user['user']);
		user_modify($user['user'],$user['pass'],$user['role'],'server',$user['ram'],$user['port'],$user['jar'],$user['key'],'null');
		break;
	case 'server_kill':
		server_kill($user['user']);
		break;
	case 'server_running':
		echo json_encode(server_running($user['user']));
		break;
	case 'server_log':
		/*if($files = glob('server' . "screenlog.?*")) {
			// Prefer GNU screen log
			echo mclogparse2(file_backread('server'));
		} else*/
		if(is_file('server' . "/logs/latest.log")) {
			// 1.7 logs
			echo mclogparse2(file_backread('server' . '/logs/latest.log', 64));
		} elseif(is_file('server' . "/server.log")) {
			// 1.6 and earlier
			echo mclogparse2(file_backread('server' . '/server.log', 64));
		} elseif(is_file('server' . "/proxy.log.0")) {
                        // BungeeCord
                        echo mclogparse2(file_backread('server' . '/proxy.log.0', 64));
		} else {
			echo "No log file found.";
		}
		break;
	case 'server_log_bytes':
		header('Content-type: application/json');

		// Find log file
		if(is_file('server' . '/logs/latest.log')) {
			$file = 'server' . '/logs/latest.log';
		} elseif(is_file('server' . '/server.log')) {
			$file = 'server' . '/server.log';
		} elseif(is_file('server' . '/proxy.log.0')) {
                        $file = 'server' . '/proxy.log.0';
		} else {
			exit(json_encode(array('error' => 1, 'msg' => 'No log file found.')));
		}

		// Get requested byte range
		$start = isset($_REQUEST['start']) ? intval($_REQUEST['start']) : 0;
		$end = isset($_REQUEST['end']) ? intval($_REQUEST['end']) : null;

		$data = @file_get_contents($file, false, null, $start, $end);

		$return = array(
			'start' => $start,
			'end' => $start + strlen($data),
			'data' => $data,
		);

		if($data === false) {
			$data = file_get_contents($file, false, null, 0, 30*1024);
			$return = array(
				'error' => 2,
				'msg' => 'Failed to requested bytes from the log file. Returned first 30 KB.',
				'start' => 0,
				'end' => strlen($data),
				'data' => $data,
			);
		}

		echo json_encode($return);

	case 'players':
		require_once 'inc/MinecraftQuery.class.php';
		$mq = new MinecraftQuery();
		try {
			$mq->Connect(KT_LOCAL_IP, $user['port'], 2); // 2 second timeout
		} catch (MinecraftQueryException $ex) {
			echo json_encode(array('error' => 1, 'msg' => $ex->getMessage()));
			die();
		}

		$data = array(
			'info' => $mq->GetInfo(),
			'players' => $mq->GetPlayers()
		);

		echo json_encode($data);
		break;
	case 'set_jar':
		$result = user_modify($user['user'], $user['pass'], $user['role'], 'server', $user['ram'], $user['port'], $_POST['jar'], $user['key']);
		echo json_encode($result);
		break;
}

?>
