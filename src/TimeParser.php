<?php
namespace wapmorgan\TimeParser;

use DateTimeImmutable;
use Exception;

class TimeParser {
	protected $languages;
	protected $languagesRules = array();
	protected $allowAlphabeticUnits = false;

	static protected $months = array(
		'january' => 1,
		'february' => 2,
		'march' => 3,
		'april' => 4,
		'may' => 5,
		'june' => 6,
		'july' => 7,
		'august' => 8,
		'september' => 9,
		'october' => 10,
		'november' => 11,
		'december' => 12,
	);

	static private $debug = false;
	static private $wordsToNumber = null;

	static public function enableDebug() {
		self::$debug = true;
	}

	static public function disableDebug() {
		self::$debug = false;
	}

	static public function debugging() {
		return self::$debug;
	}

	static public function setWordsToNumberCallback(callable $callback) {
		self::$wordsToNumber = $callback;
	}

	public function __construct($languages = null) {
		if (!empty($languages))
			$this->languages = $languages;
		$this->populateLanguageRules();
	}

	public function allowAlphabeticUnits() {
		$this->allowAlphabeticUnits = true;
	}

	public function disallowAlphabeticUnits() {
		$this->allowAlphabeticUnits = false;
	}

	protected function populateLanguageRules() {
		// collect rules
		$available_languages = array_map(function ($lang) {
			return strtolower(basename($lang, '.json'));
		}, glob(dirname(__FILE__).'/../rules/*.json'));

		if ($this->languages !== 'all') {
			if (is_array($this->languages)) {
				$available_languages = array_intersect($this->languages, $available_languages);
				if (!empty($available_languages)) {
					DebugStream::show('Selected languages: '.implode(', ', $available_languages).PHP_EOL);
				} else {
					// DebugStream::show('Parsing with default strtotime()'.PHP_EOL);
					throw new Exception('Unknown languages used: '.implode(',', $this->languages));
					// return self::parseWithStrtotime($string);
				}
			} else {
				if (in_array($this->languages, $available_languages)) {
					$available_languages = array($this->languages);
					DebugStream::show('Selected language: '.$this->languages.PHP_EOL);
				} else {
					// DebugStream::show('Parsing with default strtotime()'.PHP_EOL);
					throw new Exception('Unknown language used: '.$this->languages);
					// return self::parseWithStrtotime($string);
				}
			}
		}

		foreach ($available_languages as $language) {
			$this->languagesRules[$language] = self::populateRules($language);
		}
	}

