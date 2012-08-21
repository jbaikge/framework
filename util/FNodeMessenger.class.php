<?php
/*!
 * Node messenger to handle sending messages to the error node network.
 * 
 * @author Jake Tews <jtews@okco.com>
 * @date Wed Sep 21 16:12:30 EDT 2011
 * $Id$
 */
class FNodeMessenger {
	public static function refreshServerList () {
		$ch = curl_init();
		curl_setopt_array($ch, array(
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_URL => 'http://okcohost.appspot.com/',
		));
		$json = curl_exec($ch);
		$data = json_decode($json, true);
		$servers = array();
		$lowest_expiration = $upper_bound = pow(2, 32) - 1;
		foreach ($data['servers'] as $server_info) {
			$lowest_expiration = min($lowest_expiration, $server_info['expires']);
			$servers[$server_info['name']] = array(
				'host' => $server_info['host'],
				'ports' => array(
					(int)$server_info['low'],
					(int)$server_info['high']
				)
			);
		}
		$filename = $_ENV['config']['report.cache'];
		file_put_contents($filename, '<?php $servers = ' . var_export($servers, true) . ';');
		// Prevent issue where the node_server list was getting modified in 2106
		if ($lowest_expiration < $upper_bound) {
			touch($filename, $lowest_expiration);
		}
	}

	public static function send (array $data) {
		$data = array(
			'timestamp' => self::getTimestampFromHell(),
			'data' => $data
		);
		$json = json_encode($data);
		$json = preg_replace('/"(\d+)"/', '$1', $json);
		$uuid = substr(sha1($json), 0, 20);
		self::chunkSend($json, $uuid);
	}

	public static function sendFLog () {
		self::send(FLog::getData());
	}

	private static function chunkSend ($data, $uuid) {
		$compressed = gzcompress($data);
		$chunks = str_split($compressed, self::getChunkLength());
		foreach ($chunks as $i => $chunk) {
			self::sendToNetwork($uuid . chr($i) . $chunk);
		}
		self::sendToNetwork($uuid . chr($i+1));
	}

	private static function encode ($data) {
		$key = self::getKey();
		$data = sha1($data, true) . $data;
		$len = strlen($key);
		$data_len = strlen($data);
		for ($i = 0; $i < $data_len; $i++) {
			$data[$i] = $data[$i] ^ $key[$i % $len];
		}
		return $data;
	}

	private static function getNodes () {
		static $nodes;
		if ($nodes) {
			return $nodes;
		} else if (isset($_GET['UPDATE_SERVER_LIST'])) {
			return array();
		}

		$cache = $_ENV['config']['report.cache'];
		$cache_override = $_ENV['config']['report.my_cache'];
		$port = isset($_SERVER['HTTP_X_NODE_PORT']) ? (int)$_SERVER['HTTP_X_NODE_PORT'] : -1;
		if (0 < $port && $port < 65535) {
			$servers = array(
				'local' => array(
					'host' => 'localhost',
					'ports' => array($port, $port)
				)
			);
		}
		else if (file_exists($cache_override)) {
			require($cache_override);
		}
		else if (!file_exists($cache) || filemtime($cache) < time()) {
			// Force a refresh of the cache. Sets the modify time 10s in the
			// future to prevent clobbering and hitting the tracker more than
			// once.
			touch($_ENV['config']['report.cache'], time() + 10);
			self::refreshServerList();
		}
		else if (file_exists($cache)) {
			include($cache);
			shuffle($servers);
		}
		if ($servers) {
			$num_servers = 1;
			return $nodes = array_slice($servers, 0, $num_servers);
		} else {
			return array();
		}
	}

	private static function getKey () {
		return "hello";
	}

	private static function getChunkLength () {
		return 400;
	}

	private static function getTimestampFromHell () {
		return microtime(true) * 10000 . rand(10000, 99999);
	}
	private static function sendToNetwork ($data) {
		foreach (self::getNodes() as $node_info) {
			self::sendToNode($node_info, $data);
		}
	}

	private static function sendToNode ($node_info, $data) {
		$sock = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
		socket_connect($sock, $node_info['host'], rand($node_info['ports'][0], $node_info['ports'][1]));
		socket_write($sock, self::encode($data));
	}
}
