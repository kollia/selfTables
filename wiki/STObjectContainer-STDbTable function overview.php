<?php

class STBaseTable
{
	function title($title); // - ????????
	function getTitle();
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
	function attribute($element, $attribute, $value, $tableType= null, $aliasName= null);
	function tableAttribute($attribute, $value, $tableType= null);
	function trAttribute($attribute, $value, $tableType= null);
	function thAttribute($attribute, $value, $tableType= null);
	function tdAttribute($attribute, $value, $tableType= null, $aliasName= null);
	/**
	 * make allignment for specific column.<br />
	 * can be differend between list-, insert- and update-table
	 * 
	 * @param string $aliasName name of column
	 * @param string $value whether alignment should be 'left', 'center' or 'right'
	 * @param enum $tableType for which display table - STLIST, STINSERT or STDELETE - alignment should be.
	 * 							(default: <code>STLIST</code>)
	 */
	public function align($aliasName, $value, $tableType= STLIST);
	/**
	 * define column as must have field, maybe by different actions
	 * 
	 * @param string $column name of column or defined alias column
	 * @param enum $action column hase to be exist for all actions STADMIN (default), or only by STINSERT or STUPDATE
	 */
	public function needValue(string $column, $action= STADMIN); // maybe when column new created
	/**
	 * define a column as optional, maybe different by any action
	 * 
	 * @param string $column name of column or defined alias column
	 * @param enum $action column should be optional for all actions STADMIN (default), or only by STINSERT or STUPDATE
	 */
	public function optional(string $column, $action= STADMIN); // maybe when column new created
	public function hasDefinedFlag(string $alias, $action, string $flag) : bool; // maybe when column new created
	/**
	 * define a column with specidic given flag, different by any action
	 * 
	 * @param string $alias name of alias column (have to be checked for correctness before)
	 * @param enum $action column be set for action STLIST, STINSERT or STUPDATE
	 * @param string $flag name of the flag defined for column
	 * @param string $notDefined flag should not defined after the given flag before (3. param) is set
	 */
	private function preDefinedFlag(string $alias, $action, string $flag, string $notDefined= ""); // maybe when column new created
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
	public function accessBy($clusters, $action= STALLDEF, $toAccessInfoString= "", int $customID= null);
	public function allowQueryLimitationByOwn($bModify= null);
	/**
	 * use also limitation of table from an older container
	 * if one exist and was there set (default property)
	 */
	public function useLimitationBefore();
	/**
	 * do not use limitations whitch was set in any
	 * container before
	 */
	public function useNoLimitationBefore();
	// wenn bAlwaysIndex ist null wird der Index
	// bei keinem Eintrag in der Tabelle nicht angezeigt
	function showAlwaysIndex($bShow);
	function setMaxRowSelect($count);
	function listLayout($arrangement); // - HORIZONTAL, VERTICAL
	function distinct($bDistinct= true);
		function setDisplayName($string);
		public function joinOver(string $table, string $join= "inner");
		public function noJoinOver(string $table);
		function foreignKey($ownColumn, $toTable, $otherColumn= null, $where= null);
		function innerJoin($ownColumn, &$toTable, $otherColumn= null);
		function leftJoin($ownColumn, $toTable, $otherColumn= null);
		function rightJoin($ownColumn, $toTable, $otherColumn= null);
	public function orderBy(string $column, $bASC= true); 
		function column($name, $type, $len);// maybe when column new created
		function columnFlags($columnName, $flags);// maybe when column new created
		function removeFlag($columnName, $flag);// maybe when column new created
		/**
		 * beginning to put all entrys, for an update or insert box,
		 * into an table which have without this command two columns,
		 * to make a better design if the developer want to do more selects in one Row
		 * <code>select($dbcolumn, $displaycolumn, >break line< false)</code>
		 *
		 * @param fieldset if param is true an border is draw arround the inner table, if the param has an string the border is named, otherwise by default or false, no border showen
		 */
		function selectIBoxBegin($fieldset= false);
		/**
		 * end of put entrys in an inner Table.<br />
		 * see selectIBoxBegin for beginning and more details
		 */
		function selectIBoxEnd();
		function addSelect($column, $alias= null, $fillCallback= null, $nextLine= null); // if first selected, do not clear the default selection
		// alex 12/09/2005:	Alias kann jetzt auch eine Funktion
		//					zum f�llen einer nicht vorhandenen Spalte sein
		// alex 21/09/2005:	ACHTUNG alias darf keine Funktion sein (auch nicht PHP-function)
		// alex 06/08/2006: second column alias or third column fillCallback can also be an object from TinyMCE
		/**
		 * add column for selection
		 * 
		 * @param string $column column name from database
		 * @param string $alias column name should appear from select
		 * @param string $fillCallback if set, make callback before and after select for given column
		 * @param boolean $nextLine if <code>false</code> by <code>STItemBox</code> do not show column inside new line
		 * @param boolean $add whether new column should be added to exist original selection
		 * @param boolean $addGet if set, adding differ between select- and get-columns, otherwise parameter <code>$add</code> is for both
		 */
		public function select(string $column, $alias= null, $fillCallback= null, $nextLine= null, bool $add= false, bool $addGet= null);
		/**
		 * add content of string or HTML-Tags
		 * after the field
		 * 
		 * @param string or HTML-Tags $content which should be added
		 * @param predefined Action $action table action on which should be added
		 */
		function addContent($content, $action);
		/**
		 * add content of string or HTML-Tags
		 * between the last table row and the next
		 * 
		 * @param string or HTML-Tags $content which should be added
		 * @param predefined Action $action table action on which should be added
		 */
		function addBehind($content, $action);
		function group($name, $fieldset, $aliasColumn /*...*/); // ?????
		public function columnExist(string $column, bool $bAlias= false) : bool;
		public function getDbColumnName(string $column, int $warnFuncOutput= 0);
		function onChangeRefresh($column);
		public function clearIdentifColumns();
		function clearFKs();
		public function identifColumn(string $column, string $alias= null);
		public function showNameOverList($show); // maybe HTML - Tag definition
		public function andWhere($stwhere);
		function orWhere($stwhere);
		public function where($stwhere, string $operator= "");
		function clearWhere();
		function getName() : string; // name of table
		function upload($column, $toPath, $type, $byte= 0, $width= 0, $height= 0); // old upload function - habe to be tested
		function image($column, $toPath= null, $byte= 0, $width= 0, $height= 0); // create column content as image
		// alex 19/04/2005:	$address darf auch ein Tabellen-Name sein
		function imageLink($column, $toPath= null, $byte= 0, $width= 0, $height= 0, $address= null);
		function imageBorderLink($column, $toPath= null, $byte= 0, $width= 0, $height= 0, $address= null);
		function imagePkLink($column, $toPath= null, $byte= 0, $width= 0, $height= 0, $address= null);
		function imageBorderPkLink($column, $toPath= null, $byte= 0, $width= 0, $height= 0, $address= null);
		function imageValueLink($column, $valueColumn, $toPath= null, $byte= 0, $width= 0, $height= 0, $address= null);
		function imageBorderValueLink($column, $valueColumn, $toPath= null, $byte= 0, $width= 0, $height= 0, $address= null);
	function download($columnName, $access= null);
	public function disabled($columnName, $enum= null);
	function checkBox(string $columnName, $trueValue= false, $notSet= null);
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
	public function namedLink(string $alias, $address= null);
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
	public function namedColumnLink(string $aliasColumn, string $valueColumn, $address= null);
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
	public function namedPkLink(string $aliasColumn, $address= null);
	/**
	 * select column, do not calculate foreign keys by statement
	 * and not display inside STListBox or STItemBox
	 * 
	 * @param string $column name of column at current table
	 * @param string $alias alias name of column
	 * @param string $unknown unknown parameter for compatibility to STDbSelector
	 */
	public function getColumn(string $column, string $alias= "", string $unknown= "");
	function dropDownSelect($aliasColumn, $callbackFunction);
	public function listCallback($callbackFunction, $alias= null);
	public function insertCallback(string $callbackFunction, string $alias= null);
	public function updateCallback(string $callbackFunction, string $alias= null);
	public function indexCallback(string $callbackFunction);
	public function deleteCallback(string $callbackFunction);
	public function joinCallback($callbackFunction, $alias= null);
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
	public function preSelect(string $columnName, $value, $action= null, $unknown= null);
	function radioButtonsByEnum($aliasName);
	function pullDownMenuByEnum($aliasName);
    function noInsert();
    function noUpdate();
    function noDelete();
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
    function noHeadLineSort();
	function setListCaption($bSet); // ??? test it, there set inside STListBox <th> colums inside <tr> before <td> columns
	function displayListInColumns($nColumns);// test it
	function forwardByOneEntry($toColumn= true);
	function setFirstAction($action);
	/**
	 * whether current user has acces to table
	 *
	 * @param string $action permisson for this action, if not set from current action
	 * @param boolean $loginOnFail if set goto by fault to login page (default:false)
	 * @return boolean whether user has access, if no session defined alway true
	 */
	function hasAccess($action= STALLDEF, $loginOnFail= false);
}

