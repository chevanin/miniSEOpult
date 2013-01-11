<?php
set_time_limit(0);
ini_set("pcre.backtrack_limit",10000000);
/**
* Основной исполняемый из консоли скрипт
*
* без параметров - скрипт проходит все шаги
* -s запускает шаги, номера шагов через пробел
*     1 - загрузка ссылок в базу
*     2 - фильтр регулярками
*     3 - Фильтрация ссылок с помощью показателя популярности alexa
*     4 - Фильтрация ссылок по посещаемости за месяц по LiveInternet
*     5 - Фильтрация ссылок по свойствам страницы (кол-во ссылок, 200 OK, кол-во символов текста )
*     6 - Отправка отчета
*
* @package SAPE
*
* @todo сделать -h с описанием ключей и значений и примером
* @todo по всем фильтрам - в случае ошибок необходимо считать эту ссылку непрошедшей, но писать об этом в репорт - далее сделать отдельный репорт для ошибок
* @todo seolib сделать последним (платно)
* @todo описать все классы и методы phpdoc
*/


/**
* Подключение загрузчика ссылок из sape.ru
*/
require_once "LinksLoader.class.php";

/**
* Подключение класса для генерации отчетов
*/
require_once "Report.class.php";
/**
* Подключение класса для фильтрации по внешним параметрам (LJ, Alexa,...)
*/
require_once "LinksFilter.class.php";
/**
* Подключение класса для фильтрации по внутренним параметрам
*/
require_once "PageFilter.class.php";
/**
* Подключение класса для хранения ссылок
*/
require_once "Storage.class.php";


$argv = array();
$argv[] = "-s";
//$argv[] = "1";
//$argv[] = "2";
//$argv[] = "3";
//$argv[] = "4"; // liveinternet - если сайт не зарегистрирован в системе, то пропускаем
//$argv[] = "5";
//$argv[] = "6"; // report
//$argv[] = "7"; // black list
//$argv[] = "8"; // seolib
$argv[] = "9"; // mozrank

$argv[] = "-p";
$argv[] = "105199";


if( !in_array( "-p", $argv ) || !isset( $argv[array_search( "-p", $argv )+1] ) ) die("Project ID not defined");

$PROJECT_ID = $argv[array_search( "-p", $argv )+1];

if( !in_array( "-s", $argv ) || in_array( "1", $argv ) ) {
    // Получаем ссылки для фильтрации
    list( $Links, $NestingArray ) = LinksLoader::Get($PROJECT_ID);
} else {
    $Links = array();
    $NestingArray = array();
} // End if

$Storage = new Storage( $PROJECT_ID, $Links, $NestingArray );
list( $TrustedLinks, $NestingArray, $UnTrustedLinks, $UnTrustedLinksIDs, $UnTrustedLinksReasons ) = $Storage->GetDatas();

$ErrorLinksIDs = array();

// Фильтрация ссылок по black list
/**
* @todo сделать в конфиге переключатель по проектам/весь bl
*/
if( !in_array( "-s", $argv ) || in_array( "7", $argv ) ) {
    $BlackListIDs = $Storage->GetBL( $TrustedLinks );    
    list( $TrustedLinks, $UnTrustedLinks[4], $UnTrustedLinksIDs[4], $UnTrustedLinksReasons[4] ) = LinksFilter::BLFilter( $TrustedLinks, $BlackListIDs );
    $Storage->UnTrust(4, $UnTrustedLinksIDs[4], $UnTrustedLinksReasons[4]);
} // End if

// Фильтрация ссылок регулярками
// RegexFilter.config.php - конфиг регулярок
if( !in_array( "-s", $argv ) || in_array( "2", $argv ) ) {
    list( $TrustedLinks, $UnTrustedLinks[0], $UnTrustedLinksIDs[0], $UnTrustedLinksReasons[0] ) = LinksFilter::FilterRegex( $TrustedLinks );
    $Storage->UnTrust(0, $UnTrustedLinksIDs[0], $UnTrustedLinksReasons[0]);
} // End if

