<?php

require_once( $_stpostarray );
require_once( $_stobjectcontainer );
require_once( $_stquerystring );
require_once( $_stdbselector );
require_once( $_stclustergroupmanagement );

$__global_UserClusterGroup_CALLBACK= array();

function permissionCallback(&$callbackObject, $columnName, $rownum)
{//st_print_r($callbackObject->sqlResult[$rownum]);
    //$callbackObject->echoResult();
    global $__global_UserClusterGroup_CALLBACK;
    
    if($rownum == 0)
    {
        $instance= STUserSession::instance();
        $__global_UserClusterGroup_CALLBACK['domain']= $instance->mainDOMAIN;
    }
    
    if($callbackObject->getValue() == 1)
    {
        $aResult=	array(	"Name"=>"",
            "Description"=>"Zugriff auf alle Projekte und Untergruppen "	);
        $aResult= array($aResult);// es wird eine Zeile vorget�uscht
    }else
    {
        $clusterTable= $callbackObject->getTable("Cluster");
        $cluster= new STDbSelector($clusterTable);
        $cluster->select("Project", "Name", "Name");
        $cluster->select("Cluster", "Description", "Description");
        $cluster->select("Group", "Name", "group");
        $cluster->where("ClusterGroup", "GroupID=".$callbackObject->getValue());
        $cluster->orderBy("Project", "Name");
        $cluster->allowQueryLimitation(false);
        $cluster->execute();
        $aResult= $cluster->getResult();
    }
    $source=   "<table>";
    foreach($aResult as $row)
    {
        $source.=  "	<tr>";
        $source.=  "		<td>";
        $source.=  "			<b>";
        $source.=  "				[".$row["Name"]."]";
        $source.=  "			</b>";
        $source.=  "		</td>";
        $source.=  "		<td>";
        $source.=  "				".$row["Description"];
        $source.=  "		</td>";
        $source.=  "	</tr>";
    }
    $source.=  "</table>";
    $callbackObject->setValue($source);
    
    if( $callbackObject->sqlResult[$rownum]["domain"] != $__global_UserClusterGroup_CALLBACK['domain'] )
    {
        $callbackObject->disabled();
    }
}

class STUserClusterGroupManagement extends STObjectContainer
{
    private $clusterGroup;
    
	function __construct($name, &$container)
	{
		Tag::paramCheck($name, 1, "string");
		Tag::paramCheck($container, 2, "STObjectContainer");
		
		STObjectContainer::__construct($name, $container);
		$this->clusterGroup= new STClusterGroupManagement("ClusterGroupAssignment", $this->getDatabase());	
	}
	function create()
	{
	    $this->setDisplayName("Cluster-User Management");
	    
	    $cluster= $this->needTable("Cluster");
	    $cluster->setDisplayName("access CLUSTER Group");
	    
	    $group= $this->needNnTable("Group", "UserGroup", "User");
	    $group->setDisplayName("User - Group assignment");
	    //$clusterGroup= $this->needTable("ClusterGroup");
	    //$clusterGroup->setDisplayName("");
	    $this->setFirstTable("Group");
	    return;
	}
	function init()
	{
	    $action= $this->getAction();
	    $currentTableName= $this->getTableName();
	    $groupTableName= $this->getTableName("Group");
	    $clusterTableName= $this->getTableName("Cluster");
	    $prj= $this->getTable("Project");
	    
	    $query= new STQueryString();	    
	    $projectName= "-xxx-";
/*	    $limitation= $query->getLimitation($prj->getName());
	    if(isset($limitation))
	    {
	        $projectName= $limitation['Name'];
	        $prjIDTable= new STDbSelector($prj);
	        $prjIDTable->select("Project", "ID", "ID);
	        $prjIDTable->where("Name='$projectName'");
	        $prjIDTable->execute();
	        $projectID= $prjIDTable->getSingleResult();
	    }*/	   
	    
	    
	    //$group= $this->getTable("User");
	    
	    //$gr= $this->getTable("Group");
	    $domain= $this->getTable("AccessDomain");
	    $domain->identifColumn("Name", "Domain");
	    
	    $group= $this->needTable("Group");
	    $group->identifColumn("Name", "Group");
	    $group->distinct();
	    $group->select("AccessDomain", "Name", "domain");
	    $group->select("Group", "Name", "group");
	    $group->nnTableCheckboxColumn("Affilation");
	    $group->select("Group", "ID", "Permissions");
	    $group->listCallback("permissionCallback", "Permissions");
	    $group->select("UserGroup", "DateCreation", "member since");
	    echo __FILE__.__LINE__."<br>";
	    $group->orderBy("AccessDomain", "Name");
	    echo __FILE__.__LINE__."<br>";
	    $group->orderBy("Group", "Name");
	    $group->setMaxRowSelect(20);
	    $group->noJoinOver("Log");
	    $group->joinOver("ClusterGroup");
	    $group->joinOver("Cluster");
	    $group->joinOver("Project");
	    $group->allowQueryLimitation(true);
	    
	    
	    
	    if( $currentTableName == $groupTableName &&
	        $action= STLIST                                )
	    {
	        $user= $this->getTable("User");
	        $user= new STDbSelector($user);
	        $user->select("User", "ID", "ID");
	        $user->select("User", "domain", "domain");
	        $user->select("User", "user", "user");
	        $user->select("User", "FullName", "full");
	        $user->orderBy("User", "domain");
	        $user->orderBy("User", "user");
	        $user->orderBy("User", "FullName");
	        $user->execute();
	        $rows= $user->getResult();
	        $users= array();
	        foreach($rows as $content)
	        {
	            $field= array();
	            $field[]= $content['ID'];
	            $field[]= $content['Domain']." - ".$content['user']." - ".$content['full'];
	            $users[]= $field;
	        }
	        $post= new STPostArray();
	        $query= new STQueryString();
	        $url= $query->getUrlParamString();
	        $selected= null;
	        if($post->getValue("User") != "")
	            $selected= $post->getValue("User");
	        else
	            $selected= $users[0][0];
	        $bodyHead= new DivTag();
    	        $center= new CenterTag();
    	           $form= new FormTag();
    	               $form->name("userCheck");
    	               $form->action($url);
    	               $form->method("post");
    	               $b= new BTag();
        	               $b->add("User: ");
        	           $form->add($b);
    	               $select= new SelectTag();
    	                   $select->name("User");
    	                   $select->createOptionArray($users, 1, 0, $selected);
    	                   $select->onChange("javascript:submit()");
    	               $form->addObj($select);
    	           $center->addObj($form);
    	       $bodyHead->addObj($center);
    	    $this->addObjBehindTableIdentif($bodyHead);
    	    
    	    $userWhere= new STDbWhere();
    	    $userWhere->table("UserGroup");
    	    $userWhere->where("UserID=$selected");
    	    //$userWhere->writeWhereCondition();
    	    $group->where($userWhere);
    	    
/*    	    $groupWhere= new STDbWhere();
    	    $groupWhere->table("Group");
    	    $groupWhere->where("domain='Abwesenheitsplaner'");
    	    $group->andWhere($groupWhere);*/
	    }
	  
		$cluster= $this->getTable("Cluster");
		$cluster->select("ID", "Cluster");
		$cluster->namedLink("Cluster", $this->clusterGroup);
		$cluster->select("Description", "Description");
		$cluster->joinOver("Project");
/*		if( $currentTableName == $clusterTableName &&
		    $action == STLIST)
		{
		    $where= new STDbWhere();
		    $where->table("Project");
		    $where->where("Name='$projectName'");
		    $cluster->where($where);
		}*/
	}
}
?>