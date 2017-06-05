<?php
include("../CRClient/Driver.php");
/**
* This is default Daisy Class that acts as a driver to all Daisy devices
*/
class Daisy extends Driver {
	var $name = 'Daisy';
	var $status = array();
	var $cmd_seq = 0x20;
	var $data_offset = 5;
	
	protected function init() {
		if (!file_exists( 'daisy.cmd.seq' )) {
			$this->updateSeq();
		}
		$this->cmd_seq = unserialize(file_get_contents( 'daisy.cmd.seq' ));
	}

	/**
	 *	Moves the cash register's paper
	 *	@param string lines - Amount of lines to move the paper
	 */
	function movePaper($lines) {
		$this->BuildPacket(0x2C, $lines, '/move_paper');
		//No data - empty packet
	}

	/**
	 *	Initializes a new receipt
	 */
	function beginReceipt() {
		$this->BuildPacket(0x26, null, '/begin_receipt');
		//No data - empty packet
	}
	
	/**
	 *	Begins a fiscal receipt
	 *	@param string clerkNum - Operator number
	 *	@param string password - Operator password
	 *	@param boolean invoice - [Optional parameter] Default - false
	 *	@return array
	 */
	function beginFiscalReceipt($clerkNum, $password, $invoice = false) {
		if( $clerkNum < 1 || $clerkNum > 20 )
			throw new Exception("Clerk Num must be between 1 and 20");
		if (strlen($password) > 6) 
			throw new Exception("Password must be between 1 and 6 digits long");
		$data = str_split( $clerkNum );
		$data[] = ',';
		$data = array_merge( $data, str_split( $password ) );
		$data[] = ',';
		if( $invoice )
		{
			$data[] = pack('C', 0x01); // TillNum
			$data[] = 'I'; // Invoice
		}
		$payload = $this->BuildPacket(0x30, $data, '/begin_fiscal_receipt');
		if( $payload == null ) 
			return null;
		$all_receipt = unpack( 'I*', implode(array_slice($payload, 0, 4)) );
		$fisc_receipt = unpack( 'I*', implode(array_slice($payload, 4, 4)) );
		return [
			'AllReceipt' => $all_receipt,
			'FiscReceipt' => $fisc_receipt
		];
	}

	/**
	 *	Adds an item to the current initialized receipt
	 *	@param string text1 - Text describing the item
	 *	@param string text2 - Additional text describing the item
	 *	@param string TaxGR - One symbol describing the tax group. Valid symbols (Cyrillic Capitalized - А, Б, В, Г, Д, Е, Ж, З)
	 *	@param string sign - Whether or not there's going to be a correction to the last sell. Valid symbols ('-' or '+')
	 *	@param float price - Item price (Valid price up to 8 symbols)
	 *	@param float qty - [Optional param] Item quantity. Default - 1.000 (Valid quantity up to 8 symbols. Not more than 3 symbols after the decimal point)
	 *	@param float percent - [Optional param] Describes the discount or addition to the item in percentage. (Valid percentage varies from -99.99% to 99.99%. Up to 2 symbols after the decimal point)
	 *	@param float netto - [Optional param] Shows the amount of discount or addition on the current item
	 */
	function addItem($text1, $text2, $taxGR, $sign, $price, $qty = 1.000, $percent = null, $netto = null) {
		$data = str_split($text1);
		$i = count($data);
		$data[$i++] = pack('C', 0x0a);
		$data = array_merge($data, str_split($text2));
		$i = count($data);
		$data[$i++] = pack('C', 0x09);
		
		// Validate TaxGR
		$allowed_chars = ['А', 'Б', 'В', 'Г', 'Д', 'Е', 'Ж', 'З']; 
		if (strlen($taxGR) != 2) {
			throw new Exception('TaxGR is longer than one symbol');
		}
		if (!in_array($taxGR, $allowed_chars)) {
			throw new Exception('TaxGR is not an allowed character');
		}
		$taxGR = iconv('UTF-8', 'Windows-1251', $taxGR);
		$data[$i++] = $taxGR;
		
		// Validate sign
		if ($sign != '-' && $sign != '+') {
			throw new Exception('Invalid sign. Must be a plus or a minus');
		}
		if ($sign == '-') {
			if ($percent != null || $netto != null) {
				$percent = null;
				$netto = null;
			}
		}
		$data[$i++] = $sign;
		
		// Validate price 
		if (strlen($price) > 9) {
			throw new Exception ('Price out of range. Can be up to 8 digits');
		}
		$data = array_merge($data, str_split($price));
		$data[] = '*';
		
		// Validate qty
		$qty = number_format($qty, 3, '.', '');
		if (strlen($qty) > 9) {
			throw new Exception ('Quantity out of range. Can be up to 8 digits and only 3 after the decimal point');
		}
		$data = array_merge($data, str_split($qty));
		
		// Validate percent
		if ($percent != null) {
			$data[] = ',';
			$percent = (float)number_format($percent, 2, '.', '');
			if (strlen(abs($percent)) > 5) {
				throw new Exception('Percentage contains more symbols than it should');
			}
			if (abs($percent) > 99.99) {
				throw new Exception('Percentage out of range (Valid range: -99.99% : 99.99%)');
			}
			$data = array_merge($data, str_split($percent));
		}
		if ($netto != null) {
			$data[] = '$';
			$data = array_merge($data, str_split($netto));
		}
		
		$this->BuildPacket(0x31, $data, '/add_item');
		//$this->BuildPacket(0x2A, 'Non-Fiscal Text');
		// No Data - empty payload 
	}
	
