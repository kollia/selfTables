# DB selfTables
The goal of this project is for you to design your database table and automatically get a user-friendly GUI interface for all your generated tables in a short time.

This solution can be useful for research if you have written your own specific algorithm that uses data from the database. 
When first developing, you make your data available via an SQL interface such as phpMyAdmin. After that, when your own project is finished and you want to make it usable for other users, you need an interface through which others can also insert data. In this case, you can link to the generated interface from the DB selfTables from your own project.

Or you just want to collect data for later use or something else. There are many more solutions you can use...


## INSTALLATION
dbselftable is implemented now for php 8.x / 9.x<br />
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
<br />

## BASICs
All script files you found also in your `examples` subdirectory with the name defined before.

### first Scripting

As first try, you can use any database you want

<b>[ [01_first_try.php](examples/01_first_try.php)]</b>
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


> **Tipp:** The description reffer to an example database [00__mariadb_tables.sql](examples/00__mariaDB_tables.sql).
>           You can take this from subdirectory exampels/ and install as mariaDB dump
>           if you want to know from what is being talked about.

the first what you should do is to define which column(s) describe the table as best.<br />
In my example db there we have among other tables `Country` and `State`. If you look on the website
clicking on the `[State]` button. You see the table with the columns:
`state_id`, `name`, `country_id`
but the table in the database has:
`state_id`, `name`, <span style="color:red">`country`</span><br />
The reason is, that the State table has an foreign key to the Country table and shows the primary key ('`country_id`') of the other table
and not the own column ('`country`').<br />
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
You see now in table State as second position the name of the country as 'Country', altough you defined
the FK in table as 'from Country'. This you see by updating row or by insert (clicking on button 'new Entry')

> **Tipp:** for developing, it's a good choice to set after including 'st_pathdef.inc.php' 
>           ` STCheck::debug(true); ` it will show you php errors/warnings
>           and make also more checks in the source

To sort the table, the user has always the possibility to order the table by clicking in the headlines. 
If you want an other order by begin, order the table with the command ->orderBy()
```ex. $state->orderBy("name"); ```<br />
You can also limit the table listing with ->setMaxRowSelect(<count>)

here the full code for all tables:<br />
<b>[ [02_table_listing.php](examples/02_table_listing.php)]</b>
```php
<?php

require_once 'dbselftables/st_pathdef.inc.php';
require_once $_stdbmariadb;
require_once $_stsitecreator;

//STCheck::debug(true); // <- a good choice for developing

$db= new STDbMariaDb();
$db->connect('<host>', '<user>', '<password>');
$db->database('<your preferred database>');


$country= $db->getTable("Country");
$country->setDisplayName("existing Countries");
$country->identifColumn("name", "Country");
$country->select("name", "Name");
$country->setMaxRowSelect(20);

$state= $db->getTable("State");
$state->setDisplayName("States");
$state->identifColumn("name", "State");
$state->select("name", "Name");
$state->select("country", "from Country");
$state->orderBy("name");
$state->setMaxRowSelect(20);

$county= $db->getTable("County");
$county->identifColumn("name", "County");
$county->select("state", "State");
$county->select("name", "County");
$county->setMaxRowSelect(50);

$person= $db->getTable("Person");
$person->identifColumn("first_name", "first Name");
$person->identifColumn("last_name", "last Name");
$person->select("first_name", "first Name");
$person->select("last_name", "last Name");
$person->select("address", "Address");
$person->setMaxRowSelect(50);

$address= $db->getTable("Address");
$address->identifColumn("city", "City");
$address->identifColumn("street", "Street");
//$address->identifColumn("county", "County");
$address->select("city", "City");
$address->select("street", "Street");
$address->select("county", "from County");
$address->setMaxRowSelect(50);

$order= $db->getTable("Order");
$order->identifColumn("bill_id", "Order ID");
$order->select("bill_id", "Order ID");
$order->select("person", "for Person");
$order->select("article", "Article");
$order->select("amount", "Amount");
$order->setMaxRowSelect(50);

$article= $db->getTable("Article");
$article->identifColumn("title", "Article");
$article->select("title", "Article");
$article->select("content", "Description");
//$article->select("price", "Price");
$article->setMaxRowSelect(50);


$creator= new STSiteCreator($db);
$creator->addCssLink('dbselftables/design/websitecolors.css');
$creator->execute();
$creator->display();

```

<br /><br />
### structuring Website

Maybe this will be a little confusing when the user see all seven tables first.<br />
If you want to see only some tables, you can use needTable() instead of getTable().<br />
like this:<br />
```php
// configure tables with ->getTable("...")
$country= $db->getTable("Country");
// ... some configuration
// ... also other tables

$article= $db->needTable("Article");
// ... some configuration
// and
$order= $db->needTable("Bill");
// ... some configuration
```
In this case, you have all seven tables organized, but only see the two defined tables you need.

The idea of ​​the project is to have a container for each web page that can display one or more tables.<br />
&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;![STDbContainers](wiki/ContainerStack.png?raw=true "STDbContainer stack")

The database (STDbMariaDb) that you configured first is also a container.


For an second website create a new `STObjectContainer` from an existing container. The tables you have configured before are the same. Only you want other columns (identifColumns), you need to select the new colums.
The definition from the container before are the default config.
```php
$addressee= new STObjectContainer("addressee", $db);
$addressee->needTable("Country");
$addressee->needTable("State");
$addressee->needTable("County");
$addressee->needTable("Person");
$addressee->needTable("Address");
$addressee->setFirstTable("Person");

$db->needContainer($addressee);
```
You see that the container `STObjectContainer` need a name. This is also for the database which have as default the name `main-menue`.
(If you need an second other database, you have to define in the constructor)<br />
To link to this created other container, the container object is implemented with <nobr>`->needContainer(<object>)`</nobr> and you have access to them over an button like the other tables.
It is also possible to link to an container over an table entry, see below as in the table Bill to Order.

