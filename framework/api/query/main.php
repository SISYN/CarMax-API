<?php
class DealerData___API___Query extends DealerData___API {
    protected $BaseQuery, $CompiledQuery;
    private $QuerySelects, $QueryJoins, $QueryWheres, $QueryGroupBys, $QueryOrderBys, $QueryLimit;
    /******************************************************************************************************************
     * __construct()
     *****************************************************************************************************************/
    function __construct() {
        global $db, $pdb;
        $this->db = $db;
        $this->pdb = $pdb;

        $this->QuerySelects = [];
        $this->QueryJoins = [];
        $this->QueryWheres = [];
        $this->QueryGroupBys = [];
        $this->QueryOrderBys = [];
        $this->QueryLimit = '';

    } // End __construct() method


    public function Base($BaseQuery) {
        $this->BaseQuery = $BaseQuery;

        return $this;
    }

    public function Select() {
        $clauses = func_get_args();
        foreach($clauses as $clause)
            $this->QuerySelects[] = $clause;

        return $this;
    }
    public function Join() {
        $clauses = func_get_args();
        foreach($clauses as $clause)
            $this->QueryJoins[] = $clause;

        return $this;
    }
    public function Where() {
        $clauses = func_get_args();
        foreach($clauses as $clause)
            $this->QueryWheres[] = $clause;

        return $this;
    }
    public function GroupBy() {
        $clauses = func_get_args();
        foreach($clauses as $clause)
            $this->QueryGroupBys[] = $clause;

        return $this;
    }
    public function OrderBy() {
        $clauses = func_get_args();
        foreach($clauses as $clause)
            $this->QueryOrderBys[] = $clause;

        return $this;
    }
    public function Limit($Limit) {
        $this->QueryLimit = $Limit;

        return $this;
    }

    public function Compile() {
        $this->CompiledQuery = $this->BaseQuery;

        $additional_selects = $this->QuerySelects;
        $joins = $this->QueryJoins;
        $where_clauses = $this->QueryWheres;
        $group_clauses = $this->QueryGroupBys;
        $order_clauses = $this->QueryOrderBys;
        $limit_clause  = $this->QueryLimit;

        // If searching for sold cars, use sold_vehicles.disappear time
        // If searching for moved cars, use moved_vehicles.created
        $date_clause_field = (stristr($this->BaseQuery, 'sold_vehicles')) ? 'vehicles.last_seen' : 'moved_vehicles.created';

        // Compile Store IDs
        $this->Filters['Stores'] = [];
        foreach($this->Filters['Regions'] as $RegionInput)
            $this->Filters['Stores'] = (new DealerData___API___Filter___Region($RegionInput))->Compile($this->Filters['Stores'])->Fetch();

        $this->Filters['StoreIDs'] = [];
        foreach($this->Filters['Stores'] as $store)
            $this->Filters['StoreIDs'][] = $store['StoreId'];

        // Determine WHERE clauses
        if ( sizeof($this->Filters['Regions']) )
            $where_clauses[] = '
              vehicles.StoreId IN('.join(',', $this->Filters['StoreIDs']).')
            ';

        if ( $this->Filters['Turnover']['Max'] )
            $where_clauses[] = '
              vehicles.last_seen - vehicles.first_seen < '.$this->Filters['Turnover']['Max'].'
            ';

        if ( sizeof($this->Filters['Vehicles']) ) {
            // See which ones are included and which are excluded
            $included_vehicles = []; $excluded_vehicles = [];
            foreach($this->Filters['Vehicles'] as $vehicle) {
                if ( preg_match('#^\-#', $vehicle) )
                    $excluded_vehicles[] = preg_replace('#^\-#', '', $vehicle);
                else
                    $included_vehicles[] = $vehicle;
            }
            if ( sizeof($included_vehicles) )
                $where_clauses[] = '
                  (  CONCAT(vehicles.Make, \':\', vehicles.Model) IN ('.join(',', array_quotes($included_vehicles)).')  )
                ';
            if ( sizeof($excluded_vehicles) )
                $where_clauses[] = '
                  (  CONCAT(vehicles.Make, \':\', vehicles.Model) NOT IN ('.join(',', array_quotes($excluded_vehicles)).')  )
                ';
        }

        if ( $this->Filters['Date']['Min'] )
            $where_clauses[] = '
              '.$date_clause_field.' >= '.$this->Filters['Date']['Min'].'
            ';

        if ( $this->Filters['Date']['Max'] )
            $where_clauses[] = '
              '.$date_clause_field.' <= '.$this->Filters['Date']['Max'].'
            ';

        if ( $this->Filters['Year']['Min'] )
            $where_clauses[] = '
              vehicles.Year >= '.$this->Filters['Year']['Min'].'
            ';

        if ( $this->Filters['Year']['Max'] )
            $where_clauses[] = '
              vehicles.Year <= '.$this->Filters['Year']['Max'].'
            ';

        if ( $this->Filters['Mileage']['Min'] )
            $where_clauses[] = '
              CAST(vehicles.Miles AS SIGNED) >= '.floor($this->Filters['Mileage']['Min']/1000).'
            ';

        if ( $this->Filters['Mileage']['Max'] )
            $where_clauses[] = '
              CAST(vehicles.Miles AS SIGNED) <= '.floor($this->Filters['Mileage']['Max']/1000).'
            ';

        if ( $this->Filters['Price']['Min'] )
            $where_clauses[] = '
              vehicles.Price >= '.$this->Filters['Price']['Min'].'
            ';

        if ( $this->Filters['Price']['Max'] )
            $where_clauses[] = '
              vehicles.Price <= '.$this->Filters['Price']['Max'].'
            ';

        // Replace additional selects
        if ( sizeof($additional_selects) )
            $this->CompiledQuery = str_replace('SELECT', 'SELECT '.join(" , \n", $additional_selects).' , ', $this->CompiledQuery);

        // Now assemble the query parts
        if ( sizeof($joins) )
            $this->CompiledQuery .= join("\n", $joins);

        if ( sizeof($where_clauses) )
            $this->CompiledQuery .= '
              WHERE
            '.join('
                AND
            ', $where_clauses);


        if ( sizeof($group_clauses) )
            $this->CompiledQuery .= '
              GROUP BY '.join(' , ', $group_clauses).'
            ';
        if ( sizeof($order_clauses) )
            $this->CompiledQuery .= '
              ORDER BY '.join(' , ', $order_clauses).'
            ';

        if ( strlen($limit_clause) )
            $this->CompiledQuery .= '
              LIMIT '.$limit_clause.'
            ';


        return $this;
    }

    public function Output() {
        $this->Compile();
        return $this->CompiledQuery;
    }


    function __destruct() {
    } // End __destruct() method
}
?>