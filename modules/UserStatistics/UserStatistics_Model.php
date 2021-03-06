<?
/**
 * 
 * @using constants
 * 		User_Model::TABLE
 * 		
 * @using methods
 * 		YDate::timestamp2date()
 * 		href()
 * 		User::getPermName()
 */
class UserStatistics_Model {
	
	const TABLE = 'user_stat';
	
	private static $_enabled = FALSE;
	
	private static $_instance;

	private $_sessKey;
	
	/** ВКЛЮЧИТЬ СБОР СТАТИСТИКИ */
	public static function enable(){
		self::$_enabled = TRUE;
	}
	
	/** ОТКЛЮЧИТЬ СБОР СТАТИСТИКИ */
	public static function disable(){
		self::$_enabled = FALSE;
	}
	
	/** ПОЛУЧИТЬ ЭКЗЕМПЛЯР КЛАССА */
	public static function get(){
	
		if(is_null(self::$_instance))
			self::$_instance = new UserStatistics_Model();
			
		return self::$_instance;
	}
	
	/** КОНСТРУКТОР */
	private function __construct(){
		
		if(!self::$_enabled)
			return;

		$this->_sessKey = CFG_APP_KEY.'-user-statistics';

		if(!$this->_isSessionInited())
			$this->_initSession();
	}
	
	// ПОЛУЧИТЬ СТРОКУ ДАННЫХ ИЗ ТАБЛИЦЫ
	public function getRowPrepared($id){
		
		$id = (int)$id;
		$db = db::get();
		$data = $db->fetchRow('SELECT * FROM '.self::TABLE.' WHERE id='.$id);
		
		if(!$data)
			throw new Exception('Данные не найдены');
		
		$data['pages'] = $db->fetchAll('SELECT * FROM user_stat_pages WHERE session_id='.$id);
		return self::beforeDisplay($data, TRUE);
	}
	
	// ПРОВЕРКА, ИНИЦИАЛИЗИРОВАНА ЛИ СЕССИЯ
	private function _isSessionInited(){
		
		return !empty($_SESSION[$this->_sessKey]);
	}
	
	// ИНИЦИАЛИЗАЦИЯ СЕССИИ
	private function _initSession(){
		$_SESSION[$this->_sessKey] = array(
			'session-id' => 0,
			'last-url' => '',
			'is-client-stat-saved' => FALSE,
		);
	}
	
	public function reset(){
		$_SESSION[$this->_sessKey] = null;
	}
	
	// СОХРАНЕНИЕ ПЕРВИЧНОЙ СТАТИСТИКИ
	public function savePrimaryStatistics(){
		
		// выход если сохранение статистики отключено
		if(!self::$_enabled)
			return;
		
		// определение запришиваемого URL
		$requestUrl = getVar($_SERVER['SERVER_NAME']).getVar($_SERVER['REQUEST_URI']);
		
		$db = db::get();
		
		// создание сессии
		if(!$_SESSION[$this->_sessKey]['session-id']){
		
			$sid = $db->insert(self::TABLE, array(
				'user_ip' 		 => getVar($_SERVER['REMOTE_ADDR']),
				'user_agent_raw' => getVar($_SERVER['HTTP_USER_AGENT']),
				'referer' 		 => getVar($_SERVER['HTTP_REFERER']),
				'date'			 => time(),
			));
			$_SESSION[$this->_sessKey]['session-id'] = $sid;
			
		}
		
		$action = !empty($_POST['action'])
			? strtolower(is_array($_POST['action']) ? YArray::getFirstKey($_POST['action']) : $_POST['action'])
			: null;
		
		// инкремент запроса страницы (если запрошена та же страница)
		if($requestUrl === $_SESSION[$this->_sessKey]['last-url'] && empty($_POST)
		   && !empty($_SESSION[$this->_sessKey]['last-page-id']))
		{
			$db->update('user_stat_pages', array(
				'num_requests' => $db->raw('num_requests + 1'),
				'last_date' => time(),
			), 'id='.$_SESSION[$this->_sessKey]['last-page-id']);
		}
		// сохранение запрошенной страницы
		else {

			$pid = $db->insert('user_stat_pages', array(
				'session_id'  => $_SESSION[$this->_sessKey]['session-id'],
				'url'         => $requestUrl,
				'is_ajax'     => AJAX_MODE ? TRUE : FALSE,
				'is_post'     => !empty($_POST),
				'post_data'   => null,
				'post_action' => $action,
				'first_date'  => time(),
				'last_date' => time(),
				'num_requests'=> 1,
			));
		
			// сохраняем запрашиваемый URL
			$_SESSION[$this->_sessKey]['last-url'] = $requestUrl;
			$_SESSION[$this->_sessKey]['last-page-id'] = $pid;
		}
	}
	
