<?php

class YDate{
	
	const STR_DATE = 'strDate';
	const STR_TIME = 'strTime';
	const STR_DATE_TIME = 'strDateTime';
	
	const TYPE_NOW		 = 0; // текущая дата
	const TYPE_EMPTY     = 1; // пустая дата (null)
	const TYPE_TIMESTAMP = 2; // дата в виде timestamp
	const TYPE_DB_DATE   = 3; // дата в виде YYYY-MM-DD[ HH:MM:SS]
	const TYPE_ARRAY	 = 4; // дата в виде array('year' => 1, 'month' => 1, 'day' => 1[, 'hour' => 1, 'min' => 1, 'sec' => 1)
	
	/** var null|int $_curDate - текущая дата */
	private $_curDate = null;
	
	/** var bool $_useMonthFullName - использовать для вывода полные имена месяцев */
	private $_useMonthFullName = FALSE;
	
	// СПИСОК МЕСЯЦЕВ
	// именительный длинный
	public static $_monthsNomFull = array(1 => 'январь',2 => 'февраль', 3 => 'март', 4 => 'апрель', 5 => 'май', 6 => 'июнь', 7 => 'июль', 8 => 'август', 9 => 'сентябрь', 10 => 'октябрь', 11 => 'ноябрь', 12 => 'декабрь');
	// родительный длинный
	public static $_monthsGenFull = array(1 => 'января',2 => 'февраля', 3 => 'марта', 4 => 'апреля', 5 => 'мая', 6 => 'июня', 7 => 'июля', 8 => 'августа', 9 => 'сентября', 10 => 'октября', 11 => 'ноября', 12 => 'декабря');
	// именительный/родительный короткий
	public static $_monthsShort = array(1 => 'янв', 2 => 'фев', 3 => 'мар', 4 => 'апр', 5 => 'мая', 6 => 'июн', 7 => 'июл', 8 => 'авг', 9 => 'сен', 10 => 'окт', 11 => 'ноя', 12 => 'дек');
	
	
	/** ИНИЦИАЛИЗАЦИЯ КЛАССА ПРОИЗВОЛЬНЫМ ЗНАЧЕНИЕМ */
	public static function load($type, $date = ''){

		$instance = new YDate($type, $date);
		return $instance;
	}
	
	/** ИНИЦИАЛИЗАЦИЯ КЛАССА ТЕКУЩИМ ВРЕМЕНЕМ */
	public static function loadNow(){

		$instance = new YDate(self::TYPE_NOW);
		return $instance;
	}
	
	/** ИНИЦИАЛИЗАЦИЯ КЛАССА ПУСТЫМ ЗНАЧЕНИЕМ */
	public static function loadEmpty(){
		
		$instance = new YDate(self::TYPE_EMPTY);
		return $instance;
	}
	
	/** ИНИЦИАЛИЗАЦИЯ КЛАССА ДАТОЙ ФОРМАТА БД */
	public static function loadDbDate($date){

		$instance = new YDate(self::TYPE_DB_DATE, $date);
		return $instance;
	}
	
	/** ИНИЦИАЛИЗАЦИЯ КЛАССА TIMESTAMP-МЕТКОЙ */
	public static function loadTimestamp($date){

		$instance = new YDate(self::TYPE_TIMESTAMP, $date);
		return $instance;
	}
	
	/** ИНИЦИАЛИЗАЦИЯ КЛАССА TIMESTAMP-МЕТКОЙ */
	public static function loadArray($arr){

		$instance = new YDate(self::TYPE_ARRAY, $arr);
		return $instance;
	}
	