// Фильтрация ссылок по посещаемости за месяц по LiveInternet
// Common.config.php - общий конфиг, где всякие пределы (в том числе LiveInternet)
if( !in_array( "-s", $argv ) || in_array( "4", $argv ) ) {
    list( $TrustedLinks, $UnTrustedLinks[2], $UnTrustedLinksIDs[2], $UnTrustedLinksReasons[2], $ErrorLinksIDs[2] ) = LinksFilter::FilterLiveInternet( $TrustedLinks );
    $Storage->UnTrust(2, $UnTrustedLinksIDs[2], $UnTrustedLinksReasons[2], true);
    /*
    echo "<pre>";
    var_dump( $TrustedLinks );
    echo "<hr>";
    var_dump( $UnTrustedLinks );
    echo "<hr>";
    var_dump( $UnTrustedLinksReasons );
    echo "<hr>";
    var_dump( $ErrorLinksIDs );
    echo "<hr>";
    echo "</pre>";
    */
} // End if

// Фильтрация ссылок с помощью показателя популярности alexa
// Common.config.php - общий конфиг, где всякие пределы (в том числе alexa)
if( !in_array( "-s", $argv ) || in_array( "3", $argv ) ) {
    list( $TrustedLinks, $UnTrustedLinks[1], $UnTrustedLinksIDs[1], $UnTrustedLinksReasons[1], $ErrorLinksIDs[1] ) = LinksFilter::FilterAlexa( $TrustedLinks );
    $Storage->UnTrust(1, $UnTrustedLinksIDs[1], $UnTrustedLinksReasons[1], true);
    
    /*
    echo "<pre>";
    var_dump( $TrustedLinks );
    echo "<hr>";
    var_dump( $UnTrustedLinks );
    echo "<hr>";
    var_dump( $UnTrustedLinksReasons );
    echo "<hr>";
    var_dump( $ErrorLinksIDs );
    echo "<hr>";
    echo "</pre>";
    */
    
} // End if

// Фильтрация ссылок по свойствам страницы (кол-во ссылок, 200 OK, кол-во символов текста )
// Common.config.php - общий конфиг, где всякие пределы (кол-во символов текста, кол-во ссылок)
if( !in_array( "-s", $argv ) || in_array( "5", $argv ) ) {
    //list( $TrustedLinks, $UnTrustedLinks[3], $UnTrustedLinksIDs[3], $UnTrustedLinksReasons[3] ) = PageFilter::FilterLinksCount( $TrustedLinks, $NestingArray );
    list( $TrustedLinks, $UnTrustedLinks[3], $UnTrustedLinksIDs[3], $UnTrustedLinksReasons[3], $ErrorLinksIDs[3] ) = LinksFilter::FilterAlexa( $TrustedLinks );
    
    echo "<pre>";
    var_dump( $TrustedLinks );
    echo "<hr>";
    var_dump( $UnTrustedLinks );
    echo "<hr>";
    var_dump( $UnTrustedLinksReasons );
    echo "<hr>";
    var_dump( $ErrorLinksIDs );
    echo "<hr>";
    echo "</pre>";
    
    $Storage->UnTrust(3, $UnTrustedLinksIDs[3], $UnTrustedLinksReasons[3]);
} // End if


// Фильтрация ссылок с помощью seolib
if( !in_array( "-s", $argv ) || in_array( "8", $argv ) ) {
    list( $TrustedLinks, $UnTrustedLinks[5], $UnTrustedLinksIDs[5], $UnTrustedLinksReasons[5], $ErrorLinksIDs[5] ) = LinksFilter::FilterSEOLib( $TrustedLinks );

/*
    list( $TrustedLinks, $UnTrustedLinks[1], $UnTrustedLinksIDs[1], $UnTrustedLinksReasons[1], $ErrorLinksIDs[1] ) = LinksFilter::FilterAlexa( $TrustedLinks );
    $Storage->UnTrust(1, $UnTrustedLinksIDs[1], $UnTrustedLinksReasons[1], true);

*/    
/*
    echo "<pre>";
    var_dump( $TrustedLinks );
    echo "<hr>";
    var_dump( $UnTrustedLinks );
    echo "<hr>";
    var_dump( $UnTrustedLinksReasons );
    echo "<hr>";
    var_dump( $ErrorLinksIDs );
    echo "<hr>";
    echo "</pre>";
*/
    
    $Storage->UnTrust(5, $UnTrustedLinksIDs[5], $UnTrustedLinksReasons[5], true);
} // End if


