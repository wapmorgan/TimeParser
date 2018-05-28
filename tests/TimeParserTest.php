<?php

use wapmorgan\TimeParser\TimeParser;
use PHPUnit\Framework\TestCase;

class TimeParseTest extends TestCase {
    public function setUp() {
        // TimeParser::enableDebug();
    }
    /**
     * @dataProvider data()
     */
    public function testIterative($language, $substrings) {
        $parser = new TimeParser($language);

        foreach ($substrings as $substring => $dateTimeModifyString) {
            $expected_date = new DateTime();
            if (is_string($dateTimeModifyString)) {
                // var_dump($dateTimeModifyString);
                $expected_date->modify($dateTimeModifyString);
            } else {
                $unix = strtotime($dateTimeModifyString[1]);
                if ($dateTimeModifyString[0] == 'date') {
                    $date = explode('.', date('d.m.Y', $unix));
                    $expected_date->setDate($date[2], $date[1], $date[0]);
                }
            }
            $actual_date = $parser->parse($substring);
            $this->assertEquals($expected_date->format('r'), $actual_date->format('r'));
        }
    }

    public function data() {
        return array(
            array('russian', array(
                '15 декабря 1977 года' => '15 december 1977',
                'в 15:12:13' => '15:12:13',
                'в следующий понедельник' => array('date', 'next monday'),
                'в следующем году' => '+1 year',
                'в феврале' => 'february',
                'через 15 часов' => '+15 hour',
                'через 10 минут' => '+10 minutes',
                'через 11 секунд' => '+11 seconds',
                'через 5 лет' => '+5 years',
                'через 2 недели' => '+2 weeks',
                'через 1 день' => '+1 day',
                'через 10 месяцев' => '+10 month',
            )),
            array('english', array(
                '15 december 1977 year' => '15 december 1977',
                'at 15:12:13' => '15:12:13',
                'next monday' => array('date', 'next monday'),
                'next year' => '+1 year',
                'in february' => 'february',
                'in 15 hours' => '+15 hour',
                'in 10 minutes' => '+10 minutes',
                'in 11 seconds' => '+11 seconds',
                'in 5 years' => '+5 years',
                'in 2 weeks' => '+2 weeks',
                'in 1 day' => '+1 day',
                'in 10 months' => '+10 month',
            ))
        );
    }

    public function testEmptyDateTime() {
        $this->assertFalse(TimeParser::parseString(implode(null, range('a', 'z')), 'all', false, true));
    }
}