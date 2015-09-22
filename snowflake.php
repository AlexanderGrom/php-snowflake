<?php

/**
 * Генератор идентификаторов по мотивам Twitter Snowflake.
 *
 * Генерирует 64-битный идентификатор
 *  - 1bit  под знак
 *  - 41bit под отметку времени в микросекундах
 *  - 10bit под номер машины на котором идет генерация
 *  - 12bit под случайный номер выполнения в одну микросекунду
*/

class Snowflake
{
	/**
	 * TimeStamp начала эпохи генерации идентификаторов
	 * Установлен в 01.01.2011 00:00:00
	 * Установите свой, например, дату начала разработки проекта
	 *
	 * @var integer микросекунды
	 */
	private $epochTimeStamp = 1293840000000;

	 /**
	 * Максимальный TimeStamp 41 bit (2^41)
	 *
	 * @var integer микроссекунды
	 */
	private $maxTimeStamp = 2199023255551;

	/**
	 * Генерирует ID основываясь на текущем времени, номере машины и случайном номере выполнения
	 *
	 * @param integer $machine номер машины на котором идет генерация ID (10 bit / 1024 машины)
	 * @return string
	 */
	public function generate($machine)
	{
		// Вычитаем epochTimeStamp, что бы не растрачивать драгоценные биты
		$timestamp = floor(microtime(true) * 1000) - $this->epochTimeStamp;

		if($timestamp > $this->maxTimeStamp) {
			throw new Exception('Snowflake: TimeStamp overflow. Unable to generate any more IDs');
		}

		if($machine < 1 || $machine > 1023) {
			throw new Exception('Snowflake: Machine ID out of range');
		}

		// Случайный порядковый номер выполнения - 12 bit / 4096 числел
		$sequence = mt_rand(0, 4095);

		if(PHP_INT_SIZE == 4) {
			return $this->makeId32($timestamp, $machine, $sequence);
		} else {
			return $this->makeId64($timestamp, $machine, $sequence);
		}
	}

	private function makeId32($timestamp, $machine, $sequence)
	{
		$timestamp = gmp_mul((string)$timestamp, gmp_pow(2, 22));
		$machine = gmp_mul((string)$machine, gmp_pow(2, 12));
		$sequence = gmp_init((string)$sequence, 10);

		$value = gmp_or(gmp_or($timestamp, $machine), $sequence);

		return gmp_strval($value, 10);
	}

	private function makeId64($timestamp, $machine, $sequence)
	{
		// 22bit и 12bit это (64bit-1bit-41bit=22bit) и (64bit-1bit-41bit-10bit=12bit)
		$value = ((int)$timestamp << 22) | ($machine << 12) | $sequence;

		return (string)$value;
	}
}

?>
