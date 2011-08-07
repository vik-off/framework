<?
/**
 * Фронт-контроллер приложения. 
 * 
 * @using constants
 *		DEFAULT_CONTROLLER,
 *		CHECK_FORM_DUPLICATION,
 *		FS_ROOT,
 *		CFG_USE_SMARTY_CACHING,
 *		CFG_SMARTY_TRIMWHITESPACES,
 *		CFG_SITE_NAME,
 *		WWW_URI,
 *		WWW_PREFIX
 */
class App{
	
	private $_preventDisplay = FALSE;
	
	public static $displayController = null;
	public static $displayMethodIdentifier = null;
	public static $displayMethodParams = array();
	
	public static $adminMode = FALSE;
	
	private static $_instance = null;
	private static $_smartyInstance = null;
	
	private $_requestControllerIdentifier = null;
	private $_requestMethodIdentifier = null;
	private $_requestController = null;
	private $_requestParams = null;
	
	private $_adminMode = FALSE;

	
	/** ПОЛУЧИТЬ ЭКЗЕМПЛЯР КЛАССА */
	public static function get(){
		
		if(is_null(self::$_instance))
			self::$_instance = new App();
		
		return self::$_instance;
	}
	
	/** КОНСТРУКТОР */
	private function __construct(){
		
		// извлечение параметров запроса
		list($this->_requestControllerIdentifier,
		     $this->_requestMethodIdentifier,
			 $this->_requestParams) = Request::get()->getArray();
		
		// назначить класс контроллера
		$this->_defineRequestController();
		
		// определение режима администратора
		$this->_adminMode = $this->_requestControllerIdentifier == 'admin';
	}
	
	/** ОПРЕДЕЛИТЬ КЛАСС КОНТРОЛЛЕРА ПО ПЕРЕДАННОМУ ИДЕНТИФИКАТОРУ */
	private function _defineRequestController(){
		
		// если контроллер не передан в request
		if(empty($this->_requestControllerIdentifier)){
			
			if(CFG_REDIRECT_DEFAULT_DISPLAY){
				App::redirectHref(Request::get()->getAppended(strtolower(DEFAULT_CONTROLLER)));
			}else{
				$this->_requestControllerIdentifier = DEFAULT_CONTROLLER;
			}
		}
		$this->_requestController = self::getControllerClassName($this->_requestControllerIdentifier);
	}
	
	/**
	 * Проверить, включен ли режим администратора 
	 * @return bool
	 */
	public function isAdminMode(){
		
		return $this->_adminMode;
	}
	
	/**  ВЫПОЛНЕНИЕ ПРИЛОЖЕНИЯ */
	public function run(){
		
		// проверка контроллера отображения
		if(is_null($this->_requestController)){
			Debugger::get()->log('Контроллер не найден');
			$this->error404();
		}
		
		// определение и выполнение действия
		$this->_checkAction();
		
		// выполнение отображения (если не приостановлено)
		if(!$this->_preventDisplay){
			$displayControllerInstance = new $this->_requestController();
			$displayControllerInstance->performDisplay($this->_requestMethodIdentifier, $this->_requestParams);
		}
	}
	
	/**  ВЫПОЛНЕНИЕ AJAX-ЗАПРОСА */
	public function ajax(){
		
		// проверка контроллера отображения
		if(is_null($this->_requestController)){
			Debugger::get()->log('Контроллер не найден');
			$this->error404();
		}
		
		// выполнение ajax-метода
		$controllerInstance = new $this->_requestController();
		$controllerInstance->performAjax($this->_requestMethodIdentifier, $this->_requestParams);
	}

	
	#### ПОДГОТОВКА К ВЫПОЛНЕНИЮ ОПЕРАЦИЙ ####
	
	/** ПРОВЕРИТЬ НЕОБХОДИМОСТЬ ВЫПОЛЕННИЯ ДЕЙСТВИЯ */
	public function _checkAction(){
		
		if(!isset($_POST['action']) || !App::checkFormDuplication())
			return FALSE;
			
		$isArr = is_array($_POST['action']);
		$action = strtolower($isArr ? YArray::getFirstKey($_POST['action']) : $_POST['action']);
		$redirect = $isArr && is_array($_POST['action'][$action])
			? YArray::getFirstKey($_POST['action'][$action])
			: (isset($_POST['redirect']) ? $_POST['redirect'] : '');
		
		// параметр action должен иметь вид 'controller/method'
		if(strpos($action, '/') === FALSE){
			trigger_error('Неверный формат параметра action: '.$action.' (требуется разделитель)', E_USER_ERROR);
		}
		
		list($_controller, $_method) = YArray::trim(explode('/', $action));
		
		$controller = self::getControllerClassName($_controller);
		$method = self::getActionMethodName($_method);
		
		if(is_null($controller)){
			Debugger::get()->log('Контроллер не найден по идентификатору "'.$_controller.'"');
			$this->error404('Неизвестное действие');
		}
		
		$controllerInstance = new $controller();
		$controllerInstance->performAction($method, $redirect);
		return TRUE;
	}
	
