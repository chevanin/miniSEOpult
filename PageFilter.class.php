<?php
/**
 * 
 * Класс для филрации ссылок по параметрам самих страниц
 * 
 * @todo все включения кофигов в конструктор
 * @todo кэширование там, где регэкспы (преобразование массива в одну большубю строку можно делать в более низкоуровневой функции)
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
        $ErrorLinksIDs = array();
        
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
        /*
        $TrustedLinks = array();
        $TrustedLinks[] = "http://sape/test_href.html";
        $NestingArray = array();
        $NestingArray[] = 3;
        */
        /*
        $TrustedLinks = array();
        $TrustedLinks[] = "http://www.probuem.ru/gallery/ironsudb/P1/";
        $NestingArray = array();
        $NestingArray[] = 3;
        */
        /*
        $TrustedLinks = array();
        //$TrustedLinks[] = "http://kickboxing26.sk6.ru/index.php?option=com_content&task=view&id=56&Itemid=1";
        //$TrustedLinks[] = "http://www.1c-multimedia.ru/igry-dlya-pc/game-xroniki-riddika-gold-jewel-akella.html";
        //$TrustedLinks[] = "http://www.vobler-club.ru/index.php?page=shop&rub=1";
        //$TrustedLinks[] = "http://ntravel.ru/catalog/archive/oteli-i-gostinitsyi/roz-mari";
        //$TrustedLinks[] = "http://www.healtheconomics.ru/index.php?option=com_content&view=section&layout=blog&id=1&Itemid=95&limitstart=4720&text=TOP";
        $TrustedLinks[] = "http://funcore.net/page/5052";
        $NestingArray = array();
        $NestingArray[] = 2;
        */
        /*
        425 	3 	Недоступна (статус ответа 500) 	N
        653 	3 	Недоступна (статус ответа 503) 	N
        853 	3 	Недоступна (статус ответа 500) 	N
        873 	3 	Недоступна (статус ответа 504) 	N
        */
        
        
        
        foreach( $TrustedLinks as $ID => $URL ) {
            
            $URLPageParameters = self::_GetPageData($URL);
            
            /*
            echo $URL . "<br>";
            echo "<pre>";
            var_dump( $URLPageParameters );
            echo "</pre>";
            */
            //$URLPageParameters['error_status'] = false;
            
            
            
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
                            
                            $MainPageLinkRequestURI = preg_replace( "/#(.*?)$/is", "", $MainPageLinkRequestURI );
                            $RequestURI = preg_replace( "/#(.*?)$/is", "", $RequestURI );
                            
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
                                
                if( !$InnerLinksCountOK || !$OuterLinksCountOK || !$SymbolsOnPageCountOK || !$RealLevelOK || !$SWTextOK || !$SWLinkOK ) {
                    $UnTrustedLinks[] = $URL;
                    //$UnTrustedLinks[] = $URL . "_" . $InnerLinksCountOK . "_" . $OuterLinksCountOK . "_" . $SymbolsOnPageCountOK  . "_" .  $RealLevelOK;
                    $UnTrustedLinksIDs[] = $ID;
                    unset( $TrustedLinks[$ID] );
                    
                    $UnTrustedLinksReasons[$ID] = "";
                    //if( $URLPageParameters['avl'] ) { 
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
                    //} // End if
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
                    //$UnTrustedLinksReasons[$ID] = "Произошла ошибка при обращении к странице - возможен сбой";
                    //$ErrorLinksIDs[] = $ID;
                    unset( $TrustedLinks[$ID] );
                } // End if
            /*
                            $UnTrustedLinks[] = $URL;
                $UnTrustedLinksIDs[] = $ID;
                $UnTrustedLinksReasons[$ID] = "Произошла ошибка при проверке по Alexa.";
                $ErrorLinksIDs[] = $ID;
                unset( $TrustedLinks[$ID] );

            */
            } // End if
            
            
            
        } // End foreach
        
        echo "<br><strong>Page parameters filtering finished</strong>";
        
        return array( $TrustedLinks, $UnTrustedLinks, $UnTrustedLinksIDs, $UnTrustedLinksReasons, $ErrorLinksIDs );
        
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
        $Response = '
        "HTTP/1 200 OK
        <body><p><a href = "/images/1299633097.jpg"><img src="/images/thumbs_1299633097.jpg" class="alignnone size-full wp-image-59" style="padding-right:15px;" align="left" title=" GAME Хроники Риддика Gold (Jewel) (Акелла) " alt=" GAME Хроники Риддика Gold (Jewel) (Акелла) " /></a> Впервые под одной обложкой мы собрали все приключения Ричарда Риддика, знаменитого маньяка-психопата, героя фильмов &quot;Черная Дыра&quot;&quot;Хроники Риддика&quot; Сюжет обеих игр предваряет события первого фильма кинодилогии, &quot;Черная Дыра&quot;. По словам   <span id="more-760"></span> <br /> Впервые под одной обложкой мы собрали все приключения Ричарда Риддика, знаменитого маньяка-психопата, героя фильмов &quot;Черная Дыра&quot;&quot;Хроники Риддика&quot; Сюжет обеих игр предваряет события первого фильма кинодилогии, &quot;Черная Дыра&quot;. По словам авторитетного игрового сайта Eurogamer, &quot;зачастую кинолицензия только вредит игре, но The Chronicles of Riddick в этом плане уникальна.&quot; В 2009 году, к выходу сиквела Assault on Dark Athena, первая часть была полностью переработана, и это издание фактически является ремейком одной из лучших игр 2004 года с графикой, доработанной до технологий нашего времени, визуально ни в чем не уступающая сиквелу. Это один из первых случаев ремейка игры ее же разработчиками, с радостью встреченный поклонниками первой части The Chronicles of Riddick. The Chronicles of Riddick: Assault on Dark Athena &#8211; долгожданное продолжение приключений Ричарда Риддика. Как и в первый раз, непосредственное в озвучивании игры принял исполнитель роли Риддика в кино &#8211; актер Вин Дизель. Вобрав в себя все самое лучшее от первой части, сиквел также отличился новыми режимами игры по сети, не менее интересной сюжетной линией и сильной технической стороной: Оптимизированный под работу с последними моделями видеокарт, графический движок Assault on Dark Athena прекрасно с отрытыми пространствами и реалистичными текстурами. Как и в 2004, несмотря на жестокую конкуренцию, Chronicles of Riddick имеет шансы стать лучшим шутером 2009 года. Для активации игры требуется подключение к сети Интернет! Возраст: 16+ Язык интерфейса: русский. Системные требования: Windows XP SP2; Pentium Core 2 Duo; 1 Гб оперативной памяти; 12 Гб свободного места на жестком диске; DirectX 9 &#8211; совместимая видеокарта с памятью 256 Мб; DirectX 9.0c; 8-ми скоростное устройство для чтения DVD-дисков; Клавиатура; Мышь. </p>


        ';
        */
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
            $PageParams['stop_words_in_links_cnt'] = 0;
            
            //if( self::ToLog ) $links_log = fopen( "links.html", "a+" );
            
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
                //if( self::ToLog ) fputs( $links_log, "<hr>" );
                //if( self::ToLog ) fputs( $links_log, $link['url'] );
                if( preg_match( "/^http:\/\//is", $link['url'] ) ) {
                    if( !preg_match( "/javascript:/is", $link['url'] ) && !preg_match( "/mailto:/is", $link['url'] ) ) {
                    
                        $link_domain = self::_GetCommonDomain($link['url'], false);
                        $link_domain_www = self::_GetCommonDomain($link['url'], true);
                        /*
                        echo $link['url'];
                        echo "<br>";
                        echo $link_domain;
                        echo "<br>";
                        echo $link_domain_www;
                        echo "<br>";
                        echo $CommonDomain;
                        echo "<hr>";
                        */
                        //top100.rambler.ru
                        $NoCheckingDomains = array(
                            "top100.rambler.ru",
                            "liveinternet.ru",
                            "top.mail.ru",
                            "mc.yandex.ru",
                        );
                        
                        if( ( $link_domain == $CommonDomain ) || ( $link_domain_www == $CommonDomain ) ) {
                            $PageParams['inner_links_cnt']++;
                            //if( self::ToLog ) fputs( $links_log, "<br>inner link<br>" );
                        } elseif( in_array( $link_domain, $NoCheckingDomains ) || in_array( $link_domain_www, $NoCheckingDomains ) ) {
                            $PageParams['inner_links_cnt']++;
                        } else {
                            if( !in_array( $link['url'], $outer_links ) && ( trim($link_domain) != "" ) ) {
                                $PageParams['outer_links_cnt']++;
                                $outer_links[] = $link['url'];
                                // top100 liveinternet yandex.ru top.mail
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
                
                
                if( ( $PageParams['links'][$link_id]['outer'] == true ) && preg_match( $RegexFilter, $link['text'], $mtch ) ) {
                    $PageParams['links'][$link_id]['have_stop_words'] = true;
                    $PageParams['stop_words_in_links_cnt']++;
                } // End if
                
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
                /*
                if( preg_match_all( "/<a(.*?)href(.*?)=(.*?)<\/a>/is", $BodyText, $mt ) ) {
                    echo "<pre>";
                    var_dump( $mt );
                    echo "</pre>";
                } // End if
                */
                $BodyText = preg_replace( "/<a(.*?)href(.*?)=(.*?)<\/a>/is", "", $BodyText );
                /*
                echo "<hr><pre>";
                var_dump( htmlspecialchars($BodyText) );
                echo "</pre><hr>";
                */
                
                // Erase tags
                $BodyText = preg_replace( "/<(.*?)>/is", "", $BodyText );
                
                $PageParams['symbols_cnt'] = strlen( trim($BodyText) );
                
                /*
                echo "<hr><pre>";
                var_dump( htmlspecialchars($BodyText) );
                echo "</pre><hr>";
                */
                
                /*
                echo "<pre>";
                var_dump( $MaxAvailCountInText );
                var_dump( $StopWordsMasks );
                var_dump( $RegexFilter );
                echo "</pre>";
                */
                
                if( preg_match_all( $RegexFilter, $BodyText, $mtch, PREG_SET_ORDER ) ) {
                    /*
                    echo "<pre>";
                    var_dump( $mtch );
                    echo "</pre>";
                    */
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
    
    
    // Определяет основной домен $URL
    // Обертка - публичная ф-ия для других классов
    public function GetCommonDomain($URL, $Is3LevelOuter = true ) {
    
        return self::_GetCommonDomain($URL, $Is3LevelOuter);
        
    } // End function _GetCommonDomain


    protected function _GetSWMaskFilterArray() {
        
        require "RegexFilter.config.php";
        /*
        $RegexFilterArray = array(
            "board",
            "(php|ya|fast)bb",
            "phorum",
            "guest",
            "gbs",
            "gostevaja",
            "forum",
            "view(profile|topic|thread)",
            "show(post|topic|thread|comments|user)",
            "printthread",
            "akobook",
            "ns-comments",
            "datsogallery",
            "gbook",
            "thread\.php",
        );
        */
        
        return array( $MaxAvailCountInText, $StopWordsMasks );
        
    } // End function _GetSWMaskFilterArray
    
    
    
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