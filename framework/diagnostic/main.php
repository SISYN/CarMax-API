<?php
/******************************************************************************************************************
 ******************************************************************************************************************
 *** DealerData Diagnostic Management Class
 ******************************************************************************************************************
 *****************************************************************************************************************/
global $db, $pdb;
class DealerData___Diagnostic extends DealerData {
    protected $AttributeName, $AttributeValue;

    function __construct($AttributeName) {
        if ( isset($_SESSION['Debug.Init.Class']) )
            echo "DealerData___Diagnostic($AttributeName) init\n";

        $this->AttributeName = $AttributeName;
    } // End __construct() method


    public function Set($AttributeValue) {
        $this->AttributeValue = $AttributeValue;
        $this->UpdateDB();

        return $this;
    }

    public function Get($ReturnAll=false) {
        if ( !$this->AttributeValue ) {
            // connect to db to get attr value
            $this->AttributeValue = $this->Fetch($ReturnAll);
        }

        return $this->AttributeValue;
    }

    private function Fetch($ReturnAll=false) {
        global $db;
        $DiagnosticResult = $db->Fetch('dealerdata_diagnostics', '*', 'var_name=\'\' ORDER BY created DESC LIMIT 1');
        if ( $ReturnAll )
          return $DiagnosticResult;
        return $DiagnosticResult['var_value'];
    }

    private function UpdateDB() {
      global $db;
      $db->Create('dealerdata_diagnostics', [
        'var_name' => $this->AttributeName,
        'var_value' => $this->AttributeValue,
        'created'  => time()
      ]);

      return $this;
    }

    function __destruct() {
    } // End __destruct() method
}
?>
