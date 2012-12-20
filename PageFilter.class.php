<?php
/**
 * 
 * Класс для филрации ссылок по параметрам самих страниц
 * 
 * @todo все включения кофигов в конструктор
 * 
 */
class PageFilter {

    //var $ToLog = false;
	
	static public function FilterLinksCount( $Links, $NestingArray ) {
    
        require "Common.config.php";
        
        $TrustedLinks = $Links;
        $UnTrustedLinks = array();
        $UnTrustedLinksIDs = array();
        $UnTrustedLinksReasons = array();
        
        echo "<strong>Page parameters filtering...</strong>";
                
        /*
        $TrustedLinksA = $TrustedLinks;
        $TrustedLinks = array();
        $TrustedLinks[8] = $TrustedLinksA[8];
        var_dump( $TrustedLinks );
        */
        /*
        $TrustedLinks = array();
        $TrustedLinks[] = "http://proekt-41.ru/index.php?option=com_content&view=article&id=19&Itemid=20";
        $NestingArray = array();
        $NestingArray[] = 2;
        */
        
        
        foreach( $TrustedLinks as $ID => $URL ) {
            
            $URLPageParameters = self::_GetPageData($URL);
            
            /*
            echo $URL . "<br>";
            echo "<pre>";
            var_dump( $URLPageParameters );
            echo "</pre>";
            */
            
            $InnerLinksCountOK = false;
            $OuterLinksCountOK = false;
            $SymbolsOnPageCountOK = false;
            $RealLevelOK = false;
            
            if( $URLPageParameters['avl'] ) {
            
                // Test for outer/inner links count by levels
                if( isset( $NestingArray[$ID] ) && ( $URLPageParameters['outer_links_cnt'] <= $OuterLinksLimit[intval($NestingArray[$ID])] ) )
                    $OuterLinksCountOK = true;
                
                if( isset( $NestingArray[$ID] ) && ( $URLPageParameters['inner_links_cnt'] <= $InnerLinksLimit[intval($NestingArray[$ID])] ) )
                    $InnerLinksCountOK = true;
                    
                // Test for clear symbols on page count
                if( $URLPageParameters['symbols_cnt'] >= $SymbolsOnPageCountLimit )
                    $SymbolsOnPageCountOK = true;
                
                
                if( isset( $NestingArray[$ID] ) && ( $NestingArray[$ID] == 2 ) && ( $InnerLinksCountOK && $OuterLinksCountOK && $SymbolsOnPageCountOK ) ) {
                    $DomainURL = self::_GetCommonDomain($URL);
                    $CommonURLPageParameters = self::_GetPageData("http://" . $DomainURL);
                    
                    if( $CommonURLPageParameters['avl'] ) { 
                        
                        foreach( $CommonURLPageParameters['links'] as $MainPageLink ) {
                            
                            // can bee outer links
                            /*
                            var_dump( $MainPageLink );
                            echo "<br>";
                            */
                            
                            //$RequestURIDomain = self::_GetCommonDomain($MainPageLink['url']);
                            //echo "RequestURIDomain=" . $RequestURIDomain;
                            //echo "<br>";
                            
                            $RequestURI = self::_GetRequestURI($URL);
                            $MainPageLinkRequestURI = self::_GetRequestURI($MainPageLink['url']);
                            
                            /*
                            echo "main url=" . $URL;
                            echo "<br>";
                            echo "url=" . $MainPageLink['url'];
                            echo "<br>";
                            echo "MainPageLinkRequestURI=" . $MainPageLinkRequestURI;
                            echo "<br>";
                            echo "RequestURI=" . $RequestURI;
                            echo "<br>";
                            var_dump( htmlspecialchars($RequestURI) );
                            echo "<br>";
                            var_dump( htmlspecialchars($MainPageLinkRequestURI) );
                            echo "<br>";
                            echo "<br>";
                            var_dump( htmlspecialchars(htmlspecialchars_decode($RequestURI)) );
                            echo "<br>";
                            var_dump( htmlspecialchars(htmlspecialchars_decode($MainPageLinkRequestURI)) );
                            echo "<br>";
                            echo "<br>";
                            */
                            
                            if( !isset($MainPageLink['outer']) && ( htmlspecialchars_decode($MainPageLinkRequestURI) == htmlspecialchars_decode($RequestURI) ) ) {
                                $RealLevelOK = true;
                            } // End if
                        } // End foreach
                        
                    } // End if
                    
                    /*
                    echo "2level=" . $RealLevelOK;
                    echo "<br>";
                    */
                    
                } else {
                    $RealLevelOK = true;
                } // End if
                                
            } // End if
            
            if( !$InnerLinksCountOK || !$OuterLinksCountOK || !$SymbolsOnPageCountOK || !$RealLevelOK ) {
                $UnTrustedLinks[] = $URL;
                //$UnTrustedLinks[] = $URL . "_" . $InnerLinksCountOK . "_" . $OuterLinksCountOK . "_" . $SymbolsOnPageCountOK  . "_" .  $RealLevelOK;
                $UnTrustedLinksIDs[] = $ID;
                unset( $TrustedLinks[$ID] );
                
                $UnTrustedLinksReasons[$ID] = "";
                if( $URLPageParameters['avl'] ) { 
                    if( !$InnerLinksCountOK ) {
                        $UnTrustedLinksReasons[$ID] .= "Не прошла по кол-ву внутренних ссылок ";
                    } // End if
                    
                    if( !$OuterLinksCountOK ) {
                        $UnTrustedLinksReasons[$ID] .= "Не прошла по кол-ву внешних ссылок ";
                    } // End if
                    
                    if( !$SymbolsOnPageCountOK ) {
                        $UnTrustedLinksReasons[$ID] .= "Не прошла по кол-ву символов ";
                    } // End if
                    
                    if( !$RealLevelOK ) {
                        $UnTrustedLinksReasons[$ID] .= "Не прошла по реальному 2-ому уровню ";
                    } // End if
                } else {
                    $UnTrustedLinksReasons[$ID] .= "Недоступна (статус ответа " . $URLPageParameters['error_status'] . ")";
                } // End if
            } // End if
            
            
        } // End foreach
        
        echo "<br><strong>Page parameters filtering finished</strong>";
        
        return array( $TrustedLinks, $UnTrustedLinks, $UnTrustedLinksIDs, $UnTrustedLinksReasons );
        
    } // End function FilterLinksCount
    
    
    protected function _GetRequestURI($URL) {
        
        $BaseURL = $URL;
        $URL = preg_replace( "/http:\/\//is", "", $URL );
        
        //$URL = preg_replace( "/\?(.*?)$/is", "", $URL );
        
        if( !preg_match( "/^\//is", $URL ) && !preg_match( "/http:\/\//is", $BaseURL ) )
            $URL = "/" . $URL;
        
        //if( preg_match( "/^\//is", $URL ) ) {
            $URLArray = explode( "/", $URL );
            unset( $URLArray[0]);
            $RequestURI = "/" . implode( "/", $URLArray );            
        /*} else {
            $RequestURI = "/" . $URL;
        } // End if*/
        
        return $RequestURI;

    } // End function _GetRequestURI



