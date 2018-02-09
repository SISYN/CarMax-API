<?php
/******************************************************************************************************************
 ******************************************************************************************************************
 *** DealerData___Cron Class
 ******************************************************************************************************************
 *****************************************************************************************************************/
global $db, $pdb;
class DealerData___Cron extends DealerData {

    function __construct() {
        if ( isset($_SESSION['Debug.Init.Class']) )
            echo "DealerData___Cron init\n";
    } // End __construct() method

    public function CarMax() {
        return (new DealerData___Cron___CarMax())->Config($this->Config);;
    }


    function __destruct() {
    } // End __destruct() method
}
?>
