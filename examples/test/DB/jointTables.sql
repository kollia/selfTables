

create table JTHouse
(
  Nr int not null primary key auto_increment,
  address varchar(50) not null
) engine=InnoDB;

create table JTPerson
(
  ID int not null auto_increment primary key,
  name varchar(50) not null,
  house int not null,
  foreign key FK__person_JTHouse (house) references JTHouse (`Nr`) ON DELETE RESTRICT
) engine=InnoDb;

SELECT p.name, h.address FROM `JTPerson` as p inner join JTHouse as h on h.Nr = p.house;
SELECT p.name, h.address FROM `JTPerson` as p left join JTHouse as h on h.Nr = p.house;
SELECT p.name, h.address FROM `JTPerson` as p right join JTHouse as h on h.Nr = p.house;