	/**
	 *	Sums the total for the current open fiscal receipt
	 *	@return array
	 */
	function total() {
		$payload = $this->BuildPacket(0x35, null, '/total');
		if( $payload == null )
			return null;
		$all_receipt = unpack( 'I*', implode(array_slice($payload, 0, 4)) );
		$fisc_receipt = unpack( 'I*', implode(array_slice($payload, 4, 4)) );
		return [
			'AllReceipt' => $all_receipt,
			'FiscReceipt' => $fisc_receipt
		];
	}
	
	/**
	 *	Finishes the current non-fiscal receipt
	 *	@return array
	 */
	function endReceipt() {
		$payload = $this->BuildPacket(0x27, null, '/end_receipt');
		if( $payload == null ) return null;
		$all_receipt = unpack( 'I*', implode(array_slice($payload, 0, 4)) );
		return [
			'AllReceipt' => $all_receipt
		];
	}
	
	/**
	 *	Finishes the current fiscal receipt
	 *	@return array
	 */
	function endFiscalReceipt() {
		$payload = $this->BuildPacket(0x38, null, '/end_fiscal_receipt');
		if( $payload == null ) 
			return null;
		$all_receipt = unpack( 'I*', implode(array_slice($payload, 0, 4)) );
		$fisc_receipt = unpack( 'I*', implode(array_slice($payload, 4, 4)) );
		return [
			'AllReceipt' => $all_receipt,
			'FiscReceipt' => $fisc_receipt
		];
	}
	
	/**
	 *	Builds the packet that's used for communicating with the device
	 *	@param integer command - Command byte to be executed
	 *	@param string data - [Optional param] Data bytes used for communication. Default - null
	 * 	@param string url - [Optional param] Directory. Default - root
	 *  @return array
	 */
	private function BuildPacket($command, $data = null, $url = '/') {
		$packet = array();

		//STX
		$packet[] = pack('C', 0x01);
		
		//LEN
		$len = 0x24;
		if( $data != null && is_array($data) ) {
			$len += count($data);
		}
		else if ( $data != null && is_string( $data ) ) {
			$len += strlen( $data );
		}
		$packet[] = pack('C', $len);
		
		//SEQ
		if ($this->cmd_seq > 0xFF) {
			$this->cmd_seq = 0x20;
		}
		$packet[] = pack('C', $this->cmd_seq++);

		//CMD
		$packet[] = pack('C', $command);

		//DATA
		if ( $data != null && is_array($data) ) {
			for($i = 0 ; $i < count($data); $i++) {
				$packet[] = $data[$i];
			}
		}
		
		if ( $data != null && is_string($data) ) {
			for($i = 0 ; $i < strlen($data); $i++) {
				$packet[] = $data[$i];
			}
		}		
		
		//POSTAMBLE
		$packet[] = pack('C', 0x05);
		
		//BCC
		$temp = unpack('C*', implode($packet));
		$sum = 0;
		
        for ($i = 2; $i <= $len - 0x1F; $i++) {
            $sum += $temp[$i];
		}
		$sum = str_pad(dechex($sum), 4, "0", STR_PAD_LEFT);
		for ($i = 0; $i < 4; $i++) {
			if (strpos('abcdef', $sum[$i]) !== false) {
				$packet[] = pack('C', hexdec('0x3'.$sum[$i]));
			} else {
				$packet[] = pack('C', ord($sum[$i]));
			}
		}
		unset($temp);	
		
		//ETX
		$packet[] = pack('C', 0x03);
		
		//BUILD
		$build = implode('', $packet);
		
		$result = parent::SendToServer($build, $url);
		
		$json = json_decode($result);
		$error = $json->{'Error'};
		$response = $json->{'Response'};
		
		if ($error == null) {
			$this->updateSeq();
		} else {
			$this->cmd_seq--;
			throw new Exception($error);
		}
	
		$response = base64_decode($response);
		
		$build = unpack("C*",$response);

		try {
			$payload = $this->ParsePacket($build);
			return $payload;
		} catch (Exception $e) {
			header('Location: index.php?error='.urlencode($e->getMessage())); die();
		}
	}
	
