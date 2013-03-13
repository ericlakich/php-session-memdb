<?php
/**
 * Memcache & MySQL PHP Session Handler
 */
class App_SessionHandler
{
	/**
	 * @var int
	 * 
	 */
	public $lifeTime;

	/**
	 * @var Memcached
	 * 
	 */
	public $memcache;
 
	/**
	 * @var string
	 * 
	 */
	public $initSessionData;
 
	/**
	 * interval for session expiration update in the DB
	 * @var int
	 * 
	 */
	private $_refreshTime = 600; // 10 minutes
 
	/**
	 * constructor of the handler - initialises Memcached object
	 *
	 * @return bool
	 * 
	 */
	function __construct()
	{
		#this ensures to write down and close the session when destroying the handler object
		register_shutdown_function("session_write_close");
 
		$this->memcache = new Memcache;
		
		$this->memcache->connect('MEMCACHE_NODE_ADDRESS_HERE', 11211);
 
		$this->lifeTime = intval(ini_get("session.cookie_lifetime"));
		
		$this->initSessionData = null;
		
		return true;
	}
 
	/**
	 * opening of the session - mandatory arguments won't be needed
	 * we'll get the session id and load session data, it the session exists
	 *
	 * @param string $savePath
	 * @param string $sessionName
	 * @return bool
	 * 
	 */
	function open($savePath, $sessionName)
	{
		$sessionId = session_id();
		
		if ($sessionId !== "") {
			$this->initSessionData = $this->read($sessionId);
		}
 
		return true;
	}
 
	/**
	 * closing the session
	 *
	 * @return bool
	 * 
	 */
	function close()
	{
		$this->lifeTime = null;
		// $this->memcache = null;
		$this->initSessionData = null;
 
		return true;
	}
 
	/**
	 * reading of the session data
	 * if the data couldn't be found in the Memcache, we try to load it from the DB
	 * we have to update the time of data expiration in the db using _updateDbExpiration()
	 * the life time in Memcache is updated automatically by write operation
	 *
	 * @param string $sessionId
	 * @return string
	 * 
	 */
	function read($sessionId)
	{
		$now = time();
	
		$data = $this->memcache->get($sessionId);
	
		if ($data===false) 
		{
			#the record could not be found in the Memcache, loading from the db
			$r = sql_get_sess_data_by_id($sessionId);

			if (is_array($r)) 
			{
				#record found in the db
				$data = $r[0]['data'];
 
				$this->_updateDbExpiration($sessionId, $now);
			} 
			else 
			{
				#record not in the db
			}
		} 
		else 
		{
			#time of the expiration in the Memcache
			$expiration = $this->memcache->get('db-'.$sessionId);
			
			if($expiration) 
			{
				#if we didn't write into the db for at least
				#$this->_refreshTime (5 minutes), we need to refresh the expiration time in the db
				if(($now - $this->_refreshTime) > ($expiration - $this->lifeTime)) 
				{
					$this->_updateDbExpiration($sessionId, $now);
				}
			} 
			else 
			{
				$this->_updateDbExpiration($sessionId);
			}
		}
 
		$this->memcache->set($sessionId, $data, false, $this->lifeTime);
		
		return $data;
	}
 
	/**
	 * update of the expiration time of the db record
	 *
	 * @param string $sessionId
	 * @param int $now UNIX timestamp
	 * 
	 */
	private function _updateDbExpiration($sessionId, $now=null)
	{
		if(!$now) 
			$now = time();
		
		$expiration = $this->lifeTime + $now;
 		
		$r = sql_update_sess_expiration_by_id($sessionId,$expiration);
		
		#we store the time of the new expiration into the Memcache
		$this->memcache->set('db-'.$sessionId, $expiration, false, $this->lifeTime);
		
		$this->_refreshCookie();
	}
	
	private function _refreshCookie()
	{
		setcookie(
			ini_get("session.name"),
			session_id(),
			time()+ini_get("session.cookie_lifetime"),
			ini_get("session.cookie_path"),
			ini_get("session.cookie_domain"),
			ini_get("session.cookie_secure"),
			ini_get("session.cookie_httponly")
		);	
	}
 
	/**
	 * cache write - this is called when the script is about to finish, or when session_write_close() is called
	 * data are written only when something has changed
	 *
	 * @param string $sessionId
	 * @param string $data
	 * @return bool
	 * 
	 */
	function write($sessionId, $data)
	{
		$now = time();
 
		$expiration = $this->lifeTime + $now;
 
		#we store time of the db record expiration in the Memcache
		$result = $this->memcache->set($sessionId, $data, false, $this->lifeTime);
 
		if ($this->initSessionData !== $data) 
		{
			$r = sql_replace_sess_data($sessionId,$expiration,$data);
 
			$this->memcache->set('db-'.$sessionId, $expiration, false, $this->lifeTime);
		}
		
		return $result;
	}
 
	/**
	 * destroy of the session
	 *
	 * @param string $sessionId
	 * @return bool
	 * 
	 */
	function destroy($sessionId)
	{
		$this->memcache->delete($sessionId);
		$this->memcache->delete('db-'.$sessionId);
		sql_remove_sess_by_id($sessionId);
 
		return true;
	}
 
	/**
	 * called by the garbage collector
	 *
	 * @param int $maxlifetime
	 * @return bool
	 * 
	 **/
	function gc($maxlifetime)
	{
		$r = sql_get_old_sess_data();

		if (is_array($r))
		{
			foreach($r as $row=>$col)
			{
				$this->destroy($col["sessionId"]);	
			}
		}
		
		return true;
	}
}

