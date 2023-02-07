<?php

require_once( $_stpostarray );
require_once( $_stobjectcontainer );
require_once( $_stquerystring );
require_once( $_stdbselector );
require_once( $_stclustergroupmanagement );

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
	        $prjIDTable->select("Project", "ID");
	        $prjIDTable->where("Name='$projectName'");
	        $prjIDTable->execute();
	        $projectID= $prjIDTable->getSingleResult();
	    }*/	   
	    
	    
	    //$group= $this->getTable("User");
	    
	    //$gr= $this->getTable("Group");
	    
	    $group= $this->needTable("Group");
	    $group->identifColumn("Name", "Group");
	    $group->select("Group", "Name", "group");
	    $group->nnTableCheckboxColumn("Affilation");
	    $group->select("Group", "ID", "Permissions");
	    $group->select("UserGroup", "DateCreation", "member since");
	    $group->setMaxRowSelect(40);
	    $group->noJoinOver("Log");
	    $group->joinOver("ClusterGroup");
	    $group->joinOver("Cluster");
	    $group->joinOver("Project");
	    
	    
	    
	    if( $currentTableName == $groupTableName &&
	        $action= STLIST                                )
	    {
	        $user= $this->getTable("User");
	        $user= new STDbSelector($user);
	        $user->select("User", "ID");
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
	            $field[]= $content['domain']." - ".$content['user']." - ".$content['full'];
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
    	    
    	    $groupWhere= new STDbWhere();
    	    $groupWhere->table("Group");
    	    $groupWhere->where("domain=$projectName");
    	    //$group->andWhere($groupWhere);
	    }
	  
		$cluster= $this->getTable("Cluster");
		$cluster->select("ID", "Cluster");
		$cluster->namedLink("Cluster", $this->clusterGroup);
		$cluster->select("Description");
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