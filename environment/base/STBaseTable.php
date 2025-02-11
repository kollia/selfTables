<?php

require_once($_stsession);

$__static_global_STBaseTable_ID= array();

class STBaseTable
{
    protected $ID= 0;
	var $Name;
	/**
	 * define whether the table
	 * is an correct defined table object
	 * or only an alias table
	 * @var boolean
	 */
	private $bCorrect= false;
	/**
	 * array with start and limit key
	 * to provide limitation of select statement.<br />
	 * If variable is undefined, no limitation will be set
	 * inside the statement
	 * @var array
	 */
	protected $limitRows= null;
	/**
	 * array of statement parts,
	 * the reason is to create not every time the same statement
	 * @var array
	 */
	protected $aStatement= array();
	var $title= "";
	var	$sPKColumn;
	var $columns;
	/**
	 * all defined aliases
	 * for columns in table
	 *  
	 * @access private;
	 * @var string array
	 */
	var $aAliases= array();
	var $titles= null;
	var	$sFirstAction= STLIST;
	var	$identification= array();
	var	$bDisplayIdentifs= true;
	/**
	 * whether in a selected- or an identifier-columns (from foreign keys)
	 * selection is from parent table or first table from Database with all columns
	 * ( key [select, get or identifColumn] is true or non exist )
	 * or has made an new choice of columns 
	 * ( key [select, get or identifColumn] of original choice 
	 *   [abOrigChoice] has the value 'false' )
	 * @var array
	 */
	var	$abOrigChoice= array();
	var	$show= array();
	/**
	 * Specific GUI display for enums.<br />
	 * Whether should display as pop-up menu or radio buttons
	 * @var array
	 */
	protected $enumField= array();
	/**
	 * pre-defined value-property like an flag in database
	 * @var array
	 */
	protected $aPreDefinde= array();
	/**
	 * argument list for different properties.
	 * Currently:	binary columns	- 	
	 * 				encrypt columns	-	if column should encrypted
	 */
	var $aArgumentList= array();
	/**
	 * columns for cluster where row should only show
	 * when user has cluster
	 * @var array
	 */
	protected $aClusterColumns= array();
	var	$bDisplaySelects= true;
	var	$bColumnSelects= true; // have the Table columns in select-statement
    protected $FK= array();// bug: if in one table be two foreign keys to the same table
					 // 	 there be seen only one
	var	$aFks= array();	// so make an new one
	var	$aBackJoin= array();// for join in table where the other have an foreigen key to the own
							// and it should be in the select-statement
    /**
     * array of tables over which should also joined
     * inside the statment to reach correct usage
     * @var array
     */
	protected $aJoinOverTables= array();
	/**
	 * array of tables over which not should joined
	 * inside the statment to reach correct usage.<br />
	 * (is always harder than the tables over which should joined)
	 * @var array
	 */
	protected $aNoJoinOverTables= array();
    var $error;
	var $errorText;
	var	$showTypes= array();
	var $oWhere= null;
	var $asOrder= array();
	var	$identifier;
	var	$bDistinct= false;
	var $bOrder= NULL;
	var $aLinkAddresses= array();
	var	$bInsert= true;
	var $bUpdate= true;
	var	$bDelete= true;
	/**
	 * event listener for html input tags
	 * @var array [column][event]= javascript:function;
	 */
	public $aEvents= array();
	var $aRefresh= array();
	/**
	 * 
	 * @var boolean whether should sort Table, by clicking of one of the head-names 
	 */
    var $doTableSorting= true;
	var	$bShowName= true;
	// alex 08/06/2005:	nun koennen Werte auch Statisch in der
	//					STDbTable gesetzt werden
	var $aSetAlso= array();
	var	$aCallbacks= array();
	// alex 09/06/2005:	limitieren von Rowanzahl aus der Datenbank
	var	$nFirstRowSelect= 0;
	var $nMaxRowSelect= null;// null -> es werden alle Rows aufgelistet
	var $nAktSelectedRow= 0;
	var $bAlwaysIndex= true;
	var	$bSetLinkByNull= false;
	var	$onlyRadioButtons= array(); // wenn nur zwei Enumns vorhanden sind, trotzdem radio Buttons verwenden
	var $listArrangement= STHORIZONTAL;//bestimmt das Layout der STListBox
	var $bListCaption= true;// Beschriftung (�berschrift) der Tabelle
	var	$oSearchBox= null; // Suchen-Box bei Auflistung der Tabelle anzeigen
	
	/**
	 * whether should limitate table selection
	 * by a limitation defined inside the query string
	 * @var boolean
	 */
	protected $bLimitOwn= true;
	/**
	 * whether should limitate table selection
	 * also by foreign key limitations from query string
	 * @var boolean
	 */
	protected	$bModifyFk= true;
	// soll diese Angewand werden und nur einen Eintrag zeigen
	/**
	 * whether was query limitation modified
	 * @var boolean
	 */
	protected $bModifiedByQuery= false;
    /**
     * wenn in einen Link von dieser Tabelle aus gesprungen wird,
     * wird eine Einschränkung von dieser Tabelle gesetzt.
     * das dient dazu, dass nur die Einträge vom FK auf diesen link zurück
     * verfolgt wird.
     * springt der User nun über den BackButton auf diese Tabelle zurück
     * wird die Einschränkung bei "true" gelöscht
     * bei "false" nicht
     * und bei "older" erst wenn auch aus dieser Tabelle mit dem BackButton
     * zurückgesprungen wird
     * @var string
     */
	protected $sDeleteLimitation= "true";
	var $asAccessIds= array();
	var $sAcessClusterColumn= array(); // in den angegebenen Columns wird ein Cluster f�r den Zugriff gespeichert
	var	$aUnlink= array(); 	// wenn die Upgelodete Datei nicht gel�scht werden soll
							// ist hier der Alias-Name der Spalte eingetragen
	var	$nDisplayColumns= 1; // in wieviel Hauptspalten die Aufgelistete Tabelle angezeigt werden soll
	var	$aAttributes= array(); // alle Attribute die in den diversen Tables zu den ColumnTags/RowTags hinzugef�gt werden sollen
	var	$linkParams= array(); // alle Get-Parameter die bei einem Link eingef�gt, ge�ndert oder gel�scht werden sollen
	var $bHasGroupColumns= false; // is any group-column (count, min, max, ...) be set
	var	$bDynamicAccessIn= null; // an array with access on all actions
								// if variable is null, no dynamic access is searched
	var	$oTinyMCE= array(); // array of objects from TinyMCE for CMS, for an selected Column
	var	$aInputSize= array(); // width and height for the input/textarea - tag
	var	$asForm= array(); // save there the require names for an form-table
	var	$bIsNnTable= false; // is the table declared as among-table
	var	$aPostToGet= array(); // transfer the variables from POST- to GET-params by forwarding
	var	$aCheckDef= array();
	var	$aActiveLink= array();// active Link in navigation table
	/**
	 * count of actual inner table for an update or insert box
	 */
	var $nInnerTable= 0;
	/**
	 * array of all Inner tables, which have an begin and end column
	 */
	var $aInnerTables= array();
	var $dateIndex;
	/**
	 * null pointer for returned
	 * null refference
	 * @var null
	 */
	var $null= null;

