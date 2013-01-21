<?php
/**
* ����������� ������ ��� �������� ������
*
* @package SAPE
*/

/**
* ����� ��� �������� ������
*
* @package SAPE
* @author Chevanin Valeriy <chevanin@etorg.ru>
* @todo ���� ������ �� Common.config.sample.php
*/
class LinksLoader {

    /**
    * ����� "�������" ��� ����������� �������� ������ �� ��������� ����������
    * ���������� �� index.php
    * ������ ������������� (index.php)
    * <code>
    * list( $Links, $NestingArray, $LinksProjects ) = LinksLoader::Get();
    * </code>
    *
    * @static
    * @access public
    *
    * @example index.php ������ ������������� � index.php
    * @uses LinksLoader::_GetFromSape() ��� ��������� ������ �� sape.ru
    *
    * @return array ������ ( $Links, $Nesting ), $Links - ������ ( "ID" => "URL" ), $Nesting - ������� ����������� ( "ID" => "Level" ), $ProjectIDs - �������, �� ������� ������ ������ ( "ID ������" => "ID �������" )
    */
    static public function Get() {
        
        echo "<br><strong>Getting links...</strong><br>";       
        list( $Links, $Nesting, $ProjectIDs ) = self::_GetFromSape();
        echo "<strong>Links loaded</strong><br>";
        
        return array( $Links, $Nesting, $ProjectIDs );
        
    } // End function Get
    
    
    /**
    * ����� ��� ��������� ������ �� sape.ru (�� ������� �� ��������)
    * ���������� �� LinksLoader::Get()
    * ������ �������������
    * <code>
    * list( $Links, $NestingArray, $ProjectIDs ) = self::_GetFromSape();
    * </code>
    *
    * ������������ ��������� (Common.config.php)
    * <code>
    *    $sape_host       = "api.sape.ru";         // ���� API sape.ru
    *    $sape_location   = "/xmlrpc/?v=extended"; // �����
    *    $sape_login      = "...";                 // ����� � sape.ru
    *    $sape_password   = "...";                 // ������
    *    $sape_project_id = ...;                   // ID �������
    *    $sape_status     = "WAIT_SEO";            // ������ ������ (�������� � ������ ������)
    * </code>
    *
    * @access protected
    * @see LinksLoader::Get()
    * @uses LinksLoader::_SapeRequest() ��� �������� � sape.ru
    *
    * @return array ������ ( $Links, $Nesting, $ProjectIDs ), $Links - ������ ( "ID" => "URL" ), $Nesting - ������� ����������� ( "ID" => "Level" ), $ProjectIDs - �������, �� ������� ������ ������ ( "ID ������" => "ID �������" )
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
    * ������ � sape.ru (XML-RPC)
    * ���������� �� LinksLoader::_GetFromSape()
    *
    * ������ �������������
    * <code>
    *    // Get Project links
    *    $data = self::_SapeRequest( "sape.get_project_links", array( $sape_project_id, $sape_status ) );
    * </code>
    *
    * ������������ ��� ����������� � �������� �� ��������� ������� ������. ���������� curl
    * @link http://docs.php.net/manual/ru/ref.curl.php �������� ������� PHP CURL
    * @see LinksLoader::_GetFromSape()
    *
    * @access protected
    *
    * @param string $method �������� ������
    * @param array $params �� ��������� array(), ������������ � ������� ���������
    * @return array ������ ( $Links, $Nesting ), $Links - ������ ( "ID" => "URL" ), $Nesting - ������� ����������� ( "ID" => "Level" )
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