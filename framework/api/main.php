<?php
/******************************************************************************************************************
 ******************************************************************************************************************
 *** DealerData API Class
 ******************************************************************************************************************
 *****************************************************************************************************************/
global $db, $pdb;
class DealerData___API extends DealerData {
    protected $Filters = [
        'Regions'=>[],
        'Vehicles'=>[],
        'Date'=>[
            'Min'=>null,
            'Max'=>null
        ],
        'Turnover'=>[
            'Min'=>null,
            'Max'=>null
        ],
        'Price'=>[
            'Min'=>null,
            'Max'=>null
        ],
        'Year'=>[
            'Min'=>null,
            'Max'=>null
        ],
        'Mileage'=>[
            'Min'=>null,
            'Max'=>null
        ]
    ];
    protected $DataSet = [];

    function __construct() {
        if ( isset($_SESSION['Debug.Init.Class']) )
            echo "DealerData___API init\n";
    } // End __construct() method

    protected function InitAfterConfig() {
        if ( $this->Config ) {
            // Determine if a URL filter string is set
            $FilterVariableName = $this->Config->Get('API.Filter.GET.Key');
            if ( is_string($FilterVariableName) && isset($_GET[$FilterVariableName]) )
                $this->Filter(decode_string($_GET[$FilterVariableName]));

        }

        return $this;
    }
    public function DataSet() {
        if ( is_array($this->DataSet) )
            $this->DataSet = (new DealerData___API___DataSet())->Config($this->Config)->Filter(json_encode($this->Filters));
        return $this->DataSet;
    }
    public function Filter($FilterInput) {

        if ( !sizeof(json_decode($FilterInput, 1)) )
            return $this;

        foreach(json_decode($FilterInput, 1) as $filter_category=>$filters) {
            if ( !in_array(strtolower($filter_category), ['regions', 'vehicles', 'turnover', 'date', 'price', 'mileage', 'year']) )
                continue; // invalid filter category, skip
            // Remove 's' from end of category, ucwords(), then call its member function
            $FilterCategory = ucwords(strtolower($filter_category));
            if ( strtolower($filter_category) == 'regions' || strtolower($filter_category) == 'vehicles' ) {
                // It only gets called with one parameter
                if ( strtolower($filter_category) == 'regions' ) {
                    foreach($filters as $filter)
                        $this->Filters[$FilterCategory][] = $filter;
                } else {
                    foreach($filters as $filter)
                        $this->Filters[$FilterCategory][] = $filter;
                }
                // Only keep unique entries
                $this->Filters[$FilterCategory] = array_unique($this->Filters[$FilterCategory]);
            } else {
                if ( isset($filters['min']) && !isset($filters['max']) )
                    $this->Filters[$FilterCategory]['Min'] = $filters['min'];
                if ( !isset($filters['min']) && isset($filters['max']) )
                    $this->Filters[$FilterCategory]['Max'] = $filters['max'];
                if ( isset($filters['min']) && isset($filters['max']) )
                    $this->Filters[$FilterCategory] = ['Min'=>$filters['min'],'Max'=>$filters['max']];
            }

        }


        return $this;

    }


    /******************************************************************************************************************
     * Key() - Returns a temporary (1s) API key for API request validation
     * @return string
     *****************************************************************************************************************/
    public function Key() {
        $base_key = '--' . time() . ':' . microtime() . '--';
        return sha1($base_key);
    } // End CronKey


    public function Filters() {
        return $this->Filters;
    }


    function __destruct() {
    } // End __destruct() method
}
?>
