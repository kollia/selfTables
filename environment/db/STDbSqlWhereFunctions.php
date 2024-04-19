<?php

require_once $_stdbsqlfunctiontemplate;

class STDbSqlWhereFunctions implements STDbSqlFunctionTemplate
{
    /**
     * whether was checked for correct
     * table names
     * @var boolean
     */
    protected $bTableNamesChecked= false;
	/**
	 * all content of where clausels in an subarray with key named 'array'
	 * and also the splitet content in an subarray with key named 'aValues'.<br>
	 * In the 'array' array also can be rekursive new where clausels (STDbWhere).
	 * @private
	 */
    var $array= array();

    public function IN(string $column, string|array $content) : string
    {
        //$field= $this->table->findColumnOrAlias($column);
        //$sRv= $field['column']." IN(";
        $sRv= "`$column` IN(";
        if(is_array($content))
        {
            foreach($content as $value)
                $sRv.= "$value,";
            $sRv= substr($sRv, 0, strlen($sRv)-1);
        }else
            $sRv.= $content;
        $sRv.= ")";
        $this->bTableNamesChecked= false;
        $this->array[]= $sRv;
        return $sRv;
    }
    public function and_IN(string $column, string|array $content)
    {
        $this->array[]= " and ";
        $this->IN($column, $content);
    }
    public function or_IN(string $column, string|array $content)
    {
        $this->array[]= " or ";
        $this->IN($column, $content);
    }
}