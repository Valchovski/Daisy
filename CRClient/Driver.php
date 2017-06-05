<?php
/**
* This is default Driver class that contains some logic to child drivers
*/
abstract class Driver {
	var $name;
	var $cr;
	
	function __construct() {
		$this->init();
	}
	
	/**
	 *	Parses the received packet for problems and warnings.
	 */
	abstract protected function ParsePacket( $packet );
	/**
	 *	Initial settings  setup
	 */
	abstract protected function init();
	/**
	 *	Get the critical errors parsed from the packet
	 */
	abstract public function GetProblems();
	/**
	 *	Get the non-critical warnings parsed from the packet
	 */
	abstract public function GetStatus();

	function SendToServer($packet, $url = '/') {
		$config = Configuration::load();
		$url = $config->address . ':' . $config->port . $url;
		
		$options = array(
			'http' => array(
				'header'  => "Content-type: raw",
				'method'  => 'POST',
				'content' => $packet,
			)
		);

		$context  = stream_context_create($options);
		
		$result = file_get_contents( "http://" . $url, false, $context );

		if ($result != false) {
			return $result;
		}
		return "There is a problem with the connection";
	}
}
?>