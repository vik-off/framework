<?

class Admin_Controller extends Controller{
	
	const DEFAULT_VIEW = 1;
	const TPL_PATH = 'modules/Admin/templates/';
	
	const MODULE = 'admin';
	
	// методы, отображаемые по умолчанию
	protected $_displayIndex = 'content';
	
	// права на выполнение методов контроллера
	public $methodResources = array(
	
		'display_content'   => 'content',
		'display_config'    => 'config',
		'display_manage'    => 'manage',
		
		'action_read_modules_config' => 'manage',
		'action_make_fs_snapshot' => 'manage',
		'action_error_delete_item' => 'manage',
	);
	
	/**
	 * массив пар 'идентификатор метода' => 'Класс контроллера или Имя модуля'
	 * для проксирования запросов в другой контроллер/модуль
	 * если указан класс контроллера, то он должен принадлежать тому же модулю, что и текущий контроллер.
	 */
	public $_proxy = array(
		'sql' => 'Admin_SqlController',
		'users' => 'user',
		'modules' => 'Admin_ModulesController',
	);
	
	public function init(){
	
		BackendLayout::get()->setTitle('Административная панель');
	}
	
	/** ПРОВЕРКА ПРАВ НА ВЫПОЛНЕНИЕ РЕСУРСА */
	public function checkResourcePermission($resource){
		
		return User_Acl::get()->isResourceAllowed(self::MODULE, $resource);
	}
	
	/**
	 * ВЫПОЛНЕНИЕ ДЕЙСТВИЯ
	 * @exception Exception - ловит стандартные исключения
	 * @exception Exception403 - ловит исключения 403
	 * @exception Exception404 - ловит исключения 404
	 * @param string $method - идентификатор метода
	 * @param string $redirectUrl - url, куда надо сделать редирект после успешного выполнения
	 * @return void
	 */
	public function action($params, $redirectUrl = null){
		
		// для проксируемых методов, и тех, которые идут непосредственно к Admin_Controller
		if(isset($this->_proxy[ getVar($params[0]) ]) || count($params) == 1)
			return parent::action($params, $redirectUrl);
		
		// запросы на бэкенд-контроллеры других модулей
		$app = App::get();
		$module = $app->prepareModuleName(array_shift($params));
		return $app->getModule($module, TRUE)->action($params, $redirectUrl);
	}
	
	/** ПОЛУЧИТЬ ЭКЗЕМЛЯР КОНТРОЛЛЕРА ДЛЯ ПРОКСИРОВАНИЯ */
	public function getProxyControllerInstance($proxy){
		
		if (!CurUser::get()->isLogged()){
			BackendLayout::get()->showLoginPage();
			exit;
		}
		
		return App::get()->isModule($proxy, TRUE)
			? App::get()->getModule($proxy, TRUE)
			: new $proxy($this->_config);
	}
	
	/////////////////////
	////// DISPLAY //////
	/////////////////////
	
	/** DISPLAY CONTENT */
	public function display_content($params = array()){
		
		$viewer = BackendLayout::get();
		
		// display index
		if(empty($params[0])){
			$viewer
				->setContentHtmlFile(self::TPL_PATH.'content_index.tpl')
				->render();
			exit();
		}
		
		$app = App::get();
		$module = $app->prepareModuleName(array_shift($params));
		
		if(!$app->isModule($module, TRUE)){
			$this->error404handler('модуль <b>'.$module.'</b> не найден');
			exit();
		}
		
		if(!$app->getModule($module, TRUE)->display($params))
			$this->error404handler('недопустимое действие <b>'.getVar($params[0]).'</b> модуля <b>'.$module.'</b>');
	}
	
	/** DISPLAY CONFIG */
	public function display_config($params = array()){
		
		$viewer = BackendLayout::get();
		$section = getVar($params[0]);
		
		// display index
		if(empty($section)){
			$viewer
				->setContentHtmlFile(self::TPL_PATH.'content_index.tpl')
				->render();
			exit();
		}
		
		switch($section){
			
			case 'modules':
				$this->snippet_config_modules();
				break;
				
			default:
				$app = App::get();
				$module = $app->prepareModuleName(array_shift($params));
				
				if(!$app->isModule($module, TRUE)){
					$this->error404handler('модуль <b>'.$module.'</b> не найден');
					exit();
				}
				
				if(!$app->getModule($module, TRUE)->display($params))
					$this->error404handler('недопустимое действие <b>'.getVar($params[0]).'</b> модуля <b>'.$module.'</b>');
		}
	}
	
