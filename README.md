# DbManager
DbManager is basically used to update your running database without happer your running data.
As well you can manage the executed sql's log.

Feature:
* Ability to update multiple database
* Log creation of the executed sql
* Data updates without disturb of the old records
* Nice execution response, where you can find the no of query executed
* INI File mechanism to process sql 

* Steps to Use of the this app

Step 1: Provide your database credential inside the db.ini file. which is ease to read.

Step 2: Provide your running sprint.ini file, i used sprint naming as most of the project are runningunder agile methodology.

Step 3: Make sure the ini file must have the ini format, other script will return error that given ini file has some corruption.

Step 4: Now execute the script file, like

```php
 # php script.php
```
OR if your have zend server installed
```php
# /usr/local/zend/bin/php script.php
```

Step 5: you may get he following error:
 # mysqli not installed
Then you need to install the mysqli in your machine to use the script. mysqli is the imporoved version of mysql


Step 6: Whn your script ran successfully then your will see the db_log.txt file where all the running sqls are logged.
But make sure db_log.txt file must have the full permission.

Script will automate the sprint.ini file to sprint.ini.txt file, just for your executed reference.


I hope it will help you to upgrade your database tables on your regular interval of updates.
