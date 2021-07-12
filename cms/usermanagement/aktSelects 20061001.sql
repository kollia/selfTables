select t1.ID as 'Zugriff',
       t2.Name as 'Gruppen-Name',
       t2.Description as 'Bezeichnung',
       t3.ID as 'Cluster',
       t3.Description as 'Bezeichnung',
       t1.DateCreation as 'zugehörigkeit seit' 
from STClusterGroup as t1 
left join STCluster as t3 
      on t1.ClusterID=t3.ID and 
         ((t3.identification = 1)) 
right join STGroup as t2 
      on t1.GroupID=t2.ID order 
by t2.Name ASC,t3.ID ASC


select count(*) 
from STClusterGroup as t1 
left join STCluster as t3 
      on t1.ClusterID=t3.ID and 
         ((t3.identification = 1)) 
right join STGroup as t2 
      on t1.GroupID=t2.ID order 
by t2.Name ASC,t3.ID ASC

select distinct 
       t1.ID as 'Zugriff',
       t2.Name as 'Gruppen-Name',
       t2.Description as 'Bezeichnung',
       t1.DateCreation as 'zugehörigkeit seit',
       t5.identification 
from STUserGroup as t1 
right join STGroup as t2 
       on t1.GroupID=t2.ID and 
          (((t1.UserID = 1))) 
inner join STClusterGroup as t3 
       on t2.ID=t3.GroupID 
left join STCluster as t5 
       on t3.ClusterID=t5.ID and 
          ((t5.identification = 1)) 
right join STGroup as t6 
       on t3.GroupID=t6.ID 
left join STUser as t4 
       on t1.UserID=t4.ID 
order by t2.Name ASC




select distinct 
       t1.ID as 'Zugriff',
       t2.Name as 'Gruppen-Name',
       t2.Description as 'Bezeichnung',
       t1.DateCreation as 'zugehörigkeit seit' 
from STUserGroup as t1 
right join STGroup as t2 
       on t1.GroupID=t2.ID and 
          (((t1.UserID = 1))) 
inner join STClusterGroup as t3 
       on t2.ID=t3.GroupID 
inner join STCluster as t5 
       on t3.ClusterID=t5.ID and 
          ((t5.identification = 1)) 
inner join STGroup as t6 
       on t3.GroupID=t6.ID 
left join STUser as t4 
       on t1.UserID=t4.ID 
order by t2.Name ASC

select distinct 
       t1.ID as 'Zugriff',
       t2.Name as 'Gruppen-Name',
       t2.Description as 'Bezeichnung',
       t1.DateCreation as 'zugehörigkeit seit' 
from STUserGroup as t1 
right join STGroup as t2 
       on t1.GroupID=t2.ID and 
          (((t1.UserID = 1))) 
inner join STClusterGroup as t3 
       on t2.ID=t3.GroupID 
inner join STCluster as t5 
       on t3.ClusterID=t5.ID and 
          ((t5.identification = 1)) 
left join STUser as t4 
      on t1.UserID=t4.ID 
order by t2.Name ASC

select distinct 
       t1.ID as 'Zugriff',
       t2.Name as 'Gruppen-Name',
       t2.Description as 'Bezeichnung',
       t1.DateCreation as 'zugehörigkeit seit' 
from STUserGroup as t1 
right join STGroup as t2 
       on t1.GroupID=t2.ID and 
          (((t1.UserID = 1))) 
left join STClusterGroup as t3 
       on t2.ID=t3.GroupID 
left join STCluster as t5 
       on t3.ClusterID=t5.ID and 
          ((t5.identification = 1)) 
left join STUser as t4 
      on t1.UserID=t4.ID 
order by t2.Name ASC









select distinct
       t1.ID as 'Zugriff',
       t3.ID as 'Cluster',
       t1.ClusterID,
       t3.identification,
       t3.Description as 'Bezeichnung',
       t1.DateCreation as 'zugehörigkeit seit'
from STClusterGroup as t1 
right join STCluster as t3 
       on t1.ClusterID=t3.ID and 
          ((t3.identification = 1)) 
left join STGroup as t2 
       on t1.GroupID=t2.ID 
order by t2.Name ASC,t3.ID ASC


select count(*)
from STClusterGroup as t1 
right join STCluster as t3 
       on t1.ClusterID=t3.ID and 
          ((t3.identification = 1)) 
left join STGroup as t2 
       on t1.GroupID=t2.ID 
order by t2.Name ASC,t3.ID ASC


select distinct
       t1.ID as 'Zugriff',
       t3.ID as 'Cluster',
       t1.ClusterID,
       t3.identification,
       t3.Description as 'Bezeichnung',
       t1.DateCreation as 'zugehörigkeit seit'
from STCluster as t3
right join STClusterGroup as t1 
       on t1.ClusterID=t3.ID and 
          ((t3.identification = 1))
order by t3.ID ASC