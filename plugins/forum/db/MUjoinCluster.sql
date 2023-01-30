
CREATE TABLE MUJoinCluster(
  ID int NOT NULL auto_increment PRIMARY KEY,
  
  KategoryID int not null, # FK MUKategorys.ID
  ClusterID varchar(50) not null # FK UserManagement.MUGroup.ID
);
