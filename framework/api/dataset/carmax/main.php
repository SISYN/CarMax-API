<?php
/******************************************************************************************************************
 ******************************************************************************************************************
 *** DealerData API CarMax Class
 ******************************************************************************************************************
 *****************************************************************************************************************/
global $db, $pdb;
class DealerData___API___DataSet___CarMax extends DealerData___API___DataSet {

    function __construct() {
        if ( isset($_SESSION['Debug.Init.Class']) )
            echo "DealerData___API___DataSet___CarMax init\n";
    } // End __construct() method



    public function Query($QueryFile) {
        return (new DealerData___API___DataSet___CarMax___Query($QueryFile))->Config($this->Config)->Filter(json_encode($this->Filters));
    }

    function __destruct() {
    } // End __destruct() method
}
?>
