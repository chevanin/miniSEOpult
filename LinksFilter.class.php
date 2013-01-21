<?php
/**
* ����������� ������ ��� �������� ������ �� ��������� ����������
*
* @package SAPE
*/

/**
* ����� ��� �������� ������ �� ��������� ���������� - API � ���������� ��������� �� REQUEST_URI ������
*
* @package SAPE
* @author Chevanin Valeriy <chevanin@etorg.ru>
* @todo ��� ��������� ������� � �����������<br>��� ������ �������� �����<br>����������� ���, ��� �������� (�������������� ������� � ���� ������� ������ ����� ������ � ����� �������������� �������)<br>���� ������ �� Common.config.sample.php � ����� ���������
*/

class LinksFilter {
	
    /**
    * ���������� ������ �� black list
    * ���������� �� index.php
    * ������ ������������� (index.php)
    * <code>
    *   list( 
    *        $TrustedLinks, 
    *        $UnTrustedLinks[4], 
    *        $UnTrustedLinksIDs[4], 
    *        $UnTrustedLinksReasons[4]
    *    ) = LinksFilter::BLFilter( $TrustedLinks, $BlackListIDs );
    * </code>
    *
    * @access public
    *
    * @example index.php ������ ������������� � index.php
    *
    * @param array $Links ������ ������ ��� ����������
    * @param array $BlackListIDs ������ ��������������� $Links, ��������������� ����������� ���������� �������
    * @return array ������ ( $TrustedLinks, $UnTrustedLinks, $UnTrustedLinksIDs, $UnTrustedLinksReasons ), $TrustedLinks - ����������������� ������ ( "ID" => "URL" ), $UnTrustedLinks - ����������� ���������� ������, $UnTrustedLinksIDs - ID-� ����������� ���������� ������, $UnTrustedLinksReasons - ������� ������
    *
    */
	static public function BLFilter( $Links, $BlackListIDs ) {
        
        $TrustedLinks = $Links;
        $UnTrustedLinks = array();
        $UnTrustedLinksIDs = array();
        $UnTrustedLinksReasons = array();
        
        echo "<strong>BL filtering...</strong>";
        
        foreach( $BlackListIDs as $BLID => $BLReason ) {
            
            $UnTrustedLinks[] = $TrustedLinks[$BLID];
            $UnTrustedLinksIDs[] = $BLID;
            $UnTrustedLinksReasons[$BLID] = "������������� �� BL (����� - " . $BLReason . ")";
            unset( $TrustedLinks[$BLID] );
            
        } // End foreach
        
        echo "<br><strong>BL filtering finished</strong>";
        
        return array( $TrustedLinks, $UnTrustedLinks, $UnTrustedLinksIDs, $UnTrustedLinksReasons );
        
    } // End function BLFilter
    
    
    /**
    * �������� �� �������
    * ���������� �� index.php
    * ������ ������������� (index.php)
    * <code>
    *   list( 
    *        $TrustedLinks, 
    *        $UnTrustedLinks[6], 
    *        $UnTrustedLinksIDs[6], 
    *        $UnTrustedLinksReasons[6], 
    *        $ErrorLinksIDs[6]
    *    ) = LinksFilter::FilterMozrank( $TrustedLinks );
    * </code>
    *
    * @access public
    *
    * @uses LinkFilter::_GetFromSEOMoz() ��� ��������� ���������� ������ �� seomoz.com
    *
    * @example index.php ������ ������������� � index.php
    *
    * @param array $Links ������ ������ ��� ����������
    * @return array ������ ( $TrustedLinks, $UnTrustedLinks, $UnTrustedLinksIDs, $UnTrustedLinksReasons, $ErrorLinksIDs ), $TrustedLinks - ����������������� ������ ( "ID" => "URL" ), $UnTrustedLinks - ����������� ���������� ������, $UnTrustedLinksIDs - ID-� ����������� ���������� ������, $UnTrustedLinksReasons - ������� ������, $ErrorLinksIDs - ������ � ��������
    *
    */
    static public function FilterMozrank( $Links ) {
        
        require "Common.config.php";
        
        $TrustedLinks = $Links;
        $UnTrustedLinks = array();
        $UnTrustedLinksIDs = array();
        $UnTrustedLinksReasons = array();
        $ErrorLinksIDs = array();
        
        echo "<br><br><strong>Mozrank filtering...</strong>";
                
        foreach( $TrustedLinks as $ID => $URL ) {
        
            $URLSEOMozParameters = self::_GetFromSEOMoz( $URL );
            
            if( !$URLSEOMozParameters['error'] ) {
                if( isset($URLSEOMozParameters['umrp']) && ( $URLSEOMozParameters['umrp'] < 3 ) && ( $URLSEOMozParameters['umrp'] > 0 ) ) {
                    $UnTrustedLinks[] = $URL;
                    $UnTrustedLinksIDs[] = $ID;
                    $UnTrustedLinksReasons[$ID] = "�� ������ �������� Mozrank (0 < umrp < 3)";
                    unset( $TrustedLinks[$ID] );
                } elseif( isset($URLSEOMozParameters['umrp']) && ( $URLSEOMozParameters['umrp'] == 0 ) ) {
                    if( isset($URLSEOMozParameters['fmrp']) && ( $URLSEOMozParameters['fmrp'] < 3 ) ) {
                        $UnTrustedLinks[] = $URL;
                        $UnTrustedLinksIDs[] = $ID;
                        $UnTrustedLinksReasons[$ID] = "�� ������ �������� Mozrank (fmrp < 3)";
                        unset( $TrustedLinks[$ID] );
                    } // End if
                } // End if
            } else {
                $UnTrustedLinks[] = $URL;
                $UnTrustedLinksIDs[] = $ID;
                $UnTrustedLinksReasons[$ID] = "��������� ������ ��� �������� Mozrank";
                $ErrorLinksIDs[] = $ID;
                unset( $TrustedLinks[$ID] );
            } // End if
            
        } // End foreach
                
        echo "<br><strong>Mozrank filtering finished</strong>";
        
        return array( $TrustedLinks, $UnTrustedLinks, $UnTrustedLinksIDs, $UnTrustedLinksReasons, $ErrorLinksIDs );
        
    } // End function FilterMozrank
    
    
    /**
    * ����� ��� ��� ��������� ���������� ������ $URL �� seomoz.com
    * ���������� �� LinksFilter::FilterMozrank()
    * ������ �������������
    * <code>
    * $URLSEOMozParameters = self::_GetFromSEOMoz( $URL );
    * </code>
    *
    * ������������ ��������� (Common.config.php)
    * <code>
    *    $seomoz_access_id = "...";
    *    $seomoz_secret_key = "...";
    * </code>
    *
    * @access protected
    * @see LinksFilter::FilterMozrank()
    * @link http://apiwiki.seomoz.org/url-metrics �������� API
    *
    * @param string $URL ������ �� ������� ���������� �������� ������
    * @return array ������, ��������� �����: float umrp - �������� umrp, float fmrp - �������� fmrp, bool error - ��������� ������
    */
    protected function _GetFromSEOMoz( $URL ) {
    
        require "Common.config.php";
        
        $result = array();
        $Error = false;
        
        $URL = urlencode($URL);
        $timestamp = time() + 300; // one minute into the future
        $hash = hash_hmac("sha1", $seomoz_access_id . "\n" . $timestamp, $seomoz_secret_key, true);
        $signature = urlencode(base64_encode($hash));
        $request = "http://lsapi.seomoz.com/linkscape/url-metrics/".$URL."?Cols=49152&AccessID=".$seomoz_access_id."&Expires=".$timestamp."&Signature=".$signature;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $request);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $Response = curl_exec($ch);
        
