<?php
/**
* Определение класса для филрации ссылок по параметрам самих страниц
*
* @package SAPE
*/

/**
* Класс для для филрации ссылок по параметрам самих страниц
*
* @package SAPE
* @author Chevanin Valeriy <chevanin@etorg.ru>
* @todo все включения кофигов в конструктор<br>кэширование там, где регэкспы (преобразование массива в одну большубю строку можно делать в более низкоуровневой функции)<br>[_GetPageData]$NoCheckingDomains - массив разрешенных (не считающихся внешними) доменов (счетчики) вынести в конфиг
*/
class PageFilter {

    /**
    * Проверка <ul><li>на кол-во внутренних ссылок, кол-во внешних ссылок,</li> <li>кол-во символов на странице,</li> <li>на реальный уровень вложенности для ссылок 2-ого заявленного уровня,</li> <li>на стоп-слова на странице и в текстах ссылок.</li></ul> <br>Предельные значения для колличественных показателей берутся из Common.config.php
    * Вызывается из index.php
    * Пример использования (index.php)
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
    * @uses PageFilter::_GetPageData() для получения параметров каждой страницы
    * @uses PageFilter::_GetCommonDomain() для определения домена ссылки
    * @uses PageFilter::_GetRequestURI() для определения REQUEST_URI ссылки
    * @see Common.config.sample.php
    *
    * @example index.php Пример использования в index.php
    *
    * @param array $Links массив ссылок для фильтрации
    * @param array $NestingArray массив уровней вложенности ссылок
    * @return array Массив ( $TrustedLinks, $UnTrustedLinks, $UnTrustedLinksIDs, $UnTrustedLinksReasons, $ErrorLinksIDs ), $TrustedLinks - неотфильтрованные ссылки ( "ID" => "URL" ), $UnTrustedLinks - непрошедшие фильтрацию ссылки, $UnTrustedLinksIDs - ID-ы непрошедших фильтрацию ссылок, $UnTrustedLinksReasons - причины отказа, $ErrorLinksIDs - отказы с ошибками
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
                    
                    if( !$SWTextOK ) {
                        $UnTrustedLinksReasons[$ID] .= "Cтоп-слов в тексте больше " . $URLPageParameters['max_stop_words_cnt'] . " ";
                    } // End if
                    
                    if( !$SWLinkOK ) {
                        $UnTrustedLinksReasons[$ID] .= "В текстах внешних ссылок есть стоп-слова ";
                    } // End if
                    
                } // End if
                
            } else {
                if( !$URLPageParameters['error_status'] || ( $URLPageParameters['error_status'] == 500 ) || ( $URLPageParameters['error_status'] == 501 ) || ( $URLPageParameters['error_status'] == 502 ) || ( $URLPageParameters['error_status'] == 503 ) || ( $URLPageParameters['error_status'] == 504 ) ) {
                    $UnTrustedLinks[] = $URL;
                    $UnTrustedLinksIDs[] = $ID;
                    $UnTrustedLinksReasons[$ID] = "Произошла ошибка при обращении к странице - возможен сбой";
                    $ErrorLinksIDs[] = $ID;
                    unset( $TrustedLinks[$ID] );
                } else {
                    $UnTrustedLinks[] = $URL;
                    $UnTrustedLinksIDs[] = $ID;
                    $UnTrustedLinksReasons[$ID] = "Страница недоступна (статус ответа " . $URLPageParameters['error_status'] . ")";
                    unset( $TrustedLinks[$ID] );
                } // End if
            } // End if
            
        } // End foreach
        
        echo "<br><strong>Page parameters filtering finished</strong>";
        
        return array( $TrustedLinks, $UnTrustedLinks, $UnTrustedLinksIDs, $UnTrustedLinksReasons, $ErrorLinksIDs );
        
    } // End function FilterLinksCount
    
    
    /**
    * Определяет REQUEST_URI ссылки
    * Вызывается из PageFilter::FilterLinksCount()
    * Пример использования
    * <code>
    *   $RequestURI = self::_GetRequestURI($URL);
    * </code>
    *
    * @access protected
    *
    * @param string $URL URL для определения REQUEST_URI
    * @return string REQUEST_URI ссылки
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
    * Получение параметров страницы: <ul><li>код ответа страницы (если не 200, то дополнительно возвращает метку 'avl' => false),</li> <li>кол-во внутренних ссылок,</li> <li>кол-во внешних ссылок,</li> <li>массив ссылок,</li> <li>кол-во стоп-слов в текстах ссылок,</li> <li>кол-во стоп-слов в тексте,</li> <li>кол-во символов "чистого" текста</li></ul><br>Использует curl
    * Вызывается из PageFilter::FilterLinksCount()
    * Пример использования
    * <code>
    *   $URLPageParameters = self::_GetPageData($URL);
    * </code>
    *
    * @access protected
    *
    * @link http://docs.php.net/manual/ru/ref.curl.php описание функций PHP CURL
    * @uses PageFilter::_GetLinks() для получения ссылок со страницы
    * @uses PageFilter::_GetCommonDomain() для определения домена ссылки
    * @uses PageFilter::_GetSWMaskFilterArray() для получения стоп-слов
    * @see RegexFiter.config.sample.php
    *
    * @param string $URL URL страницы
    * @return array Массив с ключами: <ul><li>avl (bool) - доступна ли ссылка (200 OK),</li> <li>inner_links_cnt (integer) - кол-во внутренних ссылок,</li> <li>outer_links_cnt (integer) - кол-во внешних ссылок,</li> <li>links - массив ссылок на странице array( 'url' => <адрес ссылки>, 'text' => <текст ссылки>, 'outer' => <bool внешняя ли ссылка>, 'have_stop_words' => <bool содержит ли текст ссылки стоп-слова>),</li> <li>stop_words_in_links_cnt (integer) - кол-во стоп-слов в ссылках,</li> <li>stop_words_cnt (integer) - кол-во стоп-слов в тексте,</li> <li>max_stop_words_cnt (integer) - допустимое кол-во стоп-слов в тексте из конфига RegexFiter.config.sample.php,</li> <li>symbols_cnt (integer) - кол-во символов "чистого" текста страницы,</li> <li>error_status (string) - статус ответа, если отличен от 200</li></ul>
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

        // Проверяем на 200 OK
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
            
            // Считаем количество символов на странице между тегами BODY, без учёта текстов ссылок
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
    * Получение массива ссылок из HTML кода страницы
    * Вызывается из PageFilter::_GetPageData()
    * Пример использования
    * <code>
    *   $PageParams['links'] = self::_GetLinks($Response);
    * </code>
    *
    * @access protected
    *
    * @param string $Text HTML код страницы
    * @return array массив ссылок, каждый элемент массива - массив с ключами 'url' и 'text'
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
    * Определяет основной домен $URL
    * Вызывается из PageFilter::FilterLinksCount()
    * Пример использования
    * <code>
    *   $DomainURL = self::_GetCommonDomain($URL);
    * </code>
    *
    * @access protected
    *
    * @param string $URL URL для определения основного домена
    * @param bool $Is3LevelOuter считать ли ссылки с www аналогами без www
    * @return string основной домен ссылки
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
    * Обертка для PageFilter::_GetCommonDomain()
    *
    * @access public
    * @see PageFilter::_GetCommonDomain()
    *
    * @param string $URL URL для определения основного домена
    * @param bool $Is3LevelOuter считать ли ссылки вида www2.site.ru внешними (3-его уровня)
    * @return string основной домен ссылки
    */
    public function GetCommonDomain($URL, $Is3LevelOuter = true ) {
    
        return self::_GetCommonDomain($URL, $Is3LevelOuter);
        
    } // End function _GetCommonDomain


    /**
    * Получение массива стоп-слов и предельно допустимого кол-ва стоп-слов на странице из конфига RegexFilter.class.php
    * Вызывается из LinksLoader::_GetPageData()
    *
    * Пример использования
    * <code>
    *    list( $MaxAvailCountInText, $StopWordsMasks ) = self::_GetSWMaskFilterArray();
    * </code>
    *
    * @access protected
    *
    * @return array массив с элементами $MaxAvailCountInText - предельно допустимое кол-во стоп-слов на странице, $StopWordsMasks - массив стоп-слов
    */
    protected function _GetSWMaskFilterArray() {
        require "RegexFilter.config.php";
        return array( $MaxAvailCountInText, $StopWordsMasks );
    } // End function _GetSWMaskFilterArray
	
} // End class PageFilter