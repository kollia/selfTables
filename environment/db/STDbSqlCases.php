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
     * database
     */
    var $db;
    /**
     * current row which can be filled
     * with new content
     * @var integer
     */
    var $nAktRow= 0;
    /**
     * updating content of columns
     * for more than one row
     * @var array columns
     */
    var $columns= array();
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
    /**
     * all types of every field
     * @var array
     */
    protected $aFieldTypes= null;
    
    // do not take by reference
    // because into table comming
    // where statements
    public function __construct(object $oTable)
    {
        Tag::paramCheck($oTable, 1, "STDbTable");
        $this->table= clone $oTable;
        $this->db= &$oTable->db;
    }
    var $fcount= 0;
    protected function fillColumnContent(string $column, $value)
    {
        STCheck::param($value, 1, "string", "empty(string)", "null", "int", "float");
        
        $field= $this->table->findColumnOrAlias($column);
        STCheck::alert(($field["type"]=="no found"), "STDbUpdater::update()",
            "column '$column' do not exist inside table ".$this->table->getName(), 2);
        if( isset($value) && // null value is allowed, make no preg_match
            preg_match("/^[ ]*['\"](.*)['\"][ ]*$/", $value, $preg) )
        {
            $value= $preg[1];
        }
        $this->columns[$this->nAktRow][$field['column']]= $value;
    }
    function fillNextRow()
    {
        ++$this->nAktRow;
    }
    public function setStatement(string $statement, int $nr= 0)
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
     * @param string $column column of table (warning: isn't check for right name)
     * @param mixed $value value insert update to database;
     * @return mixed value with quotes
     */
    protected function add_quotes(string $column, $value)
    {
        if(!isset($this->aFieldTypes))
            $this->aFieldTypes= $this->read_inFields("type");
        $type= $this->aFieldTypes[$column];
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
        {// type should be a string
            $keyword= $this->table->db->keyword($value);
            if($keyword === false)
            {
                if(typeof($this->table, "STDbTable"))
                {
                    if( !isset($this->table->aArgumentList['binary']) ||
                        !in_array($column, $this->table->aArgumentList['binary'])   )
                    {
                        $value= $this->db->real_escape_string($value);
                    }
                    $value= $this->db->getDelimitedString($value, "string");
                    if(isset($this->table->aArgumentList['encrypt'][$column]))
                    {
                        $value= "aes_encrypt($value,";
                        $value.= "'{$this->table->aArgumentList['encrypt'][$column]['key']}'";
                        if(isset($this->table->aArgumentList['encrypt'][$column]['iv']))
                        {
                            $content= $this->table->aArgumentList['encrypt'][$column]['iv'];
                            $content=  $this->db->real_escape_string($content);
                            $value.= ",'$content'";
                        }
                        if(isset($this->table->aArgumentList['encrypt'][$column]['mode']))
                        {
                            $content= $this->table->aArgumentList['encrypt'][$column]['mode'];
                            $content=  $this->db->real_escape_string($content);
                            $value.= ",'$content'";
                        }
                        $value.= ")";
                    }
                }else
                    $value= "'".$value."'";
            }
        }else // value is not set
            $value= "null";
        return $value;
    }
}

?>