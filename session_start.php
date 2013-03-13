<?php
include_once(__DIR__ . '/classes/class.session.php');
include_once(__DIR__ . '/classes/queries.session.php');

$sessionHandler = new App_SessionHandler();

session_set_save_handler(array (&$sessionHandler,"open"),
						 array (&$sessionHandler,"close"),
						 array (&$sessionHandler,"read"),
						 array (&$sessionHandler,"write"),
						 array (&$sessionHandler,"destroy"),
						 array (&$sessionHandler,"gc")
						);

session_start();
