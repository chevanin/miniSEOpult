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
* @todo дать ссылку на Common.config.sample.php
*/
class LinksLoader {

    /**
    * Метод "обертка" для возможности загрузки ссылок из различных источников
    * Вызывается из index.php
    * Пример использования (index.php)
    * <code>
    * list( $Links, $NestingArray, $LinksProjects ) = LinksLoader::Get();
    * </code>
    *
    * @static
    * @access public
    *
    * @example index.php Пример использования в index.php
    * @uses LinksLoader::_GetFromSape() для получения ссылок из sape.ru
    *
    * @return array Массив ( $Links, $Nesting ), $Links - ссылки ( "ID" => "URL" ), $Nesting - уровень вложенности ( "ID" => "Level" ), $ProjectIDs - проекты, на которые пришли ссылки ( "ID ссылки" => "ID проекта" )
    */
    static public function Get() {
        
        echo "<br><strong>Getting links...</strong><br>";       
        list( $Links, $Nesting, $ProjectIDs ) = self::_GetFromSape();
        echo "<strong>Links loaded</strong><br>";
        
        return array( $Links, $Nesting, $ProjectIDs );
        
    } // End function Get
    
    
    /**
    * Метод для получения ссылок из sape.ru (из очереди на проверку)
    * Вызывается из LinksLoader::Get()
    * Пример использования
    * <code>
    * list( $Links, $NestingArray, $ProjectIDs ) = self::_GetFromSape();
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
    * @return array Массив ( $Links, $Nesting, $ProjectIDs ), $Links - ссылки ( "ID" => "URL" ), $Nesting - уровень вложенности ( "ID" => "Level" ), $ProjectIDs - проекты, на которые пришли ссылки ( "ID ссылки" => "ID проекта" )
    */
    protected function _GetFromSape() {
    
        require "Common.config.php";
        
        $Links = array();
        $Nesting = array();
        $ProjectIDs = array();
        
        // Login
        $data = self::_SapeRequest( "sape.login", array($sape_login, md5($sape_password), true) );
        
        // Projects List
        $data = self::_SapeRequest( "sape.get_projects", array( false, false ) );
        
        foreach( $data as $project ) {
            
            // Get Project links
            $data = self::_SapeRequest( "sape.get_project_links", array( $project['id'], $sape_status ) );
            
            foreach( $data as $link ) {
                $Links[$link['id']] = $link['site_url'] . $link['page_uri'];
                $Nesting[$link['id']] = $link['page_level'];
                $ProjectIDs[$link['id']] = $project['id'];
            } // End foreach
            
        } // End foreach PROJECTS
        
        return array($Links, $Nesting, $ProjectIDs);
        
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

        $data = xmlrpc_decode($Response);
        if( is_array($data) && xmlrpc_is_fault($data)) {
            echo $data[faultString] . "<br>";
            return array();
        } else {            
            return $data; 
        } // End if
        
    } // End function _SapeRequest
    
    
} // End class LinksLoader