	 /**
	  * Constructor of normal table object
	  * 
	  * @param {table object, string, null} $oTable exist other table, new tablename or null to create symbolic null table
	  */
	function __construct($oTable= null)
	{
	    global $__static_global_STBaseTable_ID;
	    
		STCheck::param($oTable, 0, "string", "STBaseTable", "null");


    	$this->bOrder= NULL;
		$this->asForm= array(	"button"=>	"save",
								"form"=>	"st_checkForm",
								"action"=>	null			);
    	$this->error= false;
    	$this->bCorrect= false;
    	if($oTable !== null)
    	{
    	    if(typeof($oTable, "STBaseTable"))
    	        $this->copy($oTable);       
    	    else
    	        $this->Name= $oTable;
            $this->bCorrect= true;
    	}else
    	    $this->Name= null;
    
		if(isset($__static_global_STBaseTable_ID[$this->Name]))
			$__static_global_STBaseTable_ID[$this->Name]++;
		else
			$__static_global_STBaseTable_ID[$this->Name]= 0;
		$this->ID= $__static_global_STBaseTable_ID[$this->Name];	
    	STCheck::increase("table");
        if( STCheck::isDebug() &&
            (   STCheck::isDebug("table") ||
                STCheck::isDebug("db.table.fk") )   )
        {
            if($oTable === null)
            {
                $msg= "create non correct null table $this.";
                $color= "red";
                
            }else
            {
                $msg= array();
                $msg[]= "create new table $this";
                $color= "darkblue";
                if(typeof($oTable, "STBaseTable"))
	            {
	                $msg[]= "        by copy  $oTable";
	                $color= "blue";
	            }
            }
            if(STCheck::isDebug("table"))
            {
                echo "<br /><br /><br /><br /><br /><hr color='$color' />";
                STCheck::echoDebug("table", $msg);
            }
            
            if(typeof($oTable, "STBaseTable"))
            {
                $check= "table";
                if(STCheck::isDebug("db.table.fk"))
                    $check= "db.table.fk";
                $space= STCheck::echoDebug($check, "create new ".get_class($this)."::<b>".$oTable->Name."</b> with ID:".$this->ID." from ".get_class($oTable)."::<b>".$oTable->Name."</b> with ID:".$oTable->ID);
                STCheck::echoDebug($check, "with <b>FKs:</b>");
                st_print_r($this->aFks,3,$space);
                if(STCheck::isDebug("db.table.fk"))
                {
                    for($c= 0; $c < $space; $c++)
                        echo " ";
                    echo "old <b>BackJoins</b>:<br>";
                    st_print_r($oTable->aBackJoin, 3, $space);
                }
                for($c= 0; $c < $space; $c++)
                    echo " ";
                if(STCheck::isDebug("db.table.fk"))
                    echo "new ";
                echo "<b>BackJoins</b>:<br>";
                st_print_r($this->aBackJoin, 3, $space);
            }
        }
	}
	function __clone()
	{
	    global $__static_global_STBaseTable_ID;
	    
	    $__static_global_STBaseTable_ID[$this->Name]++;
	    $oldID= $this->ID;
	    $this->ID= $__static_global_STBaseTable_ID[$this->Name];
	    	    
	    $this->bInsert= true;
	    $this->bUpdate= true;
	    $this->bDelete= true;

	    /**
	     *
	     * @var boolean whether should sort Table, by clicking of one of the head-names
	     */
	    $this->doTableSorting= true;
	    $this->bShowName= true;
	    if( $this->bModifiedByQuery &&
	        (  !$this->bLimitOwn ||
	           !$this->bModifyFk   )   )
	    {
	        $this->bLimitOwn= !$this->bLimitOwn;
	        $this->bModifyFk= !$this->bModifyFk;
	        $this->bModifiedByQuery= false;
	    }else
	    {
	        $this->bLimitOwn= true;
	        $this->bModifyFk= true;
	    }
	    // alex 08/06/2005:	nun koennen Werte auch Statisch in der
	    //					STDbTable gesetzt werden
	    $this->aSetAlso= array();
	    $this->aCallbacks= array();
	    // alex 09/06/2005:	limitieren von Rowanzahl aus der Datenbank
	    $this->nFirstRowSelect= 0;
	    $this->nMaxRowSelect= null;// null -> es werden alle Rows aufgelistet
	    $this->nAktSelectedRow= 0;
	    $this->bAlwaysIndex= true;
	    $this->listArrangement= STHORIZONTAL;//bestimmt das Layout der STListBox
	    $this->oSearchBox= null; // Suchen-Box bei Auflistung der Tabelle anzeigen
	   
		// kollia 2024-12-09:
		// switch FK, aFks and aBackJoin members cloning to the STDbTable object
		if(isset($this->db))
		{
			//---------------------------------------------------------------------------------
			// foreign keys and backjoins should always same like in first database table
			// so make an direct link from copied table
			$main= $this->db->getTable($this->Name);
			$this->FK= &$main->FK;
			$this->aFks= &$main->aFks;
			$this->aBackJoin= &$main->aBackJoin;
		}
		if(isset($this->oWhere))
			$this->oWhere->resetQueryLimitation("own", true);
	    //---------------------------------------------------------------------------------
	    STCheck::increase("table");
	    if( STCheck::isDebug() &&
	        (   STCheck::isDebug("table") ||
	            STCheck::isDebug("db.table.fk") )   )
	    {
	        if(STCheck::isDebug("table"))
	        {
	            echo "<br /><br /><br /><br /><br />";
	            //showBackTrace();
	            echo "<hr color='lightblue' />";
	            STCheck::echoDebug("table", "clone STBaseTable::content from ID:[$oldID] to $this");
	        }
	        
	        $space= STCheck::echoDebug("table", "with <b>selected</b> Columns:");
	        st_print_r($this->show,3,$space);
	        STCheck::echoDebug("table", "with <b>identif</b> Columns:");
	        st_print_r($this->identification,3,$space);
            $check= "table";
            if(STCheck::isDebug("db.table.fk"))
                $check= "db.table.fk";
            STCheck::echoDebug($check, "with <b>FKs:</b>");
            st_print_r($this->aFks,3,$space);
            for($c= 0; $c < $space; $c++)
                echo " ";
            echo "<b>BackJoins</b>:<br>";
            st_print_r($this->aBackJoin, 3, $space);
	    }
	}
	public function __toString() : string
	{
	    return $this->toString();
	}
	public function getID()
	{
		return $this->ID;
	}
	public function toString(bool $htmlTags= true) : string
	{
	    $str= get_class($this)."(";
	    if($htmlTags) $str.= "<b>";
	    $str.= $this->Name;
	    if($htmlTags) $str.= "</b>";
	    $str.= "[".$this->ID."])";
	    return $str;
	}
	function title($title)
	{
		$this->title= $title;
	}
	function getTitle()
	{
		return $this->title;
	}
	function correctTable()
	{
		if(	$this->Name == "NULL" &&
			$this->bCorrect == false	)
		{
			return false;
		}
		return true;
	}
	function copy($Table)
	{
		STCheck::param($Table, 0, "STBaseTable");
		
		$this->bOrder= NULL;
     	$this->error= $Table->error;
    	$this->errorText= $Table->errorText;
    	$this->Name= $Table->Name;
    	if( $Table->bModifiedByQuery &&
    	    (  !$Table->bLimitOwn ||
    	        !$Table->bModifyFk   )   )
    	{   
    	    $this->bLimitOwn= !$Table->bLimitOwn;
    	    $this->bModifyFk= !$Table->bModifyFk;
    	    $this->bModifiedByQuery= false;
    	}else
    	{
        	$this->bLimitOwn= true;
        	$this->bModifyFk= true;
        	$this->bModifiedByQuery= $Table->bModifiedByQuery;
    	}
     	//---------------------------------------------------------------------------------
     	// foreign keys and backjoins should always same like in first database table
     	// so make an direct link from copied table
    	$this->FK= &$Table->FK;
		$this->aFks= &$Table->aFks;
		$this->aBackJoin= &$Table->aBackJoin;
		//---------------------------------------------------------------------------------
    	$this->identification= $Table->identification;
    	$this->showTypes= $Table->showTypes;
		$this->aActiveLink= $Table->aActiveLink;
		$this->nFirstRowSelect= $Table->nFirstRowSelect;
		$this->nMaxRowSelect= $Table->nMaxRowSelect;
		if(is_object($Table->oWhere))
    		$this->oWhere= clone $Table->oWhere;
    	$this->asOrder= $Table->asOrder;
    	$this->sPKColumn= $Table->sPKColumn;
		$this->show= $Table->show;
    	$this->columns= $Table->columns;
		$this->bDistinct= $Table->bDistinct;
		$this->aSetAlso= $Table->aSetAlso;
		$this->asForm=$Table->asForm;
		$this->bDisplaySelects= $Table->bDisplaySelects;
		$this->bDisplayIdentifs= $Table->bDisplayIdentifs;
		$this->bIsNnTable= $Table->bIsNnTable;
		$this->oTinyMCE= &$Table->oTinyMCE;
		$this->aInputSize= $Table->aInputSize;
	}
	function getColumnName($column)
	{
		STCheck::paramCheck($column, 1, "string", "int");

		if( isset($this->columns[$column]) &&
			is_int($column)	)
		{
			return $this->columns[$column]["name"];
		}

		if($this->haveColumn($column))
			return $column;
		return null;
	}
	/**
	 * create currently html name used inside a form
	 * currently on April 2024 for action STINSERT or STUPDATE inside STItemBox
	 * name of input-tags are displayed as alias columns with underlines for spaces.
	 * (It's an ugly hack I know, but fast enough for now)
	 * 
	 * @param string|array $column name of column or alias for table or field of column (getting from STBaseTable::searchAliasColumn) for maybe faster execution
	 * @param bool $bUnderline whether should create an underline instead as space, default true
	 * @return string alias with underline if before exist, otherwise the column
	 */
	public function defineDocumentItemBoxName(string|array $column, bool $bUnderline= true) : string
	{
		if(is_string($column))
		{
			$field= $this->findColumnOrAlias($column);
			$column= $field['alias'];
		}else
			$column= $column['alias'];
		if($bUnderline)
			$column= preg_replace("/ /", "_", $column);
		return $column;
	}
	function postToGetTransfer($var /*, ...*/)
	{
		$vars= func_get_args();
		$this->aPostToGet[]= $vars;
	}
	function inputSize($column, $width, $height= null)
	{
		$this->aInputSize[$column]["width"]= $width;
		if($height)
			$this->aInputSize[$column]["height"]= $height;
	}
	/**
	 * set attribute by displayed table
	 * when defined for specific column
	 * and or different action type (STLIST/STINSERT/STUPDATE)
	 * 
	 * @param string $element on which tag element the attribute should be set
	 * @param string $attribute which attribute should be set
	 * @param string $value the value of the attribute
	 * @param string $tableType the action type of table (STLIST/STINSERT/STUPDATE) (default null for all actions)
	 * @param string $aliasName the specific name of column when set, otherwise for all columns
	 */
	function attribute($element, $attribute, $value, $tableType= null, $aliasName= null)
	{
	    if(isset($aliasName))
	    {
    	    $field= $this->findAliasOrColumn($aliasName);
    	    if(isset($field))
                $aliasName= $field['alias'];
    	    else
    	       STCheck::is_error($field==null, "STBaseTable::attribute()", "alias name or column ('$aliasName') does not exist");
	    }
		if(	$tableType !== null &&
			$tableType !=  STALLDEF	)
		{
		    if(STCheck::isDebug())
		    {    
		        STCheck::warning( $tableType != STLIST &&
                		          $tableType != STINSERT &&
                		          $tableType != STUPDATE &&
								  $tableType != STINSERTUPDATE, "STBaseTable::attribute()", "unknown tableType ('$tableType') be set");
		    }
			if($tableType == STINSERTUPDATE)
			{
				$this->aAttributes[STINSERT][$element][$aliasName][$attribute]= $value;
				$this->aAttributes[STUPDATE][$element][$aliasName][$attribute]= $value;
			}else
		    	$this->aAttributes[$tableType][$element][$aliasName][$attribute]= $value;
			return;
		}
		// tableType is NULL
		$this->aAttributes[STINSERT][$element][$aliasName][$attribute]= $value;
		$this->aAttributes[STUPDATE][$element][$aliasName][$attribute]= $value;
		$this->aAttributes[STLIST][$element][$aliasName][$attribute]= $value;

	}
	function tableAttribute($attribute, $value, $tableType= null)
	{
		$this->attribute("table", $attribute, $value, $tableType);
	}
	function trAttribute($attribute, $value, $tableType= null)
	{
		$this->attribute("tr", $attribute, $value, $tableType);
	}
	function thAttribute($attribute, $value, $tableType= null)
	{
		$this->attribute("th", $attribute, $value, $tableType);
	}
	function tdAttribute($attribute, $value, $tableType= null, $aliasName= null)
	{
		$this->attribute("td", $attribute, $value, $tableType, $aliasName);
	}
	/**
	 * make allignment for specific column.<br />
	 * can be differend between list-, insert- and update-table
	 * 
	 * @param string $aliasName name of column
	 * @param string $value whether alignment should be 'left', 'center' or 'right'
	 * @param enum $tableType for which display table - STLIST, STINSERT or STDELETE - alignment should be.
	 * 							(default: <code>STLIST</code>)
	 */
	public function align($aliasName, $value, $tableType= STLIST)
	{
	    $this->tdAttribute("align", $value, $tableType, $aliasName);
	}
	/**
	 * define column for a scrollable input field
	 * 
	 * @param string $aliasName name of column
	 * @param int $min minimum value of scroll bar
	 * @param int $max maximum value of scroll bar
	 * @param int $steps steps of scroll bar (default: <code>1</code>)
	 * @param int $bias alignment of scroll bar - STHORIZONTAL or STVERTICAL (default: <code>STHORIZONTAL</code> changeable with next parameter)
	 * @param enum $tableType for which display table - STLIST, STINSERT or STDELETE - width should be.
	 * 							(default: <code>STINSERTUPDATE</code>)
	 */
	public function range(string $aliasName, int $min, int $max, $steps= 1, $bias= STHORIZONTAL, $tableType= STINSERTUPDATE, $showValue= false)
	{
		if(is_bool($steps))
		{
			$showValue= $steps;
			$steps= 1;
		}
		if(!is_int($steps))
		{
			$tableType= $bias;
			$bias= $steps;
			$steps= 1;
		}
		if(	$bias != STVERTICAL &&
			$bias != STHORIZONTAL	)
		{
			if($tableType == STVERTICAL)
			{
				$newValue= STVERTICAL;
			}else
				$newValue= STHORIZONTAL;
			$tableType= $bias;
			$bias= $newValue;
		}
		STCheck::warning($tableType != STLIST, "STListBox::createTags", "implementations for STINSERT and STUPDATE are not yet implemented now");
		$value= array("min"=>$min, "max"=>$max, "step"=>$steps, "range"=>$bias, "value"=>$showValue);
	    $this->attribute("input", "range", $value, $tableType, $aliasName);
		if($tableType == STLIST)
			$this->selectPkColumnOnListIfNeed();
	}
	protected function selectPkColumnOnListIfNeed()
	{
		$pk= $this->getPkColumnName();
		$bSet= false;
		foreach($this->show as $column)
		{
			if(	$column['table'] == $this->Name &&
				$column['column'] == $pk			)
			{
				$bSet= true;
				break;
			}
		}
		if(!$bSet)
			$this->getColumn($pk, $this->Name."_table@PK_value");		
	}
	/**
	 * pre-define a cluster for every row,
	 * where the row only be shown if the user has the cluster.<br />
	 *  
	 * @param string $aliasName alias or column name where the cluster should be stored 
	 * @param string $prefix can be the name of an alias or column which be followed of the bracketed primary key 
	 */
	public function cluster(string $aliasName, string $prefix= "")
	{
	    $this->aClusterColumns[$aliasName]= $prefix;
	}
	/**
	 * define column as must have field, maybe by different actions
	 * 
	 * @param string $column name of column or defined alias column
	 * @param enum $action column hase to be exist for all actions STADMIN (default), or only by STINSERT or STUPDATE
	 */
	public function needValue(string $column, $action= STADMIN)
	{
	    STCheck::param($action, 1, "check", $action==STADMIN||$action==STINSERT||$action==STUPDATE, "can be STADMIN for all, or STINSERT / STUPDATE");
	    
	    $field= $this->findAliasOrColumn($column);
	    STCheck::alert(!isset($field), "STBaseTable::needValue", "$column is no pre-defined alias column or column inside database");
	    
	    $flag= "not null";
	    $notDef= "null";
	    if($action == STADMIN)
	    {
	        $this->preDefinedFlag($field['alias'], STINSERT, $flag, $notDef);
	        $this->preDefinedFlag($field['alias'], STUPDATE, $flag, $notDef);
	    }else
	        $this->preDefinedFlag($field['alias'], $action, $flag, $notDef);
	}
	/**
	 * define a column as optional, maybe different by any action
	 * 
	 * @param string $column name of column or defined alias column
	 * @param enum $action column should be optional for all actions STADMIN (default), or only by STINSERT or STUPDATE
	 */
	public function optional(string $column, $action= STUPDATE)
	{
	    STCheck::param($action, 1, "check", $action==STADMIN||$action==STINSERT||$action==STUPDATE, "can be STADMIN for all, or STINSERT / STUPDATE");
	    
	    $field= $this->findAliasOrColumn($column);
	    STCheck::alert(!isset($field), "STBaseTable::optional", "$column is no pre-defined alias column or column inside database");
	    
	    $flag= "null";
	    $notDef= "not null";
	    if($action == STADMIN)
	    {
	        $this->preDefinedFlag($field['alias'], STINSERT, $flag, $notDef);
	        $this->preDefinedFlag($field['alias'], STUPDATE, $flag, $notDef);
	    }else
	        $this->preDefinedFlag($field['alias'], $action, $flag, $notDef);
	}
	public function hasDefinedFlag(string $alias, $action, string $flag) : bool
	{
	    STCheck::param($action, 1, "check", $action==STLIST||$action==STINSERT||$action==STUPDATE, "can only be STLIST, STINSERT or STUPDATE");
	    
	    $field= $this->findAliasOrColumn($alias);
	    STCheck::alert(!isset($field), "STBaseTable::haseDefinedFlag", "'$alias' is no pre-defined alias column or column inside database");
	    
	    if(!isset($this->aPreDefinde[$action][$field['alias']]))
	        return false;
	    return in_array($flag, $this->aPreDefinde[$action][$field['alias']]);
	}
	/**
	 * define a column with specidic given flag, different by any action
	 * 
	 * @param string $alias name of alias column (have to be checked for correctness before)
	 * @param enum $action column be set for action STLIST, STINSERT or STUPDATE
	 * @param string $flag name of the flag defined for column
	 * @param string $notDefined flag should not defined after the given flag before (3. param) is set
	 */
	private function preDefinedFlag(string $alias, $action, string $flag, string $notDefined= "")
	{
	    STCheck::param($action, 1, "check", $action==STLIST||$action==STINSERT||$action==STUPDATE, "can only be STLIST, STINSERT or STUPDATE");
	    
	    $bSet= false;
	    if(isset($this->aPreDefinde[$action][$alias]))
	    {
	        foreach($this->aPreDefinde[$action][$alias] as $key=>$thisFlag)
	        {
	            if($thisFlag == $notDefined)
	            {
	                $bSet= true;
	                $this->aPreDefinde[$action][$alias][$key]= $flag;
	                break;
	                
	            }elseif($thisFlag == $flag)
	            {
	                $bSet= true;
	                break;
	            }
	        }
	    }
	    if(!$bSet)
	        $this->aPreDefinde[$action][$alias][]= $flag;
	}
	/**
	 * user will be forward to login page
	 * if he has no access to cluster
	 *
	 * @param string $cluster access cluster name
	 * @param string $action action can be set to:<br />
	 *                         <table>
	 *                             <tr>
	 *                                 <td>
	 *                                     STALLDEF
	 *                                 </td><td>-</td>
	 *                                     for all actions (default)
	 *                                 <td>
	 *                                 </td>
	 *                             </tr>
	 *                             <tr>
	 *                                 <td>
	 *                                     STLIST
	 *                                 </td><td>-</td>
	 *                                 <td>
	 *                                     show only the content of table
	 *                                 </td>
	 *                             </tr>
	 *                             <tr>
	 *                                 <td>
	 *                                     STINSERT
	 *                                 </td><td>-</td>
	 *                                 <td>
	 *                                     insert somthing into table
	 *                                 </td>
	 *                             </tr>
	 *                             <tr>
	 *                                 <td>
	 *                                     STUPDATE
	 *                                 </td><td>-</td>
	 *                                 <td>
	 *                                     update row of table
	 *                                 </td>
	 *                             </tr>
	 *                             <tr>
	 *                                 <td>
	 *                                     STDELETE
	 *                                 </td><td>-</td>
	 *                                 <td>
	 *                                     delete row of table
	 *                                 </td>
	 *                             </tr>
	 *                         </table>
	 *                       if no action be set, only the third description string,
	 *                       action will be also the default (STALLDEF)
	 * @param string $sInfoString description of cluster for logging table
	 * @param int $customID custom id for logging table if need
	 */
	public function accessBy($clusters, $action= STALLDEF, $toAccessInfoString= "", int $customID= null)
	{
	    STCheck::param($clusters, 0, "string", "array");
	    STCheck::param($action, 1, "string", "int");
	    STCheck::param($toAccessInfoString, 2, "string", "empty(string)", "int");
	    
	    if( $action != STALLDEF &&
	        $action != STLIST &&
	        $action != STINSERT &&
	        $action != STUPDATE &&
	        $action != STDELETE    )
	    {
	        $sInfoString= $action;
	        $action= STALLDEF;
	    }
	    if(is_integer($toAccessInfoString))
	    {
	        $customID= $sInfoString;
	        $sInfoString= "";
	    }
		if($action==STADMIN)
		{
			$this->accessBy($clusters, STINSERT, $toAccessInfoString, $customID);
			$this->accessBy($clusters, STUPDATE, $toAccessInfoString, $customID);
			$this->accessBy($clusters, STDELETE, $toAccessInfoString, $customID);
			//return;
			// set to all extra actions also STADMIN
		}
		if(	!isset($this->abOrigChoice["accessBy_".$action]) ||
			$this->abOrigChoice["accessBy_".$action]	== true		)
		{
			$this->asAccessIds[$action]= array();
			$this->abOrigChoice["accessBy_".$action]= false;
		}
		if(!is_array($this->asAccessIds[$action]))
			$this->asAccessIds[$action]= array();
		$this->asAccessIds[$action][]= array( "cluster" => $clusters,
		                                      "action" => $action,
		                                      "accessString" => $toAccessInfoString,
		                                      "customID" => $customID                 );
	}
	function clearAccess()
	{
		$this->asAccessIds= array();
	}
	function getAccessCluster($action)
	{
		STCheck::paramCheck($action, 1, "check",    $action===STALLDEF || $action===STLIST || $action===STINSERT ||
												    $action===STUPDATE || $action===STDELETE,
												    "STALLDEF", "STLIST", "STINSERT", "STUPDATE", "STDELETE");
												
		$aRv= array();
		if(isset($this->asAccessIds[STALLDEF]))
		    $aRv= $this->asAccessIds[STALLDEF];
	    if(isset($this->asAccessIds[$action]))
	        $aRv= array_merge($aRv, $this->asAccessIds[$action]);
		if( STUserSession::sessionGenerated()
		    and
			count($this->sAcessClusterColumn) )
		{
		    $session= &STUserSession::instance();
			$created= $session->getDynamicClusters($this);

			//echo "achtion:$action<br />";
			$nAction= $action;
			if($action==STLIST)
				$nAction= STACCESS;
				if(count($created[$nAction]))
				{
				    $clusters= "";
				    foreach($created[$nAction] as $cluster)
					{
						if($action==STINSERT)
						{// if the search is for action STINSERT
						 // create from the dynamic cluster the parent.
						 // user must have by STINSERT always the admin access from before
							if(preg_match("/^(.*)_[^_]+$/", $cluster, $preg))
								$cluster= $preg[1];
						}
						$clusters.= $cluster.",";
					}
					$clusters= substr($clusters, 0, strlen($clusters)-1);
					$aRv= array_merge($aRv, $clusters);
				}
				if( (    $action==STINSERT
				         OR
						 $action==STUPDATE
						 OR
						 $action==STDELETE  )
				    and
						count($created[STADMIN])    )
				{
				    if($clusters)
						    $clusters.= ",";
				    foreach($created[STADMIN] as $cluster)
					{
						if($action==STINSERT)
						{// if the search is for action STINSERT
						 // create from the dynamic cluster the parent.
						 // user must have by STINSERT always the admin access from before
							if(preg_match("/^(.*)_[^_]+$/", $cluster, $preg))
								$cluster= $preg[1];
						}
						$clusters.= $cluster.",";
					}
					$clusters= substr($clusters, 0, strlen($clusters)-1);
					$aRv= array_merge($aRv, $clusters);
				}
				if(STCheck::isDebug())
				{
				    echo "get all access clusters:";
				    st_print_r($aRv);
				    showBackTrace();
				}
		}
		return $aRv;
	}
	function getAccessInfoString($action)
	{
		$toAccessInfoString= null;
		if(isset($this->asAccessIds[$action]["accessString"]))
			$toAccessInfoString= $this->asAccessIds[$action]["accessString"];
		if(	!isset($toAccessInfoString) &&
			isset($this->asAccessIds[STLIST]["accessString"])	)
		{// else if no cluster for given action set
		 // set the STLIST clusters
			$toAccessInfoString= $this->asAccessIds[STLIST]["accessString"];
		}
		if( !isset($toAccessInfoString) ||
		    trim($toAccessInfoString) == ""   )
		{
			$actionString= "";
			if($action==STINSERT)
				$actionString= "INSERT";
			elseif($action==STUPDATE)
				$actionString= "UPDATE";
			elseif($action==STDELETE)
				$actionString= "DELETE";
			if($action==STLIST)
				$toAccessInfoString= "access table ".$this->getName();
			else
				$toAccessInfoString= $actionString." in table ".$this->getName();
		}
		return $toAccessInfoString;
	}
	function getAccessCustomID($action)
	{
		$customID= null;
		if(isset($this->asAccessIds[$action]["customID"]))
			$customID= $this->asAccessIds[$action]["customID"];
		if(	!isset($customID) &&
			isset($this->asAccessIds[STLIST]["customID"])	)
		{// else if no cluster for given action set
		 // set the STLIST clusters
			$customID= $this->asAccessIds[STLIST]["customID"];
		}
		return $customID;
	}
	public function allowQueryLimitationByOwn($bModify= true)
	{
	    if(STCheck::isDebug("db.statements.where"))
	    {
	        if($bModify)
	            $msg= "enable ";
	        else
	            $msg= "disable ";
	        $msg.= " own table ".$this->toString()." limitation";
	        STCheck::echoDebug("db.statements.where", $msg);
	    }
	    $this->bLimitOwn= $bModify;
	    if($this->bModifiedByQuery)
	        $this->resetQueryLimitation("own", $bModify);
	}
	/**
	 * use also limitation of table from an older container
	 * if one exist and was there set (default property)
	 */
	public function useLimitationBefore()
	{// old function name was deleteLimitation()
	    if(STCheck::isDebug("db.statements.where"))
	    {
	        $msg= "enable before limitation by own table ".$this->toString();
	        STCheck::echoDebug("db.statements.where", $msg);
	    }
		$this->sDeleteLimitation= "true";
	}
	/**
	 * do not use limitations whitch was set in any
	 * container before
	 */
	public function useNoLimitationBefore()
	{// old function name was noLimitationDelete()
	    if(STCheck::isDebug("db.statements.where"))
	    {
	        $msg= "disable before limitation by own table ".$this->toString();
	        STCheck::echoDebug("db.statements.where", $msg);
	    }
		$this->sDeleteLimitation= "false";
	}
	public function deleteLimitationByOlder()
	{
	    if(STCheck::isDebug("db.statements.where"))
	    {
	        $msg= "allow only one limitation before by own table ".$this->toString();
	        STCheck::echoDebug("db.statements.where", $msg);
	    }
	    echo "use limitation link set before<br>";
		echo "depricated method never testet<br>";
	    showBackTrace();
		$this->sDeleteLimitation= "older";
	}
	public function getDeleteLimitationOrder()
	{
	    return $this->sDeleteLimitation;
	}
	function withSearchBox(&$searchBox)
	{
		Tag::paramCheck($searchBox, 1, "STSearchBox");
		$this->oSearchBox= &$searchBox;
	}
	// wenn bAlwaysIndex ist null wird der Index
	// bei keinem Eintrag in der Tabelle nicht angezeigt
	function showAlwaysIndex($bShow)
	{
		$this->bAlwaysIndex= $bShow;
	}
	function needAlwaysIndex()
	{
		return $this->bAlwaysIndex;
	}
	function setFirstRowSelect($firstRow)
	{
		$this->nFirstRowSelect= $firstRow;
	}
	function clearFirstRowSelect()
	{
		$this->nFirstRowSelect= 0;
	}
	function getFirstRowSelect()
	{
		return $this->nFirstRowSelect;
	}
	function setMaxRowSelect($count)
	{
		$this->dateIndex= array();
		$this->nMaxRowSelect= $count;
	}
	function limit($start, $limit= null)
	{
		if(!$limit)
		{
			$limit= $start;
			$start= 0;
		}
		$this->limitRows= array("start"=>$start, "limit"=>$limit);
	}
	function getMaxRowSelect()
	{
		return $this->nMaxRowSelect;
	}
	function setDateIndex($fromDateColumn, $toDateColumn= null, $type= null, $showEmptyDate= false)
	{
		$this->nMaxRowSelect= null;
		$this->dateIndex= array();
		$this->dateIndex["from"]= $fromDateColumn;
		if($toDateColumn)
			$this->dateIndex["to"]= $toDateColumn;
		if($type===null)
		{
			$this->dateIndex["type"]= "unknown";
			foreach($this->columns as $column)
			{
				if($column["name"]==$fromDateColumn)
				{
					if(preg_match("/date/i", $column["type"]))
						$type= STMONTH;
					else
						$type= STDAY;
					break;
				}
			}
		}
		$this->dateIndex["type"]= $type;
		$this->dateIndex["empty"]= $showEmptyDate;
	}
	function clearIndexSelect()
	{
		$this->nMaxRowSelect= null;
		$this->dateIndex= array();
	}
	function listLayout($arrangement)
	{
		$this->listArrangement= $arrangement;
	}
		function distinct($bDistinct= true)
		{
			$this->bDistinct= $bDistinct;
		}
		function isDistinct()
		{
			return $this->bDistinct;
		}
		function setIdentifier($identifier)
		{
			Tag::deprecated("STBaseTable::setDisplayName('$identifier')", "STBaseTable::setIdentifier('$identifier')");
			$this->setDisplayName($identifier);
		}
		function setDisplayName($string)
		{
			$this->identifier= $string;
		}
		function getDisplayName()
		{
			$identifier= $this->identifier;
			if($identifier===null)
				$identifier= $this->Name;
			return $identifier;
		}
		function getWhereValue($columnName, $table= null, $bFirst= true)
		{
			if(!$table)
				$table= $this->Name;
			//echo $this->Name."::getWhereValue($column, $table)<br />";

			$aRv= array();
			if($this->oWhere)
				$whereResult= $this->oWhere->getSettingValue($columnName, $table);
			if( isset($whereResult) &&
			    is_array($whereResult) &&
			    count($whereResult)          )
			{
				foreach($whereResult as $content)
				{
					if($content["type"]==="value")
						$aRv[]= $content;
				}
			}else
			{
			    $Rv= "";
				if($bFirst)
				{
					$params= new STQueryString();
					$params= $params->getLimitation($table);
					if(isset($param[$columnName]))
					    $Rv= $params[$columnName];
				}
				if(trim($Rv) == "")
				{
					//st_print_r($this->aFks,3);
					//st_print_r($this->show,5);
					foreach($this->aFks as $content)
					{
						foreach($content as $column)
						{
							// is the column selected?
							// ask the next higher table
							if($bFirst)
							{
    							foreach($this->show as $showen)
    							{
    								if($column["own"]===$showen["column"])
    								{
    									$oTable= &$this->getTable($column["table"]->Name);
    									$Rv= $oTable->getWhereValue($columnName, $table, false);
                              			if(count($Rv))
                              			{
                              				foreach($Rv as $content)
                              				{
                              					if($content["type"]==="value")
                              						$aRv[]= $content;
                              				}
                              			}
    								}
    							}
							}else
							{
								if(Tag::isDebug())
								{
									echo "to do: search where clausel in getWhereValue for identif columns<br />";
									echo __file__.__line__."<br />";
								}

								/*foreach($this->identification as $column)
								{
									if($column["column"]==$columnName)
								}*/
							}
						}
					}
				}else
					$aRv[]= array(	"value"		=> $Rv,
									"operator"	=> "=",
									"type"		=> "value"		);
			}
			return $aRv;

		}
		function noNnTable()
		{
			$this->bIsNnTable= false;
		}
		function isNnTable()
		{
			return $this->bIsNnTable;
		}
		/**
		 * make statement with join over also this given table
		 * 
		 * @param string $table name of table
		 */
		public function joinOver(string $table, string $join= "inner")
		{
		    STCheck::param($join, 1, "check", $join==STINNERJOIN||$join==STLEFTJOIN||$join==STRIGHTJOIN, "STINNERJOIN, STLEFTJOIN or STRIGHTJOIN");
		    
		    $this->aJoinOverTables[$table]= $join;
		}
		public function noJoinOver(string $table)
		{
		    if(!in_array($table, $this->aNoJoinOverTables))
		        $this->aNoJoinOverTables[]= $table;
		}
		function getAlsoJoinOverTables() : array
		{
		    foreach($this->aNoJoinOverTables as $nojoin)
		    {
		        if(isset($this->aJoinOverTables[$nojoin]))
		            unset($this->aJoinOverTables[$nojoin]);
		    }
		    return $this->aJoinOverTables;
		}
		function getNotJoinOverTables() : array
		{
		    return $this->aNoJoinOverTables;
		}
		function foreignKey($ownColumn, $toTable, $otherColumn= null, $where= null)
		{
			STCheck::param($ownColumn, 0, "string");
			STCheck::param($toTable, 1, "STBaseTable", "string");
			STCheck::param($otherColumn, 2, "string", "empty(string)", "null");
			STCheck::param($where, 3, "string", "empty(String)", "STDbWhere", "null");
						
			$this->fk($ownColumn, $toTable, $otherColumn, null, $where);
		}
		function foreignKeyObj($ownColumn, &$toTable, $otherColumn= null, $where= null)
		{
			Tag::paramCheck($ownColumn, 1, "string");
			Tag::paramCheck($toTable, 2, "STBaseTable");
			Tag::paramCheck($otherColumn, 3, "string", "empty(string)", "null");
			Tag::paramCheck($where, 4, "string", "empty(String)", "STDbWhere", "null");

			$this->fk($ownColumn, $toTable, $otherColumn, null, $where);
		}
		/**
		 * prepare inner join foreign key between tables
		 * 
		 * @param string $fromColumn foreign key shows from this column of own table to the other
		 * @param string|STBaseTable $toTable set foreign key to table of this parameter
		 * @param string $toColumn foreign key shows from other colum to this column parameter
		 * @param null $null dummy parameter, only for compatibility with STDbSelector class
		 */
		public function join($ownColumn, $toTable, $otherColumn= null, $null= null)
		{
			$this->innerJoin($ownColumn, $toTable, $otherColumn, $null);
		}
		/**
		 * prepare inner join foreign key between tables
		 * 
		 * @param string $fromColumn foreign key shows from this column of own table to the other
		 * @param string|STBaseTable $toTable set foreign key to table of this parameter
		 * @param string $toColumn foreign key shows from other colum to this column parameter
		 * @param null $null dummy parameter, only for compatibility with STDbSelector class
		 */
		public function innerJoin($ownColumn, $toTable, $otherColumn= null, $null= null)
		{
			STCheck::param($ownColumn, 0, "string");
			STCheck::param($toTable, 1, "STBaseTable", "string");
			STCheck::param($otherColumn, 2, "string", "empty(string)", "null");
			STCheck::param($null, 3, null);

			$this->fk($ownColumn, $toTable, $otherColumn, STINNERJOIN, null);
		}
		/**
		* prepare left join foreign key between tables
		* 
		* @param string $fromColumn foreign key shows from this column of own table to the other
		* @param string|STBaseTable $toTable set foreign key to table of this parameter
		* @param string $toColumn foreign key shows from other colum to this column parameter
		* @param null $null dummy parameter, only for compatibility with STDbSelector class
		*/
		public function leftJoin($ownColumn, $toTable, $otherColumn= null, $null= null)
		{
			STCheck::param($ownColumn, 0, "string");
			STCheck::param($toTable, 1, "STBaseTable", "string");
			STCheck::param($otherColumn, 2, "string", "empty(string)", "null");
			STCheck::param($null, 3, null);

			$this->fk($ownColumn, $toTable, $otherColumn, STLEFTJOIN, null);
		}
		/**
		* prepare right join foreign key between tables
		* 
		* @param string $fromColumn foreign key shows from this column of own table to the other
		* @param string|STBaseTable $toTable set foreign key to table of this parameter
		* @param string $toColumn foreign key shows from other colum to this column parameter
		* @param null $null dummy parameter, only for compatibility with STDbSelector class
		*/
		public function rightJoin($ownColumn, $toTable, $otherColumn= null, $null= null)
		{
			STCheck::param($ownColumn, 0, "string");
			STCheck::param($toTable, 1, "STBaseTable", "string");
			STCheck::param($otherColumn, 2, "string", "empty(string)", "null");
			STCheck::param($null, 3, null);

			$this->fk($ownColumn, $toTable, $otherColumn, STRIGHTJOIN, null);
		}
    protected function fk($ownColumn, &$toTable, $otherColumn= null, $join= null, $where= null, $cascade= null)
    {// echo "function fk($ownColumn, &$toTable, $otherColumn, $join, $where)<br />";
		STCheck::param($ownColumn, 0, "string");
		STCheck::param($toTable, 1, "STBaseTable", "string");
		STCheck::param($otherColumn, 2, "string", "empty(string)", "null");
		STCheck::param($join, 3, "check", $join==STINNERJOIN||$join==STLEFTJOIN||$join==STRIGHTJOIN||$join==null,
											"null", "STINNERJOIN", "STLEFTJOIN", "STRIGHTJOIN");
		STCheck::param($where, 4, "string", "empty(String)", "STDbWhere", "null");
	
		if(typeof($toTable, "STBaseTable"))
			$toTableName= $toTable->getName();
		else
		{
			$toTableName= $toTable;
			$toTable= $this->getTable($toTableName);
		}
		// alex 26/04/2005:	where und otherColumn tauschen wenn n�tig
		if(	typeof($otherColumn, "stdbwhere") ||
			($otherColumn != null &&
			 preg_match("/^.+[!=<>].+$/", $otherColumn)	)    )
		{
			$buffer= $where;
			$where= $otherColumn;
			$otherColumn= &$buffer;
		}// end of tausch
		STCheck::echoDebug("db.table.fk", "create FK from ".$this->getName().".$ownColumn to $toTableName.$otherColumn inside STBaseTable::ID'".$this->ID."'");
		
			// alex 26/04/2005: where-clausel einfuegen
			if($where)
				$toTable->where($where);
			if($otherColumn==null)
			{
				$otherColumn= $toTable->sPKColumn;
				if(!$otherColumn)
				{
					echo "###Error: in table $toTableName is no primary key set<br />";
					echo "          pleas fill in the 3 parameter (\$otherColumn) in method foreignKey()";
					showBackTrace();
					exit;
				}
			}
			
			if($join===null)
			{
				$bInTable= false;
				$beginning_space= STCheck::echoDebug("db.table.fk", "test <b>$ownColumn:</b> for table <b>".$this->Name."</b> in object <b>".get_class($this).":</b>");
				foreach($this->columns as $field)
				{
					if(Tag::isDebug("db.table.fk"))
					{
						st_print_r($field, 1, $beginning_space);
					}
					if($field["name"]==$ownColumn)
					{//echo "fields: ";print_r($field);echo "<br />";
						$bInTable= true;
						if(preg_match("/not_null/i", $field["flags"]))
							$join= "inner";
						else
							$join= "outer";
						break;
					}
				}
				STCheck::is_warning(!$bInTable, "STBaseTable::fk()", "column $ownColumn is not in Table ".$this->Name);
			}

		$bSet= false;
		if(	isset($this->aFks[$toTableName]) &&
			is_array($this->aFks[$toTableName])	)
		{
			foreach($this->aFks[$toTableName] as $key=>$content)
			{
				if($content["own"]===$ownColumn)
				{
					$this->aFks[$toTableName][$key]['other']= $otherColumn;
					$this->aFks[$toTableName][$key]['join']= $join;
					$this->aFks[$toTableName][$key]['table']= &$toTable;
					$toTable->setBackJoin($this->Name);
					$bSet= true;
				}
			}
		}
		if(!$bSet)
		{

	     	$aFk= array(	"own"=>$ownColumn, 
							"other"=>$otherColumn, 
							"join"=>$join, 
							"table"=>&$toTable		);
			if($cascade)
				$aFk['cascade']= $cascade;
			$this->aFks[$toTableName][]= $aFk;
	     	$toTable->setBackJoin($this->Name);
		}
		if(STCheck::isDebug("db.table.fk"))
		{
		    $beginning_space= STCheck::echoDebug("db.table.fk", "new defined foreign keys on table <b>".$this->Name.":</b>");
		    st_print_r($this->aFks, 3, $beginning_space);
		}

    }
	function setBackJoin($tableName)
	{
		Tag::paramCheck($tableName, 1, "string");

		$exists= array_value_exists($tableName, $this->aBackJoin);
		if($exists !== false)
		    return;// backjoin was set befor
		
		$this->aBackJoin[]= $tableName;
		if(STCheck::isDebug("db.table.fk"))
		{
		    $space= STCheck::echoDebug("db.table.fk", "backjoin in table <b>".$this->getName()."</b> with ID:".$this->ID." was new defined to:");
		    st_print_r($this->aBackJoin, 2, $space);
		}
	}
	public function getAliasOrder()
	{
		return $this->db->getAliasOrder();
	}
	function clearSqlAliases()
	{
		STCheck::echoDebug("db.statements.aliases", "clear sql aliases for table ".$this->Name);		
		$this->aAliases= array();
	}
	function isOrdered()
	{
		$aliases= array();
		$aliases= $this->db->getAliasOrder();
		$order= $this->getOrderStatement($aliases, null, true);
		if($order)
			return true;
		return false;
	}
	public function orderBy(string $column, $bASC= true, int $warnFuncOutput= 0)
	{
	    STCheck::paramCheck($bASC, 2, "bool");
	    
	    $field= $this->findAliasOrColumn($column);
	    $column= $field["column"];
	    $this->orderByI($this->getName(), $column, $bASC, $warnFuncOutput+1);
	}
	protected function orderByI(string $tableName, string $column, bool $bASC, int $warnFuncOutput= 0)
	{
	    if( !isset($this->abOrigChoice["order"]) ||
	        $this->abOrigChoice["order"] == true    )
	    {
	        $this->asOrder= array();
	        $this->abOrigChoice["order"]= false;
	    }
	    if($bASC)
	        $sort= "ASC";
        else
            $sort= "DESC";
		if(typeof($this, "STDbTable"))
		{
			if(!preg_match("/,/", $column))
			{
				$deli= $this->db->getFieldDelimiter()[0];
				$column= "{$deli['open']['delimiter']}$column{$deli['open']['delimiter']}";
			}else
				STCheck::warning(true, "STBaseTable::orderBy()", "set only column one by one, because than can set field delimiter for columns", $warnFuncOutput+1);
		}
        $this->asOrder[]= array(    "table" => $tableName,
                                    "column"=> $column,
                                    "sort"  => $sort        );
	}
	function clearCreatedAliases()
	{
		$this->aAliases= array();
	}
		function addCount($column= "*", $alias= null)
		{
			$this->count($column, $alias, true);
		}
		public function count($table= "*", string $column= "*", $alias= null, $add= false)
		{
		    // only for compatibility with STDbSelector
		    // call private method countA where $table is $column
		    if( $table == "*" &&
		        $column == "*" &&
		        $alias === null &&
		        $add == false         )
		    {// only default calling
		        STBaseTable::countA($column, $alias, $add);
		        return;
		    }
		    if( $column == "*" &&
		        $alias === null &&
		        $add == false         )
		    {// $table should be column, all other default
		        STBaseTable::countA($table);
		        return;
		    }
		    if( $alias === null &&
		        $add == false         )
		    {// $table should be column, column is alias and all other default
		        STBaseTable::countA($table, $column);
		        return;
		    }
		    STBaseTable::countA($table, $column, $alias);
		}
		protected function countA($column= "*", string $alias= null, $add= null, $add2= false)
		{
			Tag::paramCheck($column, 1, "string", "STBaseTable");
			Tag::paramCheck($alias, 2, "string", "null");

			if($add === null)
			    $add= false;
			if(!isset($this->bOrder))
				$this->bOrder= false;
			$this->bHasGroupColumns= true;
			if(typeof($column, "STBaseTable"))
			{
				$aliasTables= array();
				$columns= $column->getSelectedColumns();
				if($column->isDistinct())
				{
					$columnString= "distinct ";
					$this->distinct(false);
					foreach($columns as $field)
						$columnString.= $field["column"].",";
					foreach($this->showTypes as $alias=>$content)
					{
						if(isset($content["get"]))
						{
							$field= $this->getAliasOrColumn($alias);
							$columnString.= $field["column"].",";
						}
					}
					$column= substr($columnString, 0, strlen($columnString)-1);
				}else
					$column= $columns[0]["column"];

				/*}else
				{
					$columnString= $column->show[0]["column"];
					if(!columnString)
						$columnString= $column->columns[0]["name"];
					$column= $columnString;
				}*/
			}
			if($add)
				STBaseTable::addSelect("count(".$column.")", $alias);
			else
				STBaseTable::select("count(".$column.")", $alias);
		}
		function column($name, $type, $len)
		{
		 	Tag::paramCheck($name, 1, "string");
			Tag::paramCheck($type, 2, "string");
			Tag::paramCheck($len, 3, "int");

			$columnKey= $this->dbColumn($name, $type, $len);
			$this->columns[$columnKey]["db"]= "alias";
		}
		function dbColumn($name, $type, $len)
		{
		 	Tag::paramCheck($name, 1, "string");
			 Tag::paramCheck($type, 2, "string");
			 Tag::paramCheck($len, 3, "int");

			$flags= "";
			if($type=="text")
			{
				$type= "blob";
				$flags= "blob";
			}
			$columnKey= $this->getColumnKey($name);
			if($columnKey!==null)
			{
				$this->columns[$columnKey]["type"]= $type;
				$this->columns[$columnKey]["len"]= $len;
			}else
			{
				$this->columns[]= array("name"=>$name, "flags"=>$flags, "type" =>$type, "len"=>$len);
				$columnKey= count($this->columns)-1;
			}
			return $columnKey;
		}
		function columnFlags($columnName, $flags)
		{
		 	$flags= strtolower($flags);
		 	$flags= pregi_replace("/not null/", "not_null", $flags);
		 	$flags= pregi_replace("/primary key/", "primary_key", $flags);
		 	$flags= pregi_replace("/multiple key/", "multiple_key", $flags);
		 	$aFlag= preg_split("/[ ,]/", $flags);
			$columnKey= $this->getColumnKey($columnName);

			$flags= "";
			foreach($aFlag as $flag)
			{
				if(!preg_match("/".$flag."/i", $this->columns[$columnKey]["flags"]))
					$flags.= " ".$flag;
			}
			$this->columns[$columnKey]["flags"]= substr($flags, 1);

		}
		function removeFlag($columnName, $flag)
		{
		 	$flag= strtolower($flag);
		 	$flag= preg_replace("/not null/", "not_null", $flag);
		 	$flag= preg_replace("/primary key/", "primary_key", $flag);
		 	$flag= preg_replace("/multiple key/", "multiple_key", $flag);
		 	$columnKey= $this->getColumnKey($columnName);
		 	$pos= strpos($this->columns[$columnKey]["flags"], $flag);
		 	//echo __file__.__line__."<br>";
		 	//echo "found flag on position $pos<br>";
		 	//echo "flags from column $columnName:<br>";
		 	//echo "before:".$this->columns[$columnKey]["flags"]."<br>";
		 	if($pos < strlen($this->columns[$columnKey]["flags"]))
		 	{
		 		$this->columns[$columnKey]["flags"]= substr($this->columns[$columnKey]["flags"], 0, $pos).
		 									substr($this->columns[$columnKey]["flags"], $pos + strlen($flag));
		 	}
		 	//echo "behind:".$this->columns[$columnKey]["flags"]."<br>";
		}
		public function getColumnField(string $column)
		{
		    $key= $this->getColumnKey($column);
		    if(!isset($key))
		        return null;
		    return $this->columns[$key];
		}
		function getColumnKey($columnName)
		{
			foreach($this->columns as $key=>$content)
			{
				if($content["name"]==$columnName)
					return $key;
			}
			return null;
		}
		function getSelectedColumnKey($columnName)
		{
			foreach($this->show as $key=>$content)
			{
				if($content["column"]==$columnName)
					return $key;
			}
			return null;
		}
		function getSelectedAliasKey($columnName)
		{
			foreach($this->show as $key=>$content)
			{
				if($content["alias"]==$columnName)
					return $key;
			}
			return null;
		}
		function getSelectedKey($column)
		{
			$key= $this->getSelectedAliasKey($column);
			if($key===null)
				$key= $this->getSelectedColumnKey($column);
			return $key;
		}
		function hasTinyMce()
		{
			if(count($this->oTinyMCE))
				return true;
			return false;
		}
		function &getTinyMCE($column= null)
		{
			if($column===null)
			{
				$mce= reset($this->oTinyMCE);
				return $mce;
			}
			return $this->oTinyMCE[$column];
		}
		function tinyMCECount()
		{
			return count($this->oTinyMCE);
		}
		function tinyMCEColumns()
		{
			$aRv= array();
			foreach($this->oTinyMCE as $column=>$mce)
			{
				$aRv[]= $column;
			}
			return $aRv;
		}
		/**
		 * beginning to put all entrys, for an update or insert box,
		 * into an table which have without this command two columns,
		 * to make a better design if the developer want to do more selects in one Row
		 * <code>select($dbcolumn, $displaycolumn, >break line< false)</code>
		 *
		 * @param fieldset if param is true an border is draw arround the inner table, if the param has an string the border is named, otherwise by default or false, no border showen
		 */
		function selectIBoxBegin($fieldset= false)
		{
			STCheck::param($fieldset, 0, "bool", "string");

			$this->aInnerTables[$this->nInnerTable]["fieldset"]= $fieldset;
			$this->aInnerTables[$this->nInnerTable]["begin"]= "NULL";
		}
		/**
		 * end of put entrys in an inner Table.<br />
		 * see selectIBoxBegin for beginning and more details
		 */
		function selectIBoxEnd()
		{
			$this->nInnerTable+= 1;
		}
		function addSelect($column, $alias= null, $fillCallback= null, $nextLine= null)
		{
			Tag::paramCheck($column, 1, "string");
			Tag::paramCheck($alias, 2, "string", "function", "TinyMCE", "bool", "null");
			Tag::paramCheck($fillCallback, 3, "function", "TinyMCE", "bool", "null");
			Tag::paramCheck($nextLine, 4, "bool", "null");
			$nParams= func_num_args();
			Tag::lastParam(4, $nParams);

			STBaseTable::select($column, $alias, $fillCallback, $nextLine, true);
		}
		// alex 12/09/2005:	Alias kann jetzt auch eine Funktion
		//					zum f�llen einer nicht vorhandenen Spalte sein
		// alex 21/09/2005:	ACHTUNG alias darf keine Funktion sein (auch nicht PHP-function)
		// alex 06/08/2006: second column alias or third column fillCallback can also be an object from TinyMCE
		function select(string $column, $alias= null, $fillCallback= null, $nextLine= null, $add= false)
		{
		    if(STCheck::isDebug())
		    {
    			STCheck::param($column, 1, "string");
    			STCheck::param($alias, 2, "string", "function", "TinyMCE", "bool", "null");
    			STCheck::param($fillCallback, 3, "function", "TinyMCE", "bool", "null");
    			STCheck::param($nextLine, 4, "bool", "null");
    			STCheck::param($add, 5, "bool");
    			$nParams= func_num_args();
    			STCheck::lastParam(5, $nParams);
		    }
		    
			if(STCheck::isDebug())
			{
				STCheck::alert(!$this->validColumnContent($column), "STBaseTable::selectA()",
											"column $column not exist in table ".$this->Name.
											"(".$this->getDisplayName().")");
			}
			$dbcolumn= $this->getDbColumnName($column, /*no warn*/-1);
			if(!isset($dbcolumn))
			{
				echo "select $column";
				st_print_r($this->columns);
			}
			$column= $dbcolumn;
			if(is_bool($alias))
			{
				$nextLine= $alias;
				$alias= null;
				
			}elseif(is_bool($fillCallback))
			{
				$nextLine= $fillCallback;
				$fillCallback= null;
				
			}elseif($nextLine===null)
				$nextLine= true;
			if(typeof($alias, "TinyMCE"))
			{
				$alias->elements($column);
				$this->oTinyMCE[$column]= $alias;
				$alias= null;
				
			}elseif(typeof($fillCallback, "TinyMCE"))
			{
				$fillCallback->elements($column);
				$this->oTinyMCE[$alias]= $fillCallback;
				$fillCallback= null;
			}
			if(	$alias != NULL &&
			    function_exists($alias) &&
				$fillCallback===null	   )
			{// alias ist ein Funktionsname zum f�llen
			 // einer nicht vorhandenen Spalte
			 	$fillCallback= $alias;
				$alias= $column;
			}elseif(!$alias)
				$alias= $column;
				
			$this->selectA($this->Name, $column, $alias, $nextLine, $add);
			if($fillCallback)
			{
				$this->listCallback($fillCallback, $alias);
				$this->insertCallback($fillCallback, $alias);
				$this->updateCallback($fillCallback, $alias);
				$this->deleteCallback($fillCallback, $alias);
			}
		}
		protected function selectA($table, $column, $alias, $nextLine, $add)
		{
			if(STCheck::isDebug())
			{
				Tag::paramCheck($table, 1, "string");
				Tag::paramCheck($column, 2, "string");
				Tag::paramCheck($alias, 3, "string");
				Tag::paramCheck($nextLine, 4, "bool");
				Tag::paramCheck($add, 5, "bool");
			}
			if(!preg_match("/^count\(.*\)$/i", $column))
				$this->bOrder= true;
			$bVirtual= false;
			foreach($this->columns as $field)
			{
				if($field['name'] == $column)
				{
					if(preg_match("/virtual/", $field['flags']))
						$bVirtual= true;
					break;
				}
			}
			if(!$bVirtual)
			{
				$desc= STDbTableDescriptions::instance($this->db->getDatabaseName());
				$column= $desc->getColumnName($table, $column);// if table is original function must not search
				$table= $desc->getTableName($table);
			}else
				$table= null;
			if(STCheck::isDebug())
			{
				if( !isset($table) ||
					$table===$this->Name	)
				{
					$oTable= &$this;
				}else
					$oTable= &$this->getTable($table);
				STCheck::alert(!$oTable->validColumnContent($column), "STBaseTable::selectA()",
											"column $column not exist in table ".$table.
											"(".$oTable->getDisplayName().")");
			}

			if(	!$add &&
				(   !isset($this->abOrigChoice["select"]) ||
				    $this->abOrigChoice["select"] == true   )   )
			{
			    $this->clearSelectColumns();
			    $this->abOrigChoice["select"]= false;
			}
			if($alias===null)
			    $alias= $column;
			if(STCheck::isDebug())
			{
    			foreach($this->show as $count=>$content)
    			{
    				if(	$content["alias"]== $alias )
    				{
				        if($column == $content["column"])
				        {
				            $msg= "column '$column' with alias '$alias' in table '$table' selected in two times";
				        }else
				            $msg= "two columns '$column' and '".$content["column"]."' will be select with one alias '$alias'";
    					STCheck::is_warning(1, "STBaseTable::select()", $msg, 2);
    				}
    			}
			}
			// if is set inner Table
			// with actual number
			// note begin- and end-column
			if(isset($this->aInnerTables[$this->nInnerTable]))
			{
				if($this->aInnerTables[$this->nInnerTable]["begin"] == "NULL")
					$this->aInnerTables[$this->nInnerTable]["begin"]= $alias;
				$this->aInnerTables[$this->nInnerTable]["end"]= $alias;
					
			}

			$aColumn= array();
			$aColumn["table"]= $table;
			$aColumn["column"]= $column;
			$aColumn["alias"]= $alias;
			$aColumn['type']= "select";
			$aColumn["nextLine"]= $nextLine;
			$this->show[]= $aColumn;
		}
		/**
		 * remove all added virtual columns before
		 */
		public function clearAddedColumns()
		{
			// 25/03/2024
			// toDo: not testet method
			foreach(array_reverse($this->columns) as $key =>$value )
			{
				if(preg_match("/virtual/", $value['flags']))
					unset($this->columns[$key]);
			}
		}
		/**
		 * Add new column to table
		 * 
		 * @param string $column name of column
		 * @param string $type type of column
		 */
		public function addColumn(string $column, string $type, bool $null= true, $default= null)
		{
			$enum_preg= null;
			$enum= null;
			if(preg_match("/(ENUM|SET)\((.*)\)/i", $type, $enum_preg))
			{
				$type= strtolower($enum_preg[1]);
				$enum2= preg_split("/[ ,]/", $enum_preg[2]);
				$enum= array();
				foreach($enum2 as $r)
				{
					$str= trim($r);
					if($str != "")
					{
						$preg= null;
						if(preg_match("/^['\"](.+)['\"]$/", $str, $preg))
						{
							$enum[]= $preg[1];
						}else
							$enum[]= $r;
					}
				}
			}
			if(typeof($this, "STDbTable"))
			{
				$db= $this->getDatabase();
			}else
				$db= new STDbMariaDb("__STBaseTable::addColumn_test");
			$types= $db->getDatatypes();
			$db_type= $types[strtoupper($type)];

			if(STCheck::isDebug())
			{
				// create types for parameter check of second parameter
				// and check whether first parameter column exist before
				$inType= strtoupper($type);
				$addStr= "(&lt;values&gt;, ...)";
				$typestring= "";
				foreach($types as $key=>$value)
				{
					$typestring.= $key;
					if($key == "SET" || $key == "ENUM")
						$typestring.= $addStr;
					$typestring.= ", ";
				}
				$typestring= substr($typestring, 0, strlen($typestring)-2);
				STCheck::param($type, 1, "check", isset($types[$inType]), $typestring);
				$bExist= false;
				foreach($this->columns as $exist)
				{
					if($exist['name'] == $column)
					{
						$bExist= true;
						break;
					}
				}
				STCheck::param($column, 0, "check", !$bExist, "column other than '$column', column exist inside database table {$this->Name}");
			}
			$flags= "virtual ";
			if(!$null)
				$flags.= "not_null ";
			if(isset($enum_preg[1]))
				$flags.= "enum ";
			$type= $db_type['type'];
			if($type == "enum")
				$type= "string";
			if(isset($db_type['length']))
				$length= $db_type['length'];
			elseif(isset($db_type['max']))
				$length= $db_type['max'];
			else
				$length= null;
			$entry= array(	"name" =>	$column,
							"flags" =>	$flags,
							"type" =>	$type,
							"len" => 	$length	);
			if(isset($enum))
				$entry['enums']= $enum;
			$this->columns[]= $entry;
		}
		/**
		 * add content of string or HTML-Tags
		 * after the field
		 * 
		 * @param string or HTML-Tags $content which should be added
		 * @param predefined Action $action table action on which should be added
		 */
		function addContent($content, $action)
		{
			STCheck::param($content, 0, "string", "tag");
			
			$count= sizeof($this->show);
			if($count == 0)
				$this->show[]= array();
			else
				$count-= 1;
			if(!isset($this->show[$count]["addContent"][$action]))
				$this->show[$count]["addContent"][$action]= new SpanTag();
			$this->show[$count]["addContent"][$action]->add($content);
			
		}
		/**
		 * add content of string or HTML-Tags
		 * between the last table row and the next
		 * 
		 * @param string or HTML-Tags $content which should be added
		 * @param predefined Action $action table action on which should be added
		 */
		function addBehind($content, $action)
		{
			STCheck::param($content, 0, "string", "tag");
			
			$count= sizeof($this->show);
			if($count == 0)
				$this->show[]= array();
			else
				$count-= 1;
			if(!isset($this->show[$count]["addBehind"][$action]))
				$this->show[$count]["addBehind"][$action]= new SpanTag();
			$this->show[$count]["addBehind"][$action]->add($content);
			
		}
		function isSelected($column)
		{
			foreach($this->show as $content)
			{
				if($content["column"]==$column)
					return true;
			}
			return false;
		}
		function group($name, $fieldset, $aliasColumn /*...*/)
		{
			Tag::paramCheck($name, 1, "string", "int");
			Tag::paramCheck($fieldset, 2, "bool", "string");

			$nArgs= func_num_args();
			$aArgs= func_get_args();
			$this->aGroups[$name]["fieldset"]= $fieldset;
			for($n= 2; $n<$nArgs; $n++)
			{
				$field= $this->findAliasOrColumn($aArgs[$n]);
				// groups selected by alias-columns
				// because in more groups can be the same column
				$this->aGroups[$name]["columns"][]= $field["alias"];
			}
			st_print_r($this->aGroups,3);
		}
		/**
		 * check whether given name is a valid column.<br />
		 * The column can also be a quoted string,
		 * or contain a keyword from SQL
		 *  
		 * @param string|int|float $content string to check
		 * @param array|boolean|null $abCorrect can be an empty array where the correct column inside by return, or the next boolean parameter
		 * @param boolean $alias whether column can also be an alias name (default:false) 
		 * @param array $aKeyword do not need this paramerer, only for overloaded method
		 * @return boolean true if the column parameter is valid
		 */		
		public function validColumnContent($content, &$abCorrect= null, bool $bAlias= false, $aKeyword= null) : bool
		{
		    STCheck::param(trim($content), 0, "string");
		    STCheck::param($abCorrect, 1, "array", "bool", "null");
		    
		    $content= trim($content);
		    if(is_numeric($content))
		    {
		        if(is_array($abCorrect))
		        {
    		        $abCorrect['keyword']= "@value";
    		        $abCorrect['content']= $content;
    		        if(is_float($content))
    		        {
    		            $abCorrect['type']= "real";
    		            $abCorrect['len']= 11;
    		        }else
    		        {
    		            $abCorrect['type']= "int";
    		            $abCorrect['len']= null;
    		        }
		        }
		        return true;
		    }
			$stringDelimiter= $this->db->getStringDelimiter();
			$pattern= "/^([";
			foreach($stringDelimiter as $deli)
				$pattern.= $deli['open']['delimiter'];
			$pattern.= "])(.*)[";			
			foreach($stringDelimiter as $deli)
				$pattern.= $deli['close']['delimiter'];
			$pattern.= "]$/";
			$bString= preg_match($pattern, $content, $preg);
		    if($bString)
		    {// column is maybe only an string content		        
		        if(is_array($abCorrect))
		        {
					$value= $preg[2];
					// replace always the first delimiter, should be the one delimited on end-product
					$value= preg_replace("/{$stringDelimiter[0]['open']['delimiter']}/", "\\{$stringDelimiter[0]['open']['delimiter']}", $value);
		            $abCorrect['keyword']= "@value";
		            $abCorrect['content']= $value;
		            $abCorrect['type']= "string";
		            $abCorrect['len']= strlen($content) - 2;
		        }
		        return true;
		    }
		    if(typeof($abCorrect, "bool"))
		        $bAlias= $abCorrect;
		    if(is_array($abCorrect))
		    {
		        $abCorrect['keyword']= "@field";
		        $abCorrect['content']= array();
		        $abCorrect['type']= "string";
		        $abCorrect['len']= strlen($content) - 2;
		    }
		    if( substr($content, 0, 1) == "`" &&
		        substr($content, -1) == "`"       )
		    {
		        $content= substr($content, 1, -1);
		    }
		    $field= $this->findColumnAlias($content, $bAlias, 2);
		    if(is_array($abCorrect))
		        $abCorrect['content']= $field;
		    if( $field['type'] == "not found" ||
		        (   $bAlias == false &&
		            $field['type'] == "alias"  )   )
		    {
		        if(is_array($abCorrect))
		        {
		            $abCorrect['keyword']= "unknown";
		            $abCorrect['len']= strlen($content);
		        }
		        return false;
		    }
		    return true;
		}
		public function columnExist(string $column, bool $bAlias= false) : bool
		{
			$split= preg_split("/[ ]/", $column);
			if( count($split) == 2 &&
			    strtolower($split[0]) == "distinct"  )
			{
			    $column= $split[1];
			}
			$dbColumn= $this->getDbColumnName($column,3);//, /*no warn*/-1);
			if(!isset($dbColumn))
			    $dbColumn= $column;
			foreach($this->columns as $tcolumn)
			{
			    if($dbColumn==$tcolumn["name"])
					return true;
			}
			if($bAlias == false)
			    return false;
			$field= $this->searchByAlias($column);
			if(isset($field))
			    return true;
			return false;
		}
		public function getDbColumnName(string $column, int $warnFuncOutput= 0)
		{
		    if($warnFuncOutput>-1)
		        $warnFuncOutput++;
		    $instance= STDbTableDescriptions::instance($this->db->getDatabaseName());
		    return $instance->getColumnName($this->getName(), $column, $warnFuncOutput);
		}
		/**
		 * search whether $aliasName exist as alias name
		 * as seleted column or only as identif column
		 * 
		 * @param string $aliasName
		 * @return array|NULL array with tablename, aliasname, type is alias and get as select or identif
		 */
		function searchByAlias(string $aliasName)
		{
			STCheck::param($aliasName, 0, "string");

			foreach($this->show as $field)
			{
				if(	isset($field["alias"]) &&
					$field["alias"] == $aliasName	)
				{
					$aRv= $field;
					// 17/02/2023 alex
					// table should always from original
					//$aRv["table"]= $this->Name;
					$aRv["type"]= "alias";
					$aRv["get"]= "select";
					$this->addFkDescription($aRv);
					//$aRv["alias"]= $field["alias"];
					return $aRv;
				}
			}
			foreach($this->identification as $column)
    		{
    			if( isset($column["alias"]) &&
    				$column["alias"] == $aliasName	)
    			{
    			    $aRv= $column;
    			    // 17/02/2023 alex
    			    // table should always from original
    			    //$aRv["table"]= $this->Name;
					$aRv["type"]= "alias";
    				$aRv["get"]= "identif";
					$this->addFkDescription($aRv);
					//$aRv["alias"]= $column["alias"];
					//st_print_r($aRv);
    				return $aRv;
    			}
    		}
			return null;
		}
		function &isForeignKey($columnName, $bIsColumn= false)
		{
			if(!$bIsColumn)
			{
				$field= $this->findAliasOrColumn($columnName);
				$columnName= $field["column"];
			}
			foreach($this->aFks as $table=>$content)
			{
				foreach($content as $key=>$column)
				{
					if($column["own"]===$columnName)
						return $this->aFks[$table][$key];
				}
			}
			$Rv= null;
			return $Rv;
		}
		function searchByIdentifColumn($columnName)
		{
			foreach($this->identification as $column)
			{
				if($column["column"]==$columnName)
				{
					$aRv= array();
					$aRv["table"]= $this->Name;
					$aRv["column"]= $columnName;
					if(isset($column["alias"]))
						$aRv["alias"]= $column["alias"];
					else
						$aRv["alias"]= $columnName;
					$aRv["type"]= "column";
					$aRv["get"]= "identif";
					return $aRv;
				}
			}
			foreach($this->showTypes as $c=>$columns)
			{
				if($c==$columnName)
				{
					foreach($columns as $type=>$a)
					{
						if($type==="get")
						{
        					$aRv= array();
        					$aRv["table"]= $this->Name;
        					$aRv["column"]= $columnName;
        					$aRv["alias"]= $this->Name."@".$columnName;
        					$aRv["type"]= "column";
        					$aRv["get"]= "get";
        					return $aRv;
						}
					}
				}
			}
			return null;
		}
		/**
		 * inform whether content of parameter is an keyword.<br />
		 * which only useful by overloaded methods like STDbTable for database keywords.
		 *
		 * @param string $column content of column
		 * @return array array of keyword, column, type and len, otherwise false.<br />
		 *                 the keyword is in lower case and have to be const/max/min<br />
		 *                 the column is the column inside the keyword (not shure whether it's a correct name/alias)<br />
		 *                 the type of returned value by execute
		 *                 the len of returned value by execute
		 */
		public function sqlKeyword(string $column)
		{
		    // normal STBaseTable joind to no database
		    return false;
		}
		function getSelectedFieldArray($bMain= true)
		{
			$fields= array();
			if($bMain)
				$columns= $this->show;
			else
				$columns= $this->identification;

			foreach($columns as $content)
			{
				$otherTable= $this->getFkTable($content["column"]);
				//echo "table:<br />";
				//st_print_r($otherTable);echo "\n<br />";
				if( isset($otherTable) &&
				    $otherTable->correctTable() )
				{
					$otherFields= $otherTable->getSelectedFieldArray(false);
					//$fields= array_merge($otherFields, $fields);
					foreach($otherFields as $newField)
						$fields[]= $newField;
				}else
				{
				    $keyword= $this->sqlKeyword($content["column"]);
					if($keyword != false)
					{
						$field= array();
						$field["name"]= $content["column"];
						$field["flags"]= "";
						$field["type"]= $keyword['type'];
						$field["len"]= $keyword['len'];
					}else
						$field= $this->getColumnContent($content["column"]);
					if(isset($content["alias"]))
						$field["name"]= $content["alias"];
					else
						$field["name"]= $content["column"];
					$fields[]= $field;
				}
			}
			foreach($this->showTypes as $column=>$content)
			{
				if(isset($content["get"]))
				{
					$field= $this->getColumnContent($column);
					//$field["name"]= $content["alias"];
					$fields[]= $field;
				}
			}
			return $fields;
		}
		private function addFkDescription(array &$field)
		{
			// WARNING: if $bisColumn not defined as true, go endless into searchByColumn method
			$bIsColumn= true;
			$fk= &$this->isForeignKey($field['column'], $bIsColumn);
			if($fk)
			{// if the column have a foreign key to an other table
			 // search for the alias name in the identif-columns from this table
				$otherTable= $this->getFkTable($field['column'], $bIsColumn);

				$other= $otherTable->searchByIdentifColumn($fk["other"]);
				if($other)
				{
					//$other["column"]= $columnName;
					$other['join']= $fk['join'];
					$aRv["fk"]= $other; //return $other;//
				}
			}
		}
		function searchByColumn($columnName, int $warnFuncOutput= 0)
		{
		    if($warnFuncOutput>-1)
		        $warnFuncOutput++;
		    $columnName= $this->getDbColumnName($columnName, $warnFuncOutput);
			foreach($this->show as $field)
			{
				if($field["column"]==$columnName)
				{
					$aRv= $field;
					if(isset($field["type"]))
						$aRv["get"]= $field["type"];
					else
						$aRv["get"]= "select";
					$aRv["type"]= "column";
					$this->addFkDescription($aRv);
					return $aRv;
				}
			}
			foreach($this->showTypes as $c=>$columns)
			{
				if($c==$columnName)
				{
					foreach($columns as $type=>$a)
					{
						if($type==="get")
						{
        					$aRv= array();
        					$aRv["table"]= $this->Name;
        					$aRv["column"]= $columnName;
        					$aRv["alias"]= $this->Name."@".$columnName;
        					$aRv["type"]= "column";
        					$aRv["get"]= "get";
							$this->addFkDescription($aRv);
        					return $aRv;
						}
					}
				}
			}
			if(isset($this->columns))
			{
				foreach($this->columns as $field)
				{
					if($field["name"]==$columnName)
					{
						$aRv= array();
						$aRv["table"]= $this->Name;
						$aRv["column"]= $columnName;
						$aRv["alias"]= $columnName;//"unknown";
						$aRv["type"]= "column";
						$aRv["get"]= false;
						$this->addFkDescription($aRv);
						// do not ask isIdentifColumn(),
						// because it asks findAliasOrColumn()
						// and this searchByColumn()
						foreach($this->identification as $column)
	        			{
	        				if($column["column"]==$columnName)
	        				{
	        					$aRv["get"]= "identif";
	        					if(isset($column["alias"]))
									$aRv["alias"]= $column["alias"];
	        					else
	        						$aRv["alias"]= $columnName;
	        					break;
	        				}
	        			}
						return $aRv;
					}
				}
			}
			return null;
		}
		/**
		 * search whether alias is a defined alias name
		 * or elsewhere a correct database column
		 *
		 * @param string $alias name of alias column
		 * @return array
		 */
		public function findAliasOrColumn(string $alias, int $warnFuncOutput= 1)
		{
		    return $this->findColumnAlias($alias, /*firstAlias*/true, $warnFuncOutput);
		}
		/**
		 * search whether column correct database column
		 * or elsewhere a defined alias name
		 *  
		 * @param string $column name of column
		 * @return array
		 */
		public function findColumnOrAlias(string $column, int $warnFuncOutput= 1)
		{
			return $this->findColumnAlias($column, /*firstAlias*/false, $warnFuncOutput);
		}
		/**
		 * search whether alias is a defined alias name
		 * or elsewhere a correct database column
		 * 
		 * @param string $name name of column or alias
		 * @param bool $firstAlias whether should search first for alias name (true) elsewhere for database column (false)
		 * @return array
		 */
		private function findColumnAlias(string $name, bool $firstAlias= false, int $warnFuncOutput= 0)
		{
		    STCheck::param($name, 0, "string");
		    
			$field= null;
			if($firstAlias)
			    $field= $this->searchByAlias($name, /*no warning*/-1);
			if(!$field)
				$field= $this->searchByColumn($name, /*no warning*/-1);
			if(!$field && !$firstAlias)
				$field= $this->searchByAlias($name, $warnFuncOutput+1);
			// alex 19/09/2005: keine Warnung! wegen aliasTable
			//STCheck::is_warning(!$field, "findAliasOrColumn()", "column ".$name." is not declared in table ".$this->Name);
			if(!$field)
			{
				STCheck::flog("creating unknown field");
				$field= array();
				$field["table"]= "unknown";
				$field["column"]= $name;
				$field["alias"]= $name;
				$field["type"]= "not found";
			}
			return $field;
		}
		function haveColumn($column, $caseSensitive= true)
		{
			$preg_string= "/$column/";
			if(!$caseSensitive)
				$preg_string.= "i";
			foreach($this->columns as $field)
			{
				if(preg_match($preg_string, $field["name"]))
					return $field["name"];
			}
			return false;
		}
		/**
		 * Add for input tags the onchange event.<br />
		 * This need also to add some javascript function into the body.
		 * 
		 * @param string $column name of column
		 * @param string $function javascript function name
		 */
		public function onFocus(string $column, string|jsFunction $function)
		{
			$field= $this->findColumnOrAlias($column);
			$this->aEvents[$field['column']]['onFocus']= $function;
		}
		/**
		 * Add for input tags the onchange event.<br />
		 * This need also to add some javascript function into the body.
		 * 
		 * @param string $column name of column
		 * @param string $function javascript function name
		 */
		public function onSelect(string $column, string|jsFunction $function)
		{
			$field= $this->findColumnOrAlias($column);
			$this->aEvents[$field['column']]['onSelect']= $function;
		}
		/**
		 * Add for input tags the onchange event.<br />
		 * This need also to add some javascript function into the body.
		 * 
		 * @param string $column name of column
		 * @param string $function javascript function name
		 */
		public function onInput(string $column, string|jsFunction $function)
		{
			$field= $this->findColumnOrAlias($column);
			$this->aEvents[$field['column']]['onInput']= $function;
		}
		/**
		 * Add for input tags the onchange event.<br />
		 * This need also to add some javascript function into the body.
		 * 
		 * @param string $column name of column
		 * @param string $function javascript function name
		 */
		public function onChange(string $column, string|jsFunction $function)
		{
			$field= $this->findColumnOrAlias($column);
			$this->aEvents[$field['column']]['onChange']= $function;
		}
		/**
		 * Add for input tags the onchange event.<br />
		 * This need also to add some javascript function into the body.
		 * 
		 * @param string $column name of column
		 * @param string $function javascript function name
		 */
		public function onBlur(string $column, string|jsFunction $function)
		{
			$field= $this->findColumnOrAlias($column);
			$this->aEvents[$field['column']]['onBlur']= $function;
		}
		function onChangeRefresh($column)
		{
			$field= $this->findColumnOrAlias($column);
			$this->aRefreshes[$field["column"]]= "refresh";
		}
		function isSelect($columnName, $alias= null)
		{
			$bRv= false;
			if($alias===null)
			{
				$field= $this->findAliasOrColumn($columnName);
				$columnName= $field["column"];
			}
			foreach($this->show as $column)
			{
				if(	$column["column"]==$columnName
					and
					(	$alias===null
						or
						$column["alias"]==$alias	)	)
				{
					$bRv= true;
					break;
				}
			}
			return $bRv;
		}
		function isIdentifColumn($columnName, $alias= null)
		{
			$bRv= false;
			if($alias===null)
			{
				$field= $this->findAliasOrColumn($columnName);
				$columnName= $field["column"];
			}
			foreach($this->identification as $column)
			{
				if(	$column["column"]==$columnName
					and
					(	$alias===null
						or
						$column["alias"]==$alias	)	)
				{
					$bRv= true;
					break;
				}
			}
			return $bRv;
		}
		function unSelect($columnName, $tableName= "")
		{
			$field= $this->findAliasOrColumn($columnName);
			$columnName= $field["column"];
			foreach($this->show as $key=>$column)
			{
				if(	$column["column"]==$columnName
					and
					(	$column["table"]==$tableName
						or
						!$tableName					)	)
				{
					unset($this->show[$key]);
				}
			}
		}
	function getSelectedColumns()
	{
		if(!$this->bDisplaySelects)
			return array();
		if( isset($this->show) &&
			count($this->show)		)
		{
			return $this->show;
		}
		if(!isset($this->columns))
			return array();// own object is an empty table (STBaseTable)
		foreach($this->columns as $column)
		{
  			$aColumn["table"]= $this->Name;
  			if(isset($column["name"]))
  			{
	  			$aColumn["column"]= $column["name"];
	  			$aColumn["alias"]= $column["name"];
	  			
  			}else if(STCheck::isDebug())
  			{
  				echo "undefined column inside table ".$this->Name."<br />";
  				echo __FILE__." ".__LINE__."<br />";  				
  			}  			
  			$this->show[]= $aColumn;
		}
		return $this->show;
	}
	function displaySelects($bDisplay)
	{
		$this->bDisplaySelects= $bDisplay;
	}
		public function clearSelects()
		{
		    $this->clearSelectColumns();
		    $this->clearGetColumns();
		}
		public function clearSelectColumns()
		{
		    $this->abOrigChoice["select"]= true;
		    foreach($this->show as $key=>$column)
		    {
		        if($column['type'] == "select")
		            unset($this->show[$key]);
		    }
		}
		function clearNoFkSelects()
		{
			foreach($this->show as $key=>$content)
			{
				$tableName= $this->getFkTableName($content["column"]);
				if(!$tableName)
					unset($this->show[$key]);
			}
		}
		function clearRekursiveNoFkSelects()
		{
			foreach($this->show as $key=>$content)
			{
			    if(STCheck::isDebug("table"))
			     STCheck::echoDebug("table", "get FK table for column <b>".$content["column"]."</b>");
				$table= &$this->getFkTable($content["column"], true);
				if(	isset($table) &&
					$table->correctTable()	)
				{
					$table->clearRekursiveNoFkIdentifColumns();
					unset($table);
				}else
					unset($this->show[$key]);
			}
		}
		function noColumnSelects()
		{
			$this->clearSelects();
			$this->clearIdentifColumns();
			$this->bColumnSelects= false;
		}
		function clearNoFkIdentifColumns()
		{
			$bExists= false;
			foreach($this->identification as $key=>$content)
			{
				$tableName= $this->getFkTableName($content["column"]);
				if(!$tableName)
					unset($this->show[$content["column"]]);
			}
			if(!$bExists)
				$this->bDisplayIdentifs= false;
		}
		function clearRekursiveNoFkIdentifColumns()
		{
			$keys= array();
			$bExists= false;
			foreach($this->identification as $key=>$content)
			{
				$table= &$this->getFkTable($content["column"], true);
				if( isset($table) &&
				    $table->correctTable()  )
				{
					$table->clearRekursiveNoFkIdentifColumns();
					unset($table);
					$bExists= true;
				}else
				{
					unset($this->identification[$key]);
				}
			}
			/*foreach($keys as $key)
			{

			}*/
			if(!$bExists)
				$this->bDisplayIdentifs= false;
		}
		public function clearIdentifColumns()
		{
		    $this->abOrigChoice["identif"]= true;
			$this->identification= array();
		}
		function clearFKs()
		{
			$this->FK= array();
			$this->aFks= array();
			$this->aBackJoin= array();
		}
		function clearAliases()
		{
			$show= array();
			foreach($this->show as $column)
			{
				$new= array();
				$new["table"]= $column["table"];
				$new["column"]= $column["column"];
				$show[]= $new;
			}
			$this->show= $show;
		}
		public function identifColumn(string $column, string $alias= null)
		{
			Tag::alert(!$this->validColumnContent($column), "STBaseTable::identifColumn()", "column '$column' not exist in table ".$this->Name, 1);

			$column= $this->getDbColumnName($column);
			if(	!isset($this->abOrigChoice["identif"]) ||
				$this->abOrigChoice["identif"]	== true		)
			{
				$this->identification= array();
			}
			$count= count($this->identification);
			$this->identification[$count]= array();
			$this->identification[$count]["column"]= $column;
			if($alias)
				$this->identification[$count]["alias"]= $alias;
			$this->identification[$count]["table"]= $this->getName();
			$this->abOrigChoice["identif"]= false;
		}
		public function getIdentifColumns()
		{
			if(!$this->bDisplayIdentifs)
				return array();
			if(!count($this->identification))
			{
				$ar= array("column"=>$this->getPkColumnName(), "table"=>$this->getName());
				return array($ar);
			}
			return $this->identification;
		}
		public function displayIdentifs(bool $bDisplay= true)
		{
			$this->bDisplayIdentifs= $bDisplay;
		}
		public function showNameOverList($show)
		{
			$this->bShowName= $show;
		}
		public function andWhere($stwhere)
		{
		 	Tag::paramCheck($stwhere, 1, "STDbWhere", "string", "empty(string)", "null");
		 	
			return $this->where($stwhere, "and");
		}
		function orWhere($stwhere)
		{
		 	Tag::paramCheck($stwhere, 1, "STDbWhere", "string", "empty(string)", "null");
		 	
			return $this->where($stwhere, "or");
		}
		public function where($stwhere, string $operator= "")
		{
		 	STCheck::parameter($stwhere, 1, "STDbWhere", "string", "empty(string)", "null");
		 	STCheck::parameter($operator, 2, "check", $operator === "", $operator == "and", $operator == "or");

		 	if(STCheck::isDebug("db.statements.where"))
		 	{
		 	    if( !isset($this->oWhere) ||
		 	        $operator == ""        )
		 	    {
		 	        $msg= "set ";
		 	        if($operator == "")
		 	            $msg.= "<b>new</b> ";
		 	    }else
		 	        $msg= "add ";		 	    
		 	    $msg.= "where clause ";
		 	    if(is_string($stwhere))
		 	        $msg.= "'$stwhere' ";
	 	        $msg.= "inside table '".$this->Name."(".$this->ID.")'";
	 	        $space= STCheck::echoDebug("db.statements.where", $msg);
	 	        if(!is_string($stwhere))
	 	            st_print_r($stwhere,10, $space);
	 	        if($operator == "")
	 	            STCheck::echoDebug("db.statements.where", "no operator for where method be set, so clear all old where clauses");
		 	}
			if(	!isset($stwhere) ||
				$stwhere == null ||
				$stwhere == ""	)
			{
				return $this->oWhere;
			}
			// remove all pre-defined where clauses
			// if define new fresh where clause (without operator)
			// to create sql statement new
			if($operator == "")
				$this->clearWhere();

		 	if(	!isset($stwhere) ||
				$stwhere == null ||
				$stwhere == ""	||
				(	is_object($stwhere) &&
					get_class($stwhere) == "STDbWhere" &&
					!$stwhere->isModified()					)	)
		 	{
		 	    STCheck::echoDebug("db.statements.where", "get undefined (or not modified) ".
		 	        "where clause, so return only current where Object");
		 		return $this->oWhere;
		 	}
		 	if($operator == "")
		 		unset($this->oWhere);

	 		if(	isset($this->oWhere) &&
	 			(	is_string($stwhere) ||
	 				$this->oWhere->isModified()	)	)
	 		{
    	 		if(STCheck::isDebug("db.statements.where"))
    	 		{
    	 		    showLine();
    	 		    echo "Name:".$this->Name."<br>";
    	 		    echo "where Name:".$this->oWhere->table()."<br>";
    	 		}
	 			// 1. parameter is an string or STDbWhere object
 				if(	is_string($stwhere) ) // &&
//                  2023/06/16 alex
//                  remove question because if where clause is string
//                  implementation should always for current table
// 					$this->Name != $this->oWhere->table()	)
 				{
 				    $stwhere= new STDbWhere($stwhere, $this->Name);
 				    if(STCheck::isDebug("db.statements.where"))
 				    {
 				        showLine();
 				        st_print_r($stwhere,10);
 				    }
 				}
 				if(	is_object($stwhere) &&
 					$stwhere->table() == ""	)
 				{
 					$stwhere->table($this->Name);
 				}
	 			if($operator == "or") {
	 				$this->oWhere->orWhere($stwhere);
	 			}else // operator can be "and" or ""
	 			    $this->oWhere->andWhere($stwhere);
 			    if(STCheck::isDebug("db.statements.where"))
 			    {
 			        showLine();
 			        st_print_r($this->oWhere,10);
 			    }
	 		}else
	 		{// no where be set
	 		    $this->oWhere= new STDbWhere();
	 		    $this->oWhere->table($this);
	 		    $this->oWhere->where($stwhere, $operator);
	 		}
	 		
		 	return $this->oWhere;
		}
		function clearWhere()
		{
			$this->oWhere= null;
			$this->aStatement['where']= null;
			$this->aStatement['whereAlias']= null;
			$this->aStatement['full']= null;
		}
		public function getWhere()
		{
			if(!isset($this->oWhere))
				return null;
			return $this->oWhere;
		}
		function getName() : string
		{
			return $this->Name;
		}
		function getContent()
		{
			return $this->columns;
		}
		function getColumnContent($columnName)
		{
			foreach($this->columns as $content)
			{
				if($content["name"]===$columnName)
					return $content;
			}
			return null;
		}
		function getPkColumnName()
		{
			if(	isset($this->sPKColumn) &&
				$this->sPKColumn			)
			{
				return $this->sPKColumn;
			}
			if(isset($this->columns))
			{
				foreach($this->columns as $column)
				{
					if(preg_match("/.*primary_key.*/i", $column["flags"]))
					{
						$this->sPKColumn= $column["name"];
						return $column["name"];
					}
				}
			}
			return false;
		}
		function getErrorText()
		{
			return $this->errorText;
		}
		function isError()
		{
			return $this->error;
		}
		function upload($column, $toPath, $type, $byte= 0, $width= 0, $height= 0)
		{
			$incomming= $toPath;
			if(substr($toPath, 0, 1)=="/")
			{
				$path= $_SERVER["SCRIPT_FILENAME"];
				$path= substr($path, 0, strlen($path)-strlen($_SERVER["SCRIPT_NAME"]));
				$incomming= $path.$toPath;
			}
			Tag::alert(!is_dir($incomming), "STBaseTable::upload", "path ".$toPath." not exist");
			$field= $this->findAliasOrColumn($column);
			$column= $field["alias"];
			$field= array();
			$field["size"]= $byte;
			$field["type"]= $type;
			$field["path"]= $toPath;
			if($width!=0)
				$field["width"]= $width;
			if($height!=0)
				$field["height"]= $height;
			if(!isset($this->showTypes[$column]))
				$this->showTypes[$column]= array();
			$this->showTypes[$column]["upload"]= $field;
		}
		function image($column, $toPath= null, $byte= 0, $width= 0, $height= 0)
		{
			$field= $this->findAliasOrColumn($column);
			$column= $field["alias"];
			if($toPath!==null)
			{
				$incomming= $toPath;
				if(substr($toPath, 0, 1)=="/")
				{
					$path= $_SERVER["SCRIPT_FILENAME"];
					$path= substr($path, 0, strlen($path)-strlen($_SERVER["SCRIPT_NAME"]));
					$incomming= $path.$toPath;
				}
				Tag::alert(!is_dir($incomming), "STBaseTable::image", "path ".$toPath." not exist");
				$this->upload($column, $toPath, "image/gif,image/pjpeg", $byte, $width, $height);
			}
			$this->showTypes[$column]["image"]= $field;
		}
		function noUnlinkData($column)
		{
			$field= $this->findAliasOrColumn($column);
			$column= $field["alias"];
			$this->aUnlink[$column]= false;
		}
		// alex 19/04/2005:	$address darf auch ein Tabellen-Name sein
		function imageLink($column, $toPath= null, $byte= 0, $width= 0, $height= 0, $address= null)
		{
			Tag::paramCheck($column, 1, "string");
			Tag::paramCheck($toPath, 2, "string", "null", "STBaseTable", "STObjectContainer");
			Tag::paramCheck($byte, 3, "int");
			Tag::paramCheck($width, 4, "int");
			Tag::paramCheck($height, 5, "int");
			Tag::paramCheck($address, 6, "string", "null", "STBaseTable", "STObjectContainer");

			$this->imageLinkA($column, null, $toPath, $byte, $width, $height, $address, false);
		}
		function imageBorderLink($column, $toPath= null, $byte= 0, $width= 0, $height= 0, $address= null)
		{
			Tag::paramCheck($column, 1, "string");
			Tag::paramCheck($toPath, 2, "string", "null", "STBaseTable", "STBaseContainer");
			Tag::paramCheck($byte, 3, "int");
			Tag::paramCheck($width, 4, "int");
			Tag::paramCheck($height, 5, "int");
			Tag::paramCheck($address, 6, "string", "null", "STBaseTable", "STBaseContainer");

			$this->imageLinkA($column, null, $toPath, $byte, $width, $height, $address, true);
		}
		function imageLinkA($column, $valueColumn, $toPath, $byte, $width, $height, $address, $border)
		{
			if($address==="")
				$address= null;
			if(typeof($toPath, "STBaseTable", "STBaseContainer"))
			{
				$address= $toPath;
				$toPath= null;
			}
			if($toPath)
				$this->upload($column, $toPath, "image/gif,image/pjpeg", $byte, $width, $height);
			$extraField= "imagelink";
			if($border)
				$extraField.= "1";
			else
				$extraField.= "0";
			$this->linkA($extraField, $this->Name, array("column"=>$column), $address, $valueColumn);
		}
		function imagePkLink($column, $toPath= null, $byte= 0, $width= 0, $height= 0, $address= null)
		{
			Tag::paramCheck($column, 1, "string");
			Tag::paramCheck($toPath, 2, "string", "null", "STBaseTable", "STBaseContainer");
			Tag::paramCheck($byte, 3, "int");
			Tag::paramCheck($width, 4, "int");
			Tag::paramCheck($height, 5, "int");
			Tag::paramCheck($address, 6, "string", "null", "STBaseTable", "STBaseContainer");

			$this->imageLinkA($column, $this->sPKColumn, $toPath, $byte, $width, $height, $address, false);
		}
		function imageBorderPkLink($column, $toPath= null, $byte= 0, $width= 0, $height= 0, $address= null)
		{
			Tag::paramCheck($column, 1, "string");
			Tag::paramCheck($toPath, 2, "string", "null", "STBaseTable", "STBaseContainer");
			Tag::paramCheck($byte, 3, "int");
			Tag::paramCheck($width, 4, "int");
			Tag::paramCheck($height, 5, "int");
			Tag::paramCheck($address, 6, "string", "null", "STBaseTable", "STBaseContainer");

			$this->imageLinkA($column, $this->sPKColumn, $toPath, $byte, $width, $height, $address, true);
		}
		function imageValueLink($column, $valueColumn, $toPath= null, $byte= 0, $width= 0, $height= 0, $address= null)
		{
			Tag::paramCheck($column, 1, "string");
			Tag::paramCheck($valueColumn, 2, "string");
			Tag::paramCheck($toPath, 3, "string", "null", "STBaseTable", "STBaseContainer");
			Tag::paramCheck($byte, 4, "int");
			Tag::paramCheck($width, 5, "int");
			Tag::paramCheck($height, 6, "int");
			Tag::paramCheck($address, 7, "string", "empty(string)", "null");

			$this->imageLinkA($column, $valueColumn, $toPath, $byte, $width, $height, $address, false);
		}
		function imageBorderValueLink($column, $valueColumn, $toPath= null, $byte= 0, $width= 0, $height= 0, $address= null)
		{
			Tag::paramCheck($column, 1, "string");
			Tag::paramCheck($valueColumn, 2, "string");
			Tag::paramCheck($toPath, 3, "string", "null", "STBaseTable", "STBaseContainer");
			Tag::paramCheck($byte, 4, "int");
			Tag::paramCheck($width, 5, "int");
			Tag::paramCheck($height, 6, "int");
			Tag::paramCheck($address, 7, "string", "empty(string)", "null");

			$this->imageLinkA($column, $valueColumn, $toPath, $byte, $width, $height, $address, true);
		}
	function download($columnName, $access= null)
	{
		Tag::paramCheck($columnName, 1, "string");
		Tag::paramCheck($access, 2, "string", "null");

		$field= $this->findAliasOrColumn($columnName);
		$columnName= $field["column"];
		$this->linkA("download", $this->Name, array("column"=>$columnName), null, $this->sPKColumn);
	}
	public function disabled($columnName, $enum= null)
	{
		Tag::paramCheck($columnName, 1, "string");
		Tag::paramCheck($enum, 2, "string", "null");

		$field= $this->findAliasOrColumn($columnName);
		$this->linkA("disabled", $this->Name, array( "alias"=>$field['alias']), null, $enum);
	}
	public function isDisabled(string $columnName)
	{
	    $field= $this->findAliasOrColumn($columnName);
	    if($field === false)
	        $aliasColumn= $columnName;
	    else
	        $aliasColumn= $field['alias'];
	    if(isset($this->showTypes[$aliasColumn]["disabled"]))
	        return true;
	    return false;
	}
	function changeFormOptions($submitButton, $formName= "st_checkForm", $action= null)
	{
		$this->asForm= array(	"button"=>	$submitButton,
								"form"=>	$formName,
								"action"=>	$action			);
	}
	function checkBox(string $columnName, $trueValue= false, $notSet= null)
	{
	    STCheck::param($trueValue, 1, "bool");
	    STCheck::alert(isset($notSet), "STBaseTable::checkBox()", 
	        "third parameter should be always NULL, only defined for extended class STDbSelector");

		$field= $this->findAliasOrColumn($columnName);
		$this->linkA("check", $this->Name, array("column"=>$columnName), null, $this->sPKColumn);
		$this->aCheckDef[$field["alias"]]= $trueValue;
	}
	// alex 08/06/2005:	alle links in eine Funktion zusammengezogen
	//					und $address darf auch ein STObjectContainer,
	// 					für die Verlinkung auf eine neuen Container, sein
	/**
	 * specify a column to select from database and whether how to display
	 * 
	 * @param string $which what to do with the selection
	 *                 check           - column with checkboxes
	 *                 get             - only selection from database, but no display on STListBox
	 *                 disabled        -
	 *                 dropdown        -
	 *                 namedlink       -
	 *                 namedcolumnlink -
	 *                 download        -     
	 * @param string $tableName name of the used table            
	 * @param array $column array with column and alias name
	 * @param object $address can be an address which should link to, or an STBaseContainer to which link should set
	 * @param string $valueColumn pre defined link when selection should be disabled
	 */ 
	protected function linkA(string $which, string $tableName, array $column, $address= null, string $valueColumn= null)
	{
	    if(STCheck::isDebug())
	    {
	        STCheck::param($which, 0, "string");
	        STCheck::param($tableName, 1, "string");
    		STCheck::param($address, 3, "string", "STBaseContainer", "STBaseTable", "null");
    		STCheck::param($valueColumn, 4, "string", "null");
	    }

	    if( !isset($column['alias']) ||
	        trim($column['alias']) == ""   )
	    {
	        $column['alias']= $column['column'];
	        
	    }elseif(!isset($column['column']) ||
	            trim($column['column']) == ""   )
	    {
	        $column['column']= $column['alias'];
	    }
	    $aColumn= array();
	    $desc= STDbTableDescriptions::instance($this->db->getDatabaseName());
	    $tableName= $desc->getTableName($tableName);
	    if($tableName != $this->Name)
	    {
	        $oTable= $this->getTable($tableName);
	        $field= $oTable->findAliasOrColumn($column['alias']);
	        if($field['type'] == "not found")
	        {
	            $field= $oTable->findColumnOrAlias($column['column']);
	            $field['alias']= $column['alias'];
	        }
	    }else
	    {
	        $field= $this->findAliasOrColumn($column['alias']);
	        if($field['type'] == "not found")
	        {
	            $field= $this->findColumnOrAlias($column['column']);
	            $field['alias']= $column['alias'];
	        }
	    }
		$aliasColumn= $field["alias"];
		if(!isset($this->showTypes[$aliasColumn]))
			$this->showTypes[$aliasColumn]= array();
		$to= $which;
		if(typeof($address, "STBaseTable"))
		{// wenn ein AliasTabel hereinkommt
		 // diesen in einen Container verpacken
			$tableName= $address->getName();
			// alex 21/10/2005:	$address auf $to ge�ndert
			//					und $address null zugewiesen
			// ?????????????????????????????????????????????????????
			// nicht DEBUGGED
			$to= new STObjectContainer("dbtable ".$tableName, $this->db);
			$to->needTable($tableName, true);
			$address= null;
		}elseif(typeof($address, "STBaseContainer"))
		{// ist die Addresse schon ein Container
		 // nimm die Referenz aus der Containerliste

			$which= "container_".$which;
			$to= &STBaseContainer::getContainer($address->getName());
			$address= null;
		}else //if(!preg_match("/^container_.*/", $which))
		{// ist die Addresse keine Tabelle oder Container
		 // k�nnte es noch sein, dass sie ein Name eines Containers ist
			$containers= STBaseContainer::getAllContainerNames();
			foreach($containers as $name)
			{
				if($name==$address)
				{
					$which= "container_".$which;
					$to= &STBaseContainer::getContainer($name);
					$address= null;
					break;
				}
			}
		}
		if($which=="disabled")
		{
			$this->showTypes[$aliasColumn][$which][]= $valueColumn;
			$valueColumn= null;	
		}elseif($which=="get")
		{
		    $aColumn= array();
	        $aColumn["table"]= $tableName;
	        $aColumn["column"]= $field['column'];
	        $aColumn["alias"]= $field['alias'];
	        $aColumn['type']= "get";
	        $aColumn["nextLine"]= true;
	        $this->show[]= $aColumn;
		}else
			$this->showTypes[$aliasColumn][$which]= $to;
		if($valueColumn)
		{
		    if( !isset($this->showTypes["valueColumns"]) || !is_array($this->showTypes["valueColumns"]))
				$this->showTypes["valueColumns"]= array();
			$this->showTypes["valueColumns"][$aliasColumn]= $valueColumn;
			$field= $this->findAliasOrColumn($valueColumn);
			$bFound= false;
			foreach($this->show as $showfield)
			{
			    if($showfield["alias"]==$field["alias"])
				{
					$bFound= true;
					break;
				}
			}
			if(!$bFound)
			{
				$this->showTypes[$valueColumn]= array();
				$this->showTypes[$valueColumn]["get"]="get";
			}

		}
		$this->aLinkAddresses[$aliasColumn]= $address;
		//echo "LinkAddresses:";st_print_r($this->aLinkAddresses,2);
		//echo "showTypes:";st_print_r($this->showTypes,2);
	}
	function activeColumnLink($alias, $representColumnValue= null)
	{
		$this->aActiveLink["column"]= $alias;
		$this->aActiveLink["represent"]= $representColumnValue;
	}
	/**
	 * define column with link to address
	 * where the name always the alias name of the column.<br />
	 * The parameter url limitation stget[limit] will be set to table with column and content
	 * 
	 * @param string $alias alias or column name of column
	 * @param object $address can be an url string, STBasetable or STBaseContainer object.<br />
	 *                        also the name of the container or table can be given.<br />
	 *                        if address is null it will be set the limitation but to the same current container
	 */
	public function link(string $alias, $address= null)
	{
	    $this->linkA("link", $this->Name, array("alias"=>$alias), $address, null);
	}
	/**
	 * define column with link to address
	 * where the name is the content of the column.<br />
	 * The parameter url limitation stget[limit] will be set to table with column and content
	 * 
	 * @param string $alias alias or column name of column
	 * @param object $address can be an url string, STBasetable or STBaseContainer object.<br />
	 *                        also the name of the container or table can be given.<br />
	 *                        if address is null it will be set the limitation but to the same current container
	 */
	public function namedLink(string $alias, $address= null)
	{
	    $this->linkA("namedlink", $this->Name, array("alias"=>$alias), $address);
	}
	/**
	 * 
	 * define column with link to address
	 * where the name is the content of the column.<br />
	 * The parameter url limitation stget[limit] will be set to table
	 * with column and content from second parameter $valueColumn
	 * 
	 * @param string $aliasColumn alias or column name of column
	 * @param string $valueColumn the alias or column name for limitation.<br />
	 *                            (but limitation name is always the column name)
	 * @param object $address can be an url string, STBasetable or STBaseContainer object.<br />
	 *                        also the name of the container or table can be given.<br />
	 *                        if address is null it will be set the limitation but to the same current container
	 */
	public function namedColumnLink(string $aliasColumn, string $valueColumn, $address= null)
	{
		if(!$valueColumn)
		    $valueColumn= $aliasColumn;
		$this->linkA("namedcolumnlink", $this->Name, array("alias"=>$aliasColumn), $address, $valueColumn);
	}
	/**
	 * define column with link to address
	 * where the name is the content of the column.<br />
	 * The parameter url limitation stget[limit] will be set to table 
	 * with column and content from primary key
	 * 
	 * @param string $aliasColumn alias or column name of column
	 * @param object $address can be an url string, STBasetable or STBaseContainer object.<br />
	 *                        also the name of the container or table can be given.<br />
	 *                        if address is null it will be set the limitation but to the same current container
	 */
	public function namedPkLink(string $aliasColumn, $address= null)
	{
		$this->namedColumnLink($aliasColumn, $this->sPKColumn, $address);
	}
	/**
	 * select column, do not calculate foreign keys by statement
	 * and not display inside STListBox or STItemBox
	 * 
	 * @param string $column name of column at current table
	 * @param string $alias alias name of column
	 * @param string $unknown unknown parameter for compatibility to STDbSelector
	 */
	public function getColumn(string $column, string $alias= "", string $unknown= "")
	{
	    STCheck::param($column, 0, "string");
	    $nParams= func_num_args();
	    STCheck::lastParam(2, $nParams);
		
		if($alias == "")
		    $alias= $column;
		$this->getColumnA($this->Name, array( "column"=>$column, "alias"=>$alias));
	}
	/**
	 * select column, do not calculate foreign keys by statement
	 * and not display inside STListBox or STItemBox
	 * 
	 * @param string $tableName name of table where column exist
	 * @param array $column array with column and alias
	 */
	protected function getColumnA(string $tableName, array $column)
	{
		if(	!isset($this->abOrigChoice["get"]) ||
		    $this->abOrigChoice["get"] == true   )   
		{
		    foreach($this->show as $key => $fields)
		    {
		        if($fields['type'] == "get")
		            unset($this->show[$key]);
		    }
		    $this->abOrigChoice["get"]= false;
		}
		$this->linkA("get", $tableName, $column, null, null);
	}
	public function clearGetColumns()
	{
	    $this->abOrigChoice['get']= true;
	    foreach($this->show as $key=>$column)
	    {
	        if($column['type'] == "get")
	        {
	            if( !$this->bIsNnTable ||
	                substr($column['alias'], 0, 5) != "join@"  )
	            {
	                unset($this->show[$key]);
	            }
	        }
	    }
	}
	function clearRekursiveGetColumns($bFromIdentif= false)
	{
		$this->clearGetColumns();
		if($bFromIdentif)
			$from= &$this->identifications;
		else
			$from= &$this->show;
    	foreach($from as $key=>$content)
    	{
    		$table= &$this->getFkTable($content["column"], true);
    		if(	isset($table) &&
    		    $this->Name != $table->getName() &&
				$table->correctTable()				)
    		{
    			$table->clearRekursiveGetColumns();
    			unset($table);
    		}
    	}
	}
	function clearLinkColumn($column)
	{
		$field= $this->findAliasOrColumn($column);
		$aliasColumn= $field["alias"];
		unset($this->showTypes[$aliasColumn]);
	}
	function dropDownSelect($aliasColumn, $callbackFunction)
	{
	    $this->linkA("dropdown", $this->Name, array("alias"=>$alias), "st_callbackFunction", null);
		$this->joinCallback($callbackFunction, $aliasColumn);
	}
	public function listCallback($callbackFunction, $alias= null)
	{
	    if(STCheck::isDebug())
	    {
	        STCheck::param($callbackFunction, 0, "string");
	        STCheck::param($alias, 1, "string", "empty(string)", "null");
	    }
	    
	    $struct['action']= STLIST;
	    $struct['function']= $callbackFunction;
	    if( isset($alias) &&
	        trim($alias) != ""   )
	    {
	        $struct['column']= $alias;
	    }
	    $this->callbackA($struct);
	}
	public function insertCallback(string $callbackFunction, string $alias= null)
	{
	    $struct['action']= STINSERT;
	    $struct['function']= $callbackFunction;
	    if( isset($alias) &&
	        trim($alias) != ""   )
	    {
	        $struct['column']= $alias;
	    }
	    $this->callbackA($struct);
	}
	public function updateCallback(string $callbackFunction, string $alias= null)
	{
	    $struct['action']= STUPDATE;
	    $struct['function']= $callbackFunction;
	    if( isset($alias) &&
	        trim($alias) != ""   )
	    {
	        $struct['column']= $alias;
	    }
	    $this->callbackA($struct);
	}
	public function indexCallback(string $callbackFunction)
	{
	    $struct['action']= STLIST;
	    $struct['function']= $callbackFunction;
	    $this->callbackA($struct);
	}
	public function deleteCallback(string $callbackFunction)
	{
	    $struct['action']= STDELETE;
	    $struct['function']= $callbackFunction;
	    $this->callbackA($struct);
	}
	public function joinCallback($callbackFunction, $alias= null)
	{
	    $struct['action']= "join";
	    $struct['function']= $callbackFunction;
	    if( isset($alias) &&
	        trim($alias) != ""   )
	    {
	        $struct['column']= $alias;
	    }
	    $this->callbackA($struct);
	}
    protected function callbackA(array $struct)//$action, $columnName, $callbackFunction)
    {
		if(isset($struct['column']))
		{
		    $field= $this->findAliasOrColumn($struct['column']);
		    $column= $field["alias"];
		    $struct['column']= $column;
		}else
		    $column= $struct['action'];
		STCheck::alert(!function_exists($struct['function']), "STBaseTable::callback()",
		    "user defined function <b>".$struct['function']."()</b> does not exist<br />", 2);
		if(!isset($this->aCallbacks[$column]))
		    $this->aCallbacks[$column]= array();
	    $this->aCallbacks[$column][]= $struct;
    }
    public function clearCallbacks()
	{
		$this->aCallbacks= array();
	}
	public function &getFkTable($fromColumn, $bIsColumn= false)
	{
		STCheck::param($fromColumn, 0, "string");
		STCheck::param($bIsColumn, 1, "bool");
		
		if(!$bIsColumn)
		{
			$field= $this->findAliasOrColumn($fromColumn);
			$fromColumn= $field["column"];
		}
		foreach($this->aFks as $table=>$content)
		{
			foreach($content as $columns)
			{
				if($fromColumn==$columns["own"])
				{
					if(isset($columns["table"]))
						return $columns["table"];
					$oTable= &$this->getTable($table);
					return $oTable;
				}
			}
		}
		return $this->null;// /*incorrect table*/STBaseTable();;
	}
	function getFkTableName($fromColumn)
	{
		$field= $this->findAliasOrColumn($fromColumn);
		$fromColumn= $field["column"];
		foreach($this->aFks as $table=>$content)
		{
			foreach($content as $columns)
			{
				if($fromColumn==$columns["own"])
				{
					$name= $table;
					if(	isset($columns["table"]) &&
						typeof($columns["table"], "STDbTable")	)
					{
						if(typeof($this, "STDbTable"))
							$ownDb= $this->container->getDatabaseName();
						else
							$ownDb= "";
						$otherDb= $columns["table"]->container->getDatabaseName();
						if($ownDb!=$otherDb)
							$name= "$otherDb.$name";
					}
					return $name;
				}
			}
		}
		return null;
	}
	function getFkContent($fromColumn)
	{
		$field= $this->findAliasOrColumn($fromColumn);
		$fromColumn= $field["column"];
		foreach($this->aFks as $table=>$content)
		{
			foreach($content as $columns)
			{
				if($fromColumn==$columns["own"])
				{
					$tableName= $content["table"]->Name;
					unset($content["table"]);
					$content["table"]= $tableName;
					return $content;
				}
			}
		}
		return null;
	}
	function getFkContainerName($fromColumn)
	{
		$field= $this->findAliasOrColumn($fromColumn);
		$fromColumn= $field["column"];
		foreach($this->aFks as $table=>$content)
		{
			foreach($content as $columns)
			{
				if($fromColumn==$columns["own"])
				{
					if(	$columns["table"]
						and
						$columns["table"]->container->getDatabaseName()!==$this->container->getDatabaseName()	)
					{
						return $columns["table"]->container->getName();
					}
					return $this->container->getName();
				}
			}
		}
		return null;
	}
	function &getForeignKeys()
	{
		return $this->aFks;
	}
	/**
	 * get query string limitation
	 * consider foreign keys
	 * 
	 * @return array array of foreign key fields 
	 */
	function getForeignKeyModification()
	{
	    $query= new STQueryString();
	    $query= $query->getArrayVars();
	    if(isset($query["stget"]['limit']))
	        $query= $query["stget"]['limit'];
        else
            $query= array();
          
         
        $aRv= array();
        $fks= $this->getForeignKeys();
        foreach($fks as $table=>$aFkFields)
        {
            if(isset($query[$table]))//[$aColumnType["other"]]
                $aRv[$table]= $aFkFields;
        }
        return $aRv;
	}
	public function modifyQueryLimitation()
	{
	    STCheck::paramCheck($this, 1, "STDbTable");
	    
	    $tableMsg= "";
	    if(STCheck::isDebug())
	    {
            $tableMsg= "inside table ".$this->toString();
            if(isset($this->container))
                $tableMsg.= " from container <b>".$this->container->getName();
            $tableMsg.= "</b>";
	    }
	    
	    if($this->bModifiedByQuery)
	    {
	        if(STCheck::isDebug("db.statements.where"))
	        {
	            echo "<br />";
	            $msg[]= "table $this was <b>modificate</b> before";
	            $msg[]= "so do not again";
	            STCheck::echoDebug("db.statements.where", $msg);
	        }
	        return;
	    }
	    $query= new STQueryString();
	    $where= new STDbWhere();
	    if($this->bModifyFk)
	    {
    	    STCheck::echoDebug("db.statements.where", "create <b>foreign Key</b> modification $tableMsg");
    	    
            $fks= $this->getForeignKeyModification();
            if(STCheck::isDebug("db.statements.where"))
            {
                STCheck::echoDebug("db.statements.where", "modify foreign Keys $tableMsg");
                if(empty($fks))
                {
                    $need= "but need no";
                    $end= ", because no ";
                    if(!empty($this->aFks))
                        $end.= "important ";
                    $end.= "foreign Keys exist";
                }else
                {
                    $need= "need";
                    $end= ":";
                }
                $nIntented= STCheck::echoDebug("db.statements.where", "$need foreign Keys from query$end");
                showBackTrace();
                if(!empty($fks))
                    st_print_r($fks, 3, $nIntented);
                elseif(!empty($this->aFks))
                    st_print_r($this->aFks, 3, $nIntented);
                echo "<br />";
            }
            foreach($fks as $table=>$fields)
            {
                foreach($fields as $aColumnType)
                {
                    $limitation= $query->getLimitation($table);
                    foreach($limitation as $column=>$value)
                    {
                        if($aColumnType["other"]==$column)
                        {
                            $clausel= $aColumnType["other"]."=";
                            if(!is_numeric($value))
                                $value= "'".$value."'";
                            $clausel.= $value;
                            $iWhere= new STDbWhere($clausel);
                            $iWhere->table($table);
                            $iWhere->isFkModifyObj= true;
                            $where->andWhere($iWhere);
                        }else
                        {
                            $clausel= $column."=";
                            if(!is_numeric($value))
                                $value= "'".$value."'";
                            $clausel.= $value;
                            $iWhere= new STDbWhere($clausel);
                            $iWhere->table($table);
                            $iWhere->isFkModifyObj= true;
                            $where->andWhere($iWhere);
                        }
                        Tag::echoDebug("db.statements.where", "set where $clausel from FK");
                    }
                }
            }
	    }
	    if(STCheck::isDebug("db.statements.where"))
	    {
            $tableName= $this->getName();
            $limitation= $query->getLimitation($tableName);
            if( !$this->bLimitOwn ||
                empty($limitation)  )
            {
                $need= "and need no";
                if(!$this->bLimitOwn)
                    $end= ", because not allowed from outside";
                else
                    $end= ", because no limitation inside query found";
            }else
            {
                $need= "and need";
                $end= ":";
            }
            $nIntented= STCheck::echoDebug("db.statements.where", "$need limitation for own table$end");
            if( $this->bLimitOwn &&
                !empty($limitation) )
            {
                st_print_r($limitation, 3, $nIntented);
                echo "<br />";
            }
        }
        if($this->bLimitOwn)
        {
            $tableName= $this->getName();
            $limitation= $query->getLimitation($tableName);
            if(	isset($limitation) &&
                is_array($limitation)	)
            {
                $where->setDatabase($this->db);
                $where->table($tableName);
                foreach($limitation as $column=>$value)
                {
                    if($this->haveColumn($column))
                    {
                        if(!is_numeric($value))
                            $value= "'".$value."'";
                        Tag::echoDebug("db.statements.where", "set where $column=$value for own table");
                        $iWhere= new STDbWhere($column."=".$value);
                        $iWhere->table($this);
                        $iWhere->isOwnModifyObj= true;
                        $where->andWhere($iWhere);
                    }
                }
            }
        }
        if($where->isModified())
        {
            $iWhere= $this->getWhere();
            if($iWhere)
                $where->andWhere($iWhere);
            $this->where($where);
        }
        $this->bModifiedByQuery= true;
	}
	function addJoinLimitationByQuery(array $aAliasTables)
	{
	    $statement= "";
	    if($this->modify())
	    {
	        $ownTableName= $this->getName();
	        $query= new STQueryString();
	        $fks= $this->getForeignKeys();
	        $limitation= $query->getLimitation($ownTableName);
	        if(STCheck::isDebug("db.statements.where"))
	        {
	            $space= STCheck::echoDebug("db.statements.where", "query limitation of table $ownTableName:");
	            st_print_r($limitation, 5, $space);
	            echo "<br />";
	        }
	        if( isset($limitation) &&
	            count($limitation)     )
	        {// add limitation for allocated table
	            foreach($limitation as $columnName=>$content)
	            {
	                $statement.= " and ";
	                $statement.= $aAliasTables[$ownTableName].".$columnName=";
	                $statement.= "'$content'";
	            }
	        }
	        foreach($fks as $tableName=>$fields)
	        {// add limitation for foreign keys
	            $limitation= $query->getLimitation($tableName);
	            if(STCheck::isDebug("db.statements.where"))
	            {
	                $space= STCheck::echoDebug("db.statements.where", "from foreign key table $tableName:");
	                st_print_r($fields, 2, $space);
	                STCheck::echoDebug("db.statements.where", "is query limitation:");
	                st_print_r($limitation, 5, $space);
	                echo "<br />";
	            }
	            foreach($fields as $content)
	            {
	                // do not need limitation from own table
	                // when table points to him self
	                if( $tableName != $ownTableName &&  
	                    isset($limitation[$content['other']])          )
	                {// if content['table'] same as $tableName -> table link to his own table
	                 // and it should have not the same limitation as own table
	                    $statement.= " and ";
	                    $statement.= $aAliasTables[$ownTableName].".".$content['own']."=";
	                    $statement.= "'".$limitation[$content['other']]."'";
	                }
	            }
	        }
	        if(STCheck::isDebug())
	        {
	            if(STCheck::isDebug("db.statements.where"))
	                $debugstr= "db.statements.where";
	            else
	                $debugstr= "db.statements.table";
	            if($statement == "")
	                STCheck::echoDebug($debugstr, "no limitation from query be set (inside ".$this->toString().")");
                else
                    STCheck::echoDebug($debugstr, "add limitation \"$statement\" from query (inside ".$this->toString().")");
	        }
	    }elseif(STCheck::isDebug())
	    {
	        if(STCheck::isDebug("db.statements.where"))
	            $debugstr= "db.statements.where";
            else
                $debugstr= "db.statements.table";
	        STCheck::echoDebug($debugstr, "do not add limitation from query -> not allowed (inside ".$this->toString().")");
	    }
        return $statement;
	}
	// alex 08/06/2005:	nun k�nnen Werte auch Statisch in der
	//					STBaseTable gesetzt werden
	/**
	 * define values for column which preselect for inserts or updates.<br />
	 * should only used for an multible table container (STDbSelector)
	 * if it is defined as N to N table
	 * 
	 * @param string $columnName name of column
	 * @param string|int $value column content for preselect
	 * @param string $action for which action (STINSERT or STUPDATE) should used (default:STINSERT)
	 * @param null $unknown not used parameter (for compatibility STDbSelector)
	 */
	public function preSelect(string $columnName, $value, $action= null, $unknown= null)
	{
	    if(!isset($action))
	        $action= STINSERT;
	    if(STCheck::isDebug())
	    {
	        STCheck::param($value, 1, "string", "int");
	        STCheck::param($action, 2, "check", $action===null||$action==STINSERT||$action==STUPDATE, "STINSERT", "STUPDATE");
	    }
		$field= $this->findAliasOrColumn($columnName);
		$columnName= $field["column"];
		if(	!isset($this->aSetAlso[$columnName]) ||
			!is_array($this->aSetAlso[$columnName])	)
		{
			$this->aSetAlso[$columnName]= array();
		}
		if($action==STALLDEF)
		{
			$this->aSetAlso[$columnName][STINSERT]= $value;
			$this->aSetAlso[$columnName][STUPDATE]= $value;
		}else
 			$this->aSetAlso[$columnName][$action]= $value;
	}
	public function getDefaultValue(string $column, $action= STALLDEF)
	{
		STCheck::param($action, 2, "check", $action===STALLDEF||$action==STLIST||$action==STINSERT||$action==STUPDATE, "STALLDEF, STLIST, STINSERT", "STUPDATE");
		
		$field= $this->findColumnOrAlias($column);
		$column= $field['column'];
		if(isset($this->aSetAlso[$column][$action]))
			return $this->aSetAlso[$column][$action];
		if(isset($this->aSetAlso[$column][STALLDEF]))
			return $this->aSetAlso[$column][STALLDEF];
		$aNotNullField= null;
		foreach($this->columns as $field)
		{
			if($field['name'] == $column)
			{
				if(isset($field['default']))
					return $field['default'];
				$aNotNullField= $field;
				if(preg_match("/not_null/", $field['flags']))
					$bNotNull= true;
				break;
			}
		}
		if(	isset($aNotNullField) &&
			preg_match("/not_null/", $aNotNullField['flags']) &&
			preg_match("/enum/", $aNotNullField['flags'])			)
		{
			return $aNotNullField['enums'][0];
		}
		return null;
	}
	function setAlso($columnName, $value, $action= "All")
	{
		Tag::deprecated("STBaseTable::preSelect(columnName, value, action)", "STBaseTable::setAlso(columnName, value, action)");
		$this->preSelect($columnName, $value, $action);
	}
	// alex 08/06/2005:	und ebenso auch entfernt werden
	function unsetAlso($columnName, $action= "All")
	{
		$field= $this->findAliasOrColumn($columnName);
		$columnName= $field["column"];
		if($action=="ALL")
			unset($this->aSetAlso[$columnName]);
		else
 			unset($this->aSetAlso[$columnName][$action]);
	}
	function setLinkByNull($bSet= true)
	{
		$this->bSetLinkByNull= $bSet;
	}
	function radioButtonsByEnum($aliasName)
	{
		$field= $this->findAliasOrColumn($aliasName);
		$this->enumField[$field["column"]]= "radio";
		$this->onlyRadioButtons[$field["column"]]= true;
	}
	function pullDownMenuByEnum($aliasName)
	{
		$field= $this->findAliasOrColumn($aliasName);
		$this->enumField[$field["column"]]= "pull_down";
	}
	/**
	 * Specific GUI display for enums.<br />
	 * Whether should display as pop-up menu or radio buttons.
	 * 
	 * @return array all columns with specific display mode
	 */
	public function getSpecificEnumFields()
	{
		return $this->enumField;
	}
	// deprecated
    function noInsert()
    {
    	$this->bInsert= false;
    }
	// deprecated
    function noUpdate()
    {
    	$this->bUpdate= false;
    }
	// deprecated
    function noDelete()
    {
    	$this->bDelete= false;
    }
    function doInsert($do= true)
    {
    	$this->bInsert= $do;
    }
    function doUpdate($do= true)
    {
    	$this->bUpdate= $do;
    }
    function doDelete($do= true)
    {
    	$this->bDelete= $do;
    }
    /**
     * whether sort an Table
     * by clicking of one of the head-names
     * 
     * @param boolean $do whether should sort
     */
    function doHeadLineSort($do)
    {
    	$this->doTableSorting= $do;
    }
    /**
     * do not sort the Table
     * by clicking of one of the head-names
     */
    function noHeadLineSort()
    {
    	$this->doTableSorting= false;
    }
    function canInsert()
    {
    	return $this->bInsert;
    }
    function canUpdate()
    {
    	return $this->bUpdate;
    }
    function canDelete()
    {
    	return $this->bDelete;
    }
	function setListCaption($bSet)
	{
		$this->bListCaption= $bSet;
	}
	function displayListInColumns($nColumns)
	{
		$this->nDisplayColumns= $nColumns;
	}
	function insertByLink($param, $linkedColumn)
	{
		Tag::paramCheck($param, 1, "string");
		Tag::paramCheck($linkedColumn, 2, "string");

		$this->linkParams[$linkedColumn][STINSERT][]= $param;
	}
	function updateByLink($param, $linkedColumn)
	{
		$this->linkParams[$linkedColumn][STUPDATE][]= $param;
	}
	function deleteByLink($param, $linkedColumn)
	{
		$this->linkParams[$linkedColumn][STDELETE][]= $param;
	}
	function forwardByOneEntry($toColumn= true)
	{
		$bForward= true;
		if(is_bool($toColumn))
		{
			$bForward= $toColumn;
			$toColumn= null;
		}else
		{
			$field= $this->findAliasOrColumn($toColumn);
			$toColumn= $field["column"];
		}

		$this->aForward["do"]= $bForward;
		$this->aForward["column"]= $toColumn;
	}
		/*public static*/function createDynamicAccess()
		{
			if($this->bDynamicAccessIn!==null)
				return $this->bDynamicAccessIn;

			$checked= array();
			if( count($this->sAcessClusterColumn)
				and
				STUserSession::sessionGenerated()   )
			{
			    //st_print_r($table->sAcessClusterColumn,2);
			    $session= &STUserSession::instance();
				$aAccess= &$session->getDynamicClusters($this);
				if(count($aAccess))
				{
    				//st_print_r($aAccess, 10);
    				$in= "";
    				$where= new STDbWhere();
					// read all columns for dynamic clustering
					// which are set in the table object
					$bFounded= false;
					$aktAccess= "";
					//$accessTo= array();
    				foreach($this->sAcessClusterColumn as $info)
    				{
    				    if($info["cluster"]!=$aktAccess)
    					{
    					    if($in)
    						{
    					        $in= substr($in, 0, strlen($in)-1).")";
    						    $where->orWhere($in);
    						}
    						$checked= array();
    						$aktAccess= $info["cluster"];
    						$in= $aktAccess." in(";
    					}
    					$checked[$info["action"]]= 0;

						//echo "info access: ";st_print_r($info["action"]);echo "<br />";
    				    foreach($aAccess[$info["action"]] as $key=>$cluster)
    					{
    					    if($session->hasAccess($cluster, null, null, false, $info["action"]))
    						{
    						    $in.= $key.",";
    							++$checked[$info["action"]];
								$bFounded= true;
								//$accessTo[$info["action"]]= true;
    						}
						}
    				}
					if($bFounded)
					{
    					$in= substr($in, 0, strlen($in)-1).")";
    					$where->orWhere($in);
    					$this->andWhere($where);
					}
					$this->bDynamicAccessIn= $checked;
				}
			}
			return $checked;
		}
	function setFirstAction($action)
	{
		STCheck::paramCheck($action, 1, "string");

		$this->sFirstAction= $action;
	}
	function getFirstAction()
	{
		return $this->sFirstAction;
	}
	/**
	 * whether current user has acces to table
	 *
	 * @param string $action permisson for this action, if not set from current action
	 * @param boolean $loginOnFail if set goto by fault to login page (default:false)
	 * @return boolean whether user has access, if no session defined alway true
	 */
	function hasAccess($action= STALLDEF, $loginOnFail= false)
	{
	    STCheck::paramCheck($action, 1, "check",	$action===STALLDEF || $action===STLIST || $action===STINSERT ||
													$action===STUPDATE || $action===STDELETE || $action==STCHOOSE,
													"STALLDEF", "STCHOOSE", "STLIST", "STINSERT", "STUPDATE", "STDELETE");
		STCheck::paramCheck($loginOnFail, 2, "bool");
		
		if(!STSession::sessionGenerated())
		    return true;
		    
		if($action===STCHOOSE)
		    $action= STLIST;
		    
	    $instance= &STUserSession::instance();
	    if(!isset($action))
	        $action= $this->getAction();
        $clusters= $this->getAccessCluster($action);
        
        $access= true;
        foreach($clusters as $aCluster)
        {
            $accessString= $aCluster['accessString'];
            if(trim($accessString) == "")
                $accessString= "access to table ".$this->getName()." on action $action";
            $access=  $instance->hasAccess($aCluster['cluster'], $accessString, $aCluster['customID'], $loginOnFail);
            if($access)
                break;
        }

        if(STCheck::isDebug("access"))
		{
			if($action==STALLDEF)
				$staction= "STALLDEF";
			elseif($action==STCHOOSE)
				$staction= "STCHOOSE";
			elseif($action==STLIST)
				$staction= "STLIST";
			elseif($action==STUPDATE)
				$staction= "STUPDATE";
			elseif($action==STINSERT)
				$staction= "STINSERT";
			elseif($action==STDELETE)
				$staction= "STDELETE";
			$clusterString= "";
			if(is_array($clusters))
			{
				foreach($clusters as $cluster)
					$clusterString.= $cluster['cluster'].", ";
				$clusterString= substr($clusterString, 0, strlen($clusterString)-2);
			}else
				$clusterString= $clusters;
		}
		if($access)
		{
		    if(STCheck::isDebug("access"))
			{
				if($clusterString)
				{
					STCheck::echoDebug("access", "user in action $staction has <b>access</b> to table "
									.$this->Name."(".$this->getDisplayName()
									.") with Clusters '<i>$clusterString</i>'");
				}else
				{
					STCheck::echoDebug("access", "in table ".$this->Name."(".$this->getDisplayName().") no cluster be set, so return true");
				}
			}
			return true;
		}

		if(STCheck::isDebug("access"))
		    STCheck::echoDebug("access", "user in action $staction has <b>no access</b> to table "
								.$this->Name."(".$this->getDisplayName()
								.") with Clusters '<i>$clusterString</i>'");
		return false;
	}
}

?>
