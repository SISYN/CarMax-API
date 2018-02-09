<?php
/******************************************************************************************************************
 ******************************************************************************************************************
 *** DealerData API CarMax Class
 ******************************************************************************************************************
 *****************************************************************************************************************/
global $db, $pdb;
class DealerData___API___DataSet extends DealerData___API {
    protected $CacheData;
    function __construct() {
        if ( isset($_SESSION['Debug.Init.Class']) )
            echo "DealerData___API___DataSet init\n";
    } // End __construct() method



    public function CarMax() {
        return (new DealerData___API___DataSet___CarMax())->Config($this->Config)->Filter(json_encode($this->Filters));
    }

    /******************************************************************************************************************
     ******************************************************************************************************************
     * BEGIN Inherited `Cache` methods
     ******************************************************************************************************************
     *****************************************************************************************************************/
    /******************************************************************************************************************
     * Cache() - This method is inhereited and changed depending on which categorical report is being requested
     * @param string $ReportCategory - Summary, Dealerships, etc
     * @return array
     *****************************************************************************************************************/
    protected function Cache($ReportCategory) {
        // Sanitize report category to all lower case and no trailing 's'
        $ReportCategory = strtolower(preg_replace('#s$#i', '', $ReportCategory));
        // Check for cache
        $cache_threshold_timestamp = (new Date)->Translate($this->Config('Threshold.Cache'));
        $cache = $this->db->Fetch(
            'dealerdata_api_cache', '*',
            'created >= '.$cache_threshold_timestamp.' AND
             report_category=\''.$ReportCategory.'\' AND
             filter_hash=\''.adom_hash($this->Filters()).'\'
             ORDER BY id DESC
             LIMIT 1'
         );

        if ( !!$cache && $cache['filter_json'] == json_encode($this->Filters()) )
            return $cache;


        return false;
    }
    /******************************************************************************************************************
     * UseCache() - This method is inhereited and changed depending on which categorical report is being requested
     * @param string $CacheData - The data you wish to use
     * @return array
     *****************************************************************************************************************/
    protected function UseCache($CacheData) {
        $CacheData['data_json'] = str_replace('CURRENT TIME', time(), $CacheData['data_json']);
        $this->CacheData = json_decode($CacheData['data_json'], 1);
        return $this;
    }
    /******************************************************************************************************************
     * CreateCache() - This method is inhereited and changed depending on which categorical report is being requested
     * @param string $ReportCategory - Summary, Dealerships, etc
     * @return array
     *****************************************************************************************************************/
    protected function CreateCache($ReportCategory) {
        $data_array = [
            'report_category'=>$ReportCategory,
            'filter_hash'=>adom_hash($this->Filters()),
            'filter_json'=>json_encode($this->Filters()),
            'data_json'=>db_escape(json_encode($this->CacheData)),
            'created'=>time()
        ];
        $this->db->Create('dealerdata_api_cache', $data_array);

        return $this;
    }
    /******************************************************************************************************************
     ******************************************************************************************************************
     * END Inherited `Cache` methods
     ******************************************************************************************************************
     *****************************************************************************************************************/


    function __destruct() {
    } // End __destruct() method
}
?>
