
What is this?
This Project folder consists of server side api of online shopping system(barebone). 
The project is completely programmed using php and mysql is used as database.

How it is structured?
 	The project is divided into four files namely 
	1. database_connection.php
	2. getProductAndCartDetails.php
	3. purchaseProducts.php
	4. authentiation.php
These files can be found in api folder.

database_connection.php
	- This file handles all the database related operatons such as establishing a connection,executing a query and closing the connection with database.

getProductAndCartDetails.php
	- This file handles all the GET request by the user. The functionalities provided by this file are getting the product details either one at a time or all products at once.
	  And also getting the cart details of the user. Detailed description of all the methods defined in this file is discussed in getProductAndCartDetails_documentation which can be found in Documents folder.

purchaseProducts.php
 	- This file handles all the POST request by the user. The functionalities provided by this file are creating a cart, adding products to the cart,checking out the cart and emptying the cart.
	  Detailed description of all the methods defined in this file is discussed in purchaseProducts_documentation file which can be found in Documents folder.	
	  
authentiation.php
	- This file handles all the authentication requests. The functionalities provided by this file are getting the system salt and authenticating the security tokens.
	  Detailed description of all the methods defined in this file and how authentication works is discussed in authentication_documentation file which can be found in Documents folder.	

General process flow.
	The end user request the product details and get the results. Then he creates a cart to add products. He then add products to the cart and can verify it whether he has successfully added it by getting cart details.
	Then if he wishes, he can checkout the cart or empty it.
	Note: At a time only one cart can be used by a user. Until he checkouts that cart, he can't create a new one. Product inventory count won't get changed until the cart is checked out.

What not included!

         The code for creating an user and his details in database. That means the user and his credential details should be entered manually in database.
	 This project by default consists of two user called admin and john and their credentials.
	

Directory Structure:

            1. api directory consists of all the necessary source code.
	    2. Sql directory consist of sql file for the database population.
	    3. Documents directory consists of the documentation for major source code files.	
       	    4. Testing directory consists of some simple test cases for methods defined in getProductAndCartDetails.php and purchaseProducts.php file.


Software Requirements:
	    1. PHP 5.0 or above with curl and mysqli module installed
	    2. MySql
	    3. php-fpm and nginx or apache


Note - make sure that all the source files are in same directory. Otherwise please don't forget to update the url and file inclusion path accordingly in all source files.

Changing database details - change is only needed in database_connection.php 

Changing resource url - change in getPRoductAndCartDetails.php and purchaseProducts.php files.

Also, note that if you are copying any code from the document, kindly watch out for quotation marks as it differs from a normal document to php code


	    			