	public function parse($string, $falseWhenNotChanged = false, &$result = null) {
		$string = self::prepareString($string);
		$datetime = $current_datetime = new DateTimeImmutable();

		// apply rules
		foreach ($this->languagesRules as $language) {
			foreach ($language->rules as $rule_type => $rules) {
				foreach ($rules as $rule_name => $rule_regex) {
					if (self::match($rule_regex, $string, $matches)) {
						DebugStream::show('Matched: '.$rule_regex.PHP_EOL);
						if ($rule_type == 'absolute') {
							switch ($rule_name) {
								case 'date':
									$month = $language->translateMonth($matches['month'][0]);
									if (!empty($matches['year'][0]))
										$year = $matches['year'][0];
									else
										$year = $datetime->format('Y');
									if (!empty($matches['digit'][0])) {
										$day = $matches['digit'][0];
										DebugStream::show('Set date: '.$year.'-'.$month.'-'.$day.PHP_EOL);
										$datetime = $datetime->setDate((int)$year, self::$months[$month], (int)$day);
									} else if ($this->allowAlphabeticUnits) {
										$alpha = $language->translateUnit($matches['alpha'][0]);
										if (is_numeric($alpha)) {
											DebugStream::show('Set date: '.$year.'-'.$month.'-'.$alpha.PHP_EOL);
											$datetime = $datetime->setDate((int)$year, self::$months[$month], (int)$alpha);
										}
										// parse here alphabetic value
									}
									break;
								case 'time':
									if (!empty($matches['sec'])) {
										$datetime = $datetime->setTime((int)$matches['hour'][0], (int)$matches['min'][0], (int)$matches['sec'][0]);
										DebugStream::show('Set time: '.$matches['hour'][0].':'.$matches['min'][0].':'.$matches['sec'][0].PHP_EOL);
									} else {
										$datetime = $datetime->setTime((int)$matches['hour'][0], (int)$matches['min'][0]);
										DebugStream::show('Set time: '.$matches['hour'][0].':'.$matches['min'][0].PHP_EOL);
									}
									break;
								case 'weekday':
									if (!empty($matches['pronoun']) && ($pronoun = $language->translatePronoun($matches['pronoun'][0])) == 'next') {
										$weekday = $language->translateWeekDay($matches['weekday'][0]);
										$time = strtotime('next week '.$weekday);
										DebugStream::show('Set weekday: next '.$weekday.PHP_EOL);
									} else {
										$weekday = $language->translateWeekDay($matches['weekday'][0]);
										$time = strtotime('this week '.$weekday);
										DebugStream::show('Set weekday: this '.$weekday.PHP_EOL);
									}
									$date = explode('.', date('d.m.Y', $time));
									$datetime = $datetime->setDate((int)$date[2], (int)$date[1], (int)$date[0]);
									break;
								case 'year':
									if (!empty($matches['pronoun'][0])) {
										$pronoun = $language->translatePronoun($matches['pronoun'][0]);
										if ($pronoun == 'next') {
											$datetime = $datetime->modify('+1 year');
											DebugStream::show('Set year: +1'.PHP_EOL);
										}
									} else {
										$year = $matches['digit'][0];
										$datetime = $datetime->setDate((int)$year, (int)$datetime->format('m'), (int)$datetime->format('d'));
										DebugStream::show('Set year: '.$year.PHP_EOL);
									}
									break;
								case 'month':
									$pronoun = $language->translatePronoun($matches['pronoun'][0]);
									if ($pronoun == 'next') {
										$datetime = $datetime->modify('+1 month');
										DebugStream::show('Set month: +1'.PHP_EOL);
									} else {
										$month = $language->translateMonth($matches['month'][0]);
										DebugStream::show('Set month: '.$month.PHP_EOL);
										$datetime = $datetime->modify($month);
									}
									break;
								case 'week':
									$pronoun = $language->translatePronoun($matches['pronoun'][0]);
									if ($pronoun == 'next') {
										$datetime = $datetime->modify('+1 week');
										DebugStream::show('Set week: +1'.PHP_EOL);
									}
									break;
							}
						} else if ($rule_type == 'relative') {
							$digit = isset($matches['digit'][0]) ? $matches['digit'][0] : '';
							$alpha = isset($matches['alpha'][0]) ? $matches['alpha'][0] : '';

							if ($digit === '' && $alpha === '') {
								$digit = 1;
							}

							switch ($rule_name) {
								case 'hour':
								case 'minute':
								case 'sec':
								case 'year':
								case 'week':
								case 'day':
								case 'month':
									if ($alpha !== '' && $this->allowAlphabeticUnits) {
										$digit = $language->translateUnit($alpha);

										if (!is_numeric($digit)) {
											if (is_callable(self::$wordsToNumber)) {
												$digit = call_user_func(self::$wordsToNumber, $alpha, $rule_name);
											} else {
												$alpha = strtr($alpha, $language->units);
												$parts = array_filter(array_map(
													function ($val) {
														return floatval($val);
													},
													preg_split('/[\s-]+/', $alpha)
												));

												$digit = array_sum($parts);
											}
										}
									}

									if ($digit && is_numeric($digit)) {
										DebugStream::show('Add offset: +'.$digit.' '.$rule_name.PHP_EOL);
										$datetime = $datetime->modify('+'.$digit.' '.$rule_name);
									}

									break;
							}
						}
					}
				}
			}
		}

		$result = $string;

		if ($datetime === $current_datetime && $falseWhenNotChanged)
			return false;
		return $datetime;
	}

	static public function parseString($string, $languages = 'all', $allowAlphabeticUnits = false, $falseWhenNotChanged = false, &$result = null) {
		static $parsers = array();
		if (!isset($parser[is_array($languages) ? implode(',', $languages) : $languages])) {
			$parsers[is_array($languages) ? implode(',', $languages) : $languages] = new self($languages);
			if ($allowAlphabeticUnits) $parsers[is_array($languages) ? implode(',', $languages) : $languages]->allowAlphabeticUnits();
		}
		return $parsers[is_array($languages) ? implode(',', $languages) : $languages]->parse($string, $falseWhenNotChanged, $result);
	}

	/**
	 * @param string $regex Regular expression to match
	 * @param string $string String to match. Will be changed if matched.
	 * @param array $matches Matched data.
	 * @return boolean true if match found
	 */
	static private function match($regex, &$string, &$matches) {
		if (preg_match($regex, $string, $matches, PREG_OFFSET_CAPTURE)) {
			$string = substr($string, 0, $matches[0][1]).substr($string, $matches[0][1] + strlen($matches[0][0]));
			return true;
		} else {
			return false;
		}
	}

	static private function parseWithStrtotime($string) {
		$datetime = new DateTime;
		$time = strtotime($string);
		if ($time === false) {
			DebugStream::show('strtotime() failed'.PHP_EOL);
			return $datetime;
		} else {
			DebugStream::show('strtotime() returned: '.$time.PHP_EOL);
			$datetime->setTimestamp($time);
			return $datetime;
		}
	}

	static private function populateRules($language) {
		$data = json_decode(file_get_contents(dirname(dirname(__FILE__)).DIRECTORY_SEPARATOR.'rules'.DIRECTORY_SEPARATOR.$language.'.json'), true);
		if ($data === null) {
			throw new Exception(json_last_error());
		}
		return new Language($data['language'], $data['rules'], $data['week_days'], $data['pronouns'], $data['months'], $data['units']);
	}

	static private function prepareString($string) {
		if (function_exists('mb_strtolower')) {
			if (($encoding = mb_detect_encoding($string)) != 'UTF-8')
				$string = mb_convert_encoding($string, 'UTF-8', $encoding);
			$string = mb_strtolower($string);
		} else
			$string = strtolower($string);
		$string = preg_replace('~[[:space:]]{1,}~', ' ', $string);
		return $string;
	}
}
