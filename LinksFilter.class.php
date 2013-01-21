<?php
/**
* Определение класса для филрации ссылок по различным параметрам
*
* @package SAPE
*/

/**
* Класс для филрации ссылок по различным параметрам - API и регулярные выражения по REQUEST_URI ссылок
*
* @package SAPE
* @author Chevanin Valeriy <chevanin@etorg.ru>
* @todo все включения кофигов в конструктор<br>для алексы добавить порог<br>кэширование там, где регэкспы (преобразование массива в одну большую строку можно делать в более низкоуровневой функции)<br>дать ссылки на Common.config.sample.php и кофиг регэкспов
*/

class LinksFilter {
	
    /**
    * Фильтрация ссылок по black list
    * Вызывается из index.php
    * Пример использования (index.php)
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
    * @example index.php Пример использования в index.php
    *
    * @param array $Links массив ссылок для фильтрации
    * @param array $BlackListIDs массив идентификаторов $Links, соответствующих непрошедшим фильтрацию ссылкам
    * @return array Массив ( $TrustedLinks, $UnTrustedLinks, $UnTrustedLinksIDs, $UnTrustedLinksReasons ), $TrustedLinks - неотфильтрованные ссылки ( "ID" => "URL" ), $UnTrustedLinks - непрошедшие фильтрацию ссылки, $UnTrustedLinksIDs - ID-ы непрошедших фильтрацию ссылок, $UnTrustedLinksReasons - причины отказа
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
            $UnTrustedLinksReasons[$BLID] = "Отфильтрована по BL (ранее - " . $BLReason . ")";
            unset( $TrustedLinks[$BLID] );
            
        } // End foreach
        
        echo "<br><strong>BL filtering finished</strong>";
        
        return array( $TrustedLinks, $UnTrustedLinks, $UnTrustedLinksIDs, $UnTrustedLinksReasons );
        
    } // End function BLFilter
    
    
    /**
    * Проверка на мозранк
    * Вызывается из index.php
    * Пример использования (index.php)
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
    * @uses LinkFilter::_GetFromSEOMoz() для получения параметров ссылки из seomoz.com
    *
    * @example index.php Пример использования в index.php
    *
    * @param array $Links массив ссылок для фильтрации
    * @return array Массив ( $TrustedLinks, $UnTrustedLinks, $UnTrustedLinksIDs, $UnTrustedLinksReasons, $ErrorLinksIDs ), $TrustedLinks - неотфильтрованные ссылки ( "ID" => "URL" ), $UnTrustedLinks - непрошедшие фильтрацию ссылки, $UnTrustedLinksIDs - ID-ы непрошедших фильтрацию ссылок, $UnTrustedLinksReasons - причины отказа, $ErrorLinksIDs - отказы с ошибками
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
                    $UnTrustedLinksReasons[$ID] = "Не прошла проверку Mozrank (0 < umrp < 3)";
                    unset( $TrustedLinks[$ID] );
                } elseif( isset($URLSEOMozParameters['umrp']) && ( $URLSEOMozParameters['umrp'] == 0 ) ) {
                    if( isset($URLSEOMozParameters['fmrp']) && ( $URLSEOMozParameters['fmrp'] < 3 ) ) {
                        $UnTrustedLinks[] = $URL;
                        $UnTrustedLinksIDs[] = $ID;
                        $UnTrustedLinksReasons[$ID] = "Не прошла проверку Mozrank (fmrp < 3)";
                        unset( $TrustedLinks[$ID] );
                    } // End if
                } // End if
            } else {
                $UnTrustedLinks[] = $URL;
                $UnTrustedLinksIDs[] = $ID;
                $UnTrustedLinksReasons[$ID] = "Произошла ошибка при проверке Mozrank";
                $ErrorLinksIDs[] = $ID;
                unset( $TrustedLinks[$ID] );
            } // End if
            
        } // End foreach
                
        echo "<br><strong>Mozrank filtering finished</strong>";
        
        return array( $TrustedLinks, $UnTrustedLinks, $UnTrustedLinksIDs, $UnTrustedLinksReasons, $ErrorLinksIDs );
        
    } // End function FilterMozrank
    
    
    /**
    * Метод для для получения параметров ссылки $URL из seomoz.com
    * Вызывается из LinksFilter::FilterMozrank()
    * Пример использования
    * <code>
    * $URLSEOMozParameters = self::_GetFromSEOMoz( $URL );
    * </code>
    *
    * Используемые параметры (Common.config.php)
    * <code>
    *    $seomoz_access_id = "...";
    *    $seomoz_secret_key = "...";
    * </code>
    *
    * @access protected
    * @see LinksFilter::FilterMozrank()
    * @link http://apiwiki.seomoz.org/url-metrics описание API
    *
    * @param string $URL ссылка по которой необходимо получить данные
    * @return array Массив, возможные ключи: float umrp - значение umrp, float fmrp - значение fmrp, bool error - произошла ошибка
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
    * Фильтрация ссылок с помощью seolib
    * Вызывается из index.php
    * Пример использования (index.php)
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
    * @uses LinkFilter::_GetFromSEOLib() для получения параметров ссылки из seolib
    *
    * @example index.php Пример использования в index.php
    *
    * @param array $Links массив ссылок для фильтрации
    * @return array Массив ( $TrustedLinks, $UnTrustedLinks, $UnTrustedLinksIDs, $UnTrustedLinksReasons, $ErrorLinksIDs ), $TrustedLinks - неотфильтрованные ссылки ( "ID" => "URL" ), $UnTrustedLinks - непрошедшие фильтрацию ссылки, $UnTrustedLinksIDs - ID-ы непрошедших фильтрацию ссылок, $UnTrustedLinksReasons - причины отказа, $ErrorLinksIDs - отказы с ошибками
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
                    $UnTrustedLinksReasons[$ID] = "Не прошла проверку API SEOlib (индексация страниц в Яндексе)";
                    unset( $TrustedLinks[$ID] );
                } elseif( !$URLSEOLibParameters['check_google'] ) {
                    $UnTrustedLinks[] = $URL;
                    $UnTrustedLinksIDs[] = $ID;
                    $UnTrustedLinksReasons[$ID] = "Не прошла проверку API SEOlib (индексация страниц в Гугле)";
                    unset( $TrustedLinks[$ID] );
                } elseif( !$URLSEOLibParameters['check_ags'] ) {
                    $UnTrustedLinks[] = $URL;
                    $UnTrustedLinksIDs[] = $ID;
                    $UnTrustedLinksReasons[$ID] = "Не прошла проверку API SEOlib (АГС фильтр Яндекса)";
                    unset( $TrustedLinks[$ID] );
                } // End if
            } else {
                $UnTrustedLinks[] = $URL;
                $UnTrustedLinksIDs[] = $ID;
                $UnTrustedLinksReasons[$ID] = "Произошла ошибка при проверке API SEOlib";
                $ErrorLinksIDs[] = $ID;
                unset( $TrustedLinks[$ID] );
            
            } // End if

        } // End foreach
                
        echo "<br><strong>SEOLib filtering finished</strong>";
        
        return array( $TrustedLinks, $UnTrustedLinks, $UnTrustedLinksIDs, $UnTrustedLinksReasons, $ErrorLinksIDs );
        
    } // End function FilterSEOLib
    
    
    /**
    * Метод для для получения параметров ссылки $URL из seolib
    * Вызывается из LinksFilter::FilterSEOLib()
    * Пример использования
    * <code>
    * $URLSEOLibParameters = self::_GetFromSEOLib( $URL );
    * </code>
    *
    * Используемые параметры (Common.config.php)
    * <code>
    * $seolib_login = "...";
    * $seolib_password = "...";
    * </code>
    *
    * @access protected
    * @see LinksFilter::FilterSEOLib()
    * @link http://www.seolib.ru/script/xmlrpc/ описание API seolib
    * @uses LinksFilter::_SEOLibRequest() для запросов к API seolib
    *
    * @param string $URL ссылка по которой необходимо получить данные
    * @return array Массив, возможные ключи: bool check_yandex_error - произошла ошибка при проверке индексации Яндексом, bool check_yandex - проверка индексации Яндексом пройдена, bool check_google_error - произошла ошибка при проверке индексации Гуглом, bool check_google - проверка индексации Гуглом пройдена, bool check_ags_error - произошла ошибка при проверке АГС фильра Яндекса, bool check_yandex_error - проверка АГС фильра Яндекса пройдена
    */
    protected function _GetFromSEOLib( $URL ) {
    
        require "Common.config.php";
        
        $result = array();
        
        // Проверка указанного URL на индексацию в Яндекс
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
        
        // Проверка указанного URL на индексацию в Google
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
        
        // Проверка указанного URL на фильтр "АГС" в Яндекс
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
    * Запрос к seolib (XML-RPC)
    * Вызывается из LinksLoader::_GetFromSEOLib()
    *
    * Пример использования
    * <code>
    *    $data = self::_SEOLibRequest( 
    *        "extlinks.checkYandexIndexedPage", 
    *        serialize( array( $seolib_login, md5($seolib_password), $URL ) )
    *    );
    * </code>
    *
    * Используется для авторизации и запросов на получение параметров ссылки. Использует curl
    * @link http://docs.php.net/manual/ru/ref.curl.php описание функций PHP CURL
    * @see LinksLoader::_GetFromSEOLib()
    *
    * @access protected
    *
    * @param string $method название метода
    * @param array $params по умолчанию array(), передаваемые в запросе параметры
    * @return array Массив с результатом запроса к API
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
    * Фильтрация ссылок с помощью регулярных выражений с целью отсечения ссылок, содержащих "board", "(php|ya|fast)bb" и т.д.
    * Вызывается из index.php
    * Пример использования (index.php)
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
    * @uses LinkFilter::_GetRegexFilterArray() для получения массива ссылок из конфига RegexFilter.class.php
    *
    * @example index.php Пример использования в index.php
    *
    * @param array $Links массив ссылок для фильтрации
    * @return array Массив ( $TrustedLinks, $UnTrustedLinks, $UnTrustedLinksIDs, $UnTrustedLinksReasons ), $TrustedLinks - неотфильтрованные ссылки ( "ID" => "URL" ), $UnTrustedLinks - непрошедшие фильтрацию ссылки, $UnTrustedLinksIDs - ID-ы непрошедших фильтрацию ссылок, $UnTrustedLinksReasons - причины отказа
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
                $UnTrustedLinksReasons[$ID] = "Не прошла проверку по Regex";
                unset( $TrustedLinks[$ID] );
            } // End if
            
        } // End foreach
        
        echo "<br><strong>Regex filtering finished</strong>";
        
        return array( $TrustedLinks, $UnTrustedLinks, $UnTrustedLinksIDs, $UnTrustedLinksReasons );
        
    } // End function FilterRegex
    
    
    /**
    * Проверка по Alexa
    * Вызывается из index.php
    * Пример использования (index.php)
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
    * @uses LinkFilter::_GetAlexaPopularity() для получения параметров ссылки из Alexa
    *
    * @example index.php Пример использования в index.php
    *
    * @param array $Links массив ссылок для фильтрации
    * @return array Массив ( $TrustedLinks, $UnTrustedLinks, $UnTrustedLinksIDs, $UnTrustedLinksReasons, $ErrorLinksIDs ), $TrustedLinks - неотфильтрованные ссылки ( "ID" => "URL" ), $UnTrustedLinks - непрошедшие фильтрацию ссылки, $UnTrustedLinksIDs - ID-ы непрошедших фильтрацию ссылок, $UnTrustedLinksReasons - причины отказа, $ErrorLinksIDs - отказы с ошибками
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
                $UnTrustedLinksReasons[$ID] = "Не прошла проверку по Alexa";
                unset( $TrustedLinks[$ID] );
            } // End if
            
        } // End foreach
        
        echo "<br><strong>Alexa filtering finished</strong>";
        
        return array( $TrustedLinks, $UnTrustedLinks, $UnTrustedLinksIDs, $UnTrustedLinksReasons, $ErrorLinksIDs );
        
    } // End function FilterAlexa
    
    
    /**
    * Проверка по LiveInternet
    * Вызывается из index.php
    * Пример использования (index.php)
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
    * @uses LinkFilter::_GetLIData() для получения параметров ссылки из liveinternet
    *
    * @example index.php Пример использования в index.php
    *
    * @param array $Links массив ссылок для фильтрации
    * @return array Массив ( $TrustedLinks, $UnTrustedLinks, $UnTrustedLinksIDs, $UnTrustedLinksReasons, $ErrorLinksIDs ), $TrustedLinks - неотфильтрованные ссылки ( "ID" => "URL" ), $UnTrustedLinks - непрошедшие фильтрацию ссылки, $UnTrustedLinksIDs - ID-ы непрошедших фильтрацию ссылок, $UnTrustedLinksReasons - причины отказа, $ErrorLinksIDs - отказы с ошибками
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
                    $UnTrustedLinksReasons[$ID] = "Не прошла проверку по LiveInternet (" . $URLLIParameter[2] . ")";
                    unset( $TrustedLinks[$ID] );
                } elseif( $URLLIParameter[0] ) {
                    $UnTrustedLinks[] = $URL;
                    $UnTrustedLinksIDs[] = $ID;
                    $ErrorLinksIDs[] = $ID;
                    $UnTrustedLinksReasons[$ID] = "Произошла ошибка при проверке по LiveInternet";
                    unset( $TrustedLinks[$ID] );
                } // End if
                
            } // End if
            
        } // End foreach
        
        echo "<br><strong>LiveInternet filtering finished</strong>";
        
        return array( $TrustedLinks, $UnTrustedLinks, $UnTrustedLinksIDs, $UnTrustedLinksReasons, $ErrorLinksIDs );
        
    } // End function FilterLiveInternet
    
    
    /**
    * Запрос к LiveInternet
    * Вызывается из LinksLoader::FilterLiveInternet()
    *
    * Пример использования
    * <code>
    *    $URLLIParameter = self::_GetLIData($URL);
    * </code>
    *
    * Используется для получения посещаемости по Liveinternet. Использует curl
    * @link http://docs.php.net/manual/ru/ref.curl.php описание функций PHP CURL
    * @see LinksLoader::FilterLiveInternet()
    *
    * @access protected
    *
    * @param string $URL адрес, по кторому интересует посещаемость
    * @return array Массив с ключами bool $Error - была ли ошибка при обращении к liveinternet, bool $NotRegistered - сайт не зарегистрирован в liveinternet, $LI_month_vis - просмотров за месяц
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
    * Запрос к Alexa
    * Вызывается из LinksLoader::FilterAlexa()
    *
    * Пример использования
    * <code>
    *    $URLAlexaPopularity = self::_GetAlexaPopularity($URL);
    * </code>
    *
    * @uses PageFilter::GetCommonDomain() для получения домена ссылки $URL
    *
    * Используется для получения посещаемости по Alexa. Использует curl
    * @link http://docs.php.net/manual/ru/ref.curl.php описание функций PHP CURL
    * @see LinksLoader::FilterAlexa()
    * @see PageFilter::GetCommonDomain()
    *
    * @access protected
    *
    * @param string $URL адрес, по кторому интересует посещаемость
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
    * Получение массива "плохих" частей REQUEST_URI из конфига RegexFilter.class.php
    * Вызывается из LinksLoader::FilterRegex()
    *
    * Пример использования
    * <code>
    *    $RegexFilterArray = self::_GetRegexFilterArray();
    * </code>
    *
    * @see LinksLoader::FilterRegex()
    *
    * @access protected
    *
    * @return array массив регулярных выражений для фильтрации
    *
    */
    protected function _GetRegexFilterArray() {
        require_once "RegexFilter.config.php";
        return $RegexFilterArray;
    } // End function _GetRegexFilterArray
	
} // End class LinksFilter