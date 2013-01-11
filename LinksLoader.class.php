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
* @todo _GetFromSape ������� ��������� � ������
* @todo _GetFromSape ������� ����������� �������� ID �������� ����� $argv, ������ ���������� ����� ���� ��� �� ��������� �������, ��� � �� ������ ���������
*/
class LinksLoader {

    /**
    * ����� "�������" ��� ����������� �������� ������ �� ��������� ����������
    * ���������� �� index.php
    * ������ ������������� (index.php)
    * <code>
    * list( $Links, $NestingArray ) = LinksLoader::Get();
    * </code>
    *
    * @static
    * @access public
    *
    * @example index.php ������ ������������� � index.php
    * @uses LinksLoader::_GetFromSape() ��� ��������� ������ �� sape.ru
    *
    * @param integer $ProjectID ����� ������� � Sape.ru
    * @return array ������ ( $Links, $Nesting ), $Links - ������ ( "ID" => "URL" ), $Nesting - ������� ����������� ( "ID" => "Level" )
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
    * ����� ��� ��������� ������ �� sape.ru (�� ������� �� ��������)
    * ���������� �� LinksLoader::Get()
    * ������ �������������
    * <code>
    * list( $Links, $NestingArray ) = self::_GetFromSape();
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
    * @param integer $ProjectID ����� ������� � Sape.ru
    * @return array ������ ( $Links, $Nesting ), $Links - ������ ( "ID" => "URL" ), $Nesting - ������� ����������� ( "ID" => "Level" )
    *
    * @todo ������� ��������� � ������
    * @todo ������� ����������� �������� ID �������� ����� $argv
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
    * @todo ������� ��������� � ������
    * @todo ������� ����������� �������� ID �������� ����� $argv
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
    * ����� ��� ��������� ������ �� CSV ����� - ������������� ��� ������������
    * @access protected
    *
    * @param string $CSVFile ��� �����
    * @return array ������ ( $Links, $Nesting ), $Links - ������ ( "ID" => "URL" ), $Nesting - ������� ����������� ( "ID" => "Level" )
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