<?php
set_time_limit(0);
ini_set("pcre.backtrack_limit",10000000);
/**
* �������� ����������� �� ������� ������
*
* ��� ���������� - ������ �������� ��� ����
* -s ��������� ����, ������ ����� ����� ������
*     1 - �������� ������ � ����
*     2 - ������ �����������
*     3 - ���������� ������ � ������� ���������� ������������ alexa
*     4 - ���������� ������ �� ������������ �� ����� �� LiveInternet
*     5 - ���������� ������ �� ��������� �������� (���-�� ������, 200 OK, ���-�� �������� ������ )
*     6 - �������� ������
*
* @package SAPE
*
* @todo ������� -h � ��������� ������ � �������� � ��������
*/


/**
* ����������� ���������� ������ �� sape.ru
*/
require_once "LinksLoader.class.php";

/**
* ����������� ������ ��� ��������� �������
*/
require_once "Report.class.php";
/**
* ����������� ������ ��� ���������� �� ������� ���������� (LJ, Alexa,...)
*/
require_once "LinksFilter.class.php";
/**
* ����������� ������ ��� ���������� �� ���������� ����������
*/
require_once "PageFilter.class.php";
/**
* ����������� ������ ��� �������� ������
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
    // �������� ������ ��� ���������
    list( $Links, $NestingArray ) = LinksLoader::Get($PROJECT_ID);
} else {
    $Links = array();
    $NestingArray = array();
} // End if

$Storage = new Storage( $PROJECT_ID, $Links, $NestingArray );
list( $TrustedLinks, $NestingArray, $UnTrustedLinks, $UnTrustedLinksIDs, $UnTrustedLinksReasons ) = $Storage->GetDatas();

// ���������� ������ �� black list
/**
* @todo ������� � ������� ������������� �� ��������/���� bl
*/
if( !in_array( "-s", $argv ) || in_array( "7", $argv ) ) {
    $BlackListIDs = $Storage->GetBL( $TrustedLinks );    
    list( $TrustedLinks, $UnTrustedLinks[4], $UnTrustedLinksIDs[4], $UnTrustedLinksReasons[4] ) = LinksFilter::BLFilter( $TrustedLinks, $BlackListIDs );
    $Storage->UnTrust(4, $UnTrustedLinksIDs[4], $UnTrustedLinksReasons[4]);
} // End if

// ���������� ������ �����������
// RegexFilter.config.php - ������ ���������
if( !in_array( "-s", $argv ) || in_array( "2", $argv ) ) {
    list( $TrustedLinks, $UnTrustedLinks[0], $UnTrustedLinksIDs[0], $UnTrustedLinksReasons[0] ) = LinksFilter::FilterRegex( $TrustedLinks );
    $Storage->UnTrust(0, $UnTrustedLinksIDs[0], $UnTrustedLinksReasons[0]);
} // End if

// ���������� ������ �� ������������ �� ����� �� LiveInternet
// Common.config.php - ����� ������, ��� ������ ������� (� ��� ����� LiveInternet)
if( !in_array( "-s", $argv ) || in_array( "4", $argv ) ) {
    list( $TrustedLinks, $UnTrustedLinks[2], $UnTrustedLinksIDs[2], $UnTrustedLinksReasons[2] ) = LinksFilter::FilterLiveInternet( $TrustedLinks );
    $Storage->UnTrust(2, $UnTrustedLinksIDs[2], $UnTrustedLinksReasons[2]);
} // End if

// ���������� ������ � ������� ���������� ������������ alexa
// Common.config.php - ����� ������, ��� ������ ������� (� ��� ����� alexa)
if( !in_array( "-s", $argv ) || in_array( "3", $argv ) ) {
    list( $TrustedLinks, $UnTrustedLinks[1], $UnTrustedLinksIDs[1], $UnTrustedLinksReasons[1] ) = LinksFilter::FilterAlexa( $TrustedLinks );
    $Storage->UnTrust(1, $UnTrustedLinksIDs[1], $UnTrustedLinksReasons[1]);
} // End if

