

select distinct `t0`.`ID`,`t6`.`Name`,`t0`.`ProjectID` 
  from MUProject as t6  
  inner join MUCluster as t0 
    on t6.ID=t0.ProjectID 
  inner join MUClusterGroup as t1 
    on t0.ID=t1.ClusterID 
  inner join MUGroup as t2 
    on t1.GroupID=t2.ID 
  right join MUUserGroup as t11 
    on t2.ID=t11.GroupID 
  where ((t2.Name = 'ONLINE#' or t2.Name = 'LOGGED_IN' or ((t11.UserID = 4))))
  
  
file:STUserManagementSession.php line:643
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
select distinct `t0`.`ID`,`t6`.`Name`,`t0`.`ProjectID` 
  from MUProject as t6  
  inner join MUCluster as t0 
    on t6.ID=t0.ProjectID 
  inner join MUClusterGroup as t1 
    on t0.ID=t1.ClusterID 
  inner join MUGroup as t2 
    on t1.GroupID=t2.ID where ((t2.Name = 'ONLINE#'))
