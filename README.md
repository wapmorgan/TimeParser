TimeParser - is a parser for date and time written in natural language for PHP.

[![Composer package](http://xn--e1adiijbgl.xn--p1acf/badge/wapmorgan/time-parser)](https://packagist.org/packages/wapmorgan/time-parser) [![Latest Stable Version](https://poser.pugx.org/wapmorgan/time-parser/v/stable)](https://packagist.org/packages/wapmorgan/time-parser) [![Total Downloads](https://poser.pugx.org/wapmorgan/time-parser/downloads)](https://packagist.org/packages/wapmorgan/time-parser) [![Latest Unstable Version](https://poser.pugx.org/wapmorgan/time-parser/v/unstable)](https://packagist.org/packages/wapmorgan/time-parser) [![License](https://poser.pugx.org/wapmorgan/time-parser/license)](https://packagist.org/packages/wapmorgan/time-parser)

## How to use
1. Install through composer:
	`composer require wapmorgan/time-parser`

2. Parse some input from user and receive a `DateTime` object.
	```php
	$datetime = wapmorgan\TimeParser\TimeParser::parse(fgets(STDIN), 'all');
	```

	Second arg is a language. Applicable values:
	* `'all'` - scan for all available languages. Use it when you can not predict user's preferred language.
	* `'LANG'` - scan only as time written in LANG.
	* `array('LANG1', 'LANG2')` - scan as LANG1 and then the rest as LANG2.
	* `'strtotime'` - force scan with system strtotime() function. In case of strtotime() scan failure, current DateTime will be returned.

    All available languages:

    | Language | Example                                                                                                                                                                                   |
    |----------|-------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
    | chinese  | 15:12:13 下星期一 下年 二月 経由15小时 経由10分钟 経由11秒钟 経由5年 経由2星期 経由1天 経由10月                                                                                                     |
    | english  | 15 december 1977 at 15:12:13 next monday next year in february in 15 hours in 10 minutes in 11 seconds in 5 years in 2 weeks in 1 day in 10 months                                        |
    | french   | 15:12:13 prochaine lundi prochaine année février bout de 15 heures bout de 10 minutes bout de 11 secondes bout de 5 années bout de 2 semaines bout de 1 jour bout de 10 mois              |
    | german   | 15:12:13 nächsten montag nächsten jahr im februar nach 15 uhr nach 10 minuten nach 11 secunden nach 5 jahre nach 2 wochen nach 1 tag nach 10 monate                                       |
    | russian  | 15 декабря 1977 года в 15:12:13 в следующий понедельник в следующем году в феврале через 15 часов через 10 минут через 11 секунд через 5 лет через 2 недели через 1 день через 10 месяцев |
    | spanish  | 15:12:13 el próximo lunes en próximo año en febrero en 15 horas en 10 minutos en 11 segundos en 5 años en 2 semanas en 1 día en 10 meses                                                  |


# Examples of dates written in natural language that will work.
```
15 декабря 1977 года в 15:12:13 в следующий понедельник в следующем году в феврале через 15 часов через 10 минут через 11 секунд через 5 лет через 2 недели через 1 день через 10 месяцев
15 december 1977 at 15:12:13 next monday next year in february in 15 hours in 10 minutes in 11 seconds in 5 years in 2 weeks in 1 day in 10 months
```

They both will be parsed and result DateTime will contain right date:
```
Sun, 03 Oct 2021 06:22:24 +0400
```

## Languages support
For this moment four languages supported: Russian, English, French and German. Two languages support is in progress: Chinese, Spanish.
Their rules are in `rules` catalog so you can improve TimeParser by adding new language or by improving existing one.
For developing reasons you may would like to see process of parsing. To do this call related methods:

```php
TimeParser::enableDebug();
// and
TimeParser::disableDebug();
```

## Parsable substrings
To understand, how it works, look at substrings separately:

* **15 december 1977** - absolute date
* **at 15:12:13** - absolute time
* **next monday** or **this friday** - absolute date
* **next year** or **2016 year** - absolute date
* **in february** or **next month** - absolute date
* **next week** - absolute date
* **in 15 hours** - relative time
* **in 10 minutes** - relative time
* **in 11 seconds** - relative time
* **in 2 weeks** - relative date
* **in 1 day** - relative date
* **in 10 months** - relative date

## Alphabetic unit values
**If you set third argument of `parse()` to `true`, TimeParser will be able to parse alphabetic values**:

* **in fifteen hours**
* **in ten minutes**
* **in eleven seconds**
* **in five years**
* **in two weeks**
* **in one day**
* **in ten months**

If you want to disallow users use words instead of numbers, save default settings or set third argument of `parse()` to `false`. In this case alphabetic values will not be parsed.
Numbers up to 20 are available to use.

## Cautions

1. Library is very sensitive to all sorts of rubbish: extra spaces between numbers and prepositions, etc.
2. Implies that the incoming data comes in lower case.

## ToDo

- [ ] Tests.
- [ ] Try to parse combinations: *in 5 hours and 2 minutes*.
- [x] Try to parse alphabetic offsets: *in five hours* and *через пять часов*.

## Languages ToDo

- [x] Chinese - check hieroglyphs.
- [x] Spanish - check prepositions.
- [ ] Portuguese
- [ ] Arabic
- [ ] Korean
