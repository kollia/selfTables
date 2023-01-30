



##DB: MUOfficer
CREATE TABLE MUOfficer(
  ID int NOT NULL auto_increment PRIMARY KEY,

  kategoryID varchar(20) NOT NULL, # FK MUKategorys.ID Name der Kategory
  userID int NOT NULL # Verantwortlicher-ID FK usermanagement@localhost.UserManagement.MUUser.ID
);