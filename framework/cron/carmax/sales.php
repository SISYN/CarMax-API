<?php
/******************************************************************************************************************
 ******************************************************************************************************************
 *** DealerData Vehicle Sales Processing Cron Job Class
 ******************************************************************************************************************
 *****************************************************************************************************************/
global $db, $pdb;
class DealerData___Cron___CarMax___Sales extends DealerData___Cron___CarMax {

    private $Threshold;

    function __construct() {
      if ( isset($_SESSION['Debug.Init.Class']) )
          echo "DealerData___Cron___CarMax___Sales init\n";

    } // End __construct() method


    protected function InitAfterConfig() {
      if ( $this->Config )
        $this->Threshold = (new Date)->Translate($this->Config()->Get('Threshold.Sales'));

      return $this;
    }

    public function Refresh() {
      $this->InsertAbsentVehicles();
      $this->RemoveReappearedVehicles();
      return $this;
    }



    private function RemoveReappearedVehicles() {


      // Find all vehicles in the DB that are inside the sold vehicles table but have been seen after the threshold
      $Query = '
        SELECT
          carmax_vehicles.Vin , carmax_vehicles.StoreId , carmax_vehicles.last_seen
        FROM
          carmax_vehicles
        LEFT OUTER JOIN
          carmax_sold_vehicles
        ON (carmax_vehicles.Vin = carmax_sold_vehicles.Vin)
        WHERE
          carmax_sold_vehicles.Vin IS NOT NULL
            AND
          carmax_vehicles.last_seen > ' . $this->Threshold . '
      ';


      echo $Query;

      return $this;
    }

    private function InsertAbsentVehicles() {


      // Find all vehicles in the DB that are not inside the sold vehicles table
      $Query = '
        SELECT
          carmax_vehicles.Vin , carmax_vehicles.StoreId , carmax_vehicles.last_seen
        FROM
          carmax_vehicles
        LEFT OUTER JOIN
          carmax_sold_vehicles
        ON (carmax_vehicles.Vin = carmax_sold_vehicles.Vin)
        WHERE
          carmax_sold_vehicles.Vin IS NULL
            AND
          carmax_vehicles.last_seen <= ' . $this->Threshold . '
      ';


      echo $Query;



      return $this;
    }


    function __destruct() {
    } // End __destruct() method
}
?>
