<?php

/**
 * Скрипт обрабатывает консольные команды.
 * Возвращает массив из команды, ключей и их значений
 *
 * Формат команды
 * command[ --key[ value][ --key[ value]]...]
 * - Имя комманды может состоять только из латинских букв маленького регистра
 * - Ключи длинные до 64-х символов
 * - Значение ключа, если
 */
class console
{

	/**
	 * Разобранная команда
	 * @var array
	 */
	public $chunks = array();

	/**
	 * Пары ключ-значение
	 * @var Array<String>
	 */
	protected $key_values = array();

	/**
	 * Имя команды
	 * @var String
	 */
	protected $cmd_name = '';

	/**
	 * Содержит код последней ошибки
	 * @var int
	 */
	protected $last_err = 0;
	public static $COMMAND_NOT_FOUND = 1;
	public static $INVALID_SYNTAX = 2;

	/**
	 * Разбирает команду на кусочки
	 * @param String строка с командой
	 * @return bool|array
	 */
	function parse($cmd)
	{
		$cmd = rtrim($cmd);
		$cmd_len = strlen($cmd);
		$i = 0;
		// Ищем имя команды
		for (; $i < $cmd_len; $i++)
		{
			if (ord($cmd[$i]) < 123 && ord($cmd[$i]) > 96)
			{
				// Читаем имя команды
				$this->cmd_name .= $cmd[$i];
			}
			elseif ($cmd[$i] == ' ' || $i + 1 == $cmd_len)
			{
				// Пробел, терминатор строки. Имя команды закончилось
				break;
			}
			else
			{
				// Ошибка в названии команды. Команда может быть только в нижнем
				// регистре и без спец. символов
				$this->set_error(self::$COMMAND_NOT_FOUND);
				break;
			}
		}
		// Проверяем мол есть ли такая команда
		if ($this->errors())
		{
			return $this->get_error_code();
		}
		// Ищем ключи и значения
		$z = -1;
		/**
		 * Говорит, что мы находимся или нет в кавычках (чтобы исключить
		 * случайное определение пары-значения конструкции вида
		 * [-q "Lorem ipsum] [-q amete sit dollar"]
		 *     [          на самом деле          ]
		 */
		$quoted = false;
		for (; $i < $cmd_len; $i++)
		{
			if ($cmd[$i] == '-')
			{
				// Увеличиваем число пар
				$z++;
				$i+=2;
				$quoted = false;
				array_push($this->key_values, '--');
				// Начало блока
				while ($i < $cmd_len)
				{
					if ($cmd[$i] == '-' && !$quoted)
					{
						// Пара ключ значение есть
						// Откатываем дефис
						$i--;
						break;
					}
					$quoted = $cmd[$i] == '"' ? !$quoted : $quoted;
					// Читаем пару ключ-значение
					$this->key_values[$z] .= $cmd[$i];
					$i++;
				}
			}
		}
		// Сохраняем всё в объект
		$this->chunks['name'] = $this->cmd_name;
		$this->chunks['key_values'] = array();
		// Да не забудем отпарсить пары
		foreach ($this->key_values as $pair)
		{
			$this->chunks['key_values'] = array_merge($this->parse_key_value($pair), $this->chunks['key_values']);
			if ($this->errors())
			{
				$this->chunks = null;
				return $this->get_error_code();
			}
		}
		// Усё.
		return 0;
	}

	/**
	 * Возвращает код последней ошибки
	 * @return int
	 */
	public function get_error_code()
	{
		return $this->last_err;
	}

	/**
	 * Возвращает человекочитаемую характеристику ошибки
	 * @param int Код ошибки
	 * @return String
	 */
	public function get_error($error_code)
	{
		switch ($error_code)
		{
			case self::$COMMAND_NOT_FOUND :
				return 'Command is not found';
				break;
			case self::$INVALID_SYNTAX :
				return 'Invalid syntax being used';
				break;
			default :
				return 'Unknown error';
		}
	}

	private function set_error($err_code)
	{
		$this->last_err = $err_code;
	}

	/**
	 * Проверяет наличие ошибок
	 * @return bool
	 */
	private function errors()
	{
		return $this->last_err == 0 ? false : true;
	}

	/**
	 * Проверяет наличие команды в реестре
	 * @param String Имя команды
	 * @return bool
	 */
	private function check_cmd($cmd_name = '')
	{
		return true;
	}

	/**
	 * Разбириает пару ключ-значение на ключ и на значение
	 * @param String Пара ключ-значение
	 * @return Array
	 */
	private function parse_key_value($kv)
	{
		$matches = array();
		if (!preg_match('/^-{2}([a-z]{1,64}) *"?([^"]*)"?$/u', rtrim($kv), $matches))
		{
			$this->set_error(self::$INVALID_SYNTAX);
			return false;
		}

		// normalize matches
		return [
			$matches[1] => $matches[2],
		];
	}

}

?>