# DB selfTables
The goal of this project is for you to design your database table and automatically get a user-friendly GUI interface for all your generated tables in a short time.

This solution can be useful for research if you have written your own specific algorithm that uses data from the database. 
When first developing, you make your data available via an SQL interface such as phpMyAdmin. After that, when your own project is finished and you want to make it usable for other users, you need an interface through which others can also insert data. In this case, you can link to the generated interface from the DB selfTables from your own project.

Or you just want to collect data for later use or something else. There are many more solutions you can use...


## INSTALLATION
dbselftable is implemented now for php 8 / 9<br />
and using as database MariaDb/MySql

You can download the .zip or .tar.zip packages from last release on the right column and extract this in your project folder.<br />
Or if you work with git, clone the main repository which should be the same.
```
git clone https://github.com/kollia/dbselftables.git
```

the folder structure be:
```
    | Folder/File          | Description                                                                  |
    |----------------------|------------------------------------------------------------------------------|
    | dbselftables         |                                                                              |
    | ├── data             | [Only need Modelio UMLs - can be removed for productive use]                 |
    | ├── design           | [Needs to be reachable from webspace (contains .css and .png or .svf files)] |
    | ├── environment      | [PHP sources]                                                                |
    | │   ├── base         | [Base classes]                                                               |
    | │   ├── db           | [Classes to handle database]                                                 |
    | │   ├── html         | [Own HTML classes]                                                           |
    | │   ├── session      | [Session objects]                                                            |
    | ├── plugins          | [Usable plugins for the project]                                             |
    | ├── examples         | [Examples for learning and test cases - can be removed for productive use]   |
    | └── wiki             | [Wiki content for GitHub - removable]                                        |
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
$creator->execute();
$creator->display();

```
now you can have three solutions:
 - an empty page - because you choose a database with no tables
 - a list of a table - only one table in database
 - all tables as buttons