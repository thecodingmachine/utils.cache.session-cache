<?php
namespace Mouf\Utils\Cache;

/**
 * This package contains a cache mechanism that relies on the session of the user.
 * Therefore, the cache is a bit special, since it is kept for the duration of the session, and is only
 * accessible by the current user. The session has to be started (using session_start()).
 *
 * Storage occurs in the column this way:
 * 	$_SESSION["sessioncache"][$key] = array($value, $timetolive)
 * 
 * @Component
 */
class SessionCache implements CacheInterface {
	
	/**
	 * The default time to live of elements stored in the session (in seconds).
	 * Please note that if the session is flushed, all the elements of the cache will disapear anyway.
	 * If empty, the time to live will be the time of the session. 
	 *
	 * @Property
	 * @var int
	 */
	public $defaultTimeToLive;
	
	/**
	 * The logger used to trace the cache activity.
	 *
	 * @Property
	 * @Compulsory
	 * @var LogInterface
	 */
	public $log;
	
	private static $CACHE_KEY = "sessioncache";
	
	/**
	 * Returns the cached value for the key passed in parameter.
	 *
	 * @param string $key
	 * @return mixed
	 */
	public function get($key) {
		if (isset($_SESSION[self::$CACHE_KEY]) && isset($_SESSION[self::$CACHE_KEY][$key])) {
			$arr = $_SESSION[self::$CACHE_KEY][$key];
			if ($arr[1] == null || $arr[1] > time()) {
				$this->log->trace("Retrieving key '$key' from session cache.");
				return $arr[0];
			} else {
				// The cache is outdated, let's flush it:
				unset($_SESSION[self::$CACHE_KEY][$key]);
				$this->log->trace("Retrieving key '$key' from session cache: key outdated, cache miss.");
				return null;
			}
		} else {
			$this->log->trace("Retrieving key '$key' from session cache: cache miss.");
			return null;
		}
	}
	
	/**
	 * Sets the value in the cache.
	 *
	 * @param string $key The key of the value to store
	 * @param mixed $value The value to store
	 * @param float $timeToLive The time to live of the cache, in seconds.
	 */
	public function set($key, $value, $timeToLive = null) {
		$this->log->trace("Storing value in cache: key '$key'");
		if ($timeToLive == null) {
			if (empty($this->defaultTimeToLive)) {
				$_SESSION[self::$CACHE_KEY][$key] = array($value, null);
			} else {
				$_SESSION[self::$CACHE_KEY][$key] = array($value, time() + $this->defaultTimeToLive);
			}
		} else {
			$_SESSION[self::$CACHE_KEY][$key] = array($value, time() + $timeToLive);
		}
	}
	
	/**
	 * Removes the object whose key is $key from the cache.
	 *
	 * @param string $key The key of the object
	 */
	public function purge($key) {
		$this->log->trace("Purging key '$key' from session cache.");
		if (isset($_SESSION[self::$CACHE_KEY])) {
			unset($_SESSION[self::$CACHE_KEY][$key]);
		}
	}
	
	/**
	 * Removes all the objects from the cache.
	 *
	 */
	public function purgeAll() {
		$this->log->trace("Purging the whole session cache.");
		unset($_SESSION[self::$CACHE_KEY]);
	}
}
?>