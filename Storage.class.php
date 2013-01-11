<?php
/**
* ����������� ������ ��� �������� ������ � ����������� ��������� (������ � ���� � ����������)
*
* @package SAPE
*/

/**
* ����� ��� �������� �������� ������ � ����������� ���������
*
* @package SAPE
* @author Chevanin Valeriy <chevanin@etorg.ru>
* @todo ������� ��������� ����������� � ������
*/
class Storage {
	
    /**
    * ID ������� � sape.ru
    * @access protected
    * @var integer
    */
    protected $ProjectID = 0;
    /**
    * ������ ������
    * @access protected
    * @var array
    */
    protected $Links = array();
    /**
    * ������ ID-�� ������, ��� �������� ������� �������� ������
    * @access protected
    * @var array
    */
    protected $ErrorLinksIDs = array();
    /**
    * ������ ������� ����������� ������
    * @access protected
    * @var array
    */
    protected $NestingArray = array();
    /**
    * ���������� ����
    * @access protected
    * @var resource
    */
    protected $DBH = "";
    /**
    * ������ ���������� ����������� � ����
    * @see Storage::__construct()
    * @access protected
    * @var array
    */
    protected $ConnectParams = array(
        'host' => "localhost",
        'user' => "root",
        'password' => "",
        'database' => "sape",
    );
    