	/**
	 *	Updates the current sequence in the file
	 */
	protected function updateSeq() {
		file_put_contents( 'daisy.cmd.seq', serialize($this->cmd_seq) );
	}


	protected function ParsePacket( $data ) {
		$this->status = array();
		$i = 1;
		if( $data[1] == 0x15 && count($data) == 1 ) 
		{
			throw new Exception('Packet sent to device was invalid - NAK response');
		}
		while ($i < count($data) && $data[$i] == 0x16) { $i++; }
		if ($i >= count($data) || $data[$i++] != 0x01) {
			throw new Exception('Invalid packet');
		}
		
		$len = $data[$i++] - 0x20;
		
		if ($len + 5 > count($data) - 1) {
			throw new Exception('Packet is not full');
		}
		$i += 2;
		$data_length = $len - 11;
		$payload = null;

		if ( $data_length > 0 ) {
			$payload = array_slice($data, $i, $data_length);
			$i += $data_length;
		}
		
		//var_dump( $data, $i, $data_length );
		
		for ($j = $i; $j < $i+6; $j++) {
			$this->status[] =$data[$j];
		}

		return $payload;
	}
	
	// Status Functions \\
	public function GetProblems() {
		$problems = array();
		if( ($this->status[0] & 32) > 0 )
			$problems[] = 'General error';
		if( ($this->status[0] & 16) > 0 )
			$problems[] = 'Printer error';
		if( ($this->status[0] & 4) > 0 ) 
			$problems[] = 'No date and time';
		if( ($this->status[0] & 2) > 0 ) 
			$problems[] = 'Invalid command';
		if( ($this->status[0] & 1) > 0 ) 
			$problems[] = 'Syntax error';
		if( ($this->status[1] & 64) > 0 )
			$problems[] = 'Wrong password';
		if( ($this->status[1] & 32) > 0 )
			$problems[] = 'Cutter error';
		if( ($this->status[1] & 4) > 0 )
			$problems[] = 'Null RAM';
		if( ($this->status[1] & 2) > 0 ) 
			$problems[] = 'Prohibited command in this mode';
		if( ($this->status[1] & 1) > 0 ) 
			$problems[] = 'Syntax error';
		if( ($this->status[2] & 1) > 0 )
			$problems[] = 'Out of paper';
		if( ($this->status[4] & 16) > 0 )
			$problems[] = 'Out of memory';
		if( ($this->status[4] & 1) > 0 )
			$problems[] = 'Error while writing on memory';
		if( ($this->status[5] & 1) > 0 ) 
			$problems[] = 'Overflowing memory';
		file_put_contents('problems.dat', serialize($problems));
		return $problems;
	}	
	public function GetStatus() {
		$status = array();
		if( ($this->status[0] & 8) > 0 )
			$status[] = 'No external display found';
		if( ($this->status[2] & 2) > 0 )			
			$status[] = 'Low amount of paper';
		if( ($this->status[4] & 8) > 0 )
			$status[] = 'Less than 50 entries left until memory is full';
		file_put_contents('status.dat', serialize($status));
		return $status;
	}
	// End of Status Functions \\
}
?>