	/**
	 * КОНСТРУКТОР КЛАССА
	 * принимает дату в гибком формате (в зависимости от параметра $type)
	 * и преобразует ее во внутреннее представление для дальнейшей работы.
	 * @param const $type одно из следующих значений:
	 *     self::TYPE_NOW
	 *     self::TYPE_EMPTY
	 *     self::TYPE_TIMESTAMP
	 *     self::TYPE_DB_DATE
	 *     self::TYPE_ARRAY
	 * @param string $date - значение даты в указанном формате
	 * @return YDate instance
	 */
	public function __construct($type, $date = ''){
		
		switch($type){
			case self::TYPE_NOW:
				$this->_curDate = time();
				break;
			case self::TYPE_EMPTY:
				$this->_curDate = null;
				break;
			case self::TYPE_TIMESTAMP:
				// дата считается пустой, если не является ненулевым числом
				$this->_curDate = !empty($date) ? $date : NULL;
				break;
			case self::TYPE_DB_DATE:
				// дата считаеся пустой, если не содержит ни одной цифры, отличной от нуля
				$this->_curDate = preg_match('/[123456789]/', $date) ? strtotime($date) : NULL;
				break;
			case self::TYPE_ARRAY:
				if(!is_array($date) || !count($date)){
					$this->_curDate = NULL;
				}else{
					$hasVal = FALSE;
					foreach($date as $k => $v) {
						if (!empty($v)) {
							$hasVal = TRUE;
							break;
						}
					}
					if ($hasVal) {
						$date = $date + array('year' => 0, 'month' => 0, 'day' => 0, 'hour' => 0, 'min' => 0, 'sec' => 0);
						$this->_curDate = mktime((int)$date['hour'], (int)$date['min'], (int)$date['sec'], (int)$date['month'], (int)$date['day'], (int)$date['year']);
					} else {
						$this->_curDate = NULL;
					}
				}
				break;
		}
	}
	
	/**
	 * ИСПОЛЬЗОВАТЬ ДЛЯ ВЫВОДА КОРОТКИЕ ИМЕНА МЕСЯЦЕВ (значение по умолчанию)
	 * @return $this
	 */
	public function useMonthShortName(){
		
		$this->_useMonthFullName = FALSE;
		return $this;
	}
	
	/**
	 * ИСПОЛЬЗОВАТЬ ДЛЯ ВЫВОДА ПОЛНЫЕ ИМЕНА МЕСЯЦЕВ
	 * @return $this
	 */
	public function useMonthFullName(){
	
		$this->_useMonthFullName = TRUE;
		return $this;
	}
	
	/**
	 * Прибавить к текущему времени переданное
	 * @param int $time - количество секунд для прибавления
	 * @return void
	 */
	public function addTime($time){
		
		$this->_curDate += $time;
		return $this;
	}
	
	/**
	 * Вычесть из текущего времени переданное
	 * @param int $time - количество секунд для вычетания
	 * @return void
	 */
	public function subTime($time){
		
		$this->_curDate -= $time;
		return $this;
	}
	
	/**
	 * ПОЛУЧИТЬ TIMESTAMP загруженной даты
	 * @return int timestamp
	 */
	public function getTimestamp(){
		
		return $this->_curDate;
	}

	/**
	 * ПОЛУЧИТЬ ЗАГРУЖЕННУЮ ДАТУ В ЗАДАННОМ ФОРМАТЕ
	 * функция понимает формат, аналогичный тому, что передается php-функции date()
	 * но расширенный 4 новыми модификаторами:
	 *     k - короткое имя месяца в именительном падеже ( например, "авг"     )
	 *     K - полное имя месяца в именительном падеже   ( например, "август"  )
	 *     b - короткое имя месяца в родительном падеже  ( например, "авг"     )
	 *     B - полное имя месяца в родительном падеже    ( например, "августа" )
	 * 
	 * @param string $format - формат даты
	 * @return string - строка даты
	 */
	public function getDate($format){
		
		if(is_null($this->_curDate))
			return '-';
			
		if(preg_match_all('/([A-Za-z])/', $format, $matches)){
			$phs = array_unique($matches[1]);
			$phVal = '';
			foreach($phs as $ph){
				switch($ph){
					// именительный короткий
					case 'k':
					// родительный короткий
					case 'b': $phVal = self::$_monthsShort[date('n', $this->_curDate)]; break;
					// именительный длинный
					case 'K': $phVal = self::$_monthsNomFull[date('n', $this->_curDate)]; break;
					// родительный длинный
					case 'B': $phVal = self::$_monthsGenFull[date('n', $this->_curDate)]; break;
					default: $phVal = date($ph, $this->_curDate);
				}
				$format = str_replace($ph, $phVal, $format);
			}
		}
		return $format;
	}
	