// ���������� ������ �� ��������� �������� (���-�� ������, 200 OK, ���-�� �������� ������ )
// Common.config.php - ����� ������, ��� ������ ������� (���-�� �������� ������, ���-�� ������)
if( !in_array( "-s", $argv ) || in_array( "5", $argv ) ) {
    list( $TrustedLinks, $UnTrustedLinks[3], $UnTrustedLinksIDs[3], $UnTrustedLinksReasons[3] ) = PageFilter::FilterLinksCount( $TrustedLinks, $NestingArray );
    $Storage->UnTrust(3, $UnTrustedLinksIDs[3], $UnTrustedLinksReasons[3]);
} // End if



/*
    �� ������ ������ ��������� �� ������� �� �������� � ������.���. ����� �����, �� ���������.


    ��������� �������� �� ����-�����. ��� ��� ������. �����, ���� ������ ������� �����, �������� ������� ����-����� ����������� ���������� ������ � ����������� �������� ��� ������ ����� ����������. ������� ����� "���" = 2 �����, � ����� "�����" = 0,75, �.�. "�����" ����� ���������� �� �����������, ��������... �� ������ ����� ����� ������ ����������� ���� ������� ����������, �� ��������� � ������� ������.


    ����������� ������ ������� ������ �� ��������, ���� �� ������� �������, ������� ����� ������������� � ����, ���� ����-�����, �� ��� ����� ������ �� �����.

    ����� �������� ��� ��������: ����������� � ��� ��������, �������� ���������� � ����������� ������� � �������/������ �� �����, ���/����, ������ �����-�� ������ ������.


    ��������� ������� �������� ����� ���. � ���������� ������ �������� ����������� �� 1 ������ � 10 ������. �������� ������ �� umrp � fmrp.
*/


if( !in_array( "-s", $argv ) || in_array( "6", $argv ) ) {

    // ����������� �����
    $Report = new Report( $PROJECT_ID );

    list( $StorageTrustedLinks, $StorageUnTrustedLinks ) = $Storage->GetReport();
    $Report->GenerateCSV( $StorageTrustedLinks, array( "URL", "�������" ), "trusted_" . date("d-m-Y") . ".csv" );
    $Report->GenerateCSV( $StorageUnTrustedLinks, array( "URL", "�������", "������� ����������" ), "untrusted_" . date("d-m-Y") . ".csv" );

    $Report->Send();

} // End if

/*

�������� �� 200 OK

    ������ ����������� � API Sape � �������� �������� ��� ������.

    ������ ���������� �������� � ���� ���������� �� ����-������� (������� �������), ������� ���������� ������� � ���������� ������ �� ��������, ������� ���������� ���������� �������� �� ��������. ��������� ��������, ��� ����������� ���������� �� ������ ����-����, ��� ����� ������� ���������� �������� ����� ������ ������ (������ - 1000 �������� ������� ������, ��� ����� ������� ������), �������� ��������, ��� ���������� ���� �������, ���� ���������� ������ ��������� ������������� �������� (� ������ �������� ���������� ��� ������ ������� �������). ������, �������� � noindex �� ���������.
    ����������� � API Seolib http://www.seolib.ru/script/xmlrpc/ ���������� ���������� ������ �� ��������: ���������� � �������, ���������� � Google, ������ ���-17 � ������ � �������.
    � ������� ������ Solomono �� ������������ ���������� ��������� ��������� ���� API, ���� ����� ���������� ���. ���� �� �������� ������, � ��� ����� ������������ � ������� �������.
    � Solomono �������� ���������� �� ��������� ������� � ������. ���� ���������� ��������� ������ ��������� ���������� ������� � ������� ������� � ����������� ���������� ���, �� ������ ����� �������.
    ����� ����� ���������� ��� ��������� ������ � ������ �� ����-������ (�����, �����, ����������� �����, �������-���������� �� � ������ ����, ������� ������ �����).
    � 10-00 ������ ������ �� ����� 2 �����: ��������������� ������ � ��������������� ������. ���� � ������ �������� ���� ����������� (��� �� ���� ��������), �� ��������������� ����� ����� ����� ����� API �������� � BL.

*/

?>