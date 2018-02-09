<?php
global $db, $pdb;
class DealerData___API___Filter___Region extends DealerData___API___Filter {
    protected $db, $pdb;
    private $AllStates;
    private $StoreIDs, $AddOrRemove;
    /******************************************************************************************************************
     * __construct()
     *****************************************************************************************************************/
    function __construct($RegionSelection='', $RegionType='auto') {
        global $db, $pdb;
        $this->db = $db;
        $this->pdb = $pdb;

        $all_states_json = '{"AL":"ALABAMA","AK":"ALASKA","AS":"AMERICAN SAMOA","AZ":"ARIZONA","AR":"ARKANSAS","CA":"CALIFORNIA","CO":"COLORADO","CT":"CONNECTICUT","DE":"DELAWARE","DC":"DISTRICT OF COLUMBIA","FM":"FEDERATED STATES OF MICRONESIA","FL":"FLORIDA","GA":"GEORGIA","GU":"GUAM","HI":"HAWAII","ID":"IDAHO","IL":"ILLINOIS","IN":"INDIANA","IA":"IOWA","KS":"KANSAS","KY":"KENTUCKY","LA":"LOUISIANA","ME":"MAINE","MH":"MARSHALL ISLANDS","MD":"MARYLAND","MA":"MASSACHUSETTS","MI":"MICHIGAN","MN":"MINNESOTA","MS":"MISSISSIPPI","MO":"MISSOURI","MT":"MONTANA","NE":"NEBRASKA","NV":"NEVADA","NH":"NEW HAMPSHIRE","NJ":"NEW JERSEY","NM":"NEW MEXICO","NY":"NEW YORK","NC":"NORTH CAROLINA","ND":"NORTH DAKOTA","MP":"NORTHERN MARIANA ISLANDS","OH":"OHIO","OK":"OKLAHOMA","OR":"OREGON","PW":"PALAU","PA":"PENNSYLVANIA","PR":"PUERTO RICO","RI":"RHODE ISLAND","SC":"SOUTH CAROLINA","SD":"SOUTH DAKOTA","TN":"TENNESSEE","TX":"TEXAS","UT":"UTAH","VT":"VERMONT","VI":"VIRGIN ISLANDS","VA":"VIRGINIA","WA":"WASHINGTON","WV":"WEST VIRGINIA","WI":"WISCONSIN","WY":"WYOMING"}';
        $this->AllStates = json_decode($all_states_json, 1);

        $RegionSelection = strtoupper($RegionSelection);

        $this->AddOrRemove = (preg_match('#^\-#', $RegionSelection)) ? 0 : 1;
        // $this->AddOrRemove = 1 is Add , $this->AddOrRemove = 0 is Remove
        // Now remove the preceding - if it exists
        $RegionSelection = preg_replace('#^\-#', '', $RegionSelection);

        $this->StoreIDs = $this->FindStores($RegionSelection, $RegionType);
    } // End __construct() method




    /******************************************************************************************************************
     * Compile() - Compiles new Store IDs with existing Store IDs
     * @param array $ExistingRegions - Regions you are already using
     * @return object
     *****************************************************************************************************************/
    public function Compile($ExistingRegions=[]) {
        foreach($ExistingRegions as $Region) {
            if ( !$this->AddOrRemove && in_array($Region, $this->StoreIDs) ) {
                // Remove this exception from the list
                unset($this->StoreIDs[array_search($Region, $this->StoreIDs)]);
            } else {
                // Add to existing stores
                if ( !in_array($Region, $this->StoreIDs) )
                    $this->StoreIDs[] = $Region;
            }
        }

        return $this;
    }


    /******************************************************************************************************************
     * Fetch() - Returns all the StoreIDs for the specified region
     * @return array
     *****************************************************************************************************************/
    public function Fetch() {
        return $this->StoreIDs;
    }


    public function Stores() {
        return $this->StoreIDs;
    }






    /******************************************************************************************************************
     * FindStores() - Finds all Store IDs in the supplied region
     * @param string $RegionSelection - Region you want to use
     * @param string $RegionType - Region type, default `auto`
     * @return array
     *****************************************************************************************************************/
    private function FindStores($RegionSelection='', $RegionType='auto') {
        $DetectedRegionType = $this->RegionType($RegionSelection);
        if ( $RegionType == 'store' || $DetectedRegionType == 'store' )
            return [$RegionSelection];
        if ( $RegionType == 'zip' || $DetectedRegionType == 'zip' )
            return $this->FindStoresByZip($RegionSelection);
        if ( $RegionType == 'state' || $DetectedRegionType == 'state' )
            return $this->FindStoresByState($RegionSelection);

        return $this->FindStoresByCity($RegionSelection);
    }



    /******************************************************************************************************************
     * RegionType() - Returns the type for the supplied region
     * @param string $RegionSelection - Region you want to use
     * @return string
     *****************************************************************************************************************/
    private function RegionType($RegionSelection) {
        if ( strlen($RegionSelection) == 5 && (int)$RegionSelection > 1 )
            return 'zip';

        if ( strlen($RegionSelection) == 4 && (int)$RegionSelection > 1 )
            return 'store';

        if ( in_array($RegionSelection, $this->AllStates) || isset($this->AllStates[$RegionSelection]) )
            return 'state';

        // Try it as a city if all else fails
        return 'city';
    }



    /******************************************************************************************************************
     * FindStoresByState() - Finds all Store IDs in the supplied state
     * @param string $State - State name or abbreviation
     * @return array
     *****************************************************************************************************************/
    private function FindStoresByState($State) {
        if ( !in_array($State, $this->AllStates) && !isset($this->AllStates[$State]) )
            return []; // State not found

        $state_abbr = ( array_key_exists($State, $this->AllStates) ) ?
            $State :
            array_search($State, $this->AllStates);
        $state_stores = $this->db->Fetch('carmax_locations', '*', 'state=\''.$state_abbr.'\'', 1);
        if ( !$state_stores )
            return [];

        return $state_stores;
    }

    /******************************************************************************************************************
     * FindStoresByCity() - Finds all Store IDs in the supplied city
     * @param string $City - City name
     * @return array
     *****************************************************************************************************************/
    private function FindStoresByCity($City) {
        if ( strstr($City, ',') ) {
            $parts = explode(',', $City);
            $City = trim($parts[0]); $State = trim($parts[1]);
            $city_stores = $this->db->Fetch('carmax_locations', '*', 'city=\''.$City.'\' AND state=\''.$State.'\'', 1);
        } else {
            $city_stores = $this->db->Fetch('carmax_locations', '*', 'city=\''.$City.'\'', 1);
        }

        if ( !$city_stores )
            return [];

        return $city_stores;
    }

    /******************************************************************************************************************
     * FindStoresByZip() - Finds all Store IDs in the supplied zip code
     * @param string $Zip - Zip code
     * @return array
     *****************************************************************************************************************/
    private function FindStoresByZip($Zip) {
        $zip_stores = $this->db->Fetch('carmax_locations', '*', 'zip_code=\''."$Zip".'\'', 1); // For Zip to be a string to help with leading 0s
        if ( !$zip_stores )
            return [];

        return $zip_stores;
    }



    function __destruct() {
    } // End __destruct() method
}
?>