	/**
	 * ПОЛУЧИТЬ ИМЯ КЛАССА КОНТРОЛЛЕРА ПО ИДЕНТИФИКАТОРУ
	 * @param string $controllerIdentifier - идентификатор контроллера
	 * @return string|null - имя класса контроллера или null, если контроллер не найден
	 */
	public static function getControllerClassName($controllerIdentifier){
			
		// если идентификатор контроллера не передан, вернем null
		if(empty($controllerIdentifier))
			return null;
		
		// если идентификатор контроллера содержит недопустимые символы, вернем null
		if(!preg_match('/^[\w\-]$/', $controllerIdentifier))
			return null;
			
		// преобразует строку вида 'any-class-name' в 'AnyClassNameController'
		$controller = str_replace(' ', '', ucwords(str_replace('-', ' ', strtolower($controllerIdentifier)))).'Controller';
		return class_exists($controller) ? $controller : null;
	}
	
	// ПОЛУЧИТЬ ИМЯ МЕТОДА ОТОБРАЖЕНИЯ ПО ИДЕНТИФИКАТОРУ
	public static function getDisplayMethodName($method){
	
		// преобразует строку вида 'any-Method-name' в 'any_method_name'
		$method = 'display_'.(strlen($method) ? strtolower(str_replace('-', '_', $method)) : 'default');
		return $method;
	}
	
	// ПОЛУЧИТЬ ИМЯ МЕТОДА ДЕЙСТВИЯ ПО ИДЕНТИФИКАТОРУ
	public static function getActionMethodName($method){
	
		// преобразует строку вида 'any-Method-name' в 'any_method_name'
		$method = 'action_'.strtolower(str_replace('-', '_', $method));
		return $method;
	}
	
	// ПОЛУЧИТЬ ИМЯ AJAX МЕТОДА ПО ИДЕНТИФИКАТОРУ
	public static function getAjaxMethodName($method){
	
		// преобразует строку вида 'any-Method-name' в 'any_method_name'
		$method = 'ajax_'.strtolower(str_replace('-', '_', $method));
		return $method;
	}
	
	/** ЗАПРЕТ ОТОБРАЖЕНИЯ */
	public function preventDisplay($prevent = TRUE){
	
		$this->_preventDisplay = (bool)$prevent;
	}
	
	
	#### ВЫПОЛНЕНИЕ РЕДИРЕКТОВ ####
	
	
	// REDIRECT
	public static function redirect($uri){
	
		// echo '<a href="'.$uri.'">'.$uri.'</a>'; die;
		header('location: '.$uri);
		exit();
	}
	
	// REDIRECT HREF
	public static function redirectHref($href){
		
		// echo '<a href="'.App::href($href).'">'.App::href($href).'</a>'; die;
		header('location: '.App::href($href));
		exit();
	}
	
	// RELOAD
	public static function reload(){
	
		$url = 'http://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];
		header('location: '.$url);
		exit();
	}
	
	
	#### FORMCODE ####
	
	
	// ПОЛУЧИТЬ HTML INPUT СОДЕРЖАЩИЙ FORMCODE
	static public function getFormCodeInput(){
		return '<input type="hidden" name="formCode" value="'.self::_generateFormCode().'" />';
	}
	
	// ПРОВЕРКА ВАЛИДНОСТИ ФОРМЫ
	static public function checkFormDuplication(){
		
		if(isset($_POST['allowDuplication']))
			return TRUE;
			
		if(!isset($_POST['formCode'])){
			trigger_error('formCode не передан', E_USER_ERROR);
			return FALSE;
		}
		$formcode = (int)$_POST['formCode'];
		
		if(!CHECK_FORM_DUPLICATION)
			return TRUE;
			
		if(self::_isAllowedFormCode($formcode)){
			return TRUE;
		}else{
			return FALSE;
		}
	}
	
	// ПОМЕТИТЬ FORMCODE ИСПОЛЬЗОВАННЫМ
	static public function lockFormCode(&$code){
	
		if(CHECK_FORM_DUPLICATION && !empty($code))
			$_SESSION['userFormChecker']['used'][] = $code;
	}
	
	// СГЕНЕРИРОВАТЬ УНИКАЛЬНЫЙ FORMCODE
	static private function _generateFormCode(){
	
		// init session variable
		if(!isset($_SESSION['userFormChecker']))
			$_SESSION['userFormChecker'] = array('current' => 0, 'used' => array());
		// generate unique code
		$_SESSION['userFormChecker']['current']++;
		return $_SESSION['userFormChecker']['current'];
	}
	
	// ПРОВЕРИТЬ ПОЛУЧЕННЫЙ FORMCODE
	static private function _isAllowedFormCode($code){
	
		if(!$code)
			return FALSE;
		if(!isset($_SESSION['userFormChecker']['used']))
			return FALSE;
		return (bool)!in_array($code, $_SESSION['userFormChecker']['used']);
	}
	
	
	#### HREF ####
	
