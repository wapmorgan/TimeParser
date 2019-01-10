TimeParser
=====
A parser for date and time written in natural language for PHP.

[![Composer package](http://composer.network/badge/wapmorgan/time-parser)](https://packagist.org/packages/wapmorgan/time-parser) [![Latest Stable Version](https://poser.pugx.org/wapmorgan/time-parser/v/stable)](https://packagist.org/packages/wapmorgan/time-parser) [![Total Downloads](https://poser.pugx.org/wapmorgan/time-parser/downloads)](https://packagist.org/packages/wapmorgan/time-parser) [![Latest Unstable Version](https://poser.pugx.org/wapmorgan/time-parser/v/unstable)](https://packagist.org/packages/wapmorgan/time-parser) [![License](https://poser.pugx.org/wapmorgan/time-parser/license)](https://packagist.org/packages/wapmorgan/time-parser) [![Testing](https://travis-ci.org/wapmorgan/TimeParser.svg?branch=master)](https://travis-ci.org/wapmorgan/TimeParser)

1. Installation
2. Usage
3. Languages support
5. ToDo

## Installation
The preferred way to install package is via composer:

```bash
composer require wapmorgan/time-parser
```

## Usage
Parse some input from user and receive a `DateTime` object.

1. Create a Parser object
    ```php
    $parser = new wapmorgan\TimeParser\TimeParser('all');
    ```

    First argument is a language. Applicable values:

    * `'all'` (by default) - scan for all available languages. Use it when you can not predict user's preferred language.
    * `'russian'` - scan only as string written in one language.
    * `array('english', 'russian')` - scan as english and then the rest as russian.

2. Enable and disable parsing of alphabetic values.
    ```php
    // To enable alphabetic parsing.
    $parser->allowAlphabeticUnits();
    // To disable alphabetic parsing.
    $parser->disallowAlphabeticUnits();
    ```

3. Parse string and return a `DateTimeImmutable` object. If second argument is `true`, method will return `false` when no date&time strings found. If third parameter is provided, then it is filled with the string obtained after all the transformations.
    ```php
    $datetime = $parser->parse(fgets(STDIN));
    // next call returns false
    $datetime = $parser->parse('abc', true);
    // $result will contains "we will come "
    $datetime = $parser->parse('We will come in a week', true, $result);
    ```
4. For advanced parsing of alphabetic values is used built-in function. You can specify your own handler for this feature. Сurrently is used for russian and english languages only.
    ```
    use wapmorgan\TimeParser\TimeParser;

    // $string will contains alphabetic value for advanced parsing. 
    // Ex.: for string "in twenty five minutes", it will contains "twenty five".
    // $rule will contains name of the parsed rule: year, month, day etc.
    TimeParser::setWordsToNumberCallback(function($string, $rule) {
        // do some magic
    });
    ```

## Languages support
For this moment four languages supported: Russian, English, French and German. Two languages support is in progress: Chinese, Spanish.
Their rules are in `rules` catalog so you can improve TimeParser by adding new language or by improving existing one.

Languages with examples of strings containing date&time:

| Language | Example                                                                                                                                                                                   |
|----------|-------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| chinese  | 15:12:13 下星期一 下年 二月 経由15小时 経由10分钟 経由11秒钟 経由5年 経由2星期 経由1天 経由10月                                                                                                     |
| english  | 15 december 1977 at 15:12:13 next monday next year in february in 15 hours in 10 minutes in 11 seconds in 5 years in 2 weeks in 1 day in 10 months                                        |
| french   | 15:12:13 prochaine lundi prochaine année février bout de 15 heures bout de 10 minutes bout de 11 secondes bout de 5 années bout de 2 semaines bout de 1 jour bout de 10 mois              |
| german   | 15:12:13 nächsten montag nächsten jahr im februar nach 15 uhr nach 10 minuten nach 11 secunden nach 5 jahre nach 2 wochen nach 1 tag nach 10 monate                                       |
| russian  | 15 декабря 1977 года в 15:12:13 в следующий понедельник в следующем году в феврале через 15 часов через 10 минут через 11 секунд через 5 лет через 2 недели через 1 день через 10 месяцев |
| spanish  | 15:12:13 el próximo lunes en próximo año en febrero en 15 horas en 10 minutos en 11 segundos en 5 años en 2 semanas en 1 día en 10 meses                                                  |

For developing reasons you may would like to see process of parsing. To do this call related methods:

```php
TimeParser::enableDebug();
// and
TimeParser::disableDebug();
```

### Parsable substrings
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
* **через час** - relative date (russian, english only)
* **in a hour** - relative date (russian, english only)
* **через двадцать пять минут** - relative date (russian, english only)
* **in twenty five minutes** - relative date (russian, english only)

## ToDo

- [x] Tests.
- [ ] Try to parse combinations: *in 5 hours and 2 minutes*.
- [x] Try to parse alphabetic offsets: *in five hours* and *через пять часов*.

### Languages ToDo

- [x] Chinese - check hieroglyphs.
- [x] Spanish - check prepositions.
- [ ] Portuguese
- [ ] Arabic
- [ ] Korean
