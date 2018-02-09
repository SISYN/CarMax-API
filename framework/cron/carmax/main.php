<?php
/******************************************************************************************************************
 ******************************************************************************************************************
 *** DealerData CarMax Cron Jobs Class
 ******************************************************************************************************************
 *****************************************************************************************************************/
global $db, $pdb;
class DealerData___Cron___CarMax extends DealerData___Cron {

    function __construct() {
        if ( isset($_SESSION['Debug.Init.Class']) )
            echo "DealerData___Cron___CarMax init\n";
    } // End __construct() method



    public function Sales() {
        return (new DealerData___Cron___CarMax___Sales())->Config($this->Config);
    }
    public function Scrape() {
        return (new DealerData___Cron___CarMax___Scrape())->Config($this->Config);
    }
    public function Locations() {
        return (new DealerData___Cron___CarMax___Locations())->Config($this->Config);
    }


    function __destruct() {
    } // End __destruct() method
}
?>