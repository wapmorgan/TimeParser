<?php
require __DIR__.'/vendor/autoload.php';

//wapmorgan\TimeParser\TimeParser::enableDebug();
$datetime = wapmorgan\TimeParser\TimeParser::parse('в 15:12:13 в следующий понедельник в следующем году в феврале через 15 часов через 10 минут через 11 секунд через 5 лет через 2 недели через 1 день через 10 месяцев', 'russian');
var_dump($datetime->format('r'));
$datetime = wapmorgan\TimeParser\TimeParser::parse('at 15:12:13 next monday next year in february in 15 hours in 10 minutes in 11 seconds in 5 years in 2 weeks in 1 day in 10 months', 'english');
var_dump($datetime->format('r'));
$datetime = wapmorgan\TimeParser\TimeParser::parse('15:12:13 prochaine lundi prochaine année février bout de 15 heures bout de 10 minutes bout de 11 secondes bout de 5 années bout de 2 semaines bout de 1 jour bout de 10 mois', 'french');
var_dump($datetime->format('r'));
$datetime = wapmorgan\TimeParser\TimeParser::parse('15:12:13 nächsten montag nächsten jahr im februar nach 15 uhr nach 10 minuten nach 11 secunden nach 5 jahre nach 2 wochen nach 1 tag nach 10 monate', 'german');
var_dump($datetime->format('r'));
$datetime = wapmorgan\TimeParser\TimeParser::parse('15:12:13 下星期一 下年 二月 経由15小时 経由10分钟 経由11秒钟 経由5年 経由2星期 経由1天 経由10月', 'chinese');
var_dump($datetime->format('r'));
