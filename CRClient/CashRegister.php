<?php
/**
* This is default Cash Register Class Used to control the application 
*/
class CashRegister {
	var $address;
	var $port;
	var $driver;
	var $driversPath = '../CRClient/drivers/';
	var $toast = "";
	
	function __construct( $server = null, $port = null, $driver = null ) {
		if( $server != null )
			$this->server = $server;
		if( $port != null )
			$this->port = $port;
		if( $driver != null )
			$this->setDriver( $driver );
	}

	/**
	 * Set Cash Register Server IP address and port
	 * @param string address - Server IP address
	 * @param int port - Server port
	 */
	function setServer( $address, $port ) {
		$this->address = $address;
		$this->port = $port;
	}
	
	/**
	 * Set Cash Register Driver
	 * @param string name - Driver name to open
	 * @return boolean
	 */
	function setDriver($name) {
		$driver = $this->driversPath.$name.".php";
		try {
			if( ! file_exists($driver) ) throw new Exception("Selected file is not a driver!");
			include($driver);
			$drv = new $name();
			if( ! is_a($drv, 'Driver') ) throw new InvalidArgumentException("Drivers should extend Driver class");
		}  catch (Exception $e) {
			$this->toast = $e->getMessage();
			return false;
		}
        $this->driver = $drv;
		$this->driver->cr = $this;
		return true;
	}

    /**
     * Calls a method from a remote object
     * @param string name - Method name
     * @param array parameters - Parameters passed to the method
     * @return Exception
     */
	function __call( $name, $parameters ) {
		if( $this->driver == null ) return new Exception("Driver is not set!");
		if( !method_exists( $this->driver, $name ) ) return new Exception("Driver is missing function ({$name})");
		call_user_func_array(array($this->driver,"{$name}"), $parameters );
	}
}