<?php

/*DATABASE CONNECTION FUNCTION
 * This function establishes a mysqli database connection
 */

/******************************************************************************
 * Database Functions
 *****************************************************************************/

function connect($override="",$custom_conn=false) 
{	
	$db = new mysqli_db; 
	
	// set the db login credentials
	$db->database_user = 'db_username';
    $db->database_pass = 'db_password';
    $db->database_host = 'db_hostname';
    $db->database_name = 'db_name';

	try 
	{
		$db->open_mysqli_db(); 
	} 
	catch (Exception $e) 
	{
	    echo 'Caught exception: ',  $e->getMessage(), "\n";
	}

	return $db;
}
