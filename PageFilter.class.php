<?php
/**
* ����������� ������ ��� �������� ������ �� ���������� ����� �������
*
* @package SAPE
*/

/**
* ����� ��� ��� �������� ������ �� ���������� ����� �������
*
* @package SAPE
* @author Chevanin Valeriy <chevanin@etorg.ru>
* @todo ��� ��������� ������� � �����������<br>����������� ���, ��� �������� (�������������� ������� � ���� �������� ������ ����� ������ � ����� �������������� �������)<br>[_GetPageData]$NoCheckingDomains - ������ ����������� (�� ����������� ��������) ������� (��������) ������� � ������
*/
class PageFilter {

    /**
    * �������� <ul><li>�� ���-�� ���������� ������, ���-�� ������� ������,</li> <li>���-�� �������� �� ��������,</li> <li>�� �������� ������� ����������� ��� ������ 2-��� ����������� ������,</li> <li>�� ����-����� �� �������� � � ������� ������.</li></ul> <br>���������� �������� ��� ��������������� ����������� ������� �� Common.config.php
    * ���������� �� index.php
    * ������ ������������� (index.php)
    * <code>
    *   list( 
    *        $TrustedLinks, 
    *        $UnTrustedLinks[3], 
    *        $UnTrustedLinksIDs[3], 
    *        $UnTrustedLinksReasons[3], 
    *        $ErrorLinksIDs[3]
    *    ) = PageFilter::FilterLinksCount( $TrustedLinks, $NestingArray );
    * </code>
    *
    * @access public
    *
    * @uses PageFilter::_GetPageData() ��� ��������� ���������� ������ ��������
    * @uses PageFilter::_GetCommonDomain() ��� ����������� ������ ������
    * @uses PageFilter::_GetRequestURI() ��� ����������� REQUEST_URI ������
    * @see Common.config.sample.php
    *
    * @example index.php ������ ������������� � index.php
    *
    * @param array $Links ������ ������ ��� ����������
    * @param array $NestingArray ������ ������� ����������� ������
    * @return array ������ ( $TrustedLinks, $UnTrustedLinks, $UnTrustedLinksIDs, $UnTrustedLinksReasons, $ErrorLinksIDs ), $TrustedLinks - ����������������� ������ ( "ID" => "URL" ), $UnTrustedLinks - ����������� ���������� ������, $UnTrustedLinksIDs - ID-� ����������� ���������� ������, $UnTrustedLinksReasons - ������� ������, $ErrorLinksIDs - ������ � ��������
    *
    */
	static public function FilterLinksCount( $Links, $NestingArray ) {
    
        require "Common.config.php";
        
        $TrustedLinks = $Links;
        $UnTrustedLinks = array();
        $UnTrustedLinksIDs = array();
        $UnTrustedLinksReasons = array();
        $ErrorLinksIDs = array();
        
        echo "<strong>Page parameters filtering...</strong>";
                
        foreach( $TrustedLinks as $ID => $URL ) {
            
            $URLPageParameters = self::_GetPageData($URL);
            
            $InnerLinksCountOK = false;
            $OuterLinksCountOK = false;
            $SymbolsOnPageCountOK = false;
            $RealLevelOK = false;
            $SWTextOK = false;
            $SWLinkOK = false;
            
            if( $URLPageParameters['avl'] ) {
            
                // Test for outer/inner links count by levels
                if( isset( $NestingArray[$ID] ) && ( $URLPageParameters['outer_links_cnt'] <= $OuterLinksLimit[intval($NestingArray[$ID])] ) )
                    $OuterLinksCountOK = true;
                
                if( isset( $NestingArray[$ID] ) && ( $URLPageParameters['inner_links_cnt'] <= $InnerLinksLimit[intval($NestingArray[$ID])] ) )
                    $InnerLinksCountOK = true;
                    
                // Test for clear symbols on page count
                if( $URLPageParameters['symbols_cnt'] >= $SymbolsOnPageCountLimit )
                    $SymbolsOnPageCountOK = true;
                    
                // Test for stop words in text
                if( $URLPageParameters['stop_words_cnt'] < $URLPageParameters['max_stop_words_cnt'] )
                    $SWTextOK = true;
                
                // Test for stop words in links text
                if( $URLPageParameters['stop_words_in_links_cnt'] == 0 )
                    $SWLinkOK = true;
                
                
                // Checking real 2-nd level
                if( isset( $NestingArray[$ID] ) && ( $NestingArray[$ID] == 2 ) && ( $InnerLinksCountOK && $OuterLinksCountOK && $SymbolsOnPageCountOK ) ) {
                    $DomainURL = self::_GetCommonDomain($URL);
                    $CommonURLPageParameters = self::_GetPageData("http://" . $DomainURL);
                    
                    if( $CommonURLPageParameters['avl'] ) { 
                        
                        foreach( $CommonURLPageParameters['links'] as $MainPageLink ) {
                            
                            $RequestURI = self::_GetRequestURI($URL);
                            $MainPageLinkRequestURI = self::_GetRequestURI($MainPageLink['url']);
                            
                            $MainPageLinkRequestURI = preg_replace( "/#(.*?)$/is", "", $MainPageLinkRequestURI );
                            $RequestURI = preg_replace( "/#(.*?)$/is", "", $RequestURI );
                            
                            if( !isset($MainPageLink['outer']) && ( htmlspecialchars_decode($MainPageLinkRequestURI) == htmlspecialchars_decode($RequestURI) ) ) {
                                $RealLevelOK = true;
                            } // End if
                        } // End foreach
                        
                    } // End if
                    
                } else {
                    $RealLevelOK = true;
                } // End if
                                
                if( !$InnerLinksCountOK || !$OuterLinksCountOK || !$SymbolsOnPageCountOK || !$RealLevelOK || !$SWTextOK || !$SWLinkOK ) {
                    $UnTrustedLinks[] = $URL;
                    $UnTrustedLinksIDs[] = $ID;
                    unset( $TrustedLinks[$ID] );
                    
                    $UnTrustedLinksReasons[$ID] = "";
                    if( !$InnerLinksCountOK ) {
                        $UnTrustedLinksReasons[$ID] .= "�� ������ �� ���-�� ���������� ������ ";
                    } // End if
                    
                    if( !$OuterLinksCountOK ) {
                        $UnTrustedLinksReasons[$ID] .= "�� ������ �� ���-�� ������� ������ ";
                    } // End if
                    
                    if( !$SymbolsOnPageCountOK ) {
                        $UnTrustedLinksReasons[$ID] .= "�� ������ �� ���-�� �������� ";
                    } // End if
                    
                    if( !$RealLevelOK ) {
                        $UnTrustedLinksReasons[$ID] .= "�� ������ �� ��������� 2-��� ������ ";
                    } // End if
                    
                    if( !$SWTextOK ) {
                        $UnTrustedLinksReasons[$ID] .= "C���-���� � ������ ������ " . $URLPageParameters['max_stop_words_cnt'] . " ";
                    } // End if
                    
                    if( !$SWLinkOK ) {
                        $UnTrustedLinksReasons[$ID] .= "� ������� ������� ������ ���� ����-����� ";
                    } // End if
                    
                } // End if
                
            } else {
                if( !$URLPageParameters['error_status'] || ( $URLPageParameters['error_status'] == 500 ) || ( $URLPageParameters['error_status'] == 501 ) || ( $URLPageParameters['error_status'] == 502 ) || ( $URLPageParameters['error_status'] == 503 ) || ( $URLPageParameters['error_status'] == 504 ) ) {
                    $UnTrustedLinks[] = $URL;
                    $UnTrustedLinksIDs[] = $ID;
                    $UnTrustedLinksReasons[$ID] = "��������� ������ ��� ��������� � �������� - �������� ����";
                    $ErrorLinksIDs[] = $ID;
                    unset( $TrustedLinks[$ID] );
                } else {
                    $UnTrustedLinks[] = $URL;
                    $UnTrustedLinksIDs[] = $ID;
                    $UnTrustedLinksReasons[$ID] = "�������� ���������� (������ ������ " . $URLPageParameters['error_status'] . ")";
                    unset( $TrustedLinks[$ID] );
                } // End if
            } // End if
            
        } // End foreach
        
        echo "<br><strong>Page parameters filtering finished</strong>";
        
        return array( $TrustedLinks, $UnTrustedLinks, $UnTrustedLinksIDs, $UnTrustedLinksReasons, $ErrorLinksIDs );
        
    } // End function FilterLinksCount
    
    
    /**
    * ���������� REQUEST_URI ������
    * ���������� �� PageFilter::FilterLinksCount()
    * ������ �������������
    * <code>
    *   $RequestURI = self::_GetRequestURI($URL);
    * </code>
    *
    * @access protected
    *
    * @param string $URL URL ��� ����������� REQUEST_URI
    * @return string REQUEST_URI ������
    *
    */
    protected function _GetRequestURI($URL) {
        
        $BaseURL = $URL;
        $URL = preg_replace( "/http:\/\//is", "", $URL );
        
        if( !preg_match( "/^\//is", $URL ) && !preg_match( "/http:\/\//is", $BaseURL ) )
            $URL = "/" . $URL;
        
        $URLArray = explode( "/", $URL );
        unset( $URLArray[0]);
        $RequestURI = "/" . implode( "/", $URLArray );            
        
        return $RequestURI;

    } // End function _GetRequestURI


