<?php
/**
* Определение класса для подготовки данных и формирования отчета
*
* @package SAPE
*/

/**
* Класс для подготовки данных и формирования отчета
*
* @package SAPE
* @author Chevanin Valeriy <chevanin@etorg.ru>
* @todo Вынести Email-ы в конфиг и заполнять в констукторе<br>дать ссылку на Common.config.sample.php 
*/
class Report {

    /**
    * Массив прикрепляемых к отчету файлов
    * @access protected
    * @var array
    */
    protected $ReportFiles = array();
    /**
    * От кото письмо с отчетом (не имеет особого значения, обратная почта не обрабатывается, можно использовать для каких-то временных сохранений отчетов)
    * @access protected
    * @var string
    */
    protected $EmailFrom = "chevanin@etorg.ru";
    /**
    * Адрес администратора, на который будет уходить письмо с отчетом
    * @access protected
    * @var string
    */
    protected $EmailTo = "test@test.ru";

    /**
    * Конструктор
    * Вызывается из index.php
    * Пример использования (index.php)
    * <code>
    *   $Report = new Report();
    * </code>
    *
    * @example index.php Пример использования в index.php
    *
    * @return void
    */
	function __construct() {
	} // End function __counstruct
        
    
    /**
    * Отправка отчета
    * Вызывается из index.php
    * Пример использования (index.php)
    * <code>
    *   $Report->Send();
    * </code>
    *
    * @example index.php Пример использования в index.php
    *
    * @access public
    *
    * @uses html_mime_mail Mail.class.php для отправки писем
    *
    * @return void
    */
    public function Send() {
    
        require_once "Mail.class.php";
        
        echo "<br><br><strong>Sending report...</strong>";
        
        $mail=new html_mime_mail();
        $mail->add_html("<html><body>Отчет по всем проектам сформирован. Результаты во вложении</body></html>");
        foreach( $this->ReportFiles as $file ) {
            $mail->add_attachment("",$file);
        } // End foreach
        $mail->build_message('win'); // если не "win", то кодиpовка koi8
        $mail->send('localhost',
            $this->EmailTo,
            $this->EmailFrom,
            'Report'
        );
        
        echo "<br><strong>Report Sent</strong>";
  
    } // End function Send

    
    /**
    * Генерация файлов CSV со ссылками для отправки отчета. Сгененрированный файл хранится на сервере, названия файлов хранятся в $this->ReportFiles
    * Вызывается из index.php
    * Пример использования (index.php)
    * <code>
    *   $Report->GenerateCSV( $StorageTrustedLinks, array( "URL", "Уровень" ), "trusted_" . date("d-m-Y") . ".csv" );
    * </code>
    *
    * @example index.php Пример использования в index.php
    *
    * @access public
    *
    * @param array $ArrayToCSV массив данных для генерации CSV файла
    * @param array $TitleString заголовки полей CSV файла
    * @param string $FileName название файла
    *
    * @return void
    */
    public function GenerateCSV( $ArrayToCSV, $TitleString, $FileName ) {
    
        $fp = fopen($FileName, 'w');

        $TotalArrayToCSV = array();
        $TotalArrayToCSV[] = $TitleString;
        $TotalArrayToCSV = array_merge( $TotalArrayToCSV, $ArrayToCSV );
        
        foreach($TotalArrayToCSV as $fields) {
            fputcsv($fp, $fields, ";");
        } // End foreach

        fclose($fp);        
        $this->ReportFiles[] = $FileName;
        
    } // End function GenerateCSV
    
    
} // End class Report