<?php 

require_once($_stdbtable);

class STDbSqlCases
{
    /**
     * current database table
     * @var STDbTable table
     */
    var	$table;
    
    public function __construct(&$oTable)
    {
        Tag::paramCheck($oTable, 1, "STDbTable");
        $this->table= &$oTable;
    }
    protected function make_sql_values($post_vars)
    {
        if(!$post_vars)
            return array();
        $fields= $this->table->db->describeTable($this->table->Name);//hole Felder aus Datenbank
        
        $aRv= array();
        foreach($fields as $field)
        {
            $name= $field["name"];
            if(array_key_exists($name, $post_vars))
                $aRv[$name]= $post_vars[$name];
        }
        return $aRv;
    }
    protected function read_inFields($type)
    {
        $fields= $this->table->db->describeTable($this->table->Name);
        $count= 0;
        $aRv= array();
        foreach($fields as $field)
        {
            $aRv[$count]= $field[$type];
            $aRv[$field["name"]]= $field[$type];
            $count++;
        }
        return $aRv;
    }
    /**
     * method add quotes to value if need,
     * or declare as null
     *
     * @param string $type type of database column
     * @param mixed $value value insert update to database;
     * @return mixed value with qutes
     */
    protected function add_quotes(string $type, $value)
    {
        if(	$type=="int" ||
            $type=="real"   )
        {
            if( !isset($value) ||
                $value === null ||
                $value === ""      )
            {
                $value= "null";
            }
        }elseif(isset($value))
        {
            $keyword= $this->table->db->keyword($value);
            if($keyword === false)
                $value= "'".$value."'";
        }else // value is not set
            $value= "null";
        return $value;
    }
}

?>