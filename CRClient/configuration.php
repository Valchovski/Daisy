<?php 
/**
* This is the Configuration class that stores the settings for the application
*/
class Configuration {
	var $address = "127.0.0.1";
	var $port = 10101;
	var $driver = "Daisy";
	var $drivers = array();
	
	/**
	 *	Loads the initial settings from a config.dat file
	 *	@return object
	 */
	static function load() {
		if( !file_exists('../demo/config.dat') )
			return new Configuration();
		return unserialize( file_get_contents('../demo/config.dat') );
	}
	
	/**
	 *	Loads all drivers found in a directory
	 */
	function load_all_drivers() {
		$path = '../CRClient/drivers';
		$this->drivers = scandir($path);
		$this->drivers = array_diff(scandir($path), array('.', '..'));
		foreach( $this->drivers as &$driver ) {
			$driver = pathinfo($driver, PATHINFO_FILENAME);
		}
	}
	
	/**
	 *	Save the current settings to a config.dat file
	 */
	function save() {
		file_put_contents( '../demo/config.dat', serialize($this) );
	}
}
?>