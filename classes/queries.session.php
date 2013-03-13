<?php
/*
 * Website queries
 * 
 * Valid push_argument values are s=string, i=integer, d=double, b=blob
 */
include_once(__DIR__ . "/class.mysqli.php");
include_once(__DIR__ . "/db_config.php");

function sql_get_sess_data_by_id($id)
{
	$query = "SELECT data FROM sessions WHERE sessionId = ?";
	
	$db = connect('sessions');
	
	$db->set_query($query);
	
	$db->push_argument($id, 's'); 
	
	$result = $db->get_results(); 
	
	$db->close_mysqli_db();
	
	return $result;
}

function sql_update_sess_expiration_by_id($id,$exp)
{
	$query = "UPDATE sessions SET expiration= ? WHERE sessionId = ?";
	
	$db = connect('sessions');
	
	$db->set_query($query);
	
	$db->push_argument($exp, 'i'); 
	$db->push_argument($id, 's'); 
	
	$result = $db->get_results(); 
	
	$db->close_mysqli_db();
	
	return $result;
}

function sql_replace_sess_data($id,$exp,$data)
{
	$query = "REPLACE INTO sessions (sessionId, expiration, data) VALUES (?, ?, ?)";
	
	$db = connect('sessions');
	
	$db->set_query($query);
	 
	$db->push_argument($id, 's'); 
	$db->push_argument($exp, 'i');
	$db->push_argument($data, 's'); 
	
	$result = $db->get_results(); 
	
	$db->close_mysqli_db();
	
	return $result;
}

function sql_remove_sess_by_id($id)
{
	$query = "DELETE FROM sessions WHERE sessionId = ?";
	
	$db = connect('sessions');
	
	$db->set_query($query);
	 
	$db->push_argument($id, 's'); 
	
	$result = $db->get_results(); 
	
	$db->close_mysqli_db();
	
	return $result;
} 

function sql_get_old_sess_data()
{
	$query = "SELECT sessionId FROM sessions WHERE expiration < ?";
	
	$db = connect('sessions');
	
	$db->set_query($query);
	 
	$db->push_argument(time(), 'i'); 
	
	$result = $db->get_results(); 
	
	$db->close_mysqli_db();
	
	return $result;
}
 
?>