	/** DISPLAY MANAGE */
	public function display_manage($params = array()){
		
		$section = getVar($params[0]);
		
		$viewer = BackendLayout::get();

		if(empty($section)){
			$viewer
				->setContentHtmlFile(self::TPL_PATH.'manage_index.php')
				->render();
			exit();
		}
		
		switch($section){
			
			case 'error-log':
				$this->snippet_error_log();
				break;
			
			case 'fs-snapshot':
				$this->snippet_fs_snapshot();
				break;
				
			default:
		
				$app = App::get();
				$module = $app->prepareModuleName(array_shift($params));
				
				if(!$app->isModule($module, TRUE)){
					$this->error404handler('модуль <b>'.$module.'</b> не найден');
					exit();
				}
				
				if(!$app->getModule($module, TRUE)->display($params))
					$this->error404handler('недопустимое действие <b>'.getVar($params[0]).'</b> модуля <b>'.$module.'</b>');
		}
	}
	
	//////////////////////
	////// SNIPPETS //////
	//////////////////////
	
	/** SNIPPET CONFIG MODULES */
	public function snippet_config_modules(){
		
		BackendLayout::get()
			->setContentPhpFile(self::TPL_PATH.'config_modules.php')
			->render();
	}
	
	/** SNIPPET ERROR LOG */
	public function snippet_error_log(){
		
		$collection = new Error_Collection();
		$variables = array(
			'collection' => $collection->getPaginated(),
			'pagination' => $collection->getPagination(),
		);
		BackendLayout::get()
			->prependTitle('Лог ошибок')
			->setContentPhpFile(self::TPL_PATH.'manage_error_log.php', $variables)
			->render();
	}
	
	public function snippet_fs_snapshot(){
		
		BackendLayout::get()
			->prependTitle('Снимок файловой системы')
			->setContentPhpFile(self::TPL_PATH.'manage_fs_snapshot.php')
			->render();
	}
	
	////////////////////
	////// ACTION //////
	////////////////////
	
	/** ACTION READ MODULES CONFIG */
	public function action_read_modules_config(){
		
		$model = new Admin_Model();
		$log = $model->readModulesConfig();
		BackendLayout::get()->setVariables(array('log' => $log));
		Messenger::get()->addSuccess('Конфигурация модулей перечитана');
		return TRUE;
	}
	
	/** ACTION MAKE FS SNAPSHOT */
	public function action_make_fs_snapshot(){
		
		$model = new Admin_Model();
		
		Tools::sendDownloadHeaders('fs_snapshot_'.date("Y-m-d_H-i").'.txt');
		$model->makeFsSnapshot(FS_ROOT, array('.git' => true));
		exit;
	}
	
	// DELETE OLD ERRORS
	public function action_delete_old_errors($params = array()){
		
		$expire = getVar($_POST['expire']);
		
		$expiredValues = array(
			'1day'   => 86400,
			'1week'  => 604800,
			'1month' => 2592000,
			'3month' => 7776000,
			'6month' => 15552000,
			'9month' => 23328000,
			'1year'  => 31536000);
			
		if(!isset($expiredValues[$expire])){
			Messenger::get()->addError('Неверный временной промежуток.');
			return FALSE;
		}
		
		UserStatistics::get()->deleteOldStatistics($expiredValues[$expire]);
		Messenger::get()->addSuccess('Старая статистика удалена.');
		return TRUE;
	}
	
	public function action_error_delete_item(){
		
		$id = getVar($_POST['id'], 0, 'int');
		try{
			Error_Model::load($id)->destroy();
			Messenger::get()->addSuccess('Запись удалена');
			return TRUE;
		}catch(Exception $e){
			Messenger::get()->addError('Не удалось удалить запись', $e->getMessage());
			return FALSE;
		}
	}
	
	////////////////////
	////// AJAX   //////
	////////////////////
	
	// ОБРАБОТЧИК 403
	public function error403handler($method, $line = 0){
		
		BackendLayout::get()->showLoginPage();
	}
	
	
	////////////////////
	////// OTHER  //////
	////////////////////
	
	public function getClass(){
		return __CLASS__;
	}

}

?>