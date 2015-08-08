<?php
class goPocket
{
	const GETPOCKET_URL = 'https://getpocket.com';

	private $consumer_key;
	private $task_queue;

	public $access_token;
	public $pset;

	public function __construct($consumer_key) {
		$this->consumer_key = $consumer_key;
		$this->pest = new Pest(self::GETPOCKET_URL);
	}

	public function connect($redirect_uri) {
		$thing = $this->pest->post('/v3/oauth/request',
			array(
				'consumer_key' => $this->consumer_key,
				'redirect_uri' => $redirect_uri
			),
			array(
				'X-Accept' => 'application/json'
			)
		);
		$token = json_decode($thing, true);
		if (isset($token['code'])) {
			$location = '%s/auth/authorize?request_token=%s&redirect_uri=%s';
			$oauth_url = sprintf($location, self::GETPOCKET_URL, $token['code'], $redirect_uri);
			return array(
				'oauth_url'	=> $oauth_url,
				'token'		=> $token['code']
			);
		} else {
			throw new Exception('connect fail');
		}
	}

	public function getAccessToken($token) {
		$thing = $this->pest->post('/v3/oauth/authorize',
			array(
				'consumer_key'	=> $this->consumer_key,
				'code'			=> $token
			),
			array(
				'X-Accept' => 'application/json'
			)
		);
		$data = json_decode($thing, true);
		$this->access_token = $data['access_token'];
	}

	public function recieveList($params = array()) {
		$param = array(
					'count'			=> 20,
					'state'			=> 'all',
					'sort'			=> 'newest',
					'detailType'	=> 'simple'
		);
		if (is_array($params) and count($params) > 0) {
			$param = array_merge($param, $params);
		}
		$thing = $this->pest->post('/v3/get',
			array(
				'consumer_key'	=> $this->consumer_key,
				'access_token'	=> $this->access_token
			) + $param,
			array(
				'X-Accept' => 'application/json'
			)
		);
		$response = json_decode($thing, true);
		if ($response['status'] === 1) {
			return $response;
		} else {
			throw new Exception('get list fail');
		}
	}

	public function addListWithDetail($params = array()) {
		if (isset($params['url'])) {
			$param = $params;
		} else {
			throw new Exception('no url data');
		}
		$thing = $this->pest->post('/v3/add',
			array(
				'consumer_key'	=> $this->consumer_key,
				'access_token'	=> $this->access_token
			) + $param,
			array(
				'X-Accept' => 'application/json'
			)
		);
		$response = json_decode($thing, true);
		if ($response['status'] !== 1) {
			throw new Exception('add new list fail');
		}
	}

	public function modify_queqe($params = array()) {
		if (isset($params['item_id'], $params['action'])) {
			$this->task_queue[] = $params;
		} else {
			throw new Exception('no item_id and action data');
		}
	}

	public function send() {
		if (!isset($this->task_queue[0])) {
			throw new Exception('no task_queue');
		}
		$thing = $this->pest->post('/v3/send',
			array(
				'consumer_key'	=> $this->consumer_key,
				'access_token'	=> $this->access_token,
				'actions'		=> json_encode($this->task_queue)
			),
			array(
				'X-Accept' => 'application/json'
			)
		);
		$this->clear_task_queue();

		$response = json_decode($thing, true);
		if ($response['status'] !== 1) {
			throw new Exception('add modify queue fail');
		}
	}

	public function clear_task_queue() {
		$this->task_queue = array();
		return isset($this->task_queue[0]);
	}
}