        if( preg_match( "/\"umrp\": (.*?),/is", $Response, $mtch ) ) {
            
            if( isset($mtch[1]) ) {
                $result['umrp'] = $mtch[1];
            } else {
                $Error = true;
            } // End if
            
        } else {
            $Error = true;
        } // End if
        
        if( preg_match( "/\"fmrp\": (.*?),/is", $Response, $mtch ) ) {
            
            if( isset($mtch[1]) ) {
                $result['fmrp'] = $mtch[1];
            } else {
                $Error = true;
            } // End if
            
        } else {
            $Error = true;
        } // End if
        
        if( $Error ) {
            $result['error'] = true;
        } // End if
        
        curl_close($ch);        
        
        return $result;
        
    } // End _GetFromSEOMoz
    
    
    /**
    * ���������� ������ � ������� seolib
    * ���������� �� index.php
    * ������ ������������� (index.php)
    * <code>
    *   list( 
    *        $TrustedLinks, 
    *        $UnTrustedLinks[5], 
    *        $UnTrustedLinksIDs[5], 
    *        $UnTrustedLinksReasons[5], 
    *        $ErrorLinksIDs[5]
    *    ) = LinksFilter::FilterSEOLib( $TrustedLinks );
    * </code>
    *
    * @access public
    *
    * @uses LinkFilter::_GetFromSEOLib() ��� ��������� ���������� ������ �� seolib
    *
    * @example index.php ������ ������������� � index.php
    *
    * @param array $Links ������ ������ ��� ����������
    * @return array ������ ( $TrustedLinks, $UnTrustedLinks, $UnTrustedLinksIDs, $UnTrustedLinksReasons, $ErrorLinksIDs ), $TrustedLinks - ����������������� ������ ( "ID" => "URL" ), $UnTrustedLinks - ����������� ���������� ������, $UnTrustedLinksIDs - ID-� ����������� ���������� ������, $UnTrustedLinksReasons - ������� ������, $ErrorLinksIDs - ������ � ��������
    *
    */
    static public function FilterSEOLib( $Links ) {
        
        require "Common.config.php";
        
        $TrustedLinks = $Links;
        $UnTrustedLinks = array();
        $UnTrustedLinksIDs = array();
        $UnTrustedLinksReasons = array();
        $ErrorLinksIDs = array();
        
        echo "<br><br><strong>SEOLib filtering...</strong>";
        
        foreach( $TrustedLinks as $ID => $URL ) {
            
            $URLSEOLibParameters = self::_GetFromSEOLib( $URL );
            
            if( !$URLSEOLibParameters['check_google_error'] && !$URLSEOLibParameters['check_google_error'] && !$URLSEOLibParameters['check_google_error'] ) {
                if( !$URLSEOLibParameters['check_yandex'] ) {
                    $UnTrustedLinks[] = $URL;
                    $UnTrustedLinksIDs[] = $ID;
                    $UnTrustedLinksReasons[$ID] = "�� ������ �������� API SEOlib (���������� ������� � �������)";
                    unset( $TrustedLinks[$ID] );
                } elseif( !$URLSEOLibParameters['check_google'] ) {
                    $UnTrustedLinks[] = $URL;
                    $UnTrustedLinksIDs[] = $ID;
                    $UnTrustedLinksReasons[$ID] = "�� ������ �������� API SEOlib (���������� ������� � �����)";
                    unset( $TrustedLinks[$ID] );
                } elseif( !$URLSEOLibParameters['check_ags'] ) {
                    $UnTrustedLinks[] = $URL;
                    $UnTrustedLinksIDs[] = $ID;
                    $UnTrustedLinksReasons[$ID] = "�� ������ �������� API SEOlib (��� ������ �������)";
                    unset( $TrustedLinks[$ID] );
                } // End if
            } else {
                $UnTrustedLinks[] = $URL;
                $UnTrustedLinksIDs[] = $ID;
                $UnTrustedLinksReasons[$ID] = "��������� ������ ��� �������� API SEOlib";
                $ErrorLinksIDs[] = $ID;
                unset( $TrustedLinks[$ID] );
            
            } // End if

        } // End foreach
                
        echo "<br><strong>SEOLib filtering finished</strong>";
        
        return array( $TrustedLinks, $UnTrustedLinks, $UnTrustedLinksIDs, $UnTrustedLinksReasons, $ErrorLinksIDs );
        
    } // End function FilterSEOLib
    
    
    /**
    * ����� ��� ��� ��������� ���������� ������ $URL �� seolib
    * ���������� �� LinksFilter::FilterSEOLib()
    * ������ �������������
    * <code>
    * $URLSEOLibParameters = self::_GetFromSEOLib( $URL );
    * </code>
    *
    * ������������ ��������� (Common.config.php)
    * <code>
    * $seolib_login = "...";
    * $seolib_password = "...";
    * </code>
    *
    * @access protected
    * @see LinksFilter::FilterSEOLib()
    * @link http://www.seolib.ru/script/xmlrpc/ �������� API seolib
    * @uses LinksFilter::_SEOLibRequest() ��� �������� � API seolib
    *
    * @param string $URL ������ �� ������� ���������� �������� ������
    * @return array ������, ��������� �����: bool check_yandex_error - ��������� ������ ��� �������� ���������� ��������, bool check_yandex - �������� ���������� �������� ��������, bool check_google_error - ��������� ������ ��� �������� ���������� ������, bool check_google - �������� ���������� ������ ��������, bool check_ags_error - ��������� ������ ��� �������� ��� ������ �������, bool check_yandex_error - �������� ��� ������ ������� ��������
    */
    protected function _GetFromSEOLib( $URL ) {
    
        require "Common.config.php";
        
        $result = array();
        
        // �������� ���������� URL �� ���������� � ������
        $data = self::_SEOLibRequest( "extlinks.checkYandexIndexedPage", serialize( array( $seolib_login, md5($seolib_password), $URL ) ) );
        
        if( isset( $data['isIndexed'] ) ) {
            $us_data = unserialize( htmlspecialchars_decode($data['isIndexed']) );
            if( isset( $us_data['result'] ) && ( $us_data['result'] == 1 ) ) {
                $result['check_yandex'] = true;
            } else {
                $result['check_yandex'] = false;
            } // End if
        } else {
            $result['check_yandex_error'] = true;
        } // End if
        
        // �������� ���������� URL �� ���������� � Google
        $data = self::_SEOLibRequest( "extlinks.checkGoogleIndexedPage", serialize( array( $seolib_login, md5($seolib_password), $URL ) ) );
        
        if( isset( $data['isIndexed'] ) ) {
            $us_data = unserialize( htmlspecialchars_decode($data['isIndexed']) );
            if( isset( $us_data['result'] ) && ( $us_data['result'] == 1 ) ) {
                $result['check_google'] = true;
            } else {
                $result['check_google'] = false;
            } // End if
        } else {
            $result['check_google_error'] = true;
        } // End if       
        
        // �������� ���������� URL �� ������ "���" � ������
        $data = self::_SEOLibRequest( "extlinks.checkYandexAGS", serialize( array( $seolib_login, md5($seolib_password), $URL ) ) );
        
        if( isset( $data['result'] ) ) {
            $us_data = unserialize( htmlspecialchars_decode($data['result']) );
            if( isset( $us_data['result'] ) && ( ( $us_data['result'] == 0 ) || ( $us_data['result'] == 4 ) ) ) {
                $result['check_ags'] = true;
            } elseif( isset( $us_data['result'] ) && ( $us_data['result'] == -1 ) ) {
                $result['check_ags_error'] = true;
            } else {
                $result['check_ags'] = false;
            } // End if
        } else {
            $result['check_ags_error'] = true;
        } // End if
                
        return $result;
        
    } // End _GetFromSEOLib
    
    
    /**
    * ������ � seolib (XML-RPC)
    * ���������� �� LinksLoader::_GetFromSEOLib()
    *
    * ������ �������������
    * <code>
    *    $data = self::_SEOLibRequest( 
    *        "extlinks.checkYandexIndexedPage", 
    *        serialize( array( $seolib_login, md5($seolib_password), $URL ) )
    *    );
    * </code>
    *
    * ������������ ��� ����������� � �������� �� ��������� ���������� ������. ���������� curl
    * @link http://docs.php.net/manual/ru/ref.curl.php �������� ������� PHP CURL
    * @see LinksLoader::_GetFromSEOLib()
    *
    * @access protected
    *
    * @param string $method �������� ������
    * @param array $params �� ��������� array(), ������������ � ������� ���������
    * @return array ������ � ����������� ������� � API
    *
    */ 
    protected function _SEOLibRequest( $method, $params = array()) {
        
        $URL = "http://www.seolib.ru/script/xmlrpc/server.php";
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
        
    } // End function _SEOLibRequest

    
    /**
    * ���������� ������ � ������� ���������� ��������� � ����� ��������� ������, ���������� "board", "(php|ya|fast)bb" � �.�.
    * ���������� �� index.php
    * ������ ������������� (index.php)
    * <code>
    *   list( 
    *        $TrustedLinks, 
    *        $UnTrustedLinks[0], 
    *        $UnTrustedLinksIDs[0], 
    *        $UnTrustedLinksReasons[0], 
    *    ) = LinksFilter::FilterRegex( $TrustedLinks );
    * </code>
    *
    * @access public
    *
    * @uses LinkFilter::_GetRegexFilterArray() ��� ��������� ������� ������ �� ������� RegexFilter.class.php
    *
    * @example index.php ������ ������������� � index.php
    *
    * @param array $Links ������ ������ ��� ����������
    * @return array ������ ( $TrustedLinks, $UnTrustedLinks, $UnTrustedLinksIDs, $UnTrustedLinksReasons ), $TrustedLinks - ����������������� ������ ( "ID" => "URL" ), $UnTrustedLinks - ����������� ���������� ������, $UnTrustedLinksIDs - ID-� ����������� ���������� ������, $UnTrustedLinksReasons - ������� ������
    *
    */
	static public function FilterRegex( $Links ) {
    
        $TrustedLinks = $Links;
        $UnTrustedLinks = array();
        $UnTrustedLinksIDs = array();
        $UnTrustedLinksReasons = array();
        
        echo "<strong>Regex filtering...</strong>";
        
        $RegexFilterArray = self::_GetRegexFilterArray();
        
        $RegexFilterArrayPat = array();
        foreach( $RegexFilterArray as $FilterStr ) {
            $RegexFilterArrayPat[] = "(" . $FilterStr . ")";
        } // End foreach

        $RegexFilter = "@" . implode( "|", $RegexFilterArrayPat ) . "@is";
        
        foreach( $TrustedLinks as $ID => $URL ) {
            
            $URL = preg_replace( "/http:\/\//is", "", $URL );
            $URLArray = explode( "/", $URL );
            unset( $URLArray[0]);
            $URL = "/" . implode( "/", $URLArray );
            
            if( preg_match( $RegexFilter, $URL, $mtch ) ) {
                $UnTrustedLinks[] = $URL;
                $UnTrustedLinksIDs[] = $ID;
                $UnTrustedLinksReasons[$ID] = "�� ������ �������� �� Regex";
                unset( $TrustedLinks[$ID] );
            } // End if
            
        } // End foreach
        
        echo "<br><strong>Regex filtering finished</strong>";
        
        return array( $TrustedLinks, $UnTrustedLinks, $UnTrustedLinksIDs, $UnTrustedLinksReasons );
        
    } // End function FilterRegex
    
    
    /**
    * �������� �� Alexa
    * ���������� �� index.php
    * ������ ������������� (index.php)
    * <code>
    *   list( 
    *        $TrustedLinks, 
    *        $UnTrustedLinks[1], 
    *        $UnTrustedLinksIDs[1], 
    *        $UnTrustedLinksReasons[1], 
    *        $ErrorLinksIDs[1]
    *    ) = LinksFilter::FilterAlexa( $TrustedLinks );
    * </code>
    *
    * @access public
    *
    * @uses LinkFilter::_GetAlexaPopularity() ��� ��������� ���������� ������ �� Alexa
    *
    * @example index.php ������ ������������� � index.php
    *
    * @param array $Links ������ ������ ��� ����������
    * @return array ������ ( $TrustedLinks, $UnTrustedLinks, $UnTrustedLinksIDs, $UnTrustedLinksReasons, $ErrorLinksIDs ), $TrustedLinks - ����������������� ������ ( "ID" => "URL" ), $UnTrustedLinks - ����������� ���������� ������, $UnTrustedLinksIDs - ID-� ����������� ���������� ������, $UnTrustedLinksReasons - ������� ������, $ErrorLinksIDs - ������ � ��������
    *
    */
    static public function FilterAlexa( $Links ) {
        
        require "Common.config.php";
        
        $TrustedLinks = $Links;
        $UnTrustedLinks = array();
        $UnTrustedLinksIDs = array();
        $UnTrustedLinksReasons = array();
        $ErrorLinksIDs = array();
        
        echo "<br><br><strong>Alexa filtering...</strong>";
        
        echo "<br>ALEXA BORDER = " . $AlexaPopularityBorder . "<br>";
        
        foreach( $TrustedLinks as $ID => $URL ) {
            
            $URLAlexaPopularity = self::_GetAlexaPopularity($URL);
            if( ( $URLAlexaPopularity !== false ) && ( $URLAlexaPopularity == 0 ) ) {
                $UnTrustedLinks[] = $URL;
                $UnTrustedLinksIDs[] = $ID;
                $UnTrustedLinksReasons[$ID] = "�� ������ �������� �� Alexa";
                unset( $TrustedLinks[$ID] );
            } // End if
            
        } // End foreach
        
        echo "<br><strong>Alexa filtering finished</strong>";
        
        return array( $TrustedLinks, $UnTrustedLinks, $UnTrustedLinksIDs, $UnTrustedLinksReasons, $ErrorLinksIDs );
        
    } // End function FilterAlexa
    
    
    /**
    * �������� �� LiveInternet
    * ���������� �� index.php
    * ������ ������������� (index.php)
    * <code>
    *   list( 
    *        $TrustedLinks, 
    *        $UnTrustedLinks[2], 
    *        $UnTrustedLinksIDs[2], 
    *        $UnTrustedLinksReasons[2], 
    *        $ErrorLinksIDs[2]
    *    ) = LinksFilter::FilterLiveInternet( $TrustedLinks );
    * </code>
    *
    * @access public
    *
    * @uses LinkFilter::_GetLIData() ��� ��������� ���������� ������ �� liveinternet
    *
    * @example index.php ������ ������������� � index.php
    *
    * @param array $Links ������ ������ ��� ����������
    * @return array ������ ( $TrustedLinks, $UnTrustedLinks, $UnTrustedLinksIDs, $UnTrustedLinksReasons, $ErrorLinksIDs ), $TrustedLinks - ����������������� ������ ( "ID" => "URL" ), $UnTrustedLinks - ����������� ���������� ������, $UnTrustedLinksIDs - ID-� ����������� ���������� ������, $UnTrustedLinksReasons - ������� ������, $ErrorLinksIDs - ������ � ��������
    *
    */
    static public function FilterLiveInternet( $Links ) {
        
        require "Common.config.php";
        
        $TrustedLinks = $Links;
        $UnTrustedLinks = array();
        $UnTrustedLinksIDs = array();
        $ErrorLinksIDs = array();
        
        echo "<br><br><strong>LiveInternet filtering...</strong>";
        
        echo "<br>LI_month_vis BORDER = " . $LI_month_vis . "<br>";
        
        foreach( $TrustedLinks as $ID => $URL ) {
        
            $URLLIParameter = self::_GetLIData($URL);
            
            if( !$URLLIParameter[0] && !$URLLIParameter[1] ) {
                
                if( !$URLLIParameter[0] && ( $URLLIParameter[2] < $LI_month_vis ) ) {
                    $UnTrustedLinks[] = $URL;
                    $UnTrustedLinksIDs[] = $ID;
                    $UnTrustedLinksReasons[$ID] = "�� ������ �������� �� LiveInternet (" . $URLLIParameter[2] . ")";
                    unset( $TrustedLinks[$ID] );
                } elseif( $URLLIParameter[0] ) {
                    $UnTrustedLinks[] = $URL;
                    $UnTrustedLinksIDs[] = $ID;
                    $ErrorLinksIDs[] = $ID;
                    $UnTrustedLinksReasons[$ID] = "��������� ������ ��� �������� �� LiveInternet";
                    unset( $TrustedLinks[$ID] );
                } // End if
                
            } // End if
            
        } // End foreach
        
        echo "<br><strong>LiveInternet filtering finished</strong>";
        
        return array( $TrustedLinks, $UnTrustedLinks, $UnTrustedLinksIDs, $UnTrustedLinksReasons, $ErrorLinksIDs );
        
    } // End function FilterLiveInternet
    
    
    /**
    * ������ � LiveInternet
    * ���������� �� LinksLoader::FilterLiveInternet()
    *
    * ������ �������������
    * <code>
    *    $URLLIParameter = self::_GetLIData($URL);
    * </code>
    *
    * ������������ ��� ��������� ������������ �� Liveinternet. ���������� curl
    * @link http://docs.php.net/manual/ru/ref.curl.php �������� ������� PHP CURL
    * @see LinksLoader::FilterLiveInternet()
    *
    * @access protected
    *
    * @param string $URL �����, �� ������� ���������� ������������
    * @return array ������ � ������� bool $Error - ���� �� ������ ��� ��������� � liveinternet, bool $NotRegistered - ���� �� ��������������� � liveinternet, $LI_month_vis - ���������� �� �����
    *
    */
    protected function _GetLIData($URL) {
    
        $Error = false;
        $NotRegistered = false;
        $LI_month_vis = 0;
        
        $URLtoLI = preg_replace( "/http\:\/\//is", "", $URL );
        $URLtoLI_array = explode( "/", $URLtoLI );       
        $URLtoLI = $URLtoLI_array[0];
        $LIRequestURL = "http://counter.yadro.ru/values?site=".$URLtoLI;
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $LIRequestURL);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $Response = curl_exec($ch);
        
        if( preg_match( "/LI_error/is", $Response ) ) {
            $Error = true;
        } // End if
        
        if( preg_match( "/LI_month_vis(.*?)=(.*?)(\d+);/is", $Response, $mtch ) ) {
            if( isset($mtch[3]) ) {
                $LI_month_vis = intval($mtch[3]);
            } else {
                $Error = true;
            } // End if
        } else {
            $Error = true;
        } // End if

        if( preg_match( "/Unregistered site:/is", $Response ) ) {
            $Error = false;
            $NotRegistered = true;
        } // End if
        
        curl_close($ch);        
        
        return array( $Error, $NotRegistered, $LI_month_vis );        
        
    } // End function _GetLIData
    
    
    /**
    * ������ � Alexa
    * ���������� �� LinksLoader::FilterAlexa()
    *
    * ������ �������������
    * <code>
    *    $URLAlexaPopularity = self::_GetAlexaPopularity($URL);
    * </code>
    *
    * @uses PageFilter::GetCommonDomain() ��� ��������� ������ ������ $URL
    *
    * ������������ ��� ��������� ������������ �� Alexa. ���������� curl
    * @link http://docs.php.net/manual/ru/ref.curl.php �������� ������� PHP CURL
    * @see LinksLoader::FilterAlexa()
    * @see PageFilter::GetCommonDomain()
    *
    * @access protected
    *
    * @param string $URL �����, �� ������� ���������� ������������
    * @return int Alexa rank
    *
    */
    protected function _GetAlexaPopularity($URL) {
        
        sleep(1);
        $ch = curl_init();
        
        $URIDomain = PageFilter::GetCommonDomain($URL);
        
        $AlexaRequestURL = "http://data.alexa.com/data?cli=10&dat=s&url=".urlencode($URIDomain);
        
        curl_setopt($ch, CURLOPT_URL, $AlexaRequestURL);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $Response = curl_exec($ch);
        
        if( preg_match( "/^<\?xml/is", trim($Response), $mt ) ) {
            $xml = simplexml_load_string($Response);
            return intval($xml->SD[1]->POPULARITY['TEXT']); 
        } else {
            return false;
        } // End if
        
        curl_close($ch);        
        
    } // End function _GetAlexaPopularity
    
    
    /**
    * ��������� ������� "������" ������ REQUEST_URI �� ������� RegexFilter.class.php
    * ���������� �� LinksLoader::FilterRegex()
    *
    * ������ �������������
    * <code>
    *    $RegexFilterArray = self::_GetRegexFilterArray();
    * </code>
    *
    * @see LinksLoader::FilterRegex()
    *
    * @access protected
    *
    * @return array ������ ���������� ��������� ��� ����������
    *
    */
    protected function _GetRegexFilterArray() {
        require_once "RegexFilter.config.php";
        return $RegexFilterArray;
    } // End function _GetRegexFilterArray
	
} // End class LinksFilter