    /**
    * ��������� ���������� ��������: <ul><li>��� ������ �������� (���� �� 200, �� ������������� ���������� ����� 'avl' => false),</li> <li>���-�� ���������� ������,</li> <li>���-�� ������� ������,</li> <li>������ ������,</li> <li>���-�� ����-���� � ������� ������,</li> <li>���-�� ����-���� � ������,</li> <li>���-�� �������� "�������" ������</li></ul><br>���������� curl
    * ���������� �� PageFilter::FilterLinksCount()
    * ������ �������������
    * <code>
    *   $URLPageParameters = self::_GetPageData($URL);
    * </code>
    *
    * @access protected
    *
    * @link http://docs.php.net/manual/ru/ref.curl.php �������� ������� PHP CURL
    * @uses PageFilter::_GetLinks() ��� ��������� ������ �� ��������
    * @uses PageFilter::_GetCommonDomain() ��� ����������� ������ ������
    * @uses PageFilter::_GetSWMaskFilterArray() ��� ��������� ����-����
    * @see RegexFiter.config.sample.php
    *
    * @param string $URL URL ��������
    * @return array ������ � �������: <ul><li>avl (bool) - �������� �� ������ (200 OK),</li> <li>inner_links_cnt (integer) - ���-�� ���������� ������,</li> <li>outer_links_cnt (integer) - ���-�� ������� ������,</li> <li>links - ������ ������ �� �������� array( 'url' => <����� ������>, 'text' => <����� ������>, 'outer' => <bool ������� �� ������>, 'have_stop_words' => <bool �������� �� ����� ������ ����-�����>),</li> <li>stop_words_in_links_cnt (integer) - ���-�� ����-���� � �������,</li> <li>stop_words_cnt (integer) - ���-�� ����-���� � ������,</li> <li>max_stop_words_cnt (integer) - ���������� ���-�� ����-���� � ������ �� ������� RegexFiter.config.sample.php,</li> <li>symbols_cnt (integer) - ���-�� �������� "�������" ������ ��������,</li> <li>error_status (string) - ������ ������, ���� ������� �� 200</li></ul>
    */
    protected function _GetPageData($URL) {
    
        $PageParams = array();
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $URL);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $Response = curl_exec($ch);
        curl_close($ch);
        
