<?php
/**
* ����������� ������ ��� ���������� ������ � ������������ ������
*
* @package SAPE
*/

/**
* ����� ��� ���������� ������ � ������������ ������
*
* @package SAPE
* @author Chevanin Valeriy <chevanin@etorg.ru>
* @todo ������� Email-� � ������ � ��������� � �����������<br>���� ������ �� Common.config.sample.php 
*/
class Report {

    /**
    * ������ ������������� � ������ ������
    * @access protected
    * @var array
    */
    protected $ReportFiles = array();
    /**
    * �� ���� ������ � ������� (�� ����� ������� ��������, �������� ����� �� ��������������, ����� ������������ ��� �����-�� ��������� ���������� �������)
    * @access protected
    * @var string
    */
    protected $EmailFrom = "chevanin@etorg.ru";
    /**
    * ����� ��������������, �� ������� ����� ������� ������ � �������
    * @access protected
    * @var string
    */
    protected $EmailTo = "test@test.ru";

    /**
    * �����������
    * ���������� �� index.php
    * ������ ������������� (index.php)
    * <code>
    *   $Report = new Report();
    * </code>
    *
    * @example index.php ������ ������������� � index.php
    *
    * @return void
    */
	function __construct() {
	} // End function __counstruct
        
    
    /**
    * �������� ������
    * ���������� �� index.php
    * ������ ������������� (index.php)
    * <code>
    *   $Report->Send();
    * </code>
    *
    * @example index.php ������ ������������� � index.php
    *
    * @access public
    *
    * @uses html_mime_mail Mail.class.php ��� �������� �����
    *
    * @return void
    */
    public function Send() {
    
        require_once "Mail.class.php";
        
        echo "<br><br><strong>Sending report...</strong>";
        
        $mail=new html_mime_mail();
        $mail->add_html("<html><body>����� �� ���� �������� �����������. ���������� �� ��������</body></html>");
        foreach( $this->ReportFiles as $file ) {
            $mail->add_attachment("",$file);
        } // End foreach
        $mail->build_message('win'); // ���� �� "win", �� ����p���� koi8
        $mail->send('localhost',
            $this->EmailTo,
            $this->EmailFrom,
            'Report'
        );
        
        echo "<br><strong>Report Sent</strong>";
  
    } // End function Send

    
    /**
    * ��������� ������ CSV �� �������� ��� �������� ������. ���������������� ���� �������� �� �������, �������� ������ �������� � $this->ReportFiles
    * ���������� �� index.php
    * ������ ������������� (index.php)
    * <code>
    *   $Report->GenerateCSV( $StorageTrustedLinks, array( "URL", "�������" ), "trusted_" . date("d-m-Y") . ".csv" );
    * </code>
    *
    * @example index.php ������ ������������� � index.php
    *
    * @access public
    *
    * @param array $ArrayToCSV ������ ������ ��� ��������� CSV �����
    * @param array $TitleString ��������� ����� CSV �����
    * @param string $FileName �������� �����
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