# ReadyAPI
ReadyAPI is a PHP module to create REST API quickly
## Install
Import with Git :
```bash
$ git clone https://github.com/Olafr9500/ReadyAPI.git <your Project>/api
```

And get the dependencies and build the autoload with Composer :
```bash
$ composer run-script build
```
## How to use it
> In your database, make sure each table has a primary key in the first position.

### Database class file
- Create an object in the folder **`database`** with the same name as your database. You can copy the file **`database/databaseSample.php`** and rename it.

- In the construct function, choose the host name, the database name and the identifiers
    ```php
    parent::__construct("<Name_Server>", "<Name_DataBase>", "<Username>", "<Password>");
    ```
    > *The connector type is defined in the extends of the database object.*
    ```php
    class DatabaseSample extends DatabaseMySql
    ```
### Object class file
- Create an object in the folder `object` with the same name as your table. You can copy the file **`object/sampleObject.php`** and rename it.
- Rename the object's class name to that of your table.
    ```php
    class SampleObject extends ObjectMySql
    ```
- Add for each column a public variable in the object.
    ```php
    public $variable1;
    public $variable2;
    public $variable3;
    ```
- In the construct function, add the table name, the list of all column names and the list of all new column.
    ```php
    parent::__construct($db, "sampleObject", ["id", "variable1", "variable2", "variable3"], ["id", "variable1", "variable2", "variable3"]);
    ```
    > *Leave "id" in the first position*
- Write the "isEmpty" and "isDataCorrect" functions so that they correspond to the specifics of your variables.
### CRUD folder
- Copy the folder **`sampleObjectMySql/`** and rename it with the name of your table.
- In each file of the folder, rename the calls of the file or of the sample class by that of your table.
    ```php
    include_once '../object/sampleObjectMySql.php';
    ...
    $sample = new SampleObject($database->conn);
    ```
### Make API secure less
- In the file **`config/function.php`**, change the "SECURE_API" constant from true to false.
    ```php
    define("SECURE_API", (true|false), true);
    ```

### Build for autoload
- After writing your objects, build the application to take them into autoload.
    ```bash
    $ composer run-script build
    ```
## About
### Contribution
Make pull requests to help the project :D
### Author
***Olafr9500***
* [github/Olafr9500](https://github.com/Olafr9500)
### License
Copyright © 2021, [Olafr9500](https://github.com/Olafr9500).
Released under the [MIT License](LICENSE).