	/**
	 * ПОЛУЧИТЬ ЗАГРУЖЕННУЮ ДАТУ В ФОРМАТЕ yyyy-mm-dd
	 * @param string $default - значение по умолчанию (если загруженная дата пуста)
	 * @return string - строка даты
	 */
	public function getDbDate($default = null){
		
		if(is_null($this->_curDate))
			return !is_null($default) ? $default : '0000-00-00';
			
		return date('Y-m-d', $this->_curDate);
	}
	
	/**
	 * ПОЛУЧИТЬ ЗАГРУЖЕННУЮ ДАТУ В ФОРМАТЕ yyyy-mm-dd hh:mm:ss
	 * @param string $default - значение по умолчанию (если загруженная дата пуста)
	 * @return string - строка даты
	 */
	public function getDbDateTime($default = null){
		
		if(is_null($this->_curDate))
			return !is_null($default) ? $default : '0000-00-00 00:00:00';
		
		return date('Y-m-d H:i:s', $this->_curDate);
	}
	
	/**
	 * ПОЛУЧИТЬ ЗАГРУЖЕННУЮ ДАТУ В ФОРМАТЕ дд месяц гггг
	 * @param string $default - значение по умолчанию (если загруженная дата пуста)
	 * @return string - строка даты
	 */
	public function getStrDate($default = null){
		
		if(is_null($this->_curDate))
			return !is_null($default) ? $default : '-';
		
		$month = ($this->_useMonthFullName ? self::$_monthsGenFull[date('n', $this->_curDate)] : self::$_monthsShort[date('n', $this->_curDate)]);
		return date('d ', $this->_curDate).$month.date(' Y', $this->_curDate);
	}
	
	/**
	 * ПОЛУЧИТЬ ЗАГРУЖЕННУЮ ДАТУ В ФОРМАТЕ дд месяц гггг чч:мм:сс
	 * @param string $default - значение по умолчанию (если загруженная дата пуста)
	 * @return string - строка даты
	 */
	public function getStrDateTime($default = null){
		
		if(is_null($this->_curDate))
			return !is_null($default) ? $default : '-';
	
		$month = ($this->_useMonthFullName ? self::$_monthsGenFull[date('n', $this->_curDate)] : self::$_monthsShort[date('n', $this->_curDate)]);
		return date('d ', $this->_curDate).$month.date(' Y H:i:s', $this->_curDate);
	}
	
	/**
	 * ПОЛУЧИТЬ ЗАГРУЖЕННУЮ ДАТУ В ФОРМАТЕ дд месяц гггг чч:мм
	 * @param string $default - значение по умолчанию (если загруженная дата пуста)
	 * @return string - строка даты
	 */
	public function getStrDateShortTime($default = null){
		
		if(is_null($this->_curDate))
			return !is_null($default) ? $default : '-';
	
		$month = ($this->_useMonthFullName ? self::$_monthsGenFull[date('n', $this->_curDate)] : self::$_monthsShort[date('n', $this->_curDate)]);
		return date('d ', $this->_curDate).$month.date(' Y H:i', $this->_curDate);
	}
	
	/** ПОЛУЧИТЬ HTML СПИСОК СЕКУНД ДЛЯ ТЭГА <SELECT> */
	public function getSecondsListCurActive(){
		$selected = is_null($this->_curDate) ? null : date('s', $this->_curDate);
		$output = '';
		for($i = 0; $i <= 59; $i++)
			$output .= '<option value="'.$i.'"'.($i == $selected ? ' selected="selected"' : '').'>'.sprintf('%02d', $i).'</option>'."\n";
		return $output;
	}
	