	// ПРОВЕРКА НЕОБХОДИМОСТИ СОХРАНЕНИЯ КЛИЕНТСКОЙ СТАТИСТИКИ
	public function checkClientSideStatistics(){
		
		// выход если сохранение статистики отключено
		if(!self::$_enabled)
			return FALSE;
	
		return empty($_SESSION[$this->_sessKey]['is-client-stat-saved']);
	}
	
	// ПОЛУЧИТЬ HTML ДЛЯ СОХРАНЕНИЯ КЛИЕНТСКОЙ СТАТИСТИКИ
	public function getClientSideStatisticsLoader(){
		
		// выход если сохранение статистики отключено
		if(!self::$_enabled)
			return '';
			
		return '
			<script type="text/javascript">
				$(function(){
					var data = {
						browser_name: $.browser.name,
						browser_version: $.browser.version,
						screen_width: screen.width,
						screen_height: screen.height
					};
					$.post(href("user-statistics/save-client-side"), data, function(r){
						if(r != "ok")
							alert("Ошибка сохранения статистики: \n" + r);
					});
				});
			</script>
		';
	}
	
	// СОХРАНЕНИЕ КЛИЕНТСКОЙ СТАТИСТИКИ
	public function saveClientSideStatistics($bName, $bVer, $sW, $sH){
		
		// выход если сохранение статистики отключено
		if(!self::$_enabled)
			return;
		
		$this->_dbSave(array(
			'has_js' => 1,
			'browser_name' => $bName,
			'browser_version' => $bVer,
			'screen_width' => $sW,
			'screen_height' => $sH,
		));
		$_SESSION[$this->_sessKey]['is-client-stat-saved'] = TRUE;
	}
	
	// СОХРАНЕНИЕ АВТОРИЗАЦИОННОЙ СТАТИСТИКИ
	public function saveAuthStatistics($uid){
		
		// выход если сохранение статистики отключено
		if(!self::$_enabled)
			return;
	
		$this->_dbSave(array(
			'uid' => $uid,
		));
	}
	
	// СОХРАНЕНИЕ ДАННЫХ В БД
	private function _dbSave($fieldvalues){
		
		if(!$_SESSION[$this->_sessKey]['session-id']){
			
			$fieldvalues['user_ip'] 		= getVar($_SERVER['REMOTE_ADDR']);
			$fieldvalues['user_agent_raw'] 	= getVar($_SERVER['HTTP_USER_AGENT']);
			$fieldvalues['referer'] 		= getVar($_SERVER['HTTP_REFERER']);
			$fieldvalues['date'] 			= time();
			$_SESSION[$this->_sessKey]['session-id'] = db::get()->insert(self::TABLE, $fieldvalues);
		}else{
			db::get()->update(self::TABLE, $fieldvalues, 'id='.$_SESSION[$this->_sessKey]['session-id']);
		}
	}
	
	// МЕТОД ПРИГОТОВЛЕНИЯ ДАННЫХ ПЕРЕД ОТОБРАЖЕНИЕМ
	public static function beforeDisplay($data, $detail = FALSE){
			
		$data['date'] = YDate::loadTimestamp($data['date'])->getStrDateTime();
		
		if (!empty($data['pages'])) {
			
			$num = count($data['pages']);
			$data['num_pages'] = $num;
			
			if ($detail)
				foreach($data['pages'] as &$p) {
					$p['first_date'] = YDate::loadTimestamp($p['first_date'])->getStrDateTime();
					$p['last_date'] = YDate::loadTimestamp($p['last_date'])->getStrDateTime();
				}
					
			$data['pages_info'] = array(
				'first_page' => $data['pages'][0]['url'],
				'last_page' => $data['pages'][ $num - 1 ]['url'],
				'first_page_time' => $detail
					? $data['pages'][0]['last_date']
					: YDate::loadTimestamp($data['pages'][0]['last_date'])->getStrDateTime(),
				'last_page_time' => $detail
					? $data['pages'][ $num - 1 ]['last_date']
					: YDate::loadTimestamp($data['pages'][ $num - 1 ]['last_date'])->getStrDateTime(),
			);
		} else {
			$data['pages'] = null;
			$data['pages_info'] = null;
		}
		
		$data['screen_resolution'] = $data['has_js']
			? $data['screen_width'].'x'.$data['screen_height']
			: '-';
		$data['browser'] = $data['has_js']
			? $data['browser_name'].' '.$data['browser_version']
			: '-';
		$data['has_js_text'] = $data['has_js']
			? '<span class="green">✔</span>'
			: '<span class="red">✘</span>';
		
		// echo '<pre>'; print_r($data); die;
		return $data;
	}
	
