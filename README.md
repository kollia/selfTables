# DB selfTables
The goal of this project is for you to design your database table and automatically get a user-friendly GUI interface for all your generated tables in a short time.

This solution can be useful for research if you have written your own specific algorithm that uses data from the database. 
When first developing, you make your data available via an SQL interface such as phpMyAdmin. After that, when your own project is finished and you want to make it usable for other users, you need an interface through which others can also insert data. In this case, you can link to the generated interface from the DB selfTables from your own project.

Or you just want to collect data for later use or something else. There are many more solutions you can use...


## INSTALLATION
dbselftable is implemented now for php 8 / 9<br />
and using as database MariaDb or MySql

You can download the .zip or .tar.zip package from last release on the right column and extract this in your project folder.
Or if you work with git, clone the main repository which should be the same.
```
git clone https://github.com/kollia/dbselftables.git
```
<br />

the folder structure should be:
```
| Folder               | Description                                                                  
|----------------------|--------------------------------------------------------------------------------
| dbselftables         |
| ├── design           | [ Needs to be reachable from website (contains .css and .png or .svg files) ]
| ├── environment      | [ PHP sources ]
| │   ├── base         | [ Base classes ]
| │   ├── db           | [ Classes to handle with database ]
| │   ├── html         | [ Own HTML classes ]
| │   ├── session      | [ Session objects ]
| ├── plugins          | [ Usable plugins for the project ]
| |
| ├── data             | [ Only required for Modelio UMLs - can be removed for productive use ]
| ├── examples         | [ Examples for learning and test cases - can be removed for productive use ]
| └── wiki             | [ Wiki content for GitHub - removable ]
```


## BASICs
As first try, you can use any database you want
```php
<?php

require_once 'dbselftables/st_pathdef.inc.php';
require_once $_stdbmariadb;
require_once $_stsitecreator;

$db= new STDbMariaDb();
$db->connect('<host>', '<user>', '<password>');
$db->database('<your preferred database>');

$creator= new STSiteCreator($db);
$creator->addCssLink('dbselftables/design/websitecolors.css');
$creator->execute();
$creator->display();

```
now you can have three solutions:
 - an empty page - because you choose a database with no tables
 - a listing of a table - only one table in database
 - all tables as buttons


> **Tipp:** sometimes I reffer to my example database [00__mariadb_tables.sql](examples/).
>           You can download and install as dump if you want to know from what I speak

the first what you should do is to define which column(s) describe the table as best.
In my example db there we have the tables `Country` and `State`. If you look on the website
clicking on the State button. You see the table with the columns:
`
state_id
name
country_id
`
but the table in the database has:
`
state_id
name
<font color="red">country</font>
`
The reason is, that the State table has an foreign key to the Country table and shows the primary key ('counry_id')
and not the own column ('country').
Pull the table 'Country' from the database object and identify the column as follow. <br />
There is also the possibility to select only the columns you want and give them an other name, also the table.
```php
$country= $db->getTable("Country");
$country->setDisplayName("existing Countries");
$country->identifColumn("name", "Country");
$country->select("name", "Name");

$state = $db->getTable("State");
$state->setDisplayName("States");
$state->select("name", "Name");
$state->select("country", "from Country");
```
You see now in table State as second position the name of the country as 'Country', alto you defined
the FK in table as 'from Country'. This you see by updating row or by insert (clicking on button 'new Entry')

> **Tipp:** for developing, it's a good choice to set after including 'st_pathdef.inc.php' 
> ```php STCheck::debug(true); ```
> it will show you php errors/warnings and make also more checks in the source

To sort the table, the user has always the possibility to order the table by clicking in the headlines. 
If you want an other order by begin, order the table with the command ->orderBy()
```php $state->orderBy("name"); ```

