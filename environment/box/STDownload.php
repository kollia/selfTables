<?php

class STDownload
{
	var	$oDb;
	var	$oTable;
	
	function STDownload(&$db, $table)
	{
		Tag::paramCheck($db, 1, "STDatabase");
		Tag::paramCheck($table, 2, "STAliasTable");
		$this->oDb= &$db;
		$this->oTable= $table;
	}
	function execute()
	{
		global $HTTP_GET_VARS;
		
		if(isset($HTTP_GET_VARS["stget"]["download"]))
		{
			$downloadColumn= $HTTP_GET_VARS["stget"]["download"];
			foreach($this->oTable->showTypes as $column=>$content)
			{
				foreach($content as $extraField=>$do)
				{
					if($extraField=="download")
					{
						$tableName= $this->oTable->getName();
						$where= $HTTP_GET_VARS["stget"][$tableName];
						Tag::alert(!is_array($where), "STDownload::execute()", "in URI must define stget[".$tableName."] for an download");					
						$this->download($downloadColumn, $where);
					}
				}
			}
		}
	}
	function download($columnName, $aWhere)
	{
		$key= key($aWhere);
		$where= $key."=".$aWhere[$key];
		$this->oTable->where($where);
		$this->oTable->clearSelects();
		$this->oTable->select($columnName);
		$statement= $this->oDb->getStatement($this->oTable);
		$download= $this->oDb->fetch_single($statement);
		
		preg_match("/[^\\\\\/]+$/", $download, $preg);
		$fileName= $preg[0];

		if(Tag::isDebug())
		{	?>
			<table width="100%" bgcolor="white">
				<tr>
					<td width="100"></td>
					<td height="50"></td>
				</tr>
				<tr>
					<td></td>
					<td>
						<h2>
							downloading file <? echo $download; ?>
							<? echo chmod($download) ?>
						</h2>
					</td>
				</tr>
			</table>	
		<?	exit;
		}
		header("Content-Type: x-type/subtype");
		header("Content-Length: ".filesize($download));
		header("Content-Disposition: attachment; filename=".$fileName);//".$download);
		readfile($download);
		exit;
	}
}
?>