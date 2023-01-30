



##DB: MUQuestionList
##
CREATE TABLE MUQuestionList(
  ID int NOT NULL auto_increment PRIMARY KEY,
  
  userID int NOT NULL, # Benutzer-ID FK usermanagement@localhost.UserManagement.MUUser.ID
  unknownUser varchar( 30 ), # wenn ein unbekannter User eingetragen ist
  email varchar( 50 ) not null, # EMail des unknowUsers
  customID int NOT NULL, # FK MUKategorys.ID
  
  
  ownRef int, # FK MUQuestionList.ID Referenze auf zuvor gestellte Fragen
  answerRef int, # FK MUAnswerList.ID Referenze auf eine Antwort

  subject varchar( 255 ) NOT NULL, # Betreff für die Anfrage
  question text, # Frage des Users
  attachment varchar( 255 ), # Pfad für das File weles angehängt werden soll

  status enum('created', 'seen', 'answered', 'finished') not null, # Status der gestellten Frage
  who varchar(255), # welche Verantwortliche haben diese Frage gesehen
  createDate datetime not null, # Datum der Frage
);