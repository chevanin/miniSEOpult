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
//$argv[] = "-s";
//$argv[] = "1";
//$argv[] = "2";
//$argv[] = "3";
//$argv[] = "4";
//$argv[] = "5";
//$argv[] = "6";
//$argv[] = "7"; // black list

$argv[] = "-p";
$argv[] = "105199";


if( !in_array( "-p", $argv ) || !isset( $argv[array_search( "-p", $argv )+1] ) ) die("Project ID not defined");

$PROJECT_ID = $argv[array_search( "-p", $argv )+1];

if( !in_array( "-s", $argv ) || in_array( "1", $argv ) ) {
    // Получаем ссылки для филтрации
    list( $Links, $NestingArray ) = LinksLoader::Get($PROJECT_ID);
} else {
    $Links = array();
    $NestingArray = array();
} // End if

$Storage = new Storage( $PROJECT_ID, $Links, $NestingArray );
list( $TrustedLinks, $NestingArray, $UnTrustedLinks, $UnTrustedLinksIDs, $UnTrustedLinksReasons ) = $Storage->GetDatas();

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
    list( $TrustedLinks, $UnTrustedLinks[2], $UnTrustedLinksIDs[2], $UnTrustedLinksReasons[2] ) = LinksFilter::FilterLiveInternet( $TrustedLinks );
    $Storage->UnTrust(2, $UnTrustedLinksIDs[2], $UnTrustedLinksReasons[2]);
} // End if

// Фильтрация ссылок с помощью показателя популярности alexa
// Common.config.php - общий конфиг, где всякие пределы (в том числе alexa)
if( !in_array( "-s", $argv ) || in_array( "3", $argv ) ) {
    list( $TrustedLinks, $UnTrustedLinks[1], $UnTrustedLinksIDs[1], $UnTrustedLinksReasons[1] ) = LinksFilter::FilterAlexa( $TrustedLinks );
    $Storage->UnTrust(1, $UnTrustedLinksIDs[1], $UnTrustedLinksReasons[1]);
} // End if

// Фильтрация ссылок по свойствам страницы (кол-во ссылок, 200 OK, кол-во символов текста )
// Common.config.php - общий конфиг, где всякие пределы (кол-во символов текста, кол-во ссылок)
if( !in_array( "-s", $argv ) || in_array( "5", $argv ) ) {
    list( $TrustedLinks, $UnTrustedLinks[3], $UnTrustedLinksIDs[3], $UnTrustedLinksReasons[3] ) = PageFilter::FilterLinksCount( $TrustedLinks, $NestingArray );
    $Storage->UnTrust(3, $UnTrustedLinksIDs[3], $UnTrustedLinksReasons[3]);
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

    list( $StorageTrustedLinks, $StorageUnTrustedLinks ) = $Storage->GetReport();
    $Report->GenerateCSV( $StorageTrustedLinks, array( "URL", "Уровень" ), "trusted_" . date("d-m-Y") . ".csv" );
    $Report->GenerateCSV( $StorageUnTrustedLinks, array( "URL", "Уровень", "Причина отклонения" ), "untrusted_" . date("d-m-Y") . ".csv" );

    $Report->Send();

} // End if

/*

проверка на 200 OK

    Скрипт коннектится к API Sape и получает материал для работы.

    Парсим оставшиеся страницы и ищем совпадения со стоп-словами (примеры отсылал), считаем количество внешних и внутренних ссылок на странице, считаем количество текстового контента на странице. Фильтруем страницы, где встретилось совпадение из списка стоп-слов, где объём чистого текстового контента менее некого порога (сейчас - 1000 символов чистого текста, без учёта текстов ссылок), зачищаем страницы, где количество либо внешних, либо внутренних ссылок превышает установленные значения (в идеале значения отличаются для разных уровней страниц). Ссылки, закрытые в noindex не учитываем.
    Коннектимся к API Seolib http://www.seolib.ru/script/xmlrpc/ отправляем оставшиеся ссылки на проверку: индексация в Яндексе, индексация в Google, фильтр АГС-17 и фильтр в Яндексе.
    В октябре сервис Solomono по инсайдерской информации планирует выпустить свой API, надо будет подключить его. Если не выпустит паблик, с ним можно договориться в частном порядке.
    В Solomono получаем информацию об исходящих ссылках с домена. Если количество исходящих ссылок превышает количество страниц в индексе Яндекса в определённое количество раз, то донора стоит отсеять.
    После этого просеиваем все исходящие ссылки с домена по стоп-словам (порно, варез, курительные смеси, дипломы-больничные ну и прочий шлак, который портит карму).
    В 10-00 скрипт кидает на почту 2 файла: зафильтрованные ссылки и отфильтрованные ссылки. Если у нашего аккаунта есть возможность (она не всем доступна), то зафильтрованные можно будет сразу через API добавить в BL.

*/

?>