class STDbTable extends STBaseTable
{
	/**
	 * add column for selection
	 *
	 * @param string $column column name from database
	 * @param string $alias column name should appear from select
	 * @param string $fillCallback if set, make callback before and after select for given column
	 * @param boolean $nextLine if <code>false</code> by <code>STItemBox</code> do not show column inside new line
	 * @param boolean $add whether new column should be added to exist original selection
	 * @param boolean $addGet if set, adding differ between select- and get-columns, otherwise parameter <code>$add</code> is for both
	 */
	public function select(string $column, $alias= null, $fillCallback= null, $nextLine= null, bool $add= false, bool $addGet= null);
    /**
     * define the column as a password field.<br />
     * The column inside the database should be a not null field or be defined with STDbTable::needValue(<column>, STInsert) from script.
     * But the update action inside this framework is defined as default first as an optional column. Because when user update the table
     * with a password column, the password shouldn't change if the user define no new one.
     *  
     * @param string $fieldName name of column or alias column
     * @param boolean $bEncode whether the password should encoded inside the database
     */
    function password(string $fieldName, bool $bEncode= true);
    function passwordNames($firstName, $secondName, $thirdName= null);
	public function currentTable(): bool; // whether table is the current table
	function column($name, $type, $len= null); // ??? create new column
	public function joinOver($table, string $join= STINNERJOIN);
	public function noJoinOver($table);
	function &getDatabase();
	public function getStatement(bool $bFromIdentifications= false);
	public function setStatement(string $statement);
	public function displayWrappedStatement();
	public function getWrappedStatement();
	/**
     * allow modification by every table has an limit in the query string
     * or an foreign key table limit points to own table with also limitation from query
     * 
     * @param bool $bModify whether should modification enabled or disabled
	 * @return array content of fk and own limitatin by query
	 */
	public function allowQueryLimitation(bool|array $modify= null) : array;
	public function allowFkQueryLimitation($bModify= null);
	/**
     * reset modification of query limitation
     * 
     * @param string $which can be 'fk' or 'own'
     * @param bool whether can modify or not
	 */
	protected function resetQueryLimitation(string $which, bool $bModify);
	public function where($stwhere, string $operator= "");
	