    /**
    * �����������
    * ���������� �� index.php
    * ������ ������������� (index.php)
    * <code>
    *   $Storage = new Storage( $Links, $NestingArray );
    * </code>
    *
    * ���������� mysqli
    * @link http://docs.php.net/manual/ru/book.mysqli.php �������� ������� mysqli
    * @access public
    *
    * @example index.php ������ ������������� � index.php
    * @uses Storage::_FirstTimeSaveLinks() ��� ���������� ������ � ������� ����������� � ����
    *
    * @param integer $ProjectID ����� ������� � Sape.ru
    * @param string $Links ������ ������, ���������� � index.php � ������� LinksLoader::Get()
    * @param string $NestingArray ������ ������� ����������� ������, ���������� � index.php � ������� LinksLoader::Get()
    * @return void
    */
	function __construct( $ProjectID, $Links, $NestingArray ) {
        //$this->Links = $Links;
        //$this->NestingArray = $NestingArray;
        
        $this->ProjectID = intval($ProjectID);
        
        $this->DBH = new mysqli( 
            $this->ConnectParams['host'], 
            $this->ConnectParams['user'], 
            $this->ConnectParams['password'], 
            $this->ConnectParams['database']
        );
        
        if( mysqli_connect_error() ) {
            die('������ ����������� (' . mysqli_connect_errno() . ') ' . mysqli_connect_error());
        } // End if
        
        // ���������� ������ � ����
        // ����� ���-�� ��������� ������� � ����������� �� ������� ������( ������, ��������)
        if( ( count( $Links ) > 0 ) && ( count( $NestingArray ) > 0 ) )
            $this->_FirstTimeSaveLinks( $Links, $NestingArray );
        
	} // End function __counstruct
    
    
    /**
    * ����� "�������" ��� ��������� ������ � ����������������� ������� � ������� ����������� �� ����
    * ���������� �� index.php
    * ������ ������������� (index.php)
    * <code>
    *   list( $TrustedLinks, $NestingArray, $UnTrustedLinks, $UnTrustedLinksIDs, $UnTrustedLinksReasons ) = $Storage->GetDatas();
    * </code>
    *
    * @access public
    *
    * @example index.php ������ ������������� � index.php
    * @uses Storage::_GetLinksAndNesting() ��� ������� �� ��������� ������ � ����������������� ������� � ������� ����������� � ����
    *
    * @return array ������ ( $TrustedLinks, $NestingArray, $UnTrustedLinks, $UnTrustedLinksIDs, $UnTrustedLinksReasons ), $TrustedLinks - ����������������� ������ ( "ID" => "URL" ), $NestingArray - ������� ����������� ( "ID" => "Level" ), $UnTrustedLinks - ���� �������� (������ ������), $UnTrustedLinksIDs - ���� �������� (������ ������), $UnTrustedLinksReasons - ���� �������� (������ ������)
    *
    * @todo ������� ������ � ����� $UnTrustedLinks
    * @todo ������� ������ � ����� $UnTrustedLinksIDs
    * @todo ������� ������ � ����� $UnTrustedLinksReasons
    */
    public function GetDatas() {
    
        // �������� ����������������� ������ � ������ ����������� �� ����
        $this->_GetLinksAndNesting();
        
        $UnTrustedLinks = array();
        $UnTrustedLinksIDs = array();
        $UnTrustedLinksReasons = array();
        
        return array( $this->Links, $this->NestingArray, $UnTrustedLinks, $UnTrustedLinksIDs, $UnTrustedLinksReasons );
        
    } // End function GetDatas
    
    
    /**
    * ���������� ������ � ����
    * ���������� �� Storage::__construct()
    * ������ �������������
    * <code>
    *   $this->_FirstTimeSaveLinks( $Links, $NestingArray );
    * </code>
    * ���������� mysqli
    * @link http://docs.php.net/manual/ru/book.mysqli.php �������� ������� mysqli
    *
    * @access protected
    *
    * @param string $Links ������ ������, ������������ � �����������
    * @param string $NestingArray ������ ������� ����������� ������, ������������ � �����������
    * @return void
    *
    */
    protected function _FirstTimeSaveLinks( $Links, $NestingArray ) {
    
        // ���������� ������ � ����
        $this->DBH->query( "TRUNCATE TABLE links_to_filter" );
        $this->DBH->query( "TRUNCATE TABLE links_untrasted" );
        if( $stmt = $this->DBH->prepare("
            INSERT INTO links_to_filter (url, nesting_level, start_date, project_id) VALUES ( ?, ?, CURRENT_DATE, ? )
            "
        ) ) {
            
            foreach( $Links as $ID => $URL ) {
                if( isset( $NestingArray[$ID] ) ) {
                
                    $stmt->bind_param( 'sii', $URL, $NestingArray[$ID], $this->ProjectID );
                    $stmt->execute();
                    
                } // End if
            } // End foreach
            
            $stmt->close();
            
        } // End if
        
    } // End function _FirstTimeSaveLinks
    
    
    /**
    * ��������� ������ �� ��������� ������ � ����������������� ������� � ������� ����������� � ���� � ���������� ��������� �  $this->Links � $this->NestingArray
    * ���������� �� Storage::GetDatas()
    * ������ �������������
    * <code>
    *   $this->_GetLinksAndNesting();
    * </code>
    * ���������� mysqli
    * @link http://docs.php.net/manual/ru/book.mysqli.php �������� ������� mysqli
    *
    * @access protected
    *
    * @return void
    *
    */
    protected function _GetLinksAndNesting() {
    
        // �������� ����������������� ������ � ������ ����������� �� ����
        /*
        if( $result = $this->DBH->query( "
            SELECT id, url, nesting_level
            FROM links_to_filter

        " ) ) {
        */
        
        if( $result = $this->DBH->query( "
            SELECT id, url, nesting_level
            FROM links_to_filter
            WHERE is_filtered = 'N'
        " ) ) {
                
            while( $row = $result->fetch_assoc() ) {
                $this->Links[$row['id']] = $row['url'];
                $this->NestingArray[$row['id']] = $row['nesting_level'];
            } // End while
            
            $result->free();
            
        } // End if
        
    } // End function _FirstTimeSaveLinks
    
    
    /**
    * ����������
    * ���������� mysqli
    * @link http://docs.php.net/manual/ru/book.mysqli.php �������� ������� mysqli
    *
    * @access public
    *
    * @return void
    *
    */
    function __destruct() {   
        $this->DBH->close();
    } // End function __destruct
    
    
    /**
    * �������� � ���� ������ ��� �� ��������� ���������
    * ���������� �� index.php
    * ������ ������������� (index.php)
    * <code>
    *   $Storage->UnTrust(0, $UnTrustedLinksIDs[0], $UnTrustedLinksReasons[0]);
    * </code>
    *
    * @access public
    *
    * @example index.php ������ ������������� � index.php
    *
    * @param integer $Step ���, �� ������� "����������" ������
    * @param array $UnTrustedLinksIDs ������ ID-�� "������������" ������
    * @param array $UnTrustedLinksReasons ������� ������
    * @param bool $APILog ���� ���� ���������� � true, �� �������� ������� �� �������� � ��� API
    * @return void
    */
    function UnTrust( $Step, $UnTrustedLinksIDs, $UnTrustedLinksReasons, $APILog = false ) {
        
        if( $APILog ) {
            $insert_untrasted_q = "
                INSERT INTO links_untrasted ( link_id, step, reason, api_log ) VALUES ( ?, ?, ?, 'Y' ) 
                ON DUPLICATE KEY UPDATE step = ?, reason = ?
            ";
        } else {
            $insert_untrasted_q = "
                INSERT INTO links_untrasted ( link_id, step, reason ) VALUES ( ?, ?, ? ) 
                ON DUPLICATE KEY UPDATE step = ?, reason = ?, api_log = 'N'
            ";
        } // End if
        
        $update_links_q = "
            UPDATE links_to_filter SET is_filtered = 'Y', is_good = 'N' WHERE id = ?
        ";
        
        if( ( $iu_s = $this->DBH->prepare($insert_untrasted_q) ) && ( $ul_s = $this->DBH->prepare($update_links_q) ) ) {

            foreach( $UnTrustedLinksIDs as $ID ) {
            
                $Reason = ( isset( $UnTrustedLinksReasons[$ID] ) ) ? $UnTrustedLinksReasons[$ID] : "";
                
                $iu_s->bind_param( 'iisis', $ID, $Step, $Reason, $Step, $Reason );
                $iu_s->execute();
                
                $ul_s->bind_param( 'i', $ID );
                $ul_s->execute();
                
                
            } // End ofreach
            
            $iu_s->close();
            $ul_s->close();
        
        } // End if
        
    } // End function UnTrust
    
    
    /**
    * �������� ������ ��� ��������� ��� ���������� �� �� BL
    * ���������� �� index.php
    * ������ ������������� (index.php)
    * <code>
    *   $Storage->MarkErrors($ErrorLinksIDs);
    * </code>
    *
    * @access public
    *
    * @example index.php ������ ������������� � index.php
    *
    * @param array $ErrorLinksIDs ������ ID-�� ������, ��� ��������� ������� ��������� ������
    */
    function MarkErrors( $ErrorLinksIDs ) {
    
        foreach( $ErrorLinksIDs as $step => $IDs ) {
            foreach( $IDs as $ID )
                $this->ErrorLinksIDs[] = $ID;
        } // End foreach
                
    } // End function MarkErrors
    
    
    /**
    * ��������� ������ ��� ��������� ������
    * ���������� �� index.php
    * ������ ������������� (index.php)
    * <code>
    *   list( $StorageTrustedLinks, $StorageUnTrustedLinksAPI, $StorageUnTrustedLinks ) = $Storage->GetReport();
    * </code>
    *
    * @access public
    *
    * @example index.php ������ ������������� � index.php
    *
    * @return array ������ ( $StorageTrustedLinks, $StorageUnTrustedLinks ), $StorageTrustedLinks - ��������� ������ ������, $StorageUnTrustedLinksAPI - ����������� ������ ������ � ��������� ������� ������ ��� �������� � �������������� API, $StorageUnTrustedLinks - ����������� ������ ������ � ��������� ������� ������ ��� API
    */
    function GetReport() {
    
        $StorageTrustedLinks = array();
        $StorageUnTrustedLinksAPI = array();
        $StorageUnTrustedLinks = array();
        $StorageUnTrustedLinksBL = array();
        
        /*
        echo "<pre>";
        var_dump( $this->ErrorLinksIDs );
        echo "</pre>";
        */

        // API
        if( $result = $this->DBH->query( "
            SELECT lf.id, lf.url, lf.nesting_level, lf.is_good, lu.reason, lu.api_log
            FROM links_to_filter lf LEFT JOIN links_untrasted lu ON (lu.link_id = lf.id)
        " ) ) {
        
            while( $row = $result->fetch_assoc() ) {
                
                /*
                echo "<pre>";
                var_dump( $row );
                echo "</pre>";
                */
                
                
                if( $row['is_good'] == "Y" ) {
                    $StorageTrustedLinks[$row['id']]['url'] = $row['url'];
                    $StorageTrustedLinks[$row['id']]['level'] = $row['nesting_level'];
                } else {
                
                    if( $row['api_log'] && ( $row['api_log'] == "Y" ) ) {
                        
                        $StorageUnTrustedLinksAPI[$row['id']]['url'] = $row['url'];
                        $StorageUnTrustedLinksAPI[$row['id']]['level'] = $row['nesting_level'];
                        $StorageUnTrustedLinksAPI[$row['id']]['reason'] = $row['reason'];
                        
                        if( !in_array( $row['id'], $this->ErrorLinksIDs ) )
                            $StorageUnTrustedLinksBL[$row['id']] = $StorageUnTrustedLinksAPI[$row['id']];
                        
                    } else {
                    
                        $StorageUnTrustedLinks[$row['id']]['url'] = $row['url'];
                        $StorageUnTrustedLinks[$row['id']]['level'] = $row['nesting_level'];
                        $StorageUnTrustedLinks[$row['id']]['reason'] = $row['reason'];
                        
                        if( !in_array( $row['id'], $this->ErrorLinksIDs ) )
                            $StorageUnTrustedLinksBL[$row['id']] = $StorageUnTrustedLinks[$row['id']];
                    
                    } // End if
                    
                } // End if
            } // End while
            
            $result->free();
            
        } // End if
        
        /*
        // Not API
        if( $result = $this->DBH->query( "
            SELECT lf.id, lf.url, lf.nesting_level, lf.is_good, lu.reason
            FROM links_to_filter lf LEFT JOIN links_untrasted lu ON (lu.link_id = lf.id)
            WHERE lu.api_log = 'N'
        " ) ) {
        
            while( $row = $result->fetch_assoc() ) {
                
                
                echo "<pre>";
                var_dump( $row['is_good'] );
                echo "</pre>";
                
                
                if( $row['is_good'] == "Y" ) {
                    $StorageTrustedLinks[$row['id']]['url'] = $row['url'];
                    $StorageTrustedLinks[$row['id']]['level'] = $row['nesting_level'];
                } else {
                    $StorageUnTrustedLinks[$row['id']]['url'] = $row['url'];
                    $StorageUnTrustedLinks[$row['id']]['level'] = $row['nesting_level'];
                    $StorageUnTrustedLinks[$row['id']]['reason'] = $row['reason'];
                    
                    if( !in_array( $row['id'], $this->ErrorLinksIDs ) )
                        $StorageUnTrustedLinksBL[$row['id']] = $StorageUnTrustedLinks[$row['id']];
                    
                } // End if
            } // End while
            
            $result->free();
            
        } // End if
        */
        
        $this->_ToBL($this->ProjectID, $StorageUnTrustedLinksBL);
        
        return array( $StorageTrustedLinks, $StorageUnTrustedLinksAPI, $StorageUnTrustedLinks );
        
    } // End function GetReport
	
    
    /**
    * ���������� ������ � black list
    * ���������� �� Storage::GetTrusted()
    * ������ ������������� Storage::GetTrusted()
    * <code>
    *   $this->_ToBL($ProjectID, $StorageUnTrustedLinks);
    * </code>
    *
    * @access protected
    *
    * @example Storage::GetTrusted() ������ ������������� � Storage::GetTrusted()
    *
    * @param integer $ProjectID ������������� �������
    * @param array $UnTrustedLinks ������ ������ ��� ������ � black list � ��������� ������
    * @return void
    */
    protected function _ToBL( $ProjectID, $UnTrustedLinks ) {
        
        $insert_bl_q = "
            INSERT IGNORE INTO links_bl ( project_id, url, hash, reason ) VALUES ( ?, ?, ?, ? ) 
        ";
        
        /*
        $insert_bl_q = "
            INSERT INTO links_bl ( project_id, url, hash, reason ) VALUES ( ?, ?, ?, ? ) 
            ON DUPLICATE KEY UPDATE hash = ?
        ";
        */
        
        if( $bl_s = $this->DBH->prepare($insert_bl_q) ) {

            foreach( $UnTrustedLinks as $Link ) {
            
                //$bl_s->bind_param( 'issss', $ProjectID, $Link['url'], md5($Link['url']), $Link['reason'], md5($Link['url']) );
                $bl_s->bind_param( 'isss', $ProjectID, $Link['url'], md5($Link['url']), $Link['reason'] );
                $bl_s->execute();
                
            } // End ofreach
            
            $bl_s->close();
        
        } // End if

    } // End function _ToBL
    

    /**
    * ��������� �������� ������ �� black list � ���������� ������. ����������� ����������
    * ���������� �� index.php
    * ������ ������������� (index.php)
    * <code>
    *   $BlackListIDs = $Storage->GetBL( $TrustedLinks );
    * </code>
    *
    * @access public
    *
    * @example index.php ������ ������������� � index.php
    *
    * @param array $TrustedLinks ������ ������ ��� ���������� �� bl
    * @param boolean $ForAllProjects ������������ �� bl ��� ���� ��������
    * @return array $UntrustedIDs - ������ ID-�� ����������� ���������� ������
    *
    */
    public function GetBL( $TrustedLinks, $ForAllProjects = false ) {
    
        $UnTrusted = array();
        
        $URLHashes = array();
        foreach( $TrustedLinks as $Link ) {
            $URLHashes[] = "'" . md5($Link) . "'";
        } // End foreach
        
        if( $ForAllProjects ) {
            $query = "
                SELECT id, url, reason
                FROM links_bl
                WHERE hash IN (" . implode( ",", $URLHashes ) . ")
                ";
        } else {
            $query = "
                SELECT id, url, reason
                FROM links_bl
                WHERE hash IN (" . implode( ",", $URLHashes ) . ")
                AND project_id = " . $this->ProjectID . "
                ";
        } // End if
        
        if( $result = $this->DBH->query( $query ) ) {
        
            while( $row = $result->fetch_assoc() ) {
                
                $UntrastedID = array_search( $row['url'], $TrustedLinks );
                
                if( $UntrastedID ) $UnTrusted[$UntrastedID] = $row['reason'];
                                
            } // End while
                
            $result->close();
            
        } // End if        
        
        return $UnTrusted;
        
    } // End function GetDatas    
    
    
} // End class Storage