	/** ПОЛУЧИТЬ HTML СПИСОК МИНУТ ДЛЯ ТЭГА <SELECT> */
	public function getMinutesListCurActive(){
		$selected = is_null($this->_curDate) ? null : date('i', $this->_curDate);
		$output = '';
		for($i = 0; $i <= 59; $i++)
			$output .= '<option value="'.$i.'"'.($i == $selected ? ' selected="selected"' : '').'>'.sprintf('%02d', $i).'</option>'."\n";
		return $output;
	}
	
	/** ПОЛУЧИТЬ HTML СПИСОК ЧАСОВ <SELECT> */
	public function getHoursListCurActive(){
		$selected = is_null($this->_curDate) ? null : date('H', $this->_curDate);
		$output = '';
		for($i = 0; $i <= 23; $i++)
			$output .= '<option value="'.$i.'"'.($i == $selected ? ' selected="selected"' : '').'>'.sprintf('%02d', $i).'</option>'."\n";
		return $output;
	}

	/** ПОЛУЧИТЬ HTML СПИСОК ДНЕЙ МЕСЯЦА ДЛЯ ТЭГА <SELECT> */
	public function getDaysListCurActive(){
		$selected = is_null($this->_curDate) ? null : date('d', $this->_curDate);
		$output = '';
		for($i = 1; $i <= 31; $i++)
			$output .= '<option value="'.$i.'"'.($i == $selected ? ' selected="selected"' : '').'>'.$i.'</option>'."\n";
		return $output;
	}
	
	/** ПОЛУЧИТЬ HTML СПИСОК МЕСЯЦЕВ (РОДИТЕЛЬНЫЙ ПАДЕЖ) ДЛЯ ТЭГА <SELECT> */
	public function getMonthsListCurActive(){
		$selected = is_null($this->_curDate) ? null : date('n', $this->_curDate);
		$output = '';
		foreach(self::$_monthsGenFull as $index => $name)
			$output .= '<option value="'.$index.'"'.($index == $selected ? ' selected="selected"' : '').'>'.$name.'</option>'."\n";
		return $output;
	}
	
	/** ПОЛУЧИТЬ HTML СПИСОК МЕСЯЦЕВ (ИМЕНИТЕЛЬНЫЙ ПАДЕЖ) ДЛЯ ТЭГА <SELECT> */
	public function getNomMonthsListCurActive(){
		$selected = is_null($this->_curDate) ? null : date('n', $this->_curDate);
		$output = '';
		foreach(self::$_monthsNomFull as $index => $name)
			$output .= '<option value="'.$index.'"'.($index == $selected ? ' selected="selected"' : '').'>'.$name.'</option>'."\n";
		return $output;
	}
	
	/** ПОЛУЧИТЬ HTML СПИСОК ЛЕТ ДЛЯ ТЭГА <SELECT> */
	public function getYearsListCurActive($startYear = null, $endYear = null){
		$selected = is_null($this->_curDate) ? null : date('Y', $this->_curDate);
		$output = '';
		$startVal = !is_null($startYear) ? $startYear : date('Y');
		$endVal = !is_null($endYear) ? $endYear : ($startVal - 100);
		for($i = $startVal; $i >= $endVal; $i--)
			$output .= '<option value="'.$i.'"'.($i == $selected ? ' selected="selected"' : '').'>'.$i.'</option>'."\n";
		return $output;
	}

	// ПОЛУЧИТЬ НАЗВАНИЕ МЕСЯЦА ПО ИНДЕКСУ
	public static function getMonthName($index){
		
		$index = (int)$index; // преобразует 01 в 1
		return (isset(self::$_monthsNomFull[$index]) ? self::$_monthsNomFull[$index] : '');
	}
	