	/**
	 * pre-define an access cluster for every row,
	 * where the row only be shown if the user has the cluster.<br />
	 *  beschreibe für welche Zugriffs Berechtigung der Cluster
	 * @param string $column column or alias name where the cluster should be stored
	 * @param string $prefix the prefix can be a column name or alias name of a column,
	 *                         which is enclosed in curly brackets; any characters can be chosen outside the brackets.
	 *                         as example: 'AccessCountry_{c:<column-name>}:'.
	 *                         The prefix will be followd with a count from table entrys.
	 * @param string $accessInfoString the string which should be written inside logging table
	 * @param boolean $addGroup whether by creating the cluster, also a group for it should be created
	 */
	public function accessCluster(string $column, string $prefix= "", string $accessInfoString= "", $addGroup= true);
	/**
	 * pre-define an admin cluster for every row,
	 * where the user can insert/update or delete content inside the linked tabel
	 * if the user has the cluster.<br />
	 * @param string $column column or alias name where the cluster should be stored
	 * @param string $prefix the prefix can be a column name or alias name of a column,
	 *                         which is enclosed in curly brackets; any characters can be chosen outside the brackets.
	 *                         as example: 'AccessCountry_{c:<column-name>}:'.
	 *                         The prefix will be followd with a count from table entrys.
	 * @param string $accessInfoString the string which be used as Description for cluster
	 *                                  and will be always written inside logging table.<br />
	 *                                  The string can be cantain an @ which will be replaced with all
	 *                                  defined identif-columns
	 * @param boolean $addGroup whether by creating the cluster, also a group for it should be created
	 */
	public function adminCluster(string $column, string $prefix= "", string $accessInfoString= "", $addGroup= true);
	/**
	 * pre-define a cluster for every row,
	 * where the row only be shown if the user has the cluster.<br />
	 *  beschreibe für welche Zugriffs Berechtigung der Cluster 
	 * @param string $access describe for which permission the cluster should have access.<br />
	 *                       If this method will be used, by first calling the variable have to be STLIST
	 *                       to see the new inserted entry in the STListBox. If you want that the user
	 *                       have permission to change anything inside the linked table, fill this variable
	 *                       with STADMIN. Than the user in the upper linked STListBox have access to insert,
	 *                       update or delete new entrys. Also allowed all other entrys by this variable, where
	 *                       the developer have to handle the cluster permission by his own. He can get the
	 *                       permissions inside the upper container with the methode <code>getLinkedCluster()</code> 
	 * @param string $column column or alias name where the cluster should be stored
	 * @param string $prefix the prefix can be a column name or alias name of a column, 
	 *                         which is enclosed in curly brackets; any characters can be chosen outside the brackets.
	 *                         as example: 'AccessCountry_{c:<column-name>}:'.
	 *                         The prefix will be followd with a count from table entrys.
	 * @param string $accessInfoString the string which should be written inside logging table
	 * @param boolean $addGroup whether by creating the cluster, also a group for it should be created
	 */
	public function cluster(string $access, string $column, string $prefix, string $accessInfoString= "", $addGroup= true);
}

?>