<?php

		require_once($_stobjectcontainer);
		require_once($_stdbtabledescriptions);
		
		$_stusermanagement_table_description= &STDbTableDescriptions::instance($DBin_UserDatabase);

		$_stusermanagement_table_description->table("Query");
		$_stusermanagement_table_description->column("Query", "ID", "BIGINT", false);
		$_stusermanagement_table_description->primaryKey("Query", "ID");
		$_stusermanagement_table_description->autoIncrement("Query", "ID");
		$_stusermanagement_table_description->column("Query", "path", "TEXT", false);
		$_stusermanagement_table_description->indexKey("Query", "path", 1, 255);

		$_stusermanagement_table_description->table("Translate");
		$_stusermanagement_table_description->column("Translate", "ID", "varchar(50)", false);
		$_stusermanagement_table_description->primaryKey("Translate", "ID");
		$_stusermanagement_table_description->uniqueKey("Translate", "ID", 1);
		$_stusermanagement_table_description->indexKey("Translate", "ID", 1);
		$_stusermanagement_table_description->column("Translate", "lang", "char(3)", false);
		$_stusermanagement_table_description->uniqueKey("Translate", "lang", 1);
		$_stusermanagement_table_description->indexKey("Translate", "lang", 1);
		$_stusermanagement_table_description->column("Translate", "translation", "text", false);

		$_stusermanagement_table_description->table("Project");
		$_stusermanagement_table_description->column("Project", "ID", "TINYINT", false);
		$_stusermanagement_table_description->primaryKey("Project", "ID");
		$_stusermanagement_table_description->autoIncrement("Project", "ID");
		$_stusermanagement_table_description->column("Project", "Name", "varchar(70)", false);
		$_stusermanagement_table_description->uniqueKey("Project", "Name", 1);
		$_stusermanagement_table_description->column("Project", "Path", "varchar(255)", false);
		$_stusermanagement_table_description->column("Project", "Description", "text");
		$_stusermanagement_table_description->column("Project", "DateCreation", "datetime", false);
		//$_stusermanagement_table_description->column("Project", "has_access", "varchar(255)", false);
		//$_stusermanagement_table_description->column("Project", "can_insert", "varchar(255)", false);
		//$_stusermanagement_table_description->column("Project", "can_update", "varchar(255)", false);
		//$_stusermanagement_table_description->column("Project", "can_delete", "varchar(255)", false);

		$_stusermanagement_table_description->table("Partition");
		$_stusermanagement_table_description->column("Partition", "ID", "SMALLINT", false);
		$_stusermanagement_table_description->primaryKey("Partition", "ID");
		$_stusermanagement_table_description->autoIncrement("Partition", "ID");
		$_stusermanagement_table_description->column("Partition", "Name", "varchar(100)", false);
		$_stusermanagement_table_description->uniqueKey("Partition", "Name", 1);
		$_stusermanagement_table_description->column("Partition", "ProjectID", "TINYINT", false);
		$_stusermanagement_table_description->foreignKey("Partition", "ProjectID", "Project");
		$_stusermanagement_table_description->column("Partition", "has_access", "varchar(255)", false);
		//$_stusermanagement_table_description->column("Partition", "can_insert", "varchar(255)", false);
		//$_stusermanagement_table_description->column("Partition", "can_update", "varchar(255)", false);
		$_stusermanagement_table_description->column("Partition", "can_delete", "varchar(255)", false);
		$_stusermanagement_table_description->column("Partition", "DateCreation", "DATETIME", false);

		$_stusermanagement_table_description->table("Cluster");
		$_stusermanagement_table_description->column("Cluster", "ID", "varchar(100)", false);
		$_stusermanagement_table_description->primaryKey("Cluster", "ID");
		$_stusermanagement_table_description->column("Cluster", "ProjectID", "TINYINT", false);
		$_stusermanagement_table_description->foreignKey("Cluster", "ProjectID", "Project", 1);
		$_stusermanagement_table_description->column("Cluster", "Description", "TEXT", false);
		$_stusermanagement_table_description->column("Cluster", "identification", "SMALLINT", false);
		$_stusermanagement_table_description->foreignKey("Cluster", "identification", "Partition", 2);
		//$_stusermanagement_table_description->column("Cluster", "lastDynamicAccess", "set('false', 'true')", true);
		$_stusermanagement_table_description->column("Cluster", "DateCreation", "DATETIME", false);

		$_stusermanagement_table_description->table("Group");
		$_stusermanagement_table_description->column("Group", "ID", "INT", false);
		$_stusermanagement_table_description->primaryKey("Group", "ID");
		$_stusermanagement_table_description->autoIncrement("Group", "ID");
		$_stusermanagement_table_description->column("Group", "Name", "varchar(100)", false);
		$_stusermanagement_table_description->uniqueKey("Group", "Name", 1);
		$_stusermanagement_table_description->column("Group", "Description", "TEXT");
		$_stusermanagement_table_description->column("Group", "DateCreation", "DATETIME", false);

		$_stusermanagement_table_description->table("ClusterGroup");
		$_stusermanagement_table_description->column("ClusterGroup", "ID", "INT", false);
		$_stusermanagement_table_description->primaryKey("ClusterGroup", "ID");
		$_stusermanagement_table_description->autoIncrement("ClusterGroup", "ID");
		$_stusermanagement_table_description->column("ClusterGroup", "ClusterID", "varchar(100)", false);
		$_stusermanagement_table_description->foreignKey("ClusterGroup", "ClusterID", "Cluster", 1);
		$_stusermanagement_table_description->column("ClusterGroup", "GroupID", "INT", false);
		$_stusermanagement_table_description->foreignKey("ClusterGroup", "GroupID", "Group", 2);
		$_stusermanagement_table_description->column("ClusterGroup", "DateCreation", "DATETIME", false);

		$_stusermanagement_table_description->table("UserGroup");
		$_stusermanagement_table_description->column("UserGroup", "ID", "INT", false);
		$_stusermanagement_table_description->primaryKey("UserGroup", "ID");
		$_stusermanagement_table_description->autoIncrement("UserGroup", "ID");
		$_stusermanagement_table_description->column("UserGroup", "UserID", "INT", false);
		$_stusermanagement_table_description->foreignKey("UserGroup", "UserID", "User", 1);
		$_stusermanagement_table_description->column("UserGroup", "GroupID", "INT", false);
		$_stusermanagement_table_description->foreignKey("UserGroup", "GroupID", "Group", 2);
		$_stusermanagement_table_description->column("UserGroup", "DateCreation", "DATETIME", false);

		$_stusermanagement_table_description->table("GroupGroup");
		$_stusermanagement_table_description->column("GroupGroup", "ID", "INT", false);
		$_stusermanagement_table_description->primaryKey("GroupGroup", "ID");
		$_stusermanagement_table_description->autoIncrement("GroupGroup", "ID");
		$_stusermanagement_table_description->column("GroupGroup", "Group1ID", "INT", false);
		$_stusermanagement_table_description->column("GroupGroup", "Group2ID", "INT", false);
		$_stusermanagement_table_description->foreignKey("GroupGroup", "Group1ID", "Group", 1);
		$_stusermanagement_table_description->foreignKey("GroupGroup", "Group2ID", "Group", 2);
		$_stusermanagement_table_description->column("GroupGroup", "DateCreation", "DATETIME", false);

		$_stusermanagement_table_description->table("User");
		$_stusermanagement_table_description->column("User", "ID", "INT", false);
		$_stusermanagement_table_description->primaryKey("User", "ID");
		$_stusermanagement_table_description->autoIncrement("User", "ID");
		$_stusermanagement_table_description->column("User", "UserName", "varchar(50)", false);
		$_stusermanagement_table_description->uniqueKey("User", "UserName", 1);
		$_stusermanagement_table_description->column("User", "GroupType", "TINYINT", false);
		$_stusermanagement_table_description->uniqueKey("User", "GroupType", 1);
		$_stusermanagement_table_description->foreignKey("User", "GroupType", "GroupType");
		$_stusermanagement_table_description->column("User", "Pwd", "char(16) binary", false);
		$_stusermanagement_table_description->column("User", "NrLogin", "INT UNSIGNED");
		$_stusermanagement_table_description->column("User", "LastLogin", "DATETIME");
		$_stusermanagement_table_description->column("User", "currentLogin", "DATETIME");
		$_stusermanagement_table_description->column("User", "DateCreation", "DATETIME", false);

		$_stusermanagement_table_description->table("GroupType");
		$_stusermanagement_table_description->column("GroupType", "ID", "TINYINT", false);
		$_stusermanagement_table_description->primaryKey("GroupType", "ID");
		$_stusermanagement_table_description->autoIncrement("GroupType", "ID");
		$_stusermanagement_table_description->column("GroupType", "Label", "varchar(30)", false);
		$_stusermanagement_table_description->column("GroupType", "description", "varchar(50)");
		$_stusermanagement_table_description->column("GroupType", "DateCreation", "DATETIME", false);

		$_stusermanagement_table_description->table("Log");
		$_stusermanagement_table_description->column("Log", "ID", "BIGINT UNSIGNED", false);
		$_stusermanagement_table_description->primaryKey("Log", "ID");
		$_stusermanagement_table_description->autoIncrement("Log", "ID");
		$_stusermanagement_table_description->column("Log", "UserID", "INT", false);
		$_stusermanagement_table_description->foreignKey("Log", "UserID", "User", 1);
		$_stusermanagement_table_description->column("Log", "ProjectID", "TINYINT", false);
		$_stusermanagement_table_description->foreignKey("Log", "ProjectID", "Project", 2);
		$_stusermanagement_table_description->column("Log", "Type", "set('ERROR','LOGIN','LOGOUT','ACCESS')", false);
		$_stusermanagement_table_description->column("Log", "CustomID", "varchar(255)");
		$_stusermanagement_table_description->column("Log", "description", "TEXT", false);
		$_stusermanagement_table_description->column("Log", "DateCreation", "DATETIME", false);

	STObjectContainer::install("um_install", "STUM_InstallContainer", "userDb", $_stum_installcontainer);
	STObjectContainer::install("user", "STUserManagement", "userDb", $_stusermanagement);
	STObjectContainer::install("projects", "STProjectManagement", "user", $_stprojectmanagement);
	STObjectContainer::install("partition", "STPartitionManagement", "user", $_stpartitionmanagement);
	STObjectContainer::install("usergroup", "STUserGroupManagement", "user", $_stusergroupmanagement);
	STObjectContainer::install("groupgroup", "STGroupGroupManagement", "userDb", $_stgroupgroupmanagement);
	STObjectContainer::install("clustergroup", "STClusterGroupManagement", "user", $_stclustergroupmanagement);

?>