	/**
	 * HREF
	 * Генерация валидного абсолютного URL адреса
	 * @param string $href - строка вида 'contoller/method/addit?param1=val1&param2=val2
	 * return string абсолютный URL
	 */
	public static function href($href){
	
		return WWW_ROOT.(CFG_USE_SEF
			? $href											// http://site.com/controller/method?param=value
			: 'index.php?r='.str_replace('?', '&', $href));	// http://site.com/index.php?r=controller/method&param=value
	}
	
	/**
	 * GET HREF REPLACED
	 * Получить валидный url с замененным/добавленным параметром (одним или несколькими)
	 * @param string|array $nameOrPairs - имя параметра, или массив ($имя => $параметр)
	 * @param string|null $valueOrNull - значение параметра (если первый аргумент - строка) или null
	 * @return string валидный абсолютный URL с нужными параметрами
	 */
	public static function getHrefReplaced($nameOrPairs, $valueOrNull = null){
		
		// получить пары для замены
		$pairs = is_array($nameOrPairs)
			? $nameOrPairs
			: array($nameOrPairs => $valueOrNull);
		
		// получить копию $_GET с нужными заменами
		$copyOfGet = $_GET;
		foreach($pairs as $name => $value){
			if(is_null($value))	// если value == null, удалим параметр из QS
				unset($copyOfGet[$name]);
			else				// иначе добавим / заменим параметр в QS
				$copyOfGet[$name] = $value;
		}
		
		// сформировать валидный URL
		$r = isset($copyOfGet['r']) ? $copyOfGet['r'] : '';
		unset($copyOfGet['r']);
		$qs = array();
		foreach($copyOfGet as $k => $v)
			$qs[] = $k.'='.$v;
		
		return App::href($r.(count($qs) ? '?'.implode('&', $qs) : ''));
	}

	#### ПРОЧЕЕ ####
	
	// ERROR 403
	public static function error403($msg){
		
		if(AJAX_MODE){
			header($_SERVER['SERVER_PROTOCOL'].' 403 Forbidden'); // 'HTTP/1.1 403 Forbidden'
			echo $msg;
		}else{
			CommonViewer::get()->error403($msg);
		}
		exit();
	}
	
	/** ПОКАЗАТЬ СТРАНИЦУ 404 */
	public function error404($msg){
		
		if(AJAX_MODE){
			header($_SERVER['SERVER_PROTOCOL'].' 404 Not Found'); // 'HTTP/1.1 404 Not Found'
			echo $msg;
		}else{
			CommonViewer::get()->error404($msg);
		}
		exit();
	}
	
	// ПОЛУЧИТЬ ЭКЗЕМПЛЯР SMARTY
	public static function smarty(){
	
		if(is_null(self::$_smartyInstance)){
		
			require_once(FS_ROOT.'includes/smarty/libs/Smarty.class.php');
			require_once(FS_ROOT.'includes/smarty/VIKOFF_SmartyPlugins.php');
			
			self::$_smartyInstance = new Smarty();
			
			$path = FS_ROOT.'includes/smarty/';
			
			self::$_smartyInstance->template_dir = FS_ROOT.'templates/';
			self::$_smartyInstance->compile_dir = $path.'templates_c/';
			self::$_smartyInstance->config_dir = $path.'configs/';
			self::$_smartyInstance->cache_dir = $path.'cache/';
			
			self::$_smartyInstance->caching = (bool)CFG_USE_SMARTY_CACHING;
			
			// использование подстановщиков в JS
			self::$_smartyInstance->register_prefilter(array('SmartyPlugins', 'escape_script'));
			
			// использование тега <a href=""></a> в шаблонах
			self::$_smartyInstance->register_function('a', array('SmartyPlugins', 'function_a'));
			
			// удаление всех лишних пробельных символов
			if(CFG_SMARTY_TRIMWHITESPACES)
				self::$_smartyInstance->register_prefilter(array('SmartyPlugins', 'trimwhitespace'));
			
			// назначение псевдоконстант
			self::$_smartyInstance->assign(array(
				'CFG_SITE_NAME'		=> CFG_SITE_NAME,	
				'WWW_ROOT' 			=> WWW_ROOT,
				'WWW_URI' 			=> WWW_URI,
			));
			
			// назначение других переменных
			self::$_smartyInstance->assign(array(
				'formcode' => self::getFormCodeInput(),
				'hasPermModerator' => (USER_AUTH_PERMS >= PERMS_MODERATOR),
				'hasPermAdmin' => (USER_AUTH_PERMS >= PERMS_ADMIN),
				'hasPermSuperadmin' => (USER_AUTH_PERMS >= PERMS_SUPERADMIN),
				'hasPermRoot' => (USER_AUTH_PERMS >= PERMS_ROOT),
			));
			
		}
		
		return self::$_smartyInstance;
	}

}
?>