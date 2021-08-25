# ReadyAPI
ReadyAPI is a PHP module to create REST API quickly
## Install
Import with Git :
```bash
$ git clone https://github.com/Olafr9500/ReadyAPI.git <your Project>/api
```

And get the dependenties with Composer :
```bash
$ composer update
```
## How to use it
### MySQL
> In your MySql database, make sure each table has a primary key in the first position.
#### Object class file
- Create an object in the folder with the same name as your table. You can copy the file **`object/sample-objectMySql.php`** and rename it.
- Rename the object's class name to that of your table.
    ```php
    class SampleObject extends ObjectMySql
    ```
- Add for each column a public varible in the object.
    ```php
    public $variable1;
    public $variable2;
    public $variable3;
    ```
- In the construct function, add the table name and the list of all new column names.
    ```php
    parent::__construct($db, "sampleObject", ["id", "variable1", "variable2", "variable3"]);
    ```
    > *Leave "id" in the first position*
- Write the "isEmpty" and "isDataCorrect" functions so that they correspond to the specifics of your variables.
#### CRUD folder
- Copy the folder **`sample-objectMySql/`** and rename it with the name of your table.
- In each file of the folder, rename the calls of the file or of the sample class by that of your table.
    ```php
    include_once '../object/sample-objectMySql.php';
    ...
    $sample = new SampleObject($database->conn);
    ```
#### Make API secure less
- In the file **`config/core.php`**, change the "SECURE_API" constant from true to false.
    ```php
    define("SECURE_API", (true|false), true);
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