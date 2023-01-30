



##DB: MURefNumbers
##
CREATE TABLE MURefNumbers(
  ID bigint NOT NULL auto_increment PRIMARY KEY,

  firstMailID int not null, # FK MUQuestionList.ID Referenze auf die erste gestellte Frage
  lockID varchar(16) # eindeutige ID um die Datei zu Locken
);