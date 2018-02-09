<?php
/******************************************************************************************************************
 ******************************************************************************************************************
 *** DealerData PriceCheck Class
 ******************************************************************************************************************
 *****************************************************************************************************************/
global $db, $pdb;
class DealerData___PriceCheck extends DealerData {
    protected $RequiredSequentialFields = 3;
    protected $Criteria = [
      'Year'      =>  '',
      'Make'      =>  '',
      'Model'     =>  '',
      'Engine'    =>  '',
      'Mileage'   =>  '',
      'Turnover'  =>  '',
      'Color'     =>  '',
      'Location'  =>  '',
      'Date'      =>  ''
    ];

    function __construct() {
        if ( isset($_SESSION['Debug.Init.Class']) )
            echo "DealerData___PriceCheck init\n";
    } // End __construct() method

    public function PushCriteria($PostData) {
      $FieldPrefix = 'PriceCheck/InputGroup/';
      foreach($this->Criteria as $FieldName=>$FieldValue) {
        if (   isset( $PostData[ $FieldPrefix . $FieldName ] )   )
          $this->Criteria[$FieldName] = $PostData[ $FieldPrefix . $FieldName ];
        if (   isset( $PostData[ $FieldName ] )   )
          $this->Criteria[$FieldName] = $PostData[ $FieldName ];
      }

      return $this;
    }

    public function Form() {
        return (new DealerData___PriceCheck___Form())->Config($this->Config)->PushCriteria($this->Criteria);
    }


    function __destruct() {
    } // End __destruct() method
}
?>
