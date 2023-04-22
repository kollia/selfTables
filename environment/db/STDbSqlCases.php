<?php 

require_once($_stdbtable);

class STDbSqlCases
{
    /**
     * current database table
     * @var STDbTable table
     */
    var	$table;
    /**
     * exist sql statement for all rows
     * @var string array
     */
    protected $statements= array();
    /**
     * if one statement produce an error,
     * value stored in this array
     * @var array
     */
    protected $nErrorRowNr= null;
    
    public function __construct(&$oTable)
    {
        Tag::paramCheck($oTable, 1, "STDbTable");
        $this->table= &$oTable;
    }public function setStatement(string $statement, int $nr= 0)
    {
        if(STCheck::isDebug("db.statement"))
        {
            echo "<br /><br />";
            echo "<hr />";
            $msg[]= "set ".($nr+1).". statement for <b>box display</b> inside table ".$this->table;
            $msg[]= "\"".$statement."\"";
            STCheck::echoDebug("db.statement", $msg);
            if(STCheck::isDebug("db.statement.from"))
            {showBackTrace(1);echo "<br />";}
            echo "<hr />";
            //STCheck::info(1, "STDbTable::getStatement()", "called STDbTable::<b>getStatement()</b> method from:", 1);
        }
        $this->statements[$nr]= $statement;
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
            $type=="real" ||
            $type=="datetime" ||
            $type=="time"           )
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