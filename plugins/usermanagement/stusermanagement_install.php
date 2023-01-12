<?php

		require_once($_stobjectcontainer);
		require_once($_stdbtabledescriptions);


	STObjectContainer::install("user", "STUserManagement", "userDb", $_stusermanagement);
	STObjectContainer::install("projects", "STProjectManagement", "user", $_stprojectmanagement);
	STObjectContainer::install("partition", "STPartitionManagement", "user", $_stpartitionmanagement);
	STObjectContainer::install("usergroup", "STUserGroupManagement", "user", $_stusergroupmanagement);
	STObjectContainer::install("groupgroup", "STGroupGroupManagement", "userDb", $_stgroupgroupmanagement);
	STObjectContainer::install("clustergroup", "STClusterGroupManagement", "user", $_stclustergroupmanagement);

?>