// Фильтрация ссылок с помощью Мозранк
if( !in_array( "-s", $argv ) || in_array( "9", $argv ) ) {
    list( $TrustedLinks, $UnTrustedLinks[6], $UnTrustedLinksIDs[6], $UnTrustedLinksReasons[6], $ErrorLinksIDs[6] ) = LinksFilter::FilterMozrank( $TrustedLinks );
/*
    list( $TrustedLinks, $UnTrustedLinks[1], $UnTrustedLinksIDs[1], $UnTrustedLinksReasons[1], $ErrorLinksIDs[1] ) = LinksFilter::FilterAlexa( $TrustedLinks );
    $Storage->UnTrust(1, $UnTrustedLinksIDs[1], $UnTrustedLinksReasons[1], true);


*/
    echo "<pre>";
    var_dump( $TrustedLinks );
    echo "<hr>";
    var_dump( $UnTrustedLinks );
    echo "<hr>";
    var_dump( $UnTrustedLinksReasons );
    echo "<hr>";
    var_dump( $ErrorLinksIDs );
    echo "<hr>";
    echo "</pre>";
    $Storage->UnTrust(6, $UnTrustedLinksIDs[6], $UnTrustedLinksReasons[6], true);
} // End if



/*
    На всякий случай проверяем не закрыта ли страница в роботс.тхт. Очень редко, но случается.


    Проверяем страницу на стоп-слова. Вот тут засада. Думаю, надо делать сложную схему, присвоив каждому стоп-слову определённое количество баллов и фильтровать страницу при наборе этого количества. Условно слово "хуй" = 2 балла, а слово "порно" = 0,75, т.к. "порно" может попадаться на новостниках, например... На первом этапе можем просто фильтровать урлы имеющие совпадения, но стремимся к бальной оценке.


    Анализируем тексты внешних ссылок со страницы, если во внешних ссылках, которые будут соседствовать с нами, есть стоп-слова, то нам такие соседи не нужны.

    Пункт временно под вопросом: коннектимся к АПИ Соломоно, получаем информацию о соотношении страниц в индексе/ссылок на сайте, дин/доут, делаем какие-то шайтан выводы.


    Проверяем Мозранк страницы через АПИ. В бесплатном режиме работает ограничение на 1 запрос в 10 секунд. Получаем данные об umrp и fmrp.
*/


if( !in_array( "-s", $argv ) || in_array( "6", $argv ) ) {

    // Гененрируем отчет
    $Report = new Report( $PROJECT_ID );
    
    // Помечаем ошибки при проверках для исключения их из BL
    $Storage->MarkErrors($ErrorLinksIDs);
    
    

    list( $StorageTrustedLinks, $StorageUnTrustedLinksAPI, $StorageUnTrustedLinks ) = $Storage->GetReport();
    /*
    echo "<pre>";
    var_dump( $StorageTrustedLinks );
    echo "<hr>";
    var_dump( $StorageUnTrustedLinksAPI );
    echo "<hr>";
    var_dump( $StorageUnTrustedLinks );
    echo "<hr>";
    echo "</pre>";
    */
    $Report->GenerateCSV( $StorageTrustedLinks, array( "URL", "Уровень" ), "trusted_" . date("d-m-Y") . ".csv" );
    $Report->GenerateCSV( $StorageUnTrustedLinksAPI, array( "URL", "Уровень", "Причина отклонения" ), "API_untrusted_" . date("d-m-Y") . ".csv" );
    $Report->GenerateCSV( $StorageUnTrustedLinks, array( "URL", "Уровень", "Причина отклонения" ), "ACCESS_untrusted_" . date("d-m-Y") . ".csv" );

    $Report->Send();
    
    

} // End if



?>