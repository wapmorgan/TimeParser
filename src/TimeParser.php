<?php
namespace wapmorgan\TimeParser;

use DateTimeImmutable;
use Exception;

class TimeParser {
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
		$this->populateLanguageRules($languages);
	}

	public function allowAlphabeticUnits() {
		$this->allowAlphabeticUnits = true;
	}

	public function disallowAlphabeticUnits() {
		$this->allowAlphabeticUnits = false;
	}

	protected function populateLanguageRules($languages = null) {
		// collect rules
		$availableLanguages = array_map(function ($lang) {
			return strtolower(basename($lang, '.json'));
		}, glob(dirname(__FILE__).'/../rules/*.json'));

		if ($languages !== null && $languages !== 'all') {
			if (!is_array($languages)) {
				$languages = [$languages];
			}

			$availableLanguages = array_intersect($languages, $availableLanguages);

			if (empty($availableLanguages)) {
				throw new Exception(sprintf('Unknown language used: %s', 
					implode(', ', array_diff($languages, $availableLanguages))
				));
			}
		}

		foreach ($availableLanguages as $language) {
			$data = json_decode(file_get_contents(dirname(dirname(__FILE__)).DIRECTORY_SEPARATOR.'rules'.DIRECTORY_SEPARATOR.$language.'.json'), true);

			if (json_last_error() !== JSON_ERROR_NONE) {
				throw new Exception(json_last_error_msg());
			}
			
			$this->addLanguage($data);
		}
	}

	public function addLanguage(array $data)
	{
		if (!isset($data['language']) 
			|| !is_string($data['language'])
			|| !preg_match('/^[a-z]+((\s|\/)[a-z]+)?$/ui', $data['language'])
		) {
			throw new Exception('Wrong language name given');
		}

		if (!isset($data['rules']['absolute']) || !is_array($data['rules']['absolute'])) {
			throw new Exception('"rules.absolute" must be an array');
		}

		if (!isset($data['rules']['relative']) || !is_array($data['rules']['relative'])) {
			throw new Exception('"rules.relative" must be an array');
		}

		if (!isset($data['week_days']) || !is_array($data['week_days'])) {
			throw new Exception('"week_days" must be an array');
		}

		if (!isset($data['pronouns']) || !is_array($data['pronouns'])) {
			throw new Exception('"pronouns" must be an array');
		}

		if (!isset($data['months']) || !is_array($data['months'])) {
			throw new Exception('"months" must be an array');
		}

		if (!isset($data['units']) || !is_array($data['units'])) {
			throw new Exception('"months" must be an array');
		}

		$name = mb_strtolower($data['language']);
		$lang = new Language($data['language'], $data['rules'], $data['week_days'], $data['pronouns'], $data['months'], $data['units']);

		$this->languagesRules[$name] = $lang;
	}

	public function parse($string, $falseWhenNotChanged = false, &$result = null) {
		$string = self::prepareString($string);
		$datetime = $currentDatetime = new DateTimeImmutable();

		// apply rules
		foreach ($this->languagesRules as $name => $language) {
			foreach ($language->rules as $ruleType => $rules) {
				foreach ($rules as $ruleName => $ruleRegex) {
					if (self::match($ruleRegex, $string, $matches)) {
						DebugStream::show('Matched: '.$ruleRegex.PHP_EOL);
						if ($ruleType == 'absolute') {
							switch ($ruleName) {
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
						} else if ($ruleType == 'relative') {
							$digit = isset($matches['digit'][0]) ? $matches['digit'][0] : '';
							$alpha = isset($matches['alpha'][0]) ? $matches['alpha'][0] : '';

							if ($digit === '' && $alpha === '') {
								$digit = 1;
							}

							if ($alpha !== '' && $this->allowAlphabeticUnits) {
								$digit = $language->translateUnit($alpha);

								if (!is_numeric($digit)) {
									if (is_callable(self::$wordsToNumber)) {
										$digit = call_user_func(self::$wordsToNumber, $alpha, $name);
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
								if (preg_match('/^[a-z]+$/', $ruleName)) {
									$modify = "+{$digit} {$ruleName}";
								} else {
									$modify = str_replace('$1', $digit, $ruleName);
								}

								if (preg_match('/^[\+\-]\d+ [a-z]+$/', $modify)) {
									DebugStream::show('Add offset: '.$modify.PHP_EOL);
									$datetime = $datetime->modify($modify);
								}
							}
						}
					}
				}
			}
		}

		$result = trim(preg_replace(['/^[\pZ\pC]+|[\pZ\pC]+$/u', '/[\pZ\pC]{2,}/u'], ['', ' '], $string));

		if ($datetime === $currentDatetime && $falseWhenNotChanged)
			return false;
		return $datetime;
	}

	static public function parseString($string, $languages = 'all', $allowAlphabeticUnits = false, $falseWhenNotChanged = false, &$result = null) {
		static $parsers = array();

		$key = is_array($languages) ? implode(',', $languages) : $languages;

		if (!isset($parsers[$key])) {
			$parsers[$key] = new self($languages);
			if ($allowAlphabeticUnits) $parsers[$key]->allowAlphabeticUnits();
		}
		return $parsers[$key]->parse($string, $falseWhenNotChanged, $result);
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