	public static function getMonthFullName($index){
		
		$index = (int)$index; // преобразует 01 в 1
		return (isset(self::$fullMonths[$index]) ? self::$fullMonths[$index] : '');
	}
	
	// ПОЛУЧИТЬ ИНДЕКС МЕСЯЦА ПО НАЗВАНИЮ
	public static function getMonthIndex($name){
		$name = (string)$name;
		return (int)(in_array($name, self::$_monthsNomFull) ? array_search($name, self::$_monthsNomFull) : 0);
	}
	
	// ПЕРЕВОД TIMESTAMP В ТЕКСТОВОЕ ЗНАЧЕНИЕ ДАТЫ
	public static function timestamp2date($t, $part = self::STR_DATE_TIME, $defatult = ' - '){
	
		$t = (int)$t;
		if(!$t)	return $defatult;
		
		if($part == self::STR_TIME)
			return date("H:i:s", $t);
		elseif($part == self::STR_DATE)
			return date('j', $t).' '.self::getMonthName(date("n", $t)).' '.date('Y', $t);
		elseif($part == self::STR_DATE_TIME)
			return date('j', $t).' '.self::getMonthName(date("n", $t)).' '.date('Y H:i:s', $t);
		else
			Error::fatal_error('неверный формат даты "'.$part.'"');
	}

	// ПЕРЕВОД ДАТЫ РОЖДЕНИЯ В СТРОКУ 'YYYY-MM-DD'
	public static function ymd2date($year, $month, $day){

		return sprintf("%04d-%02d-%02d", substr($year, 0, 4), substr($month, 0, 2), substr($day, 0, 2));
	}

	// ПЕРЕВОД ДАТЫ В СТРОКУ 'YYYY-MM-DD'
	public static function ymdArr2date($arr){

		$arr = $arr + array('day' => 0, 'month' => 0, 'year' => 0);
		return sprintf("%04d-%02d-%02d", substr($arr['year'], 0, 4), substr($arr['month'], 0, 2), substr($arr['day'], 0, 2));
	}
	
	// ПЕРЕВОД ДАТЫ ИЗ ФОРМАТА 'гггг-мм-дд' В СТРОКУ 'число месяц год'
	public static function date2string($date){
		
		$dateArr = explode('-', $date) + array('', '', '');
		$year = $dateArr[0];
		$month = !empty($dateArr[1]) ? self::getMonthName($dateArr[1]) : '';
		$day = $dateArr[2];
		
		return $day.' '.$month.' '.$year;
	}
	
	// ПЕРЕВОД ДАТЫ ИЗ ФОРМАТА 'гггг-мм-дд' В МАССИВ array('day' => day, 'month' => month, 'year' => year)
	public static function date2array($date, $intMode = false){
		
		$dateArr = explode('-', $date) + array('', '', '');
		$year = $dateArr[0];
		$month = !empty($dateArr[1])
			? ($intMode ? (int)$dateArr[1] : self::getMonthName($dateArr[1]))
			: '';
		$day = $intMode ? (int)$dateArr[2] : $dateArr[2];
		
		return array('day' => $day, 'month' => $month, 'year' => $year);
	}
	
	// ПЕРЕВОД ДАТЫ, РАЗДЕЛЕННОЙ СЕПАРАТОРОМ В ЧЕЛОВЕКОЧИТАЕМЫЙ ФОРМАТ
	public static function sepNum2date($num, $sep = '-', $toString = FALSE){
		$num = (string)$num;
		if(strlen($num) != 10)
			return array('day' => '', 'month' => '', 'year' => '');
		$numArr = explode($sep, $num);
		$day   = isset($numArr[2]) ? $numArr[2] : '';
		$month = isset($numArr[1]) ? self::getMonthName($numArr[1]) : '';
		$year  = isset($numArr[0]) ? $numArr[0] : '';
		return $toString
			? $day.' '.$month.' '.$year
			: array('day' => $day, 'month' => $month, 'year' => $year);
	}
	
