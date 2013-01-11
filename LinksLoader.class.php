<?php
/**
* Определение класса для загрузки ссылок
*
* @package SAPE
*/

/**
* Класс для загрузки ссылок
*
* @package SAPE
* @author Chevanin Valeriy <chevanin@etorg.ru>
* @todo _GetFromSape Вынести параметры в конфиг
* @todo _GetFromSape сделать возможность задавать ID компании через $argv, причем фильтрация может быть как по блэклисту проекта, так и по общему блэклисту
*/
class LinksLoader {

    /**
    * Метод "обертка" для возможности загрузки ссылок из различных источников
    * Вызывается из index.php
    * Пример использования (index.php)
    * <code>
    * list( $Links, $NestingArray ) = LinksLoader::Get();
    * </code>
    *
    * @static
    * @access public
    *
    * @example index.php Пример использования в index.php
    * @uses LinksLoader::_GetFromSape() для получения ссылок из sape.ru
    *
    * @param integer $ProjectID номер проекта в Sape.ru
    * @return array Массив ( $Links, $Nesting ), $Links - ссылки ( "ID" => "URL" ), $Nesting - уровень вложенности ( "ID" => "Level" )
    */
    static public function Get( $ProjectID ) {
        
        echo "<br><strong>Getting links...</strong><br>";
        
        list( $Links, $Nesting ) = self::_GetFromSape( $ProjectID );
        
        /*
        $CSVFile = "test3.csv";
        list( $Links, $Nesting ) = self::_GetFromCSV( $CSVFile );
        */
        
        echo "<strong>Links loaded</strong><br>";
        
        return array( $Links, $Nesting );
        
    } // End function Get
    
    
    /**
    * Метод для получения ссылок из sape.ru (из очереди на проверку)
    * Вызывается из LinksLoader::Get()
    * Пример использования
    * <code>
    * list( $Links, $NestingArray ) = self::_GetFromSape();
    * </code>
    *
    * Используемые параметры (Common.config.php)
    * <code>
    *    $sape_host       = "api.sape.ru";         // Хост API sape.ru
    *    $sape_location   = "/xmlrpc/?v=extended"; // Адрес
    *    $sape_login      = "...";                 // Логин в sape.ru
    *    $sape_password   = "...";                 // Пароль
    *    $sape_project_id = ...;                   // ID проекта
    *    $sape_status     = "WAIT_SEO";            // Статус ссылок (ожидание в данном случае)
    * </code>
    *
    * @access protected
    * @see LinksLoader::Get()
    * @uses LinksLoader::_SapeRequest() для запросов к sape.ru
    *
    * @param integer $ProjectID номер проекта в Sape.ru
    * @return array Массив ( $Links, $Nesting ), $Links - ссылки ( "ID" => "URL" ), $Nesting - уровень вложенности ( "ID" => "Level" )
    *
    * @todo Вынести параметры в конфиг
    * @todo сделать возможность задавать ID компании через $argv
    */
    protected function _GetFromSape( $ProjectID ) {
    
        require "Common.config.php";
        $sape_project_id = $ProjectID;
        //$sape_date_start = date( "Y-m-d H:i:s",  mktime( date("H"), date("i"), date("s"), date("n"), date("j")-30, date("Y") ) );
        //$sape_date_stop = date("Y-m-d H:i:s");
        
        // Login
        $data = self::_SapeRequest( "sape.login", array($sape_login, md5($sape_password), true) );
        
        // Get Project links
        $data = self::_SapeRequest( "sape.get_project_links", array( $sape_project_id, $sape_status ) );
        
        foreach( $data as $link ) {
            $Links[$link['id']] = $link['site_url'] . $link['page_uri'];
            $Nesting[$link['id']] = $link['page_level'];
        } // End foreach
        
        return array($Links, $Nesting);
        
    } // End _GetFromSape
    
    
    /**
    * Запрос к sape.ru (XML-RPC)
    * Вызывается из LinksLoader::_GetFromSape()
    *
    * Пример использования
    * <code>
    *    // Get Project links
    *    $data = self::_SapeRequest( "sape.get_project_links", array( $sape_project_id, $sape_status ) );
    * </code>
    *
    * Используется для авторизации и запросов на получение очереди ссылок. Использует curl
    * @link http://docs.php.net/manual/ru/ref.curl.php описание функций PHP CURL
    * @see LinksLoader::_GetFromSape()
    *
    * @access protected
    *
    * @param string $method название метода
    * @param array $params по умолчанию array(), передаваемые в запросе параметры
    * @return array Массив ( $Links, $Nesting ), $Links - ссылки ( "ID" => "URL" ), $Nesting - уровень вложенности ( "ID" => "Level" )
    *
    * @todo Вынести параметры в конфиг
    * @todo сделать возможность задавать ID компании через $argv
    */
    protected function _SapeRequest( $method, $params = array()) {
        
        $URL = "http://api.sape.ru/xmlrpc/";
        $request = xmlrpc_encode_request( $method, $params, array( 'encoding' => "UTF-8") );
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $URL);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_COOKIEFILE, 'cookiefile.txt');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $request );
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: text/xml; charset=utf-8", "Content-Length: ".strlen($request)) );

        $Response = curl_exec($ch);
        curl_close($ch);
        
        /*
        echo "<pre>";
        var_dump( htmlspecialchars($Response) );
        echo "</pre><hr>";klhjklh
        */
        
        $data = xmlrpc_decode($Response);
        if( is_array($data) && xmlrpc_is_fault($data)) {
            echo $data[faultString] . "<br>";
            return array();
        } else {            
            return $data; 
        } // End if
        
    } // End function _SapeRequest
    
    
    /**
    * Метод для получения ссылок из CSV файла - использовался для тестирования
    * @access protected
    *
    * @param string $CSVFile имя файла
    * @return array Массив ( $Links, $Nesting ), $Links - ссылки ( "ID" => "URL" ), $Nesting - уровень вложенности ( "ID" => "Level" )
    */
    protected function _GetFromCSV( $CSVFile ) {
    
        echo "<h2>Getting links from CSV</h2>";
        
        echo $CSVFile;
        echo "<br>";
        
        $Links = array();
        $Nesting = array();
        if( ( $handle = fopen($CSVFile, "r") ) !== FALSE ) {
            $row = 0;
            //while( ( $data = fgetcsv($handle, 0, ",") ) !== FALSE ) {
            while( ( ( $data = fgetcsv($handle, 0, ";") ) !== FALSE ) && ( $row < 100 ) ) {
                                            
                $Links[] = $data[8];
                $Nesting[] = $data[11];
                
                $row++;
                
            } // End while
            
            fclose($handle);
            
        } // End if     
        
        echo "<h2>Links from CSV loaded</h2>";
        
        return array($Links, $Nesting);
        
    } // End _GetFromCSV
    
} // End class LinksLoader