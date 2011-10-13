<?

class Page_Model extends GenericObject{
	
	/** имя модуля */
	const MODULE = 'page';
	
	const TABLE = 'pages';
	
	const NOT_FOUND_MESSAGE = 'Страница не найдена';
	
	const TYPE_FULL = 1;
	const TYPE_CHUNK = 2;
	
	/** ТОЧКА ВХОДА В КЛАСС (СОЗДАНИЕ НОВОГО ОБЪЕКТА) */
	public static function create(){
			
		return new Page_Model(0, self::INIT_NEW);
	}
	
	/** ТОЧКА ВХОДА В КЛАСС (ЗАГРУЗКА СУЩЕСТВУЮЩЕГО ОБЪЕКТА) */
	public static function load($id){
		
		return new Page_Model($id, self::INIT_EXISTS);
	}

	/** ТОЧКА ВХОДА В КЛАСС (ЗАГРУЗКА СУЩЕСТВУЮЩЕГО ОБЪЕКТА) */
	public static function forceLoad($id, $fieldvalues){
		
		return new Page_Model($id, self::INIT_EXISTS_FORCE, $fieldvalues);
	}
	
	/** ТОЧКА ВХОДА В КЛАСС (ЗАГРУЗКА СУЩЕСТВУЮЩЕГО ОБЪЕКТА ПО ПСЕВДОНИМУ) */
	public static function loadByAlias($alias){
		
		$data = db::get()->getRow("SELECT * FROM ".self::TABLE." WHERE alias=".db::get()->qe($alias)." LIMIT 1", FALSE);
		if(!$data)
			throw new Exception404(self::NOT_FOUND_MESSAGE);
			
		return new Page_Model($data['id'], self::INIT_EXISTS_FORCE, $data);
	}
	
	/** ПОЛУЧИТЬ ИМЯ КЛАССА */
	public function getClass(){
		return __CLASS__;
	}
	
	/**
	 * ПРОВЕРКА ВОЗМОЖНОСТИ ДОСТУПА К ОБЪЕКТУ
	 * Вызывается автоматически при загрузке существующего объекта
	 * В случае запрета доступа генерирует нужное исключение
	 */
	protected function _accessCheck(){
		
		if(!App::get()->isAdminMode() && !$this->getField('published'))
			throw new Exception403('Доступ к странице ограничен');
	}
	
	/** ПОЛУЧИТЬ ЭКЗЕМПЛЯР ВАЛИДАТОРА */
	public function getValidator(){
		
		$validator = new Validator();
		$validator->rules(array(
			'required' => array('type', 'title', 'format'),
		), array(
			'type' => array('in' => array(self::TYPE_FULL, self::TYPE_CHUNK)),
			'title' => array('strip' => TRUE, 'length' => array('max' => '65535')),
			'alias' => array('trim' => TRUE, 'match' => '/^[\w\-]{0,255}$/'),
			'body' => array('length' => array('max' => '65535')),
			'stored_in_file' => array('checkbox' => array('on' => TRUE, 'off' => FALSE)),
			'published' => array('checkbox' => array('on' => TRUE, 'off' => FALSE)),
			'format' => array('in' => array('php', 'html')),
			'meta_description' => array('strip' => TRUE, 'length' => array('max' => '65535')),
			'meta_keywords' => array('strip' => TRUE, 'length' => array('max' => '65535')),
		));
		$validator->setFieldTitles(array(
			'id' => 'id',
			'title' => 'Заголовок',
			'alias' => 'Псевдоним',
			'body' => 'Тело страницы',
			'published' => 'Опубликовать',
			'type' => 'Тип',
		));
		
		return $validator;
	}
	
	public function preValidation(&$data){
		
		$data['type'] = getVar($data['type'], 0, 'int');
		// echo '<pre>'; print_r($data); die;
	}
	
	// ПОДГОТОВКА ДАННЫХ К СОХРАНЕНИЮ
	public function postValidation(&$data){
		
		if(!$this->_checkAlias($data))
			return FALSE;
		
		$data['body'] = str_replace(array("\r\n", "\n"), '', $data['body']);
		$data['modif_date'] = time();
		
		if($this->isNewObj){
			$data['author'] = USER_AUTH_ID;
			$data['create_date'] = time();
		}
		
		// echo '<pre>'; var_dump($data); die;
	}
	