	// ПОЛУЧИТЬ РАЗНИЦУ МЕЖДУ ДВУМЯ ДАТАМИ
	public static function dateDiff($timestamp1, $timestamp2, $toString = FALSE){
		
		$diff = abs($timestamp1 - $timestamp2);
		$result = array(
			'years' => floor($diff / 31104000),
			'months' => floor(($diff % 31104000) / 2592000),
			'days' => floor(($diff % 2592000) / 86400),
			'hours' => floor(($diff % 86400) / 3600),
			'minutes' => floor(($diff % 3600) / 60),
			'seconds' => $diff % 60,
		);
		if($toString){
			foreach($result as $k => $v){
				if($v == 0)
					unset($result[$k]);
				else
					break;
			}
			if(isset($result['years']))
				$result['years'] = $result['years'].' лет';
			if(isset($result['months']))
				$result['months'] = $result['months'].' месяцев';
			if(isset($result['days']))
				$result['days'] = $result['days'].' дней';
			if(isset($result['hours']))
				$result['hours'] = $result['hours'].' часов';
			if(isset($result['minutes']))
				$result['minutes'] = $result['minutes'].' минут';
			if(isset($result['seconds']))
				$result['seconds'] = $result['seconds'].' секунд';
			return implode(', ', $result);
				
		}else{
			return $result;
		}
	}

	// ПОЛУЧИТЬ HTML СПИСОК МИНУТ ДЛЯ ТЭГА <SELECT>
	public static function getMinutesList($selected = 0){
		$output = '';
		for($i = 0; $i <= 59; $i++)
			$output .= '<option value="'.$i.'"'.($i == $selected ? ' selected' : '').'>'.sprintf('%02d', $i).'</option>';
		return $output;
	}
	
	// ПОЛУЧИТЬ HTML СПИСОК ЧАСОВ <SELECT>
	public static function getHoursList($selected = 0){
		$output = '';
		for($i = 0; $i <= 23; $i++)
			$output .= '<option value="'.$i.'"'.($i == $selected ? ' selected' : '').'>'.sprintf('%02d', $i).'</option>';
		return $output;
	}

	// ПОЛУЧИТЬ HTML СПИСОК ДНЕЙ МЕСЯЦА ДЛЯ ТЭГА <SELECT>
	public static function getDaysList($selected = 0){
	
		$output = '';
		for($i = 1; $i <= 31; $i++)
			$output .= '<option value="'.$i.'"'.($i == $selected ? ' selected' : '').'>'.$i.'</option>';
		return $output;
	}
	
	// ПОЛУЧИТЬ HTML СПИСОК МЕСЯЦЕВ ДЛЯ ТЭГА <SELECT>
	public static function getMonthsList($selected = ''){
		$output = '';
		foreach(self::$_monthsNomFull as $index => $name)
			$output .= '<option value="'.$index.'"'.(($index == $selected || $name == $selected) ? ' selected' : '').'>'.$name.'</option>';
		return $output;
	}
	
	// ПОЛУЧИТЬ HTML СПИСОК ЛЕТ ДЛЯ ТЭГА <SELECT>
	public static function getYearsList($selected = 0){
		$output = '';
		$startVal = date('Y');
		$endVal = $startVal - 100;
		for($i = $startVal; $i >= $endVal; $i--)
			$output .= '<option value="'.$i.'"'.($i == $selected ? ' selected' : '').'>'.$i.'</option>';
		return $output;
	}
	
	// ПОЛУЧИТЬ TIMESTAMP ОПРЕДЕЛЕННЫХ СУТОК
	public static function mktime($param, $month = null, $day = null){
		if(is_array($param)){
			$year = $param[0];
			$month = $param[1];
			$day = $param[2];
		}else{
			$year = $param;
		}
		return mktime(12, 0, 0, $month, $day, $year);
	}
	
	
}
