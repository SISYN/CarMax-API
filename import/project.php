<?php
/******************************************************************************************************************
 * Adom / lib / src / base / project.php
 * Defines project-specific functions
 *****************************************************************************************************************/

/******************************************************************************************************************
 * CarMax_ActiveFilters() - Returns the current set of filters
 * @param bool $UseReferringURL - Set to true if you wish to get filters from the referring URL instead of current
 * @param bool $ReturnEncoded - If true, will return resulting ajax in encoded version
 * @return mixed - Returns string for $to_num=false and int for $to_num=true
 *****************************************************************************************************************/
function CarMax_ActiveFilters($UseReferringURL=false, $ReturnEncoded=false) {
    // Start by trying to use the actual/current URL vars
    $ActiveFilters = (isset($_GET[CarMax_ActiveFilters_VarName()])) ? $_GET[CarMax_ActiveFilters_VarName()] : '';

    // If that fails (results in empty str) then try checking $_SESSION['CarMax_Reports']
    if ( $ActiveFilters == '' ) {
        // Check if the current page is a data view, if not, see if they have any previous reports
        if ( !stristr($_SERVER['REQUEST_URI'], '/data/') && @sizeof($_SESSION['CarMax_Reports']) )
            $ActiveFilters = end($_SESSION['CarMax_Reports']);
    }

    // If nothing was found still, try from the referring URL if they are allowing it
    if ( $ActiveFilters == '' && $UseReferringURL ) {
        $Referring_Vars = preg_replace('#^.*\?#', '', $_SERVER['HTTP_REFERER']);
        if ( strlen($Referring_Vars) > 0 ) {
            $URL_Vars = explode('&', $Referring_Vars);
            $Parsed_Vars = [];
            foreach($URL_Vars as $var_string) {
                $var_parts = explode('=', $var_string);
                $Parsed_Vars[$var_parts[0]] = @$var_parts[1];
            }

            $ActiveFilters = (isset($Parsed_Vars[CarMax_ActiveFilters_VarName()])) ? $Parsed_Vars[CarMax_ActiveFilters_VarName()] : '';
        }
    }




    $ActiveFilters = ( !@decode_string($ActiveFilters) ) ? $ActiveFilters : decode_string($ActiveFilters);
    /*
    if ( $ActiveFilters == '' ) {
        // See if they have a previously used report
        if ( isset($_SESSION['CarMax_Reports']) && sizeof($_SESSION['CarMax_Reports']) > 0 )
            $ActiveFilters = $_SESSION['CarMax_Reports'][sizeof($_SESSION['CarMax_Reports'])-1];
    }
     */

    if ( $ReturnEncoded )
        return encode_string($ActiveFilters);

    return $ActiveFilters;
}

/******************************************************************************************************************
 * CarMax_ActiveFilters_VarName() - Returns the current filter hash URL variable name
 * @return string - Returns string name of the URL variable used to get filter hashes
 *****************************************************************************************************************/
function CarMax_ActiveFilters_VarName() {
    return 'i';
}

/******************************************************************************************************************
 * CarMax_Instance_Default() - Determines if using demo or full version
 * @return object - Returns the proper version of the CarMax class instance
 *****************************************************************************************************************/
function CarMax_Instance_Default() {
    global $db;
    global $ActiveMember;
    global $CarMax;
    $SubscriberID = isset($ActiveMember['id']) ? $ActiveMember['id'] : -1;
    // Check for existing version first
    if ( is_object($CarMax) )
        return $CarMax;
    // check if in demo mode
    $CarMax = isset($_GET['demo']) ? (new CarMax())->Region('KY', '-Louisville') : new CarMax(CarMax_ActiveFilters());
    // Keep record of their reports for this session
    $_SESSION['CarMax_Reports'][] = CarMax_ActiveFilters();
    // Keep track of data views for this session
    if ( preg_match('#^.*/data/(sales|vehicles|combined).*$#i',$_SERVER['REQUEST_URI']) )
        $_SESSION['CarMax_Views'][] = preg_replace('#^.*/data/(sales|vehicle|combined).*$#i', '$1', $_SERVER['REQUEST_URI']);
    // Allow a forced refresh of the cache
    if ( isset($_GET['fresh']) )
        $CarMax->Config('Threshold.Cache', '1s');

    // Check if this user has any special config capabilities in the db
    $special_configs = $db->Fetch('carmax_subscribers_vars', 'var_name,var_value', 'subscriber='.$SubscriberID.' AND active=1 AND var_name REGEXP \'^config\.\'', 1);
    if ( !!$special_configs ) {
        foreach($special_configs as $special_config) {
            // Format it from namespace.parent.child to Namespace.Parent.Child
            $callsign_parts = explode('.', str_replace('config.', '', $special_config['var_name']));
            foreach($callsign_parts as $k=>$callsign_part)
                $callsign_parts[$k] = ucwords(strtolower($callsign_part));
            $formatted_callsign = join('.', $callsign_parts);

            // Now set the config option
            $CarMax->Config($formatted_callsign, $special_config['var_value']);


            /*
             *
             *
             * Need to devise plan to handle array data like mileage groups?
             *
             *
             *
             */
        }
    }

    return $CarMax;
}




?>