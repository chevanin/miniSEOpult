<?php
/**
 * 
 * ����� ��� �������� ������ �� ��������� ����������
 * 
 * @todo ��� ��������� ������� � �����������
 * @todo ��� ������ �������� �����
 * @todo ������ ������� � _GetAlexaPopularity
 * 
 */
class LinksFilter {
	
    /**
    * ���������� ������ �� black list
    * ���������� �� index.php
    * ������ ������������� (index.php)
    * <code>
    *   list( $TrustedLinks, $UnTrustedLinks[4], $UnTrustedLinksIDs[4], $UnTrustedLinksReasons[4] ) = LinksFilter::BLFilter( $TrustedLinks, $BlackListIDs );
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
        
        /*
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
        */
        
        echo "<br><strong>BL filtering finished</strong>";
        
        return array( $TrustedLinks, $UnTrustedLinks, $UnTrustedLinksIDs, $UnTrustedLinksReasons );
        
    } // End function BLFilter
    
    
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
    
    
    static public function FilterAlexa( $Links ) {
        
        require "Common.config.php";
        
        $TrustedLinks = $Links;
        $UnTrustedLinks = array();
        $UnTrustedLinksIDs = array();
        $UnTrustedLinksReasons = array();
        
        echo "<br><br><strong>Alexa filtering...</strong>";
        
        echo "<br>ALEXA BORDER = " . $AlexaPopularityBorder . "<br>";
        
        /*
        $TrustedLinks = array();
        $TrustedLinks[] = "http://forceprom.ru/prommat/1165";
        $TrustedLinks[] = "http://www.ua-femida.ru/gotovie_firmi/SRO.aspx";
        $NestingArray = array();
        $NestingArray[] = 2;
        $NestingArray[] = 2;
        */

        foreach( $TrustedLinks as $ID => $URL ) {
            
            $URLAlexaPopularity = self::_GetAlexaPopularity($URL);
            if( $URLAlexaPopularity === false ) {
                $UnTrustedLinks[] = $URL;
                $UnTrustedLinksIDs[] = $ID;
                $UnTrustedLinksReasons[$ID] = "������ ��� �������� �� Alexa. ����� ���������";
                unset( $TrustedLinks[$ID] );
            } elseif( $URLAlexaPopularity == 0 ) {
                $UnTrustedLinks[] = $URL;
                $UnTrustedLinksIDs[] = $ID;
                $UnTrustedLinksReasons[$ID] = "�� ������ �������� �� Alexa";
                unset( $TrustedLinks[$ID] );
            } // End if
            
        } // End foreach
        
        echo "<br><strong>Alexa filtering finished</strong>";
        
        return array( $TrustedLinks, $UnTrustedLinks, $UnTrustedLinksIDs, $UnTrustedLinksReasons );
        
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
                $UnTrustedLinksReasons[$ID] = "�� ������ �������� �� LiveInternet";
                unset( $TrustedLinks[$ID] );
            } // End if
            
        } // End foreach
        
        echo "<br><strong>LiveInternet filtering finished</strong>";
        
        return array( $TrustedLinks, $UnTrustedLinks, $UnTrustedLinksIDs, $UnTrustedLinksReasons );
        
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
        
        //$AlexaRequestURL = "http://data.alexa.com/data?cli=10&dat=s&url=".$URL;
        $AlexaRequestURL = "http://data.alexa.com/data?cli=10&dat=s&url=".urlencode($URL);
        
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
        
        /*
        if( ( $xml = simplexml_load_string($Response) ) === true ) {
            return intval($xml->SD[1]->POPULARITY['TEXT']); 
        } else {
            return 100000000; // �������
        } // End if
        */
        
    } // End function _GetAlexaPopularity
    
    
    protected function _GetAlexaPopularityD($URL) {
        
        sleep(1);
        $AlexaRequestURL = "http://data.alexa.com/data?cli=10&dat=s&url=".$URL;
        echo $AlexaRequestURL;
        echo "<br>";
        $xml = simplexml_load_file("http://data.alexa.com/data?cli=10&dat=s&url=".$URL);
        return intval($xml->SD[1]->POPULARITY['TEXT']); 
        /*
        if( ( $xml = simplexml_load_file("http://data.alexa.com/data?cli=10&dat=s&url=".$URL) ) === true ) {
            return intval($xml->SD[1]->POPULARITY['TEXT']); 
        } else {
            return 100000000; // �������
        } // End if
        */
        
    } // End function _GetAlexaPopularity
    
    
    protected function _GetRegexFilterArray() {
        
        require_once "RegexFilter.config.php";
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
        
        return $RegexFilterArray;
        
    } // End function _GetRegexFilterArray
	
} // End class LinksFilter