    // нужна проверка на ошибку подключения
    // 404 - обрабатывается хорошо
    // если отсутствует домен, то тоже все норм
    // проверка на подключение - есть
    
    // домены 3-его уровня - сделай в конфиге флаг да/нет. Для Яндекса = внутренняя ссылка, для Гугла - внешняя... надо анализировать на больших выборках. пока можем считать, что является внешней.
    // пока, считаем, что внутренняя
    // в логе ссылок есть про украину
    // а вот если www.site.ru ссылается на site.ru, то по идее надо считать внутренней. кстати можно запилить отдельный параметр - ссылки на поддомены, и для них отдельное количество выставлять, наверное так будет оптимальней всего
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

        /*
        echo "<pre>";
        var_dump( htmlspecialchars($Response) );
        echo "</pre><hr>";
        */

        $CommonDomain = self::_GetCommonDomain($URL);

        // Проверяем на 200 OK
        if( preg_match( "/HTTP\/(.*?)\s200\sOK/is", $Response ) ) {
        
            $PageParams['avl'] = true;
            $PageParams['inner_links_cnt'] = 0;
            $PageParams['outer_links_cnt'] = 0;
            $PageParams['links'] = self::_GetLinks($Response);
            
            //if( self::ToLog ) $links_log = fopen( "links.html", "a+" );
            
            $outer_links = array();
        
            foreach( $PageParams['links'] as $link_id => $link ) {
                //if( self::ToLog ) fputs( $links_log, "<hr>" );
                //if( self::ToLog ) fputs( $links_log, $link['url'] );
                if( preg_match( "/http:\/\//is", $link['url'] ) ) {
                    if( !preg_match( "/javascript:/is", $link['url'] ) && !preg_match( "/mailto:/is", $link['url'] ) ) {
                    
                        $link_domain = self::_GetCommonDomain($link['url'], false);
                        if( $link_domain == $CommonDomain ) {
                            $PageParams['inner_links_cnt']++;
                            //if( self::ToLog ) fputs( $links_log, "<br>inner link<br>" );
                        } else {
                            if( !in_array( $link['url'], $outer_links ) && ( trim($link_domain) != "" ) ) {
                                $PageParams['outer_links_cnt']++;
                                $outer_links[] = $link['url'];
                                $PageParams['links'][$link_id]['outer'] = true;
                                //if( self::ToLog ) fputs( $links_log, "<br>outer link<br>" );
                            } // End if
                        } // End if
                        
                    } // End if
                } else {
                    if( !preg_match( "/javascript:/is", $link['url'] ) && !preg_match( "/mailto:/is", $link['url'] ) ) {
                        $PageParams['inner_links_cnt']++;
                        //if( self::ToLog ) fputs( $links_log, "<br>inner link<br>" );
                    } // End if
                } // End if
                //if( self::ToLog ) fputs( $links_log, "<hr>" );
            } // End foreach
            
            //if( self::ToLog ) fclose( $links_log );
            
            // Считаем количество символов на странице между тегами BODY, без учёта текстов ссылок
            $PageParams['symbols_cnt'] = 0;
            if( preg_match( "/<body(.*?)>(.*?)$/is", $Response, $mt ) ) {
            
                $BodyText = $mt[2];
                
                // Erase noindex
                //$BodyText = preg_replace( "/<noindex>(.*?)<\/noindex>/is", "", $BodyText );
                //$BodyText = preg_replace( "/<\!-- noindex -->(.*?)<\!-- \/noindex -->/is", "", $BodyText );
                
                // Erase comments
                //$BodyText = preg_replace( "/<\!--(.*?)-->/is", "", $BodyText );
                
                // Erase scripts and styles
                //$BodyText = preg_replace( "/<script(.*?)<\/script>/is", "", $BodyText );
                $BodyText = preg_replace( "/<style(.*?)<\/style>/is", "", $BodyText );
                
                // Erase links
                $BodyText = preg_replace( "/<a(.*?)href=(.*?)<\/a>/is", "", $BodyText );
                
                // Erase tags
                $BodyText = preg_replace( "/<(.*?)>/is", "", $BodyText );
                
                $PageParams['symbols_cnt'] = strlen( trim($BodyText) );
                
                /*
                echo "<pre>";
                var_dump( htmlspecialchars($BodyText) );
                echo "</pre><hr>";
                */
                
            } // End if
        
        } else {
            $PageParams['avl'] = false;
            if( preg_match( "/HTTP\/(.*?)\s(\d{3}?)\s/is", $Response, $mt ) ) {
                $PageParams['error_status'] = $mt[2];
            } else {
                $PageParams['error_status'] = "не определен ПРОВЕРИТЬ";
            } // End if
        } // End if
        
