<?php
/******************************************************************************************************************
 ******************************************************************************************************************
 *** DealerData PriceCheck Form Control Class
 ******************************************************************************************************************
 *****************************************************************************************************************/
global $db, $pdb;
class DealerData___PriceCheck___Form extends DealerData___PriceCheck {
    protected $pdb;
    private $Query, $QueryModifiers = [];
    private $QueryModificationMethods = [];
    private $QueryResultProcessingMethods = [];

    private $IncludeUnsold = false;

    private $OutputData = [];

    function __construct() {
      global $pdb;
        if ( isset($_SESSION['Debug.Init.Class']) )
            echo "DealerData___PriceCheck___Form init\n";

        $this->pdb = $pdb;
        $this->QueryModificationMethods = [
          'Year'     =>     function($Input) {
            if ( strstr($Input, '-') ) {
              list($min,$max) = explode('-', $Input);
              return ' Year >= ' . (int)trim($min) . ' AND Year <= ' . (int)trim($max);
            }

            if ( strstr($Input, '>') || strstr($Input, '&gt;') )
              return ' Year >= ' . (int)trim(str_replace(['>','&gt;'], '', $Input));


            if ( strstr($Input, '<') || strstr($Input, '&lt;') )
              return ' Year <= ' . (int)trim(str_replace(['<','&gt;'], '', $Input));

            return ' Year = '.(int)$Input;

          },
          'Make'      =>     function($Input) {
            return ' Make LIKE \'%'.$Input.'%\' ';
          },
          'Model'      =>     function($Input) {
            return ' Model LIKE \'%'.$Input.'%\' ';
          },
          'Engine'      =>     function($Input) {
            list($EngineSize , $EngineCylinders) = explode(' ', $Input);
            $EngineCylinders = (int)$EngineCylinders;
            return ' EngineSize = \''.$EngineSize.'\' AND Cylinders = '.$EngineCylinders;
          },
          'Mileage'      =>     function($Input) {
            if ( strstr($Input, '-') ) {
              list($min,$max) = explode('-', $Input);
              return ' CAST(Miles AS UNSIGNED) >= ' . (int)trim($min) . ' AND CAST(Miles AS UNSIGNED) <= ' . (int)trim($max);
            }

            if ( strstr($Input, '>') )
              return ' CAST(Miles AS UNSIGNED) >= ' . (int)trim(str_replace(['>','k'], '', $Input));


            if ( strstr($Input, '<') )
              return ' CAST(Miles AS UNSIGNED) <= ' . (int)trim(str_replace(['<','k'], '', $Input));

            return 'Unknown mileage format';
          },
          'Turnover'      =>     function($Input) {
            if ( strstr($Input, '-') ) {
              list($min,$max) = explode('-', $Input);
              return ' (last_seen-first_seen) >= ' . (new Date)->Translate(  trim(   str_replace('>', '', $min)   ) , 0 ) . ' AND (last_seen-first_seen) <= ' . (new Date)->Translate(  trim(   str_replace('>', '', $max)   ) , 0 );
            }

            if ( strstr($Input, '>') )
              return ' (last_seen-first_seen) >= ' . (new Date)->Translate(  trim(   str_replace('>', '', $Input)   ) , 0 );


            if ( strstr($Input, '<') )
              return ' (last_seen-first_seen) <= ' . (new Date)->Translate(  trim(   str_replace('<', '', $Input)   ) , 0 );

            return 'Unknown turnover format';
          },
          'Color'      =>     function($Input) {
            return ' ExteriorColor LIKE \'%'.$Input.'%\' ';
          },
          'Location'      =>     function($Input) {
            die('hai');
            // Get all store IDs from this Location
            global $db;
            list($city,$state) = explode(',', $Input);
            $stores = $db->Fetch('carmax_locations', 'StoreId', 'city LIKE \'%'.trim($city).'%\' AND state LIKE \'%'.trim($state).'%\'', 1);
            $store_ids = [];
            foreach($stores as $store)
              $store_ids[] = $store['StoreId'];
            return ' StoreId IN('.join(',', $store_ids).') ';
          }
        ];

        $this->QueryResultProcessingMethods = [


          'Turnover'    =>     function($OptionValue) {
            $OptionValue = max($OptionValue, 60*60*24);
              return (new Date)->Abbr($OptionValue, 0);
          },

          'Location'    =>     function($OptionValue) {
            // Get the Store City name
            return 'Winston-Salem, NC';
            global $db;
            $store = $db->Fetch('carmax_locations', 'city,state,zip_code', 'StoreId='.$OptionValue);
            return $store['city'] . ', '.$store['state'].' '.$store['zip_code'];
          }




        ];

        $this->QueryFieldModificationMethods = [
          'Location' => function() {
            // The query below doesn't work, and this parent array isnt even used yet, but we will need
            // something like this because there is no way to JOIN with the current query modifier setup
              return
                '(SELECT CONCAT(city, \',\', state) FROM carmax_locations WHERE StoreId=)';
          }
        ];
    } // End __construct() method

