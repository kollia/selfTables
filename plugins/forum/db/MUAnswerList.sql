



##DB: MUAnswerList
##
CREATE TABLE MUAnswerList(
  ID int NOT NULL auto_increment PRIMARY KEY,

  questionRef int not null, # FK MUQuestionList.ID Referenze auf zuvor gestellte Frage
  ownRef int, # FK MUAnswerList.ID Referenze auf eigene Antwort

  subject varchar(255) NOT NULL, # Betreff für die Anfrage
  answer text, # Antwort des Verantwortlichen
  who int not null, # welcher verantwortlicher hat diese Antwort durchgeführt?

  sendDate datetime # wann die Antwort abgeschickt wurde
);