        if( preg_match( "/Content-Type:(.*?)charset=(.*?)\s/is", $Response, $mt ) ) {
            if( isset($mt[2]) && ( $mt[2] != "" ) ) {
                if( $newResponse = iconv( $mt[2], "Windows-1251//IGNORE", $Response ) ) {
                    $Response = $newResponse;
                } // End if
            } // End if
        } // End if
        
        
        $Response = preg_replace( "/<!--(.*?)-->/is", "", $Response );
        $Response = preg_replace( "/<script(.*?)<\/script>/is", "", $Response );
        $Response = preg_replace( "/<noindex>(.*?)<\/noindex>/is", "", $Response );
        $Response = preg_replace( "/<\!--(.*?)noindex(.*?)-->(.*?)<\!--(.*?)\/noindex(.*?)-->/is", "", $Response );
        $Response = preg_replace( "/<noindex>(.*?)$/is", "</body>", $Response );

        $CommonDomain = self::_GetCommonDomain($URL);

        // ��������� �� 200 OK
        if( preg_match( "/HTTP\/(.*?)\s200\sOK/is", $Response ) ) {
        
            $PageParams['avl'] = true;
            $PageParams['inner_links_cnt'] = 0;
            $PageParams['outer_links_cnt'] = 0;
            $PageParams['links'] = self::_GetLinks($Response);
            $PageParams['stop_words_in_links_cnt'] = 0;
            
            $outer_links = array();
                
            list( $MaxAvailCountInText, $StopWordsMasks ) = self::_GetSWMaskFilterArray();
            $PageParams['stop_words_cnt'] = 0;
            $PageParams['max_stop_words_cnt'] = $MaxAvailCountInText;
            
            $RegexFilterArrayPat = array();
            foreach( $StopWordsMasks as $FilterStr ) {
                $RegexFilterArrayPat[] = "(\s" . $FilterStr . "\s)";
            } // End foreach

            $RegexFilter = "@" . implode( "|", $RegexFilterArrayPat ) . "@is";
            
            foreach( $PageParams['links'] as $link_id => $link ) {
                if( preg_match( "/^http:\/\//is", $link['url'] ) ) {
                    if( !preg_match( "/javascript:/is", $link['url'] ) && !preg_match( "/mailto:/is", $link['url'] ) ) {
                    
                        $link_domain = self::_GetCommonDomain($link['url'], false);
                        $link_domain_www = self::_GetCommonDomain($link['url'], true);
                        $CommonDomain_nowww = self::_GetCommonDomain($URL, false);

                        $NoCheckingDomains = array(
                            "top100.rambler.ru",
                            "liveinternet.ru",
                            "top.mail.ru",
                            "mc.yandex.ru",
                        );
                        
                        if( ( $link_domain == $CommonDomain ) || ( $link_domain_www == $CommonDomain ) || ( $link_domain == $CommonDomain_nowww ) || ( $link_domain_www == $CommonDomain_nowww ) ) {
                            $PageParams['inner_links_cnt']++;
                        } elseif( in_array( $link_domain, $NoCheckingDomains ) || in_array( $link_domain_www, $NoCheckingDomains ) ) {
                            $PageParams['inner_links_cnt']++;
                        } else {
                            if( !in_array( $link['url'], $outer_links ) && ( trim($link_domain) != "" ) ) {
                                $PageParams['outer_links_cnt']++;
                                $outer_links[] = $link['url'];
                                $PageParams['links'][$link_id]['outer'] = true;
                            } // End if
                        } // End if
                        
                    } // End if
                } else {
                    if( !preg_match( "/javascript:/is", $link['url'] ) && !preg_match( "/mailto:/is", $link['url'] ) ) {
                        $PageParams['inner_links_cnt']++;
                    } // End if
                } // End if
                                
                if( ( $PageParams['links'][$link_id]['outer'] == true ) && preg_match( $RegexFilter, $link['text'], $mtch ) ) {
                    $PageParams['links'][$link_id]['have_stop_words'] = true;
                    $PageParams['stop_words_in_links_cnt']++;
                } // End if
                
            } // End foreach
            
            // ������� ���������� �������� �� �������� ����� ������ BODY, ��� ����� ������� ������
            $PageParams['symbols_cnt'] = 0;
            if( preg_match( "/<body(.*?)>(.*?)$/is", $Response, $mt ) ) {
            
                $BodyText = $mt[2];
                $BodyText = preg_replace( "/<style(.*?)<\/style>/is", "", $BodyText );
                $BodyText = preg_replace( "/<a(.*?)href(.*?)=(.*?)<\/a>/is", "", $BodyText );
                $BodyText = preg_replace( "/<(.*?)>/is", "", $BodyText );              
                $PageParams['symbols_cnt'] = strlen( trim($BodyText) );
                
                if( preg_match_all( $RegexFilter, $BodyText, $mtch, PREG_SET_ORDER ) ) {
                    $PageParams['stop_words_cnt'] += count( $mtch );
                } // End if
                                
            } // End if
        
        } else {
            $PageParams['avl'] = false;
            if( preg_match( "/HTTP\/(.*?)\s(\d{3}?)\s/is", $Response, $mt ) ) {
                $PageParams['error_status'] = $mt[2];
            } else {
                $PageParams['error_status'] = false;
            } // End if
        } // End if
        
