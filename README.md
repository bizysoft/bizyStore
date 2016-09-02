bizyStore
--------------
PHP Object Interface for Databases.

What is it
-----------
Similar to the classic ORM in that it does Object to Relational Mapping and vice-versa, but a much better 
description is that it presents your database(s) as Objects without your intervention.

Can be used in any PHP environment, particularly suited for database integration into your hosted website. Configuring your database connection details in XML is all that's required.

Store, retrieve and manipulate your data with ease. Use the basics including tables & views, columns & column meta-data through a simple CRUD interface. When you are ready, more sophisticated bizyStore techniques that use database primary keys, unique keys, sequences, foreign keys, and the concept of key candidates can help manipulate your data.

bizyStore makes it easy. No need to get caught up in the complexities of database coding again.

Features
---------
+ Suitable for hosted environments.
+ Takes away the complexity of database coding and allows you to focus on writing robust applications.
+ Has support for accessing multiple databases at the same time from different vendors.
+ Small footprint.
+ Easy installation.
+ Easy configuration. Just set up your config file and away you go.
+ Easy to use. Simple CRUD interface and automatic Model/Schema generation.
+ No boiler-plate code to write or huge framework API's to learn.
+ The popular free databases MySQL ,SQLite and PostgreSQL are supported.
+ Extensible through plugins to support other databases without touching existing core code.
+ Core code uses prepared statements via PHP PDO so your data is safe from SQL injection attacks.

Where do I get it
------------------
Our GitHub repository contains everything you need. No external dependencies, other than a PHP installation and your database(s).

How do I use it
--------------
Documentation in the form of a user guide and also PHPDoc is available from [our website](http://www.bizysoft.com.au).

The easiest way to start the ball rolling is to:

+ Copy the "bizySoft" directory from this distribution to somewhere on your include_path OUTSIDE your web server's DOCUMENT_ROOT
+ Put your database connection details in bizySoft/bizyStore/config/bizyStoreConfig.xml.
+ Use the automatically generated Model classes to start coding your database application straight away. 

You can look at the User Guide, example files and test cases to guide you in the right direction.

Here is some code based on our examples that shows bizyStore's ease of use. It's a fully functional PHP file that can store Member details from an HTML form post action into a database. 'Member' is a generated Model class which refers to a database table called 'member'. Boiler-plate code is not necessary, it's handled once during automatic Model/Schema generation. SQL injection issues are handled as a matter of course.

```php
<?php
include "bizySoft/bizyStore/services/core/bootstrap.php";

use bizySoft\bizyStore\app\bizyStoreExample\Member;

$member = new Member($_POST);
$member->create();
?>
```

Helping out
------------
Feel free to use the normal GitHub channels to download the distribution and suggest changes etc.

If you find our software helpful in any way, the best way to contribute is to hire us to work for you, details at [our website](http://www.bizysoft.com.au/contribute.php).

License
------------
Our software is free for you to use under the license terms. See the LICENSE file with the distribution.

Contact:
--------
Chris Maude through [our website](http://www.bizysoft.com.au).


