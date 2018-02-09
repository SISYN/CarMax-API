<?php
/*
 * CarMax->Region
 * If calling CarMax->Region separately, exceptions must be stated last or after the region from which they are removed.
 * Correct Example:
 *  (new CarMax)->Region(27103)->Region('raleigh')->Region('ca')->Region('-27616');
 * Incorrect Example:
 *  (new CarMax)->Region(27103)->Region('-27616')->Region('raleigh')->Region('ca');
 *  ^ Removing the Raleigh zip 27616 before the city Raleigh has been added will have no effect
 *
 * But if done in junction, can be anywhere
 * (new CarMax)->Region('-27616', 27103, 'raleigh', 'ca')
 *
 */
/******************************************************************************************************************
 ******************************************************************************************************************
 *** DealerData Parent Class
 ******************************************************************************************************************
 *****************************************************************************************************************/
global $db, $pdb;
class DealerData extends Base {
    protected $db, $pdb;
    protected $Config;
    /******************************************************************************************************************
     * __construct() - Inherited
     *****************************************************************************************************************/
    function __construct() {
        $this->Init();
        $this->Config = new DealerData___Config();
    } // End __construct() method

    public function Init() {
        global $db, $pdb;
        $this->db = $db;
        $this->pdb = $pdb;

        return $this;
    }

    public function Config() {
        if ( sizeof(func_get_args()) ) { // Allow setting of a new config
            $this->Config = func_get_arg(0);
            if ( method_exists($this, 'InitAfterConfig') )
                $this->InitAfterConfig();
            return $this;
        }
        return $this->Config;
    }


    public function API() {
        return (new DealerData___API())->Config($this->Config);
    }

    public function PriceCheck() {
        return (new DealerData___PriceCheck())->Config($this->Config);
    }

    public function Cron() {
        return (new DealerData___Cron())->Config($this->Config);
    }

    public function Diagnostic($AttributeName) {
        return (new DealerData___Diagnostic($AttributeName))->Config($this->Config);
    }

    public function Report() {
        return (new DealerData___Report())->Config($this->Config);
    }

    public function Subscriber() {
        return (new DealerData___Subscriber())->Config($this->Config);
    }



    function __destruct() {
    } // End __destruct() method
}
?>
