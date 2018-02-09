<?php
/******************************************************************************************************************
 ******************************************************************************************************************
 *** DealerData API CarMax Sold Vehicles Class
 ******************************************************************************************************************
 *****************************************************************************************************************/
global $db, $pdb;
class DealerData___API___DataSet___CarMax___Query extends DealerData___API___DataSet___CarMax {
    protected $QueryFile, $OrderBy, $GroupBy;
    function __construct($QueryFile) {
        $this->Init();
        $this->QueryFile = $QueryFile;
        if ( isset($_SESSION['Debug.Init.Class']) )
            echo "DealerData___API___DataSet___CarMax___Query init\n";

        $this->OrderBy = ['turnover ASC', 'last_seen DESC', 'Price DESC'];
        $this->GroupBy = [];
    } // End __construct() method

    /******************************************************************************************************************
     * Output() - Returns all sold vehicles for the supplied filter(s)
     *   Includes `total_moves` and `lot_time`
     * @return array
     *****************************************************************************************************************/
    public function Output() {

        // Check cache
        /*
        $CacheIdentifier = $QueryFile . '-' . adom_hash(json_endcode($this->Filters));
        $AttemptCache = $this->Cache($QueryFile . '-' . ' something else ');
        if ( $AttemptCache ) {
          $output = $this->UseCache($AttemptCache);
        } else {
          $this->CreateCache($CacheIdentifier);
        }
        */

        $query = (new DealerData___API___Query())->Config($this->Config)->Filter(json_encode($this->Filters))->
            Base(
                (new SQL)->File($this->QueryFile)
            );

        foreach($this->GroupBy as $OrderClause)
            $query->GroupBy($OrderClause);

        foreach($this->OrderBy as $OrderClause)
            $query->OrderBy($OrderClause);

            // Check for cache

        $query = $query->Output();
        return $this->pdb->Query($query)->FetchAll();
    }

    public function OrderBy() {
        $this->OrderBy = [];
        foreach(func_get_args() as $OrderClause)
            $this->OrderBy[] = $OrderClause;

        return $this;
    }

    public function GroupBy() {
        $this->GroupBy = [];
        foreach(func_get_args() as $OrderClause)
            $this->GroupBy[] = $OrderClause;

        return $this;
    }



    function __destruct() {
    } // End __destruct() method
}
?>
