Project Name : 
--------------
bizyStore

What is it:
-----------
Object Interface for Databases (OID), database in this context means relational databases, however I wouldn't call it an ORM.
It does do Object to Relational Mappings, but a much better description is that it presents your database(s) as Objects. 
This includes tables + views, columns + column meta-data, primary keys, unique keys, sequences, foreign keys, and the concept of key candidates.

Features:
---------
+ Takes away the complexity of database coding and allows you to focus on writing robust applications.
+ Small footprint.
+ Easy installation.
+ Easy configuration. Just set up your config file and away you go.
+ Easy to use. Simple CRUD interface and automatic schema generation, no huge framework API's to learn.
+ Has support for accessing multiple databases at the same time from different vendors.
+ The popular free databases MySQL ,SQLite and PostgreSQL are supported.
+ Extensible through plugins to support other databases without touching existing core code.
+ Core code uses prepared statements via PHP PDO so your data is safe from SQL injection attacks.

Where do I get it:
------------------
Our GitHub repository contains everything you need.

How do I use it:
--------------
Documentation in the form of a user guide and also PHPDoc is available from [our website](http://www.bizysoft.com.au).

The easiest way to start the ball rolling is to:
+ Copy the "bizySoft" directory from this distribution to somewhere on your include_path OUTSIDE your web server's DOCUMENT_ROOT
+ Put your database connection details in bizySoft/bizyStore/config/bizyStoreConfig.xml.
+ Use the automatically generated classes to start coding your database application straight away. 
You can look at the User Guide, example files and test cases to guide you in the right direction.

Helping out:
------------
Our software is free for you to use under the license terms. However if you find our software helpful in any way, 
the best way to contribute is to hire us to work for you, details at [our website](http://www.bizysoft.com.au/contribute.php).

Contact:
--------
Chris Maude through [our website](http://www.bizysoft.com.au).


