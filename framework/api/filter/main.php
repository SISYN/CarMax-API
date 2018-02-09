<?php
/******************************************************************************************************************
 ******************************************************************************************************************
 *** DealerData Filtering Class
 ******************************************************************************************************************
 *****************************************************************************************************************/
class DealerData___API___Filter extends DealerData___API {
    protected $Filters;
    function __construct() {
        $this->Filters = [];
        if ( isset($_SESSION['Debug.Init.Class']) )
            echo "DealerData___API___Filter init\n";

        // Determine if a URL filter string is set
        if ( isset($_GET[$this->Config['API.Filter.GET.Key']]) )
            $this->Add(decode_string($_GET[$this->Config['API.Filter.GET.Key']]));

    } // End __construct() method

    public function Get($AttributeName='*') {
        if ( $AttributeName == '*' )
            return $this->Filters;

        return $this;
    }

    public function Add($FilterInput) {
        if ( is_json($FilterInput) )
            $this->AddFiltersFromJSON($FilterInput);
        else
            $this->AddFilterFromCallsign($FilterInput);
    }

    public function Remove() {

    }


    /******************************************************************************************************************
     ******************************************************************************************************************
     * BEGIN Result Filtering
     ******************************************************************************************************************
     *****************************************************************************************************************/

    private function AddFilterFromCallsign($Callsign) {

    }

    /******************************************************************************************************************
     * AddJSON() - Applies a JSON string of filters
     * @param mixed $JSON - JSON String to process
     * @return mixed
     *****************************************************************************************************************/
    private function AddFiltersFromJSON($JSON) {
        foreach(json_decode($JSON, 1) as $filter_category=>$filters) {
            if ( !in_array($filter_category, ['regions', 'vehicles', 'turnover', 'date', 'price', 'mileage', 'year']) )
                continue; // invalid filter category, skip
            // Remove 's' from end of category, ucwords(), then call its member function
            $member_method_name = ucwords(strtolower(preg_replace('#s$#i', '', $filter_category)));
            if ( $filter_category == 'regions' || $filter_category == 'vehicles' ) {
                // It only gets called with one parameter
                foreach($filters as $filter)
                    $this->$member_method_name($filter);
            } else {
                if ( isset($filters['min']) && !isset($filters['max']) )
                    $this->$member_method_name($filters['min']);
                if ( !isset($filters['min']) && isset($filters['max']) )
                    $this->$member_method_name(-1, $filters['max']);
                if ( isset($filters['min']) && isset($filters['max']) )
                    $this->$member_method_name($filters['min'], $filters['max']);
            }
        }

        return $this;
    }

    /******************************************************************************************************************
     * Region() - Accepts an unlimited number of regions as parameters
     * @return mixed
     *****************************************************************************************************************/
    public function Region() {
        $NewRegions = func_get_args();
        if ( !sizeof($NewRegions) )
            return $this->Get('Regions');

        // Put exceptions (^-) entries at back of array so they'll be removed after all have been added
        usort($NewRegions, function($a) {
            if ( preg_match('#^\-#', $a) )
                return 1;
            return 0;
        });

        if ( !isset($this->Filters['Regions']['Stores']) )
            $this->Filters['Regions']['Stores'] = [];
        foreach($NewRegions as $NewRegion) {
            $this->Filters['Regions']['Input'][] = $NewRegion;
            $this->Filters['Regions']['Stores'] = (new DealerData___API___Filter___Region($NewRegion))->
                Config($this->Config)->Compile($this->Filters['Regions']['Stores'])->Fetch();
        }

        foreach($this->Filters['Regions']['Stores'] as $store_details)
            $this->Filters['Regions']['StoreIDs'][] = $store_details['StoreId'];

        // Ensure all Store IDs are unique
        $this->Filters['Regions']['Input'] = array_unique($this->Filters['Regions']['Input'], SORT_REGULAR);
        $this->Filters['Regions']['Stores'] = array_unique($this->Filters['Regions']['Stores'], SORT_REGULAR);
        $this->Filters['Regions']['StoreIDs'] = array_unique($this->Filters['Regions']['StoreIDs'], SORT_REGULAR);

        return $this;
    } // End Region
    public function Stores() { return $this->Region(); } // End alias Stores for Region

    /******************************************************************************************************************
     * Vehicle() - Accepts an unlimited number of vehicles as parameters
     * @return mixed
     *****************************************************************************************************************/
    public function Vehicle() {
        $vehicles = func_get_args();
        if ( !sizeof($vehicles) )
            return $this->Filters['Vehicles'];

        // Put exceptions (^-) entries at back of array so they'll be removed after all have been added
        usort($vehicles, function($a) {
            if ( preg_match('#^\-#', $a) )
                return 1;
            return 0;
        });

        foreach($vehicles as $vehicle)
            $this->Filters['Vehicles'][] = trim(str_replace('\'', '', $vehicle)); // remove any quotes or space that might be present

        return $this;
    } // End Vehicle
    public function Vehicles() { return $this->Vehicle(); } // End alias Vehicles for Vehicle