Now let us organize the scripts inside two files.<br />
<b>[ [03_common_db.php](examples/03_common_db.php) ]</b>
```php
<?php

require_once 'dbselftables/st_pathdef.inc.php';
require_once $_stdbmariadb;
require_once $_stsitecreator;

//STCheck::debug(true); // <- a good choice for developing

$db= new STDbMariaDb();
$db->connect('<host>', '<user>', '<password>');
$db->database('<your preferred database>');


$country= $db->getTable("Country");
$country->setDisplayName("existing Countries");
$country->identifColumn("name", "Country");
$country->select("name", "Name");
$country->setMaxRowSelect(20);

$state= $db->getTable("State");
$state->setDisplayName("States");
$state->identifColumn("name", "State");
$state->select("name", "Name");
$state->select("country", "from Country");
$state->orderBy("name");
$state->setMaxRowSelect(20);

$county= $db->getTable("County");
$county->identifColumn("name", "County");
$county->select("state", "State");
$county->select("name", "County");
$county->setMaxRowSelect(50);

$person= $db->getTable("Person");
$person->identifColumn("first_name", "first Name");
$person->identifColumn("last_name", "last Name");
$person->select("first_name", "first Name");
$person->select("last_name", "last Name");
$person->select("address", "Address");
$person->setMaxRowSelect(50);

$address= $db->getTable("Address");
$address->identifColumn("city", "City");
$address->identifColumn("street", "Street");
//$address->identifColumn("county", "County");
$address->select("city", "City");
$address->select("street", "Street");
$address->select("county", "from County");
$address->setMaxRowSelect(50);

$bill= $db->getTable("Bill");
$bill->identifColumn("bill_id", "Bill");
$bill->select("bill_id", "Bill");
$bill->select("person", "for Person");
$bill->setMaxRowSelect(50);

$order= $db->getTable("Order");
$order->select("order_id", "Order ID");
$order->select("bill", "Bill");
$order->select("count", "Count");
$order->select("article", "Article");
$order->setMaxRowSelect(50);

$article= $db->getTable("Article");
$article->identifColumn("title", "Article");
$article->identifColumn("price", "Price");
$article->select("title", "Article");
$article->select("content", "Description");
$article->select("price", "Price");
$article->setMaxRowSelect(50);

```

<b>[ [04_basic_main.php](examples/04_basic_main.php) ]</b>
```php
<?php

require_once 'common_db.php';

//STCheck::debug("query"); // <- to see current query from URL

$addressee= new STObjectContainer("addressee", $db);
$addressee->setDisplayName("Addressee");
$addressee->needTable("Country");
$addressee->needTable("State");
$addressee->needTable("County");
$addressee->needTable("Person");
$addressee->needTable("Address");
$addressee->setFirstTable("Person");

$orderContainer= new STObjectContainer("Order", $db);
$orderContainer->needTable("article");
$orderTable= $orderContainer->needTable("Order");
$orderContainer->setFirstTable("Order");
if( $orderContainer->getContainerName() == "Order" &&
    $orderContainer->getTableName() == "Order" &&
    $orderContainer->getAction() == STINSERT       )
{
    $query= new STQueryString();
    $limitation= $query->getLimitation("bill");
    $bill_id= $limitation['bill_id'];
    $orderTable->preSelect("bill", $bill_id);
}

$main= new STObjectContainer("bill", $db);
$main->needContainer($addressee);
$main->needTable("Article");
$main->setFirstTable("Bill");
$bill= $main->needTable("Bill");
$bill->namedLink("bill_id", $orderContainer);


$creator= new STSiteCreator($main);
$creator->addCssLink('dbselftables/design/websitecolors.css');
$creator->execute();
$creator->display();

```
When you test this scripts, you can see as first a listing of all bills, where every
bill has an ID which you link to all exist orders of the bill.<br />
The reason is, because by creating the main container (<code>$main</code>) the Bill table defined as need
and the column `bill_id` defined with the <code>$orderContainer</code> as `->namedLink()`.

After organising the container <code>$orderContainer</code>, you can see an request of the current container, table and action.
This you can do for every table if you want better performance. Because tables and containers not always need to organise when not displayed.
> If you want developing object oriented, it's also possible to overload class `STObjectContainer`.
> You can organize container inside the method create() and init().
> ```php
> class MyNewContainer extends STObjectContainer
> {
>      protected function create()
>      {
>           // definition of which tables and
>           // other containers need from this container
>           // and maybe an other display-name if you want
>      }
>
>      protected function init(string $action, string $table)
>      {
>           // all other definitions
>           // which need for current container
>      }
> }
> ```


### change listing content with callbacks



## use DB selftables only as pur sql client



## Design

As described before, the DB selftables should create fast results for you own research projects to handle data on database.
There is no need to make the project particularly beatifull at first if you don't even know if the project is useful.
But there are many helpers to make the functionality of tables and containers more clear.

Here we will first describe how to create better functionality,<br />
and then, if you want to make the project more beautiful later because you know you can use it productively,<br />
how to improve the containers with additional HTML tags.

### Functionality
#### Tables
#### Containers

### HTML tags
The Idea of `STObjectContainer` and `STSiteCreator` is that they are derived from a &lt;body&gt;-Tag and &lt;html&gt;-Tag.
You can always add html-Tags before and after <nobr>`STSiteCreator::execute()`.</nobr><br />





