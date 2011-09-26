<?class Debugger {		private static $_config = array(				'displayPageStatistics' => FALSE,	);		private static $_instance = null;			/**	 * Задать конфигурацию класса	 * @param array $config - массив директива=>значение	 * @return void;	 */	public static function setConfig($config){			foreach($config as $key => $val){			if(array_key_exists($key, self::$_config)){				self::$_config[$key] = $val;			}else{				trigger_error('Не удалось установить конфигурацию обработчика обшибок. Неизвестный ключ ['.$key.']', E_USER_ERROR);			}		}	}		/**	 * Получить значение конфигурационной директивы, или весь массив конфигурации	 * @param null|string $key	 * @return array|string	 */	public static function getConfig($key = null){				return is_null($key)			? self::$_config			: self::$_config[$key];	}		public static function get(){				if(is_null(self::$_instance))			self::$_instance = new Debugger();				return self::$_instance;	}		private function __construct(){		}		public function log($string){		}		/**	 * ПОЛУЧИТЬ HTML, ВЫВОДЯЩИЙ СТАТИСТИКУ О ЗАГРУЖЕННОЙ СТРАНИЦЕ	 * Статистика будет отображена в VikDebug консоли.	 * Выводится только если в конфиге задан параметр displayPageStatistics = true	 * @return string - html статистики	 */	public function getPageStatisticsHtml(){				if(!self::$_config['displayPageStatistics'])			return '';					$scriptExecutionTime = round(microtime(1) - $GLOBALS['__vikOffTimerStart__'], 4);				$output = ''			.'<table class="php-page-statistics">'			.'<tr class="section"><th colspan="2" >PHP</th></tr>'			.'<tr><th>Показатель</th><th>Значение</th></tr>'			.'<tr><td>Версия интерпретатора</td><td>'.phpversion().'</td></tr>'			.'<tr><td>Время выполнения скрипта</td><td>'.$scriptExecutionTime.' сек.</td></tr>'			.'<tr><td>Подключенных файлов</td><td>'.count(get_included_files()).'</td></tr>'			.'<tr><td>Пик использования памяти</td><td>'.Tools::formatByteSize(memory_get_peak_usage()).'</td></tr>'						.'<tr class="section"><th colspan="2" >SQL</th></tr>'			.'<tr><th>Запрос</th><th>Время, сек</th></tr>'		;		foreach(db::get()->getQueriesWithTime() as $q)			$output .= '<tr><td>'.$q['sql'].'</td><td>'.round($q['time'], 5).'</td></tr>';		$output .= ''			.'<tr class="b"><td>Всего запросов</td><td>'.db::get()->getQueriesNum().'</td></tr>'			.'<tr class="b"><td>Общее время выполнения</td><td>'.round(db::get()->getQueriesTime(), 5).' сек.</td></tr>'			.'</table>'		;				$output = '			<script type="text/javascript">			$(function(){				VikDebug.print(\''.preg_replace(array("/\s+/", "/'/"), array(" ", "\\'"), $output).'\', "performance", {activateTab: false, onPrintAction: "none"});			});			</script>';		return $output;	}	}?>