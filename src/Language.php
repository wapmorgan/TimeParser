<?php
namespace wapmorgan\TimeParser;

class Language {
	public $name;
	public $rules;
	public $week_days;
	public $pronouns;
	public $months;
	public $units;

	public function __construct($name, $rules, $week_days, $pronouns, $months, $units) {
		$this->name = $name;
		$this->rules = $rules;
		$this->week_days = $week_days;
		$this->pronouns = $pronouns;
		$this->months = $months;
		$this->units = $units;
	}

	public function translatePronoun($pronoun) {
		if (isset($this->pronouns[$pronoun]))
			return $this->pronouns[$pronoun];
		else
			return $pronoun;
	}

	public function translateWeekDay($weekDay) {
		if (isset($this->week_days[$weekDay]))
			return $this->week_days[$weekDay];
		else
			return $weekDay;
	}

	public function translateMonth($month) {
		if (isset($this->months[$month]))
			return $this->months[$month];
		else
			return $month;
	}

	public function translateUnit($unit) {
		if (isset($this->units[$unit]))
			return $this->units[$unit];
		else
			return $unit;
	}
}