    /******************************************************************************************************************
     * Date() - Sets min/max timestamp
     * @param int $MinDateInput - Min timestamp
     * @param int $MaxDateInput - Max timestamp
     * @return mixed
     *****************************************************************************************************************/
    public function Date($MinDateInput=-1, $MaxDateInput=-1) {
        if ( $MinDateInput == -1 && $MaxDateInput == -1 )
            return ['min'=>$this->Filters['Date']['Min'], 'max'=>$this->Filters['Date']['Max']];

        if ( is_string($MinDateInput) && is_string($MaxDateInput) ) {
            // Use $MinDateInput as the period length and $MaxDateInput as starting point

        }

        if ( $MinDateInput == -1 )
            $MinDateInput = $this->Filters['Date']['Min'];
        if ( $MaxDateInput == -1 )
            $MaxDateInput = $this->Filters['Date']['Max'];

        $this->Filters['Date']['Min'] = ( !is_int($this->Filters['Date']['Min']) ) ? (new Date)->Translate($MinDateInput) : $MinDateInput;
        $this->Filters['Date']['Max'] = ( !is_int($this->Filters['Date']['Max']) ) ? (new Date)->Translate($MaxDateInput) : $MaxDateInput;


        return $this;
    }

    /******************************************************************************************************************
     * LotTime() - Sets/gets the max turnover period
     * @param mixed $TimeInput - Max turnover period
     * @return mixed
     *****************************************************************************************************************/
    public function LotTime($TimeInput=false) {
        if ( $TimeInput === false )
            return $this->Filters['Turnover']['Max'];

        $this->Filters['Turnover']['Max'] = (new Date)->Translate($TimeInput, 0);
        return $this;
    } // End LotTime
    /******************************************************************************************************************
     * Turnover() - Sets/gets the max turnover period
     * @param mixed $TimeInput - Max turnover period
     * @return mixed
     *****************************************************************************************************************/
    public function Turnover($TimeInput=false) {
        return $this->LotTime($TimeInput);
    } // End Turnover


    /******************************************************************************************************************
     * Price() - Sets min/max price
     * @param int $MinPrice - Min price
     * @param int $MaxPrice - Max price
     * @return mixed
     *****************************************************************************************************************/
    public function Price($MinPrice=-1, $MaxPrice=-1) {
        if ( $MinPrice == -1 )
            $MinPrice = $this->Filters['Price']['Min'];
        if ( $MaxPrice == -1 )
            $MaxPrice = $this->Filters['Price']['Max'];

        $this->Filters['Price']['Min'] = $MinPrice;
        $this->Filters['Price']['Max'] = $MaxPrice;

        if ( $MinPrice == -1 && $MaxPrice == -1 )
            return ['min'=>$this->Filters['Price']['Min'], 'max'=>$this->Filters['Price']['Max']];

        return $this;
    }

    /******************************************************************************************************************
     * Mileage() - Sets min/max mileage
     * @param int $MinMileage - Min mileage
     * @param int $MaxMileage - Max mileage
     * @return mixed
     *****************************************************************************************************************/
    public function Mileage($MinMileage=-1, $MaxMileage=-1) {
        // Determine if it has 'K' at the end of it
        if ( preg_match('#k$#i', $MinMileage) )
            $MinMileage = (((int)$MinMileage)*1000);
        if ( preg_match('#k$#i', $MaxMileage) )
            $MaxMileage = (((int)$MaxMileage)*1000);

        // Determine if no values have been set
        if ( $MinMileage == -1 )
            $MinMileage = $this->Filters['Mileage']['Min'];
        if ( $MaxMileage == -1 )
            $MaxMileage = $this->Filters['Mileage']['Max'];

        $this->Filters['Mileage']['Min'] = $MinMileage;
        $this->Filters['Mileage']['Max'] = $MaxMileage;

        if ( $MinMileage == -1 && $MaxMileage == -1 )
            return ['min'=>$this->Filters['Mileage']['Min'], 'max'=>$this->Filters['Mileage']['Max']];

        return $this;
    }

    /******************************************************************************************************************
     * Year() - Sets min/max year
     * @param int $MinYear - Min year
     * @param int $MaxYear - Max year
     * @return mixed
     *****************************************************************************************************************/
    public function Year($MinYear=-1, $MaxYear=-1) {
        if ( $MinYear == -1 )
            $MinYear = $this->Filters['Year']['Min'];
        if ( $MaxYear == -1 )
            $MaxYear = $this->Filters['Year']['Max'];

        $this->Filters['Year']['Min'] = $MinYear;
        $this->Filters['Year']['Max'] = $MaxYear;

        if ( $MinYear == -1 && $MaxYear == -1 )
            return ['min'=>$this->Filters['Year']['Min'], 'max'=>$this->Filters['Year']['Max']];

        return $this;
    }



    /******************************************************************************************************************
     ******************************************************************************************************************
     * END Result Filtering
     ******************************************************************************************************************
     *****************************************************************************************************************/


    function __destruct() {
    } // End __destruct() method
}
?>