        return $PageParams;        
        
    } // End function _GetPageData
    
    
    // Информаия о ссылках со страницы (текста страницы)
    // Поработать еще со вложенными ссылками
    protected function _GetLinks($Text) {
    
        // Clearing text - deleting noindex blocks
        $Text = preg_replace( "/<noindex>(.*?)<\/noindex>/is", "", $Text );
        $Text = preg_replace( "/<\!-- noindex -->(.*?)<\!-- \/noindex -->/is", "", $Text );
        
        $links = array();
        // Для вложенных ссылок берутся параметры самой нижней
        //if( preg_match_all( "/<a([^<>]*)>([^<>]*)<\/a>/is", $Text, $LnkMtch, PREG_SET_ORDER ) ) {
        // Для вложенных ссылок берутся параметры самой верхней
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
                        //'a_text' => $LinkMArray[1],
                        'url' => $mt[1],
                        'text' => $LinkMArray[2]
                    );
                    $links[] = $link;
                } // End if
                
            } // End foreach
            
        } // End if
        
        return $links;
        
    } // End function _GetLinks
    
    
    // Определяет основной домен $URL
    protected function _GetCommonDomain($URL, $Is3LevelOuter = true ) {
    
        $CommonDomain = preg_replace( "/http:\/\//is", "", $URL );
        $CommonDomainArray = explode( "/", $CommonDomain );
        $CommonDomain = $CommonDomainArray[0];
        
        if( !$Is3LevelOuter ) {
            $CommonDomainLvlArray = explode( ".", $CommonDomain );            
            $CommonDomain = $CommonDomainLvlArray[count($CommonDomainLvlArray)-2] . "." . $CommonDomainLvlArray[count($CommonDomainLvlArray)-1];
        } // End if
        
        return $CommonDomain;
        
    } // End function _GetCommonDomain


    
    /*
	static public function FilterRegex( $Links ) {
    
        $TrustedLinks = $Links;
        $UnTrustedLinks = array();
        $UnTrustedLinksIDs = array();
        
        echo "<strong>Regex filtering...</strong>";
        
        $RegexFilterArray = self::_GetRegexFilterArray();
        
        $RegexFilterArrayPat = array();
        foreach( $RegexFilterArray as $FilterStr ) {
            $RegexFilterArrayPat[] = "(" . $FilterStr . ")";
        } // End foreach

        $RegexFilter = "@" . implode( "|", $RegexFilterArrayPat ) . "@is";
        
        foreach( $TrustedLinks as $ID => $URL ) {
            
            if( preg_match( $RegexFilter, $URL, $mtch ) ) {
                $UnTrustedLinks[] = $URL;
                $UnTrustedLinksIDs[] = $ID;
                unset( $TrustedLinks[$ID] );
            } // End if
            
        } // End foreach
        
        echo "<br><strong>Regex filtering finished</strong>";
        
        return array( $TrustedLinks, $UnTrustedLinks, $UnTrustedLinksIDs );
        
    } // End function FilterRegex
    
    
    static public function FilterAlexa( $Links ) {
        
        require "Common.config.php";
        
        $TrustedLinks = $Links;
        $UnTrustedLinks = array();
        $UnTrustedLinksIDs = array();
        
        echo "<br><br><strong>Alexa filtering...</strong>";
        
        echo "<br>ALEXA BORDER = " . $AlexaPopularityBorder . "<br>";

        foreach( $TrustedLinks as $ID => $URL ) {
            
            $URLAlexaPopularity = self::_GetAlexaPopularity($URL);
            
            if( $URLAlexaPopularity == 0 ) {
                $UnTrustedLinks[] = $URL;
                $UnTrustedLinksIDs[] = $ID;
                unset( $TrustedLinks[$ID] );
            } // End if
            
        } // End foreach
        
        echo "<br><strong>Alexa filtering finished</strong>";
        
        return array( $TrustedLinks, $UnTrustedLinks, $UnTrustedLinksIDs );
        
    } // End function FilterAlexa
    
    
    static public function FilterLiveInternet( $Links ) {
        
        require "Common.config.php";
        
        $TrustedLinks = $Links;
        $UnTrustedLinks = array();
        $UnTrustedLinksIDs = array();
        
        echo "<br><br><strong>LiveInternet filtering...</strong>";
        
        echo "<br>LI_month_vis BORDER = " . $LI_month_vis . "<br>";

        foreach( $TrustedLinks as $ID => $URL ) {
        
            $URLLIParameter = self::_GetLIData($URL);
            
            if( !$URLLIParameter[0] && ( $URLLIParameter[1] < $LI_month_vis ) ) {
                $UnTrustedLinks[] = $URL;
                $UnTrustedLinksIDs[] = $ID;
                unset( $TrustedLinks[$ID] );
            } // End if
            
        } // End foreach
        
        echo "<br><strong>LiveInternet filtering finished</strong>";
        
        return array( $TrustedLinks, $UnTrustedLinks, $UnTrustedLinksIDs );
        
    } // End function FilterLiveInternet
    
    
    protected function _GetLIData($URL) {
    
        $Error = false;
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

        curl_close($ch);        
        
        return array( $Error, $LI_month_vis );        
        
    } // End function _GetLIData
    
    
    protected function _GetAlexaPopularity($URL) {
        
        sleep(1);
        $ch = curl_init();
        
        $AlexaRequestURL = "http://data.alexa.com/data?cli=10&dat=s&url=".$URL;
        
        curl_setopt($ch, CURLOPT_URL, $AlexaRequestURL);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $Response = curl_exec($ch);
        $xml = simplexml_load_string($Response);
        return intval($xml->SD[1]->POPULARITY['TEXT']); 
        
        curl_close($ch);        
        
    } // End function _GetAlexaPopularity
    
    
    protected function _GetAlexaPopularityD($URL) {
        
        sleep(1);
        $AlexaRequestURL = "http://data.alexa.com/data?cli=10&dat=s&url=".$URL;
        echo $AlexaRequestURL;
        echo "<br>";
        $xml = simplexml_load_file("http://data.alexa.com/data?cli=10&dat=s&url=".$URL);
        return intval($xml->SD[1]->POPULARITY['TEXT']); 
        
    } // End function _GetAlexaPopularity
    
    
    protected function _GetRegexFilterArray() {
        
        require_once "RegexFilter.config.php";
        
        return $RegexFilterArray;
        
    } // End function _GetRegexFilterArray
    
    */
	
} // End class PageFilter