<?php

class Report {

    /**
    * ID ������� � sape.ru
    * @access protected
    * @var integer
    */
    protected $ProjectID = 0;
    protected $ReportValues = array();
    protected $ReportFiles = array();
    protected $EmailFrom = "chevanin@etorg.ru";
    protected $EmailTo = "test@test.ru";

    /**
    * �����������
    * ���������� �� index.php
    * ������ ������������� (index.php)
    * <code>
    *   $Report = new Report( $PROJECT_ID );
    * </code>
    *
    * @example index.php ������ ������������� � index.php
    *
    * @param integer $ProjectID ����� ������� � Sape.ru
    * @return void
    */
	function __construct( $ProjectID ) {
        
        $this->ProjectID = intval($ProjectID);
        
	} // End function __counstruct
    
    
    public function SetValue( $Values ) {
        
        foreach( $Values as $ValueKey => $Value ) {
            $this->ReportValues[$ValueKey] = $Value;
        } // End foreach
        
    } // End function SetValue
    
    
    public function Send() {
        echo "<br><br><strong>Sending report...</strong>";
        
        require_once "Mail.class.php";
    
        $mail=new html_mime_mail();
        $mail->add_html("<html><body>����� �� ������� " . $this->ProjectID . " �����������. ���������� �� ��������</body></html>");
        foreach( $this->ReportFiles as $file ) {
            $mail->add_attachment("",$file);
        } // End foreach
        $mail->build_message('win'); // ���� �� "win", �� ����p���� koi8
        $mail->send('localhost',
            $this->EmailTo,
            $this->EmailFrom,
            'Report'
        );
        
        /*
        echo "<pre>";
        var_dump( $this->ReportFiles );
        echo "</pre>";
        
        echo "<pre>";
        var_dump( $this->ReportValues );
        echo "</pre>";
        */
        
        echo "<br><strong>Report Sent</strong>";
  
        
    } // End function Send

    
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