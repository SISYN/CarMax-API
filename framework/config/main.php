<?php
/******************************************************************************************************************
 ******************************************************************************************************************
 *** DealerData Configuration Class
 ******************************************************************************************************************
 *****************************************************************************************************************/
global $db, $pdb;
class DealerData___Config extends DealerData {
    protected $Attributes = [
        'Threshold.Cache'                            =>    '1h',
        'Threshold.Demo'                             =>    '4h',
        'Threshold.Sales'                            =>    '1w',
        'Threshold.Liquidated'                       =>    '31d',
        'Threshold.Cron.Inventory.Refresh.PerPage'   =>    50,
        'Limit.Sales.Hot'                            =>    100,
        'Limit.Sales.Popular'                        =>    100,
        'Limit.Sales.Liquidated'                     =>    100,
        'Brackets.Mileage'                           =>    [15, 25, 40, 60, 80],
        'Brackets.Price'                             =>    [10, 15, 20, 25, 30],
        'Limit.Sales.Timeline'                       =>    '6w',



        'API.Filter.GET.Key'                         =>    'i'                  // ?i=FILTER_STRING
    ];

    function __construct() {
        if ( isset($_SESSION['Debug.Init.Class']) )
            echo "DealerData___Config init\n";
    } // End __construct() method

    public function Set($AttributeName, $AttributeValue) {
        $this->Attributes[$AttributeName] = $AttributeValue;
        return $this;
    }

    public function Get($AttributeName='*') {
        if ( $AttributeName == '*' )
            return $this->Attributes;
        return $this->Attributes[$AttributeName];
    }


    function __destruct() {
    } // End __destruct() method
}
?>