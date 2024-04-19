<?php 

require_once $_stdbsqlcases;

class STDbSqlWhereCases extends STDbSqlCases
{
    /**
     * where object from table
     * @var STDbWhere
     */
    private $tableWhere= null;
    /**
     * array of where statements
     * for every update statement
     * @var array
     */
    protected $wheres= array();
    
    public function where($where, string $operator= "")
    {
        if( $operator == "" ||
            !isset($this->wheres[$this->nAktRow])   )
        {
            if(is_string($where))
            {
                $where= new STDbWhere($where);
                $where->table($this->table);
            }
            $this->wheres[$this->nAktRow]= array(   "clause" => $where,
                                                    "operator" => $operator );
        }else
            $this->wheres[$this->nAktRow]['clause']->where($where, $operator);
    }
    public function andWhere($stwhere)
    { $this->where($stwhere, "and"); }
    public function orWhere($stwhere)
    { $this->where($stwhere, "or"); }
    protected function getWhereObject(int $nr= 0) : STDbWhere
    {
        if(isset($this->wheres[$nr]['clause']))
            return $this->wheres[$nr]['clause'];
        if(!isset($this->tableWhere))
        {
            $this->table->modifyQueryLimitation();
            $this->tableWhere= $this->table->getWhere();
        }
        return $this->tableWhere;
    }
    protected function getWhereStatement(int $nr= 0) : string
    {
        // create ->tableWhere from current table if not exist
        $this->getWhereObject($nr);

        if(!isset($this->wheres[$nr]))
        {
            STCheck::alert(!isset($this->tableWhere), "STCheck::getUpdateStatement()", "no where usage for update exist");
            $whereStatement= $this->table->getWhereStatement("where");
            return $whereStatement;
        }
        if( !isset($this->tableWhere) ||
            $this->wheres[$nr]['operator'] == ""    )
        {   
            $where= $this->wheres[$nr]['clause']->getStatement($this->table, "where");
            return "where ".$where['str'];
        }
        $oWhere= clone $this->tableWhere;// <- need always fresh where object
        $oWhere->where($this->wheres[$nr]['clause'], $this->wheres[$nr]['operator']);
        $where= $oWhere->getStatement($this->table, "where");
        return "where ".$where['str'];
    }
}
?>