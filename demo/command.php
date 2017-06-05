<?php
include( '../CRClient/configuration.php' );
include( '../CRClient/CashRegister.php' );
/**
* This is the class that contains the commands logic
*/
class Commands {
	var $command;
	var $cr;
	
	function __construct () {
		$this->command = $_GET['cmd'];
		$config = Configuration::Load();
		$this->cr = new CashRegister($config->address, $config->port, $config->driver);
	}
	
	/**
	 *	Moves Paper by amount of lines
	 */
	function movePaper() {
		try {
			$this->cr->movePaper('1');
			$this->saveStatus();
		} catch (Exception $e) {
			header('Location: index.php?error='.urlencode($e->getMessage())); die();
		}
	}
	
	/**
	 *	Starts a non-fiscal receipt
	 */
	function beginReceipt() {
		try {
			$data = $this->cr->beginReceipt();
			$this->saveStatus();
			// var_dump( $data ); // Example of returned data
			// die();
		} catch (Exception $e) {
			header('Location: index.php?error='.urlencode($e->getMessage())); die();
		}
	}
	
	/**
	 *	Starts a fiscal receipt
	 */
	function beginFiscalReceipt() {
		try {
			$data = $this->cr->beginFiscalReceipt('20', '9999');
			$this->saveStatus();
			//var_dump( $data ); // Example of returned data
			// die();
		} catch (Exception $e) {
			header('Location: index.php?error='.urlencode($e->getMessage())); die();
		}
	}
	
	/**
	 *	Adds an item to the fiscal receipt
	 */
	function addItem() {
		
		$item = iconv("UTF-8", "Windows-1251", $_POST['item']);
		$price = $_POST['price'];
		$qty = $_POST['quantity'];
		
		try {
			$data = $this->cr->addItem($item, ' ', 'А', '+', $price, $qty);
			$this->saveStatus();
			// var_dump( $data ); // Example of returned data
			// die();
		} catch (Exception $e) {
			header('Location: index.php?error='.urlencode($e->getMessage())); die();
		}
	}
	
	/**
	 *	Sums the fiscal receipt total
	 */
	function total() {
		try {
			$data = $this->cr->total();
			$this->saveStatus();
			// var_dump( $data ); // Example of returned data
			// die();
		} catch (Exception $e) {
			header('Location: index.php?error='.urlencode($e->getMessage())); die();
		}
	}
	
	/**
	 *	Finalizes an open non-fiscal receipt
	 */
	function endReceipt() {
		try {
			$data = $this->cr->endReceipt();
			$this->saveStatus();
			// var_dump( $data ); // Example of returned data
			// die();
		} catch (Exception $e) {
			header('Location: index.php?error='.urlencode($e->getMessage())); die();
		}
	}
	
	/**
	 *	Finalizes an open fiscal receipt
	 */
	function endFiscalReceipt() {
		try {
			$data = $this->cr->endFiscalReceipt();
			$this->saveStatus();
			// var_dump( $data ); // Example of returned data
			// die();
		} catch (Exception $e) {
			header('Location: index.php?error='.urlencode($e->getMessage())); die();
		}
	
	}
	
	/**
	 *	Saves the problems and warnings to files in the root directory that are opened in the index
	 */
	function saveStatus() {
		$problems = $this->cr->GetProblems();
		$status = $this->cr->GetStatus();
		file_put_contents('problems.dat', serialize($problems));
		file_put_contents('status.dat', serialize($status));
	}
	
	/**
	 *	Call the command that is currently called from the user interface
	 */
	function run() {
		switch( $this->command ) {
			case 'movePaper': $this->movePaper(); break;
			case 'beginReceipt': $this->beginReceipt(); break;
			case 'beginFiscalReceipt': $this->beginFiscalReceipt(); break;
			case 'addItem': $this->additem(); break;
			case 'total': $this->total(); break;
			case 'endReceipt': $this->endReceipt(); break;
			case 'endFiscalReceipt': $this->endFiscalReceipt(); break;
			default; header('Location: index.php?error=Unknown%20command'); die();
		}

		header('Location: index.php?success=Command%20executed%20successfully');
	}
}

$c = new Commands ();
$c->run();
?>