        return $PageParams;        
        
    } // End function _GetPageData
    
    
    /**
    * ��������� ������� ������ �� HTML ���� ��������
    * ���������� �� PageFilter::_GetPageData()
    * ������ �������������
    * <code>
    *   $PageParams['links'] = self::_GetLinks($Response);
    * </code>
    *
    * @access protected
    *
    * @param string $Text HTML ��� ��������
    * @return array ������ ������, ������ ������� ������� - ������ � ������� 'url' � 'text'
    */
    protected function _GetLinks($Text) {
    
        // Clearing text - deleting noindex blocks
        $Text = preg_replace( "/<noindex>(.*?)<\/noindex>/is", "", $Text );
        $Text = preg_replace( "/<\!-- noindex -->(.*?)<\!-- \/noindex -->/is", "", $Text );
        
        $links = array();
        if( preg_match_all( "/<a([^<>]*)>(.*?)<\/a>/is", $Text, $LnkMtch, PREG_SET_ORDER ) ) {
        
            foreach( $LnkMtch as $LinkMArray ) {
            
                $nofollow = false;
                $have_href = false;
                if( preg_match( "/href=\"(.*?)\"/is", $LinkMArray[1], $mt ) || preg_match( "/href=\'(.*?)\'/is", $LinkMArray[1], $mt ) ) {
                
                    $have_href = true;
                    
                    if( preg_match( "/(rel=\"(.*?)\s(nofollow)\s(.*?)\")|(rel=\"(.*?)\s(nofollow)\")|(rel=\"(nofollow)\s(.*?)\")|(rel=\"(nofollow)\")/is", $LinkMArray[1], $mt2 ) || 
                    preg_match( "/(rel=\'(.*?)\s(nofollow)\s(.*?)\')|(rel=\'(.*?)\s(nofollow)\')|(rel=\'(nofollow)\s(.*?)\')|(rel=\'(nofollow)\')/is", $LinkMArray[1], $mt2 ) ) {
                        $nofollow = true;
                    } // End if
                    
                } // End if
                
                if( !$nofollow && $have_href ) {
                    $link = array( 
                        'url' => $mt[1],
                        'text' => $LinkMArray[2]
                    );
                    $links[] = $link;
                } // End if
                
            } // End foreach
            
        } // End if
        
        return $links;
        
    } // End function _GetLinks
    
    
    /**
    * ���������� �������� ����� $URL
    * ���������� �� PageFilter::FilterLinksCount()
    * ������ �������������
    * <code>
    *   $DomainURL = self::_GetCommonDomain($URL);
    * </code>
    *
    * @access protected
    *
    * @param string $URL URL ��� ����������� ��������� ������
    * @param bool $Is3LevelOuter ������� �� ������ � www ��������� ��� www
    * @return string �������� ����� ������
    */
    protected function _GetCommonDomain($URL, $Is3LevelOuter = true ) {
    
        $CommonDomain = preg_replace( "/http:\/\//is", "", $URL );
        $CommonDomainArray = explode( "/", $CommonDomain );
        $CommonDomain = $CommonDomainArray[0];
        
        if( !$Is3LevelOuter ) {
            $CommonDomainLvlArray = explode( ".", $CommonDomain );   
            if( ( count( $CommonDomainLvlArray ) == 3 ) && ( $CommonDomainLvlArray[0] == "www" ) ) {
                $CommonDomain = $CommonDomainLvlArray[count($CommonDomainLvlArray)-2] . "." . $CommonDomainLvlArray[count($CommonDomainLvlArray)-1];
            } // End if
        } // End if
        
        return $CommonDomain;
        
    } // End function _GetCommonDomain
    
    
    /**
    * ������� ��� PageFilter::_GetCommonDomain()
    *
    * @access public
    * @see PageFilter::_GetCommonDomain()
    *
    * @param string $URL URL ��� ����������� ��������� ������
    * @param bool $Is3LevelOuter ������� �� ������ ���� www2.site.ru �������� (3-��� ������)
    * @return string �������� ����� ������
    */
    public function GetCommonDomain($URL, $Is3LevelOuter = true ) {
    
        return self::_GetCommonDomain($URL, $Is3LevelOuter);
        
    } // End function _GetCommonDomain


    /**
    * ��������� ������� ����-���� � ��������� ����������� ���-�� ����-���� �� �������� �� ������� RegexFilter.class.php
    * ���������� �� LinksLoader::_GetPageData()
    *
    * ������ �������������
    * <code>
    *    list( $MaxAvailCountInText, $StopWordsMasks ) = self::_GetSWMaskFilterArray();
    * </code>
    *
    * @access protected
    *
    * @return array ������ � ���������� $MaxAvailCountInText - ��������� ���������� ���-�� ����-���� �� ��������, $StopWordsMasks - ������ ����-����
    */
    protected function _GetSWMaskFilterArray() {
        require "RegexFilter.config.php";
        return array( $MaxAvailCountInText, $StopWordsMasks );
    } // End function _GetSWMaskFilterArray
	
} // End class PageFilter