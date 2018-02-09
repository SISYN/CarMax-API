<?php
/******************************************************************************************************************
 ******************************************************************************************************************
 *** DealerData New Data Mining/Scraping Cron Job Class
 ******************************************************************************************************************
 *****************************************************************************************************************/
global $db, $pdb;
class DealerData___Cron___CarMax___Scrape extends DealerData___Cron___CarMax {
    private $RefreshAttempts, $VehiclesPerPage, $ScrapeLocation;

    function __construct() {
      if ( isset($_SESSION['Debug.Init.Class']) )
          echo "DealerData___Cron___CarMax___Scrape init\n";

      $this->RefreshAttempts = 0;
      $this->ScrapeLocation = $this->db->Fetch('carmax_locations', 'id,zip_code,StoreId,last_updated_page', 'ORDER BY last_updated ASC LIMIT 1');
    } // End __construct() method


    protected function InitAfterConfig() {
      if ( $this->Config )
        $this->VehiclesPerPage = (new Date)->Translate($this->Config()->Get('Threshold.Cron.Inventory.Refresh.PerPage'));

      return $this;
    }

    public function Refresh() {
      // Increment $this->RefreshAttempts
      $this->RefreshAttempts++;
      $ScrapeURL = $this->URL($this->ScrapeZip(), $this->ScrapePage());
      $ScrapedData = $this->Scrape($ScrapeURL);
      if ( $ScrapedData == [] ) {
        // No data returned, set the last_updated_page back to 0 in the db and set last_updated
        $this->NextLocation();
        return $this;
      }

      // At this point, we know there is data to handle and store
      $this->BatchSave($ScrapedData);

    }


    private function RecordRefreshDiagnostic($StoreID, $PageNumber, $BatchSize, $ExecutionTime) {
        $this->db->Create('carmax_diagnostics_refreshes', [
            'created'=>time(),
            'StoreId'=>$StoreID,
            'batch_size'=>$BatchSize,
            'page_number'=>$PageNumber,
            'execution_time'=>$ExecutionTime
        ]);
    }

    private function NextLocation() {
      // No data returned, set the last_updated_page back to 0 in the db and set last_updated
      $this->db->Update('carmax_locations', [
      'last_updated_page' => 0,
      'last_updated'      => time()
      ], 'id='.$this->ScrapeLocationID());

      if ( $this->RefreshAttempts == 1 )
        $this->Refresh();

      return $this;
    }

    private function NextLocation() {
      // Update the last_updated_page
      $this->db->Update('carmax_locations', [
          'last_updated_page'=>$this->ScrapePage()
      ], 'id='.$this->ScrapeLocationID());


      return $this;
    }


    private function ScrapeZIP() {
      return $this->ScrapeLocation['zip_code'];
    }
    private function ScrapePage() {
      return $this->ScrapeLocation['last_updated_page'] + 1;
    }
    private function ScrapeLocationID() {
      return $this->ScrapeLocation['id'] + 1;
    }
    private function ScrapeStoreID() {
      return $this->ScrapeLocation['id'] + 1;
    }

    private function BatchSave($BatchData) {
        foreach($BatchData as $vehicle) {
            $expected_fields = [
                'Vin',
                'Year',
                'Make',
                'Model',
                'Miles',
                'Price',
                'ExteriorColor',
                'InteriorColor',
                'DriveTrain',
                'Transmission',
                'Highlights',
                'MpgCity',
                'MpgHighway',
                'Cylinders',
                'EngineSize',
                'NewTireCount',
                'NumberOfReviews',
                'AverageRating',
                'StoreId',
                'StockNumber',
            ];

            $is_vehicle_data_complete = true;
            $sanitized_vehicle_data = [];
            foreach($expected_fields as $field) {
                if ( !isset($vehicle[$field]) )
                    $is_vehicle_data_complete = false;
                else
                    $sanitized_vehicle_data[$field] = $vehicle[$field];
            }


            if ( $is_vehicle_data_complete ) {
                // Generate the last_seen timestamp
                $sanitized_vehicle_data['last_seen'] = time();
                // Insert into the temporary scrape db it will be checked for duplicates by another cron
                $this->db->Create('dealerdata_carmax_vehicles_scrape');
                // Check to see if this vehicle is already stored
                $existing_vehicle_record = $this->ByVin($sanitized_vehicle_data['Vin']);
                // If vehicle does not exist, generate its first_seen timestamp then create it
                if ( !$existing_vehicle_record ) {
                    $this->NewVehicle($sanitized_vehicle_data);
                } else {
                    // Make sure this vehicle has not been moved to a new location before updating it
                    if ( $existing_vehicle_record['StoreId'] != $sanitized_vehicle_data['StoreId'] ) // Vehicle has been moved
                        $this->NewMovedVehicle(array_merge($sanitized_vehicle_data, ['OldStoreId'=>$existing_vehicle_record['StoreId']]));
                    // Update vehicle row with current data
                    $this->UpdateVehicle($sanitized_vehicle_data);
                } // End if ( !$vehicle_already_exists ) else
            } // End if ( $is_vehicle_data_complete )
        } // End foreach($Inventory as $vehicle)



        $ScrapeLog = [
          'StoreId'=>$this->ScrapeStoreID(),
          'Page'=>$this->ScrapePage(),
          'PerPage'=>$this->VehiclesPerPage,
          'ExecutionTime'=>elapsed_execution_time()
        ];

        $this->Diagnostic('log.cron.carmax.scrape')->Set(json_encode($ScrapeLog));
        $this->NextLocation();

      return $this;
    }
    /******************************************************************************************************************
     * Scrape() - Fetches inventory data from the supplied URL
     * @param string $URL - CarMax API URL containing inventory data
     * @param mixed $ReturnRaw - Set to 0/false if you want an array returned, 1/true if you want raw data text
     * @return array
     *****************************************************************************************************************/
    private function Scrape($URL, $ReturnRaw=0) {
        $data = file_get_contents($URL);
        if ( $ReturnRaw )
            return $data;

        $json = json_decode($data, 1);

        if ( sizeof($json['Results']) < 1 )
            return [];

        return $json['Results'];
    }

    private function URL($ZipCode, $Page, $Distance=10) {
        // Static settings
        $settings['sort_key']           =      0;
        $settings['api_key']            =      'adfb3ba2-b212-411e-89e1-35adab91b600';
        $settings['start_index']        =      ($Page-1) * $this->VehiclesPerPage;

        // URL generator
        $url = 'https://api.carmax.com/v1/api/vehicles?'.
            'Zip='.         $ZipCode                                             .'&'.
            'Distance='.    $Distance                                            .'&'.
            'Page='.        $Page                                                .'&'.
            'PerPage='.     $this->VehiclesPerPage                               .'&'.
            'StartIndex='.  $settings['start_index']                             .'&'.
            'SortKey='.     $settings['sort_key']                                .'&'.
            'Refinements=&ExposedDimensions=249+250+1001+1000+265+999+772&'.
            'Sorts=0+14+6+9&ExposedCategories=249+250+1001+1000+265+999+772&platform=carmax.com&'.
            'apikey='.      $settings['api_key'];

        return $url;
    }

    function __destruct() {
    } // End __destruct() method
}
?>