	/** ПРОВЕРКА ПСЕВДОНИМА */
	private function _checkAlias($data){
	
		// проверка псевдонима на уникальность (если задан)
		if(strlen($data['alias']) && db::get()->getOne('SELECT COUNT(1) FROM '.self::TABLE.' WHERE alias='.db::get()->qe($data['alias']).' '.($this->isExistsObj ? ' AND id!='.$this->id : ''), 0)){
			$this->setError('Запись с таким псевдонимом уже существует');
			return FALSE;
		}
		
		// проверка чтобы псевдоним не был числом, соответствующим id другой страницы
		if($this->isNewObj && is_numeric($data['alias'])){
			$this->setError('Псевдоним новой записи не может быть задан числом.');
			return FALSE;
		}
		
		// проверка если id задан числом, он должен совпадать с id текущей записи
		if($this->isExistsObj && is_numeric($data['alias']) && $data['alias'] != $this->id){
			$this->setError('Если псевдоним задан числом, то он должен совпадать с id записи.');
			return FALSE;
		}
		
		return TRUE;
	}
	
	// ДЕЙСТВИЕ ПОСЛЕ СОХРАНЕНИЯ
	public function afterSave($data){
	
		if(!strlen($data['alias'])){
			$this->setField('alias', $this->id);
			$this->_save();
		}
	}
	
	// ПОДГОТОВКА ДАННЫХ К ОТОБРАЖЕНИЮ
	public function beforeDisplay($data){
		
		$data['modif_date'] = YDate::loadTimestamp($data['modif_date'])->getStrDateShortTime();
		$data['create_date'] = YDate::loadTimestamp($data['create_date'])->getStrDateShortTime();
		$data['type_str']    = self::getPageTypeTitle($data['type']);
		return $data;
	}
	
	// ОПУБЛИКОВАТЬ СТРАНИЦУ
	public function publish(){
	
		$this->setField('published', TRUE);
		$this->_save();
	}
	
	// СКРЫТЬ СТРАНИЦУ
	public function unpublish(){
	
		$this->setField('published', FALSE);
		$this->_save();
	}
	
	
	/** ПРОВЕРИТЬ, ЯВЛЯЕТСЯ ЛИ СТРАНИЦА ФРАГМЕТНОМ */
	public function isChunk(){
		
		return $this->getField('type') == self::TYPE_CHUNK;
	}
	
	public static function getPageTypeTitle($type){
		
		switch($type) {
			case self::TYPE_FULL: return 'Основная';
			case self::TYPE_CHUNK: return 'Фрагмент';
			default: trigger_error('Неизвестный тип страницы: '.$type, E_USER_ERROR);
		}
	}
}

class Page_Collection extends GenericObjectCollection{
	
	// поля, по которым возможна сортировка коллекции
	// каждый ключ должен быть корректным выражением для SQL ORDER BY
	protected $_sortableFieldsTitles = array(
		'id' => 'id',
		'title' => 'Заголовок',
		'alias' => 'Псевдоним',
		'author' => 'author',
		'published' => 'Публикация',
		'type' => 'Тип',
		'modif_date' => 'Последнее изменение',
	);
	
	
	// ТОЧКА ВХОДА В КЛАСС
	public static function load(){
			
		$instance = new Page_Collection();
		return $instance;
	}
	
	// ПОЛУЧИТЬ СПИСОК С ПОСТРАНИЧНОЙ РАЗБИВКОЙ
	public function getPaginated(){
		
		$sorter = new Sorter('id', 'DESC', $this->_sortableFieldsTitles);
		$paginator = new Paginator('sql', array('*', 'FROM '.Page_Model::TABLE.' ORDER BY '.$sorter->getOrderBy()), 50);
		
		$data = db::get()->getAll($paginator->getSql(), array());
		
		foreach($data as &$row)
			$row = Page_Model::forceLoad($row['id'], $row)->getAllFieldsPrepared();
		
		$this->_sortableLinks = $sorter->getSortableLinks();
		$this->_pagination = $paginator->getButtons();
		$this->_linkTags = $paginator->getLinkTags();
		
		return $data;
	}
	
}

?>