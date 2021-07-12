<?php


class STUserGroupManagement extends STObjectContainer
{
	function STUserClusterManagement($name, &$container)
	{
		Tag::paramCheck($name, 1, "string");
		Tag::paramCheck($container, 2, "STObjectContainer");
		
		STObjectContainer::STObjectContainer($name, $container);
		
	}
	function create()
	{	
		$this->setDisplayName("Cluster-Auswahl");
			
		$cluster= &$this->needTable("Cluster");
		$cluster->setDisplayName("Gruppen-Zuweisung zu den expliziten Clustern");
		$cluster->select("ID", "Cluster");
		$cluster->select("Description", "Bezeichnung");
		//$cluster->identifColumn("ID", "Cluster");
		$cluster->namedLink("ID", "clustergroup");
		$cluster->noInsert();
		$cluster->noUpdate();
		$cluster->noDelete();
		$cluster->orderBy("ID");
		//$cluster->setMaxRowSelect(20);
			
		$usergroup= &$this->needTable("UserGroup");
		$usergroup->setDisplayName("Zugriffs-Gruppen");
		$usergroup->nnTable("Zugriff");
		$usergroup->select("GroupID");
		$usergroup->select("UserID");
		$usergroup->select("DateCreation", "zugeh�rigkeit seit");
		$usergroup->getColumn("GroupID");
		$usergroup->preSelect("DateCreation", "sysdate()");
		//$usergroup->distinct();
		$usergroup->changeFormOptions("Speichern");
		$usergroup->noInsert();
		$usergroup->noUpdate();
		$usergroup->noDelete();
		$usergroup->callback("Bezeichnung", "st_usermanagement_description_casher", STLIST);
		
		$group= &$this->getTable("Group");
		$group->identifColumn("Name", "Gruppen-Name");
		$group->identifColumn("Description", "Bezeichnung");
		$group->getColumn($group->getPkColumnName());
		$group->orderBy("Name");
			$instance= &STSession::instance();
			$where= new STDbWhere("Name!='".$instance->onlineGroup."'");
			$where->andWhere("Name!='".$instance->loggedinGroup."'");
		$group->where($where);
		
		
		$user= &$this->getTable("User");
		$identifs= $user->getIdentifColumns();
		//$user->clearIdentifColumns();
		$user->displayIdentifs(false);
		//$user->setListCaption(false);
		$user->modifyForeignKey(false);
		$user->setMaxRowSelect(20);
		$user->clearWhere();
		$firstColumn= "";
		foreach($identifs as $content)
		{
			if(!$firstColumn)
			{
				$firstColumn= $content["column"];
				$user->activeColumnLink($content["alias"]);
			}
			$user->select($content["column"], $content["alias"]);
		}
		$user->namedPkLink($firstColumn, $this);
		$user->orderBy($firstColumn);
		
		$this->getTable("Cluster");		
		
		$this->setFirstTable("UserGroup");
	}
	function init()
	{
  		$stget= $this->stgetParams();
  		$cluster= $this->getTableName("Cluster");
  		$clusterIdentification= $this->getColumnFromTable("Cluster", "identification");
  		$partition= $this->getTableName("Partition");
  		$partitionId= $this->getColumnFromTable("Partition", "ID");
			
		if($stget[$partition][$partitionId])
		{
    		$clusterWhere= new STDbWhere($clusterIdentification."=".$stget[$partition][$partitionId]);
    		$clusterWhere->forTable($cluster);
		}else
			STCheck::warning($this->isAktContainer(), "STUserGroupManagement::init()",
								"primary key ".$partitionId." from table ".$partition." is not set in the querystring");
			
			
		$usergroupTableName= $this->getTableName("UserGroup");
		$aktTable= $this->getTableName();
		if($aktTable===$usergroupTableName)
		{
			$clusterGroup= &$this->getTable("ClusterGroup");
			$clusterGroup->leftJoin("ClusterID", "Cluster");
			$clusterGroup->leftJoin("GroupID", "Group");
			$clusterGroup->setMaxRowSelect(20);
			
    		$userId= $this->getColumnFromTable($usergroupTable, "UserID");	
	  		$usergroup= &$this->needTable("UserGroup");	
    		$usergroupTable= $usergroup->getName();
			
    		$usergroup->andWhere($clusterWhere);
			$user= &$this->getTable("User");
			$query= new STQueryString();
			$limitation= $query->getLimitation("User");
    		if(!$limitation)
    		{
        		/*$where= &new STDbWhere("UserID=1");
        		$where->forTable("STUser");*/				
				$pk= $user->getPkColumnName();
				
				$userSelector= new OSTDbSelector($user, STSQL_ASSOC);
				$userSelector->execute(1);
				$result= $userSelector->getRowResult();
        		$userWhere= new STDbWhere();
        		$userWhere->andWhere($userId."=".$result[$pk]);
    			$userWhere->forTable($usergroupTable);
    			$usergroup->andWhere($userWhere);
				
				$user->activeColumnLink(key($result), $result[$pk]);
    		}
			$this->navigationTable($user, "UserGroup", STLEFT);
		}else
		{
			$group= &$this->getTable("Group");
			//$group->displayIdentifs(false);
			
			$cluster= &$this->getTable("Cluster");
			$cluster->where($clusterWhere);
		}
	}
}

