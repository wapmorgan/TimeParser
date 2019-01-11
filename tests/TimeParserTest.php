<?php

use wapmorgan\TimeParser\TimeParser;

class TimeParserTest extends PHPUnit_Framework_TestCase
{
    protected static $parsers = [];

    public static function setUpBeforeClass()
    {
        self::$parsers['russian'] = new TimeParser('russian');
        self::$parsers['english'] = new TimeParser('english');
    }

    /**
     * @dataProvider dataProviderEnglish()
     */
    public function testEnglish($string, $expected, $midnight = false)
    {
        $result = self::$parsers['english']->parse($string, true);

        $this->prepareDate($result, $expected, $midnight);
        $this->assertEquals($expected, $result);
    }

    public function dataProviderEnglish()
    {
        return [
            ['15 december 1977 year', '15 december 1977'],
            ['at 15:12:13', '15:12:13'],
            ['next monday', 'next monday', true],
            ['next year', '+1 year'],
            ['in february', 'february'],
            ['in 15 hours', '+15 hour'],
            ['in 10 minutes', '+10 minutes'],
            ['in 11 seconds', '+11 seconds'],
            ['in 5 years', '+5 years'],
            ['in 2 weeks', '+2 weeks'],
            ['in 1 day', '+1 day'],
            ['in 10 months', '+10 month'],
            ['tomorrow', '+1 day'],
            ['yesterday', '-1 day'],
            ['2 hours ago', '-2 hour'],
            ['10 years ago', '-10 year'],
            ['the string does not contain the date', false],
        ];
    }

    /**
     * @dataProvider dataProviderRussian()
     */
    public function testRussian($string, $expected, $midnight = false)
    {
        $result = self::$parsers['russian']->parse($string, true);

        $this->prepareDate($result, $expected, $midnight);
        $this->assertEquals($expected, $result);
    }

    public function dataProviderRussian()
    {
        return [
            ['15 декабря 1977 года', '15 december 1977'],
            ['в 15:12:13', '15:12:13'],
            ['в следующий понедельник', 'next monday', true],
            ['в следующем году', '+1 year'],
            ['в феврале', 'february'],
            ['через 15 часов', '+15 hour'],
            ['через 10 минут', '+10 minutes'],
            ['через 11 секунд', '+11 seconds'],
            ['через 5 лет', '+5 years'],
            ['через 2 недели', '+2 weeks'],
            ['через 1 день', '+1 day'],
            ['через 10 месяцев', '+10 month'],
            ['завтра', '+1 day'],
            ['вчера', '-1 day'],
            ['2 часа назад', '-2 hour'],
            ['10 лет назад', '-10 year'],
            ['строка не содержит дату', false],
        ];
    }

    protected function prepareDate(&$result, &$expected, $midnight)
    {
        $date = new DateTimeImmutable();

        if ($result !== false) {
            if ($midnight) {
                $result = $result->setTime(0, 0);
            }

            $result = $result->format('r');
        }

        if ($expected !== false) {
            $expected = $date->modify($expected)->format('r');
        }
    }
}