    private function PopulateQueryModifiers() {
        foreach($this->Criteria as $CriteriaField=>$CriteriaValue) {
          if ( !strlen(trim($CriteriaValue)) )
            continue;
          $QueryModifier = $this->ParseToQueryModifier($CriteriaField, $CriteriaValue);
          if ( !in_array($QueryModifier, $this->QueryModifiers) )
            $this->QueryModifiers[] = $this->ParseToQueryModifier($CriteriaField, $CriteriaValue);
        }
    }

    public function PopulateCriteriaOptions($CriteriaFieldDistinction) {
      $this->PopulateQueryModifiers();

      $JoinModifier = 'LEFT JOIN carmax_sold_vehicles ON carmax_sold_vehicles.Vin = carmax_vehicles.Vin';
      if ( $this->IncludeUnsold )
        $JoinModifier = '';

      $Query = '
        SELECT
          DISTINCT '.$CriteriaFieldDistinction.'
        FROM
          carmax_vehicles
        '.$JoinModifier.'
      ';

      $OrderByField = $CriteriaFieldDistinction;
      if ( stristr($CriteriaFieldDistinction, ' AS ') ) {
        if ( !stristr($CriteriaFieldDistinction, 'CAST') )
          $OrderByField = preg_replace('#^.* AS (.*)$#i', '$1', $CriteriaFieldDistinction);
        else
          $OrderByField = preg_replace('#^CAST\((.*) AS$#i', '$1', $CriteriaFieldDistinction);
      }

      if ( sizeof($this->QueryModifiers) )
        $Query .= ' WHERE ' . join(' AND ', $this->QueryModifiers);

      $Query .= ' ORDER BY '.$OrderByField.' ASC ';



      $GetCriteriaOptions = $this->pdb->Query($Query)->FetchAll();

      $CriteriaOptions = [];
      foreach($GetCriteriaOptions as $key=>$val) {
        $val = $val[$OrderByField];
        if ( isset($this->QueryResultProcessingMethods[$OrderByField]) )
          $CriteriaOptions[] = $this->QueryResultProcessingMethods[$OrderByField]($val);
        else
          $CriteriaOptions[] = $val;
      }

      $CriteriaOptions = array_values(array_unique($CriteriaOptions));

      $this->OutputData['Options']       = $CriteriaOptions;
      $this->OutputData['FieldDB']       = $CriteriaFieldDistinction;
      $this->OutputData['FieldLabel']    = $OrderByField;

      $this->OutputData['OptionsQuery']  = $Query;

      return $this;
    }

    public function PopulateAverages() {
      $this->PopulateQueryModifiers();

      $Query = '
        SELECT
          MIN(Price) AS MinPrice,
          AVG(Price) AS AvgPrice,
          MAX(Price) AS MaxPrice,
          MIN( CAST(Miles AS UNSIGNED) ) * 1000 AS MinMileage,
          AVG( CAST(Miles AS UNSIGNED) ) * 1000 AS AvgMileage,
          MAX( CAST(Miles AS UNSIGNED) ) * 1000 AS MaxMileage
        FROM
          carmax_vehicles
      ';

      if ( sizeof($this->QueryModifiers) )
        $Query .= ' WHERE ' . join(' AND ', $this->QueryModifiers);

      if ( stristr($Query, 'Turnover') )
        echo $Query;

      $Spread = $this->pdb->Query($Query)->Fetch();

      $this->OutputData['Spread']      = [
        'Min'=>[
          'Price'    =>   $Spread['MinPrice'],
          'Mileage'  =>   $Spread['MinMileage']
        ],
        'Avg'=>[
          'Price'    =>   $Spread['AvgPrice'],
          'Mileage'  =>   $Spread['AvgMileage']
        ],
        'Max'=>[
          'Price'    =>   $Spread['MaxPrice'],
          'Mileage'  =>   $Spread['MaxMileage']
        ]
      ];

      $this->OutputData['AveragesQuery'] = $Query;

      return $this;
    }

    public function Output() {
      return $this->OutputData;
    }


    private function ParseToQueryModifier($CriteriaField, $CriteriaValue) {
      $QueryModifier = $CriteriaField . ' = ' . $CriteriaValue;
      if ( !isset($this->OutputData['QueryModifiers']) || !in_array($QueryModifier, $this->OutputData['QueryModifiers']) )
        $this->OutputData['QueryModifiers'][] = $QueryModifier;
      return $this->QueryModificationMethods[$CriteriaField]($CriteriaValue);
    }


    function __destruct() {
    } // End __destruct() method
}
?>