function st_usermanagement_description_casher(&$oCallback)
{
	global	$global_stusermanagement_group_descriptions,
			$global_stusermanagement_aliasname;
	
	//$oCallback->echoResult();
	if(!$global_stusermanagement_group_descriptions)
	{//echo "-------------------------------------------------------------------------------------------------------------------------------<br />";
		$groubManagement= &STBaseContainer::getContainer("usergroup");
		$groupTable= &$groubManagement->getTable("Group");
		$aliasName= $groupTable->findColumnOrAlias("Name");
		$columnName= $aliasName["column"];
		$aliasName= $aliasName["alias"];
		$global_stusermanagement_aliasname= $aliasName;
		//$description= $groupTable->findColumnOrAlias("Description");
		//$global_stusermanagement_description= $description["alias"];
		$inClausl= "";
		$count= $oCallback->countSqlResult();
		for($n= 0; $n<$count; $n++)
		{
			$name= $oCallback->getValue($aliasName, $n);
			$inClausl.= "'".$name."',";
		}
		if($inClausl)
		{
			$inClausl= substr($inClausl, 0, strlen($inClausl)-1);
			$inClausl= " in(".$inClausl.")";
			$selector= new OSTDbSelector($groupTable);
			$selector->select("Group", "Name");
			$selector->select("Cluster", "Description");			
			$selector->where($columnName.$inClausl);
			$selector->modifyForeignKey(false);
			//echo $selector->getStatement()."<br />";
			$selector->execute();
			$result= $selector->getResult();
			//st_print_r($result,2);
			foreach($result as $row)
			{
				$description= trim($row[1]);
				if($description)
				{
					if(isset($global_stusermanagement_group_descriptions[$row[0]]))
						$global_stusermanagement_group_descriptions[$row[0]].= "<br />";
					//else
					//	$global_stusermanagement_group_descriptions[$row["Name"]]= "";
					
					$global_stusermanagement_group_descriptions[$row[0]].= $row[1];
				}
			} 
			//st_print_r($global_stusermanagement_group_descriptions);
		}
	}
	$instance= &STSession::instance();
	$group= $oCallback->getValue($global_stusermanagement_aliasname);
	if(	$group===$instance->onlineGroup
		or
		$group===$instance->loggedinGroup	)
	{
		$oCallback->skipRow();
		return;
	}
	$description= $oCallback->getValue();
	$span= null;
	if($description)
	{
		$span= new SpanTag();
			$b= new BTag();
				$b->addObj($description);
			$span->addObj($b);
			$span->addObj(br());
			$span->addObj($global_stusermanagement_group_descriptions[$group]);
	}else
		$span= $global_stusermanagement_group_descriptions[$group];
	$oCallback->setValue($span);
	//$oCallback->echoResult();
}

?>