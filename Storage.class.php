<?php
/**
* Определение класса для хранения ссылок и результатов обработки (записи в базу и считывания)
*
* @package SAPE
*/

/**
* Класс для загрузки хранения ссылок и результатов обработки
*
* @package SAPE
* @author Chevanin Valeriy <chevanin@etorg.ru>
* @todo Вынести параметры подключения в конфиг<br>[GetDatas] Сделать запрос и вывод $UnTrustedLinks<br>[GetDatas] Сделать запрос и вывод $UnTrustedLinksIDs<br>[GetDatas] Сделать запрос и вывод $UnTrustedLinksReasons<br>дать ссылку на Common.config.sample.php 
*/
class Storage {
	
    /**
    * Массив ссылок
    * @access protected
    * @var array
    */
    protected $Links = array();
    /**
    * Массив ID-ов ссылок, при проверке которых возникли ошибки
    * @access protected
    * @var array
    */
    protected $ErrorLinksIDs = array();
    /**
    * Массив уровней вложенности ссылок
    * @access protected
    * @var array
    */
    protected $NestingArray = array();
    /**
    * Массив идентификаторов проектов для ссылок
    * @access protected
    * @var array
    */
    protected $LinksProjects = array();
    /**
    * Дескриптор базы
    * @access protected
    * @var resource
    */
    protected $DBH = "";
    /**
    * Массив параметров подключения к базе
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
    * Конструктор
    * Вызывается из index.php
    * Пример использования (index.php)
    * <code>
    *   $Storage = new Storage( $Links, $NestingArray, $LinksProjects );
    * </code>
    *
    * Использует mysqli
    * @link http://docs.php.net/manual/ru/book.mysqli.php описание функций mysqli
    * @access public
    *
    * @example index.php Пример использования в index.php
    * @uses Storage::_FirstTimeSaveLinks() для сохранения ссылок и уровней вложенности в базу
    *
    * @param string $Links массив ссылок, получаемых в index.php с помощью LinksLoader::Get()
    * @param string $NestingArray массив уровней вложенности ссылок, получаемых в index.php с помощью LinksLoader::Get()
    * @param string $LinksProjects массив идентификаторов проектов для ссылок, получаемых в index.php с помощью LinksLoader::Get()
    * @return void
    */
	function __construct( $Links, $NestingArray, $LinksProjects ) {
        
        $this->DBH = new mysqli( 
            $this->ConnectParams['host'], 
            $this->ConnectParams['user'], 
            $this->ConnectParams['password'], 
            $this->ConnectParams['database']
        );
        
        if( mysqli_connect_error() ) {
            die('Ошибка подключения (' . mysqli_connect_errno() . ') ' . mysqli_connect_error());
        } // End if
        
        // Записываем ссылки в базу
        // Можно как-то придумать пропуск в зависимости от входных данных( пустых, например)
        if( ( count( $Links ) > 0 ) && ( count( $NestingArray ) > 0 ) )
            $this->_FirstTimeSaveLinks( $Links, $NestingArray, $LinksProjects );
        
	} // End function __counstruct
    
    
    /**
    * Метод "обертка" для получения данных о неотфильтрованных ссылках и уровнях вложенности из базы
    * Вызывается из index.php
    * Пример использования (index.php)
    * <code>
    *   list( $TrustedLinks, $NestingArray, $UnTrustedLinks, $UnTrustedLinksIDs, $UnTrustedLinksReasons ) = $Storage->GetDatas();
    * </code>
    *
    * @access public
    *
    * @example index.php Пример использования в index.php
    * @uses Storage::_GetLinksAndNesting() для запроса на получения данных о неотфильтрованных ссылках и уровнях вложенности к базе
    *
    * @return array Массив ( $TrustedLinks, $NestingArray, $LinksProjects, $UnTrustedLinks, $UnTrustedLinksIDs, $UnTrustedLinksReasons ), $TrustedLinks - неотфильтрованные ссылки ( "ID" => "URL" ), $NestingArray - уровень вложенности ( "ID" => "Level" ), $LinksProjects - проекты для ссылок ( "ID ссылки" => "ID проекта" ), $UnTrustedLinks - пока заглушка (пустой массив), $UnTrustedLinksIDs - пока заглушка (пустой массив), $UnTrustedLinksReasons - пока заглушка (пустой массив)
    */
    public function GetDatas() {
    
        // Получаем неотфильтрованные ссылки и уровни вложенности из базы
        $this->_GetLinksAndNesting();
        
        $UnTrustedLinks = array();
        $UnTrustedLinksIDs = array();
        $UnTrustedLinksReasons = array();
        
        return array( $this->Links, $this->NestingArray, $this->LinksProjects, $UnTrustedLinks, $UnTrustedLinksIDs, $UnTrustedLinksReasons );
        
    } // End function GetDatas
    
    
    /**
    * Сохранение ссылок в базе
    * Вызывается из Storage::__construct()
    * Пример использования
    * <code>
    *   $this->_FirstTimeSaveLinks( $Links, $NestingArray, $LinksProjects );
    * </code>
    * Использует mysqli
    * @link http://docs.php.net/manual/ru/book.mysqli.php описание функций mysqli
    *
    * @access protected
    *
    * @param string $Links массив ссылок, передаваемых в конструктор
    * @param string $NestingArray массив уровней вложенности ссылок, передаваемых в конструктор
    * @param string $LinksProjects идентификаторов проектов для ссылок, передаваемых в конструктор
    * @return void
    *
    */
    protected function _FirstTimeSaveLinks( $Links, $NestingArray, $LinksProjects ) {
    
        // Записываем ссылки в базу
        $this->DBH->query( "TRUNCATE TABLE links_to_filter" );
        $this->DBH->query( "TRUNCATE TABLE links_untrasted" );
        if( $stmt = $this->DBH->prepare("
            INSERT INTO links_to_filter (url, nesting_level, start_date, project_id) VALUES ( ?, ?, CURRENT_DATE, ? )
            "
        ) ) {
            
            foreach( $Links as $ID => $URL ) {
                if( isset( $NestingArray[$ID] ) ) {
                    $stmt->bind_param( 'sii', $URL, $NestingArray[$ID], $LinksProjects[$ID] );
                    $stmt->execute();
                } // End if
            } // End foreach
            
            $stmt->close();
            
        } // End if
        
    } // End function _FirstTimeSaveLinks
    
    
    /**
    * Выполняет запрос на получения данных о неотфильтрованных ссылках и уровнях вложенности к базе и записывает результат в  $this->Links, $this->NestingArray и $this->LinksProjects
    * Вызывается из Storage::GetDatas()
    * Пример использования
    * <code>
    *   $this->_GetLinksAndNesting();
    * </code>
    * Использует mysqli
    * @link http://docs.php.net/manual/ru/book.mysqli.php описание функций mysqli
    *
    * @access protected
    *
    * @return void
    *
    */
    protected function _GetLinksAndNesting() {
    
        if( $result = $this->DBH->query( "
            SELECT id, url, nesting_level, project_id
            FROM links_to_filter
            WHERE is_filtered = 'N'
        " ) ) {
                
            while( $row = $result->fetch_assoc() ) {
                $this->Links[$row['id']] = $row['url'];
                $this->NestingArray[$row['id']] = $row['nesting_level'];
                $this->LinksProjects[$row['id']] = $row['project_id'];
            } // End while
            
            $result->free();
            
        } // End if
        
    } // End function _FirstTimeSaveLinks
    
    
    /**
    * Деструктор
    * Использует mysqli
    * @link http://docs.php.net/manual/ru/book.mysqli.php описание функций mysqli
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
    * Помечает в базе ссылку как не прошедшую филтрацию
    * Вызывается из index.php
    * Пример использования (index.php)
    * <code>
    *   $Storage->UnTrust(0, $UnTrustedLinksIDs[0], $UnTrustedLinksReasons[0]);
    * </code>
    *
    * @access public
    *
    * @example index.php Пример использования в index.php
    *
    * @param integer $Step шаг, на котором "отвалились" ссылки
    * @param array $UnTrustedLinksIDs массив ID-ов "отвалившихся" ссылок
    * @param array $UnTrustedLinksReasons причина отказа
    * @param bool $APILog если флаг установлен в true, то ставится пометка об отправке в лог API
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
    * Помечаем ошибки при проверках для исключения их из BL
    * Вызывается из index.php
    * Пример использования (index.php)
    * <code>
    *   $Storage->MarkErrors($ErrorLinksIDs);
    * </code>
    *
    * @access public
    *
    * @example index.php Пример использования в index.php
    *
    * @param array $ErrorLinksIDs массив ID-ов ссылок, при генерации которых произошли ошибки
    */
    function MarkErrors( $ErrorLinksIDs ) {
    
        foreach( $ErrorLinksIDs as $step => $IDs ) {
            foreach( $IDs as $ID )
                $this->ErrorLinksIDs[] = $ID;
        } // End foreach
                
    } // End function MarkErrors
    
    
    /**
    * Получение данных для генерации отчета
    * Вызывается из index.php
    * Пример использования (index.php)
    * <code>
    *   list( $StorageTrustedLinks, $StorageUnTrustedLinksAPI, $StorageUnTrustedLinks ) = $Storage->GetReport();
    * </code>
    *
    * @access public
    *
    * @example index.php Пример использования в index.php
    *
    * @return array Массив ( $StorageTrustedLinks, $StorageUnTrustedLinks ), $StorageTrustedLinks - прошедшие фильтр ссылки, $StorageUnTrustedLinksAPI - непрошедшие фильтр ссылки с указанием причины отказа для проверок с использованием API, $StorageUnTrustedLinks - непрошедшие фильтр ссылки с указанием причины отказа без API
    */
    function GetReport() {
    
        $StorageTrustedLinks = array();
        $StorageUnTrustedLinksAPI = array();
        $StorageUnTrustedLinks = array();
        $StorageUnTrustedLinksBL = array();

        // API
        if( $result = $this->DBH->query( "
            SELECT lf.id, lf.url, lf.nesting_level, lf.is_good, lu.reason, lu.api_log
            FROM links_to_filter lf LEFT JOIN links_untrasted lu ON (lu.link_id = lf.id)
        " ) ) {
        
            while( $row = $result->fetch_assoc() ) {               
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
        
        $this->_ToBL($StorageUnTrustedLinksBL);
        
        return array( $StorageTrustedLinks, $StorageUnTrustedLinksAPI, $StorageUnTrustedLinks );
        
    } // End function GetReport
	
    
    /**
    * Отправляет ссылки в black list
    * Вызывается из Storage::GetTrusted()
    * Пример использования Storage::GetTrusted()
    * <code>
    *   $this->_ToBL($StorageUnTrustedLinks);
    * </code>
    *
    * @access protected
    *
    * @example Storage::GetTrusted() Пример использования в Storage::GetTrusted()
    *
    * @param array $UnTrustedLinks массив ссылок для записи в black list с указанием причин
    * @return void
    */
    protected function _ToBL( $UnTrustedLinks ) {
        
        $insert_bl_q = "
            INSERT IGNORE INTO links_bl ( project_id, url, hash, reason ) VALUES ( ?, ?, ?, ? ) 
        ";
        
        if( $bl_s = $this->DBH->prepare($insert_bl_q) ) {
        
            foreach( $UnTrustedLinks as $ID => $Link ) {                
                $bl_s->bind_param( 'isss', $this->LinksProjects[$ID], $Link['url'], md5($Link['url']), $Link['reason'] );
                $bl_s->execute();
            } // End foreach
            
            $bl_s->close();
        
        } // End if

    } // End function _ToBL
    

    /**
    * Фильтрует входящие ссылки по black list и возвращает ссылки. непрошедшие фильтрацию
    * Вызывается из index.php
    * Пример использования (index.php)
    * <code>
    *   $BlackListIDs = $Storage->GetBL( $TrustedLinks );
    * </code>
    *
    * @access public
    *
    * @example index.php Пример использования в index.php
    *
    * @param array $TrustedLinks массив ссылок для фильтрации по bl
    * @return array $UntrustedIDs - массив ID-ов непрошедших фильтрацию ссылок
    *
    */
    public function GetBL( $TrustedLinks ) {
    
        $UnTrusted = array();
        
        $URLHashes = array();
        foreach( $TrustedLinks as $Link ) {
            $URLHashes[] = "'" . md5($Link) . "'";
        } // End foreach
        
        $query = "
            SELECT id, url, reason
            FROM links_bl
            WHERE hash IN (" . implode( ",", $URLHashes ) . ")
            ";
        
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