	// УДАЛИТЬ СТАРУЮ СТАТИСТИКУ
	public function deleteOldStatistics($expireTime){
		
		$minDate = time() - $expireTime;
		db::get()->delete(self::TABLE, 'date < '.$minDate);
	}

	public function getSessData($key = null)
	{
		return $key
			? (isset($_SESSION[$this->_sessKey][$key])
				? $_SESSION[$this->_sessKey][$key]
				: null)
			: $_SESSION[$this->_sessKey];
	}
}


class UserStatistics_Collection extends ARCollection {
	
	protected $_sortArray = array();
	
	// поля, по которым возможно сортировка коллекции
	// каждый ключ должен быть корректным выражением для SQL ORDER BY
	protected $_sortableFieldsTitles = array(
		'id' => array('s.id _DIR_', 'id'),
		'uid' => array('uid _DIR_', 'uid'),
		'login' => 'Логин',
		'last_date' => array('(SELECT MAX(last_date) FROM user_stat_pages WHERE session_id=s.id) _DIR_', 'Последняя дата'),
		'num_pages' => array('(SELECT COUNT(1) FROM user_stat_pages WHERE session_id=s.id) _DIR_', 'Кол-во страниц'),
		'user_ip' => 'IP',
		'referer' => 'referer',
		'has_js' => 'JS',
		'browser' => array('browser_name _DIR_, browser_version _DIR_', 'Браузер'),
		'screen_resolution' => array('screen_width * screen_height _DIR_', 'Разрешение'),
	);
	
	
	// ТОЧКА ВХОДА В КЛАСС
	public static function load($filters = array()){
			
		$instance = new UserStatistics_Collection($filters);
		return $instance;
	}

	public function __construct($filters = array()){
	
		$this->_filters = array();
		
		if (!empty($filters['users'][0]))
			$this->_filters['uid'] = $filters['users'];
		if (!empty($filters['ips'][0]))
			$this->_filters['user_ip'] = $filters['ips'];
		if (!empty($filters['browsers'][0]))
			$this->_filters['browser_name'] = $filters['browsers'];
		
		if (!empty($this->_filters['uid']) && ($key = array_search(-1, $this->_filters['uid'])) !== FALSE)
			$this->_filters['uid'][$key] = 0;
			
	}
	
	// ПОЛУЧИТЬ СПИСОК С ПОСТРАНИЧНОЙ РАЗБИВКОЙ
	public function getPaginated(){
		
		$where = $this->_getSqlFilter();
		// echo $where; die;
		
		$sorter = new Sorter('last_date', 'DESC', $this->_sortableFieldsTitles);
		$paginator = new Paginator('sql', array(
			's.*, u.'.CurUser::LOGIN_FIELD.' AS login,
			(SELECT MAX(last_date) FROM user_stat_pages WHERE session_id=s.id) AS last_date,
			(SELECT COUNT(1) FROM user_stat_pages WHERE session_id=s.id) AS num_pages',
			'FROM '.UserStatistics_Model::TABLE.' s
			LEFT JOIN '.User_Model::TABLE.' u ON u.id=s.uid
			'.$where.'
			ORDER BY '.$sorter->getOrderBy()), '~50');
		
		$db = db::get();
		$data = $db->fetchAssoc($paginator->getSql(), 'id', array());
		
		// echo '<pre>'; print_r($data); die;
		
		// получение посещенных страниц
		if (!empty($data)){
			$pages = $db->fetchAll('SELECT * FROM user_stat_pages WHERE session_id IN('.implode(',', array_keys($data)).')');
			foreach($pages as $p)
				$data[ $p['session_id'] ]['pages'][] = $p;
		}
		
		// получение краткой информации о страницах
		foreach($data as &$row)
			$row = UserStatistics_Model::beforeDisplay($row);
		
		$this->_sortableLinks = $sorter->getSortableLinks();
		$this->_sortArray = $sorter->getSortArray();
		$this->_pagination = $paginator->getButtons();
		$this->_linkTags = $paginator->getLinkTags();
		
		return $data;
	}
	
	public function getSortArray(){
		
		return $this->_sortArray;
	}
	
	public function getFiltersLists(){
		
		$db = db::get();
		$filters = array();
		
		$filters['users'] = $db->fetchPairs('
			SELECT DISTINCT uid, u.'.CurUser::LOGIN_FIELD.' AS login
			FROM user_stat s
			JOIN '.User_Model::TABLE.' u ON u.id=s.uid ORDER BY login');
		
		$filters['ips'] = $db->fetchPairs('SELECT DISTINCT(user_ip), user_ip FROM user_stat ORDER BY user_ip');
		$filters['browsers'] = $db->fetchPairs('SELECT DISTINCT(browser_name), browser_name FROM user_stat WHERE LENGTH(browser_name) > 0 ORDER BY browser_name');
		
		return $filters;
	}
	
}

?>