<?php
//!/usr/bin/php
namespace ConfigurationsL;
if(!defined("SERVER_MODE")) define("SERVER_MODE", FALSE);
if(!defined("GLOBAL_CONFIG")) define("GLOBAL_CONFIG", "config/global.json");


require_once SERVER_MODE ? $_SERVER['DOCUMENT_ROOT'] . "/lpgp-php-sdk/config/Exceptions.php" : "./config/Exceptions.php";
// The program'll run at the app root dir

use ConfigExceptions\FileError;
use ConfigExceptions\InvalidFile;
use ConfigExceptions\PermissionError;
use Exception;
use DateTime;

/**
 * That class loads all the configurations on the configurations file and work with the data at the PHP level.
 * 
 * @var bool $gotFile That attribute represents if the class got a configurations file loaded or not.
 * @var string|null $file_loaded The configurations path of the configurations file loaded, it's null when there's no configurations file loaded.
 * @var array|null $document The configurations file JSON content parsed and setted up to the attribute, it's null when there's no configurations file loaded.
 * @access public
 * @author Giulliano Rossi <giulliano.scatalon.rossi@gmail.com>
 */
class ConfigurationsLoader{
	private $gotFile = false;
	private $file_loaded = null;
	private $document = null;

	/**
	 * Static function that checks if a file received is a valid configurations file. To be a valid configurations file
	 * the file must have the following JSON structure.
	 * * LocalInfo (The received file information):
	 * 			* ReadOnly (bool) => If the file can't be changed.
	 * 			* MainPath (string) => The default path of the configurations file.
	 *          * GeneralLogsF (string) => The configurations loader logs file used for that configurations.
	 * * ServerConfig (The local server configurations):
	 * 			* DateF (string) => The date format used at the logs file (same PHP date function syntax).
	 * 			* TimeF (string) => The time format used at the logs file (same PHP date function syntax).
	 * 			* LogsFPath (string) => The server logs file path.
	 * 			* Token (string) => The official server token, encoded with ASCII characters.
	 * 			* AutoStart (bool) => If the server'll start with the SDK application.
	 * * ClientConfig (The local client configurations):
	 * 			* Name (string) => The client name, for authentication at a server.
	 * 			* Port (integer) => The port that the client socket will use to connect.
	 * 			* DateF (string) => The date format used at the logs file (same PHP date function syntax).
	 * 			* TimeF (string) => The time format used at the logs file (same PHP date function syntax).
	 *			* AccountKey (string) => The LPGP account key, to connect at the official server using the access server.
	 *			* AccountType (string ["prop", "usr"]) => The LPGP account type, "prop" is for proprietaries and "usr" is for normal users.
	 *			* LogsFPath (string) => The client logs file path
	 * * AppConfig (The local applications services): 
	 * 			* Name (string) => The application name,
	 * 			* Port (integer) => The port that the application will use
	 * 			* DateF (string) => The date format used at the logs file (same PHP date function syntax).
	 * 			* TimeF (string) => The time format used at the logs file (same PHP date function syntax).
	 *			* RootMode (bool) => If the application connection will change the account data.
	 * 			* Token (string) => The application token, for the access server authenticate.
	 * 			* AutoInsertTK (bool) => If the application will authenticate the default Token automaticly or will use a login form.
	 * 			* LogsFPath (string) => The application logs file path.
	 * * CLIConfig (The local Command line configurations):
	 * 			* UseLogin (bool) => If at every LPGP-PHP-SDK service start will ask for a login.
	 * 			* Username (string) => The username of the SDK service user (encoded with base64);
	 * 			* Passwd (string) => The SDK service user password (encoded with base64).
	 * 			* LogsFPath (string) => The logs file that the command line login will use.
	 * 			* DateF (string) => The date format to use at the logs file
	 * 			* TimeF (string) => The time format to use at the logs file
	 * @param string $file The configurations file to check.
	 * @access private
	 * @return integer The error codes of the file invalidation. The possible codes are:
	 * 					* 0 => No errors.
	 * 					* 1 => Invalid File type (not a .json file),
	 * 					* 2 => Missing  Block
	 * 					* 3 => Missing  Field
	 * 					* 4 => Invalid value.
	 */
	final public static function ckFile(string $file){
		$exp = explode(".", $file);
		if($exp[count($exp) - 1] != "json") return 1;
		unset($exp);
		$con = file_get_contents($file);
		$doc = json_decode($con, true);
		try{
			// LocalInfo verification
			$loci = $doc['LocalInfo'];
			try{
				if($loci['ReadOnly'] != "true" && $loci != "false") return 4;
				else if(strlen($loci['MainPath']) < 5) return 4;
				else if(strlen($loci['GeneralLogsF']) < 4) return 4;
				else ; // do nothing

			}
			catch(Exception $local_err){return 3;} // missing field;
			// no errors => valid LocalInfo block 
			unset($loci);
			// ServerConfig validation
			$server = $doc['ServerConfig'];
			try{
				// still here only for the field existence verification.
				// I'll work more in it when my internet's back
				try{
					$dt_teste = new DateTime($server['DateF']);
					$tm_teste = new DateTime($server['TimeF']);
				}
				catch(Exception $dt_err){ return 4;}
				// other configurations fields.
				if(strlen($server['LogsFPath']) <= 4) return 4;
				else if(strlen($server['Token']) <= 2) return 4;
				else if($server['AutoStart'] != "true" && $server['AutoStart'] != "false") return 4;
				else ; // nothing to do
			}
			catch(Exception $e){ return 3;}
			unset($server);
			// ClientConfig validation
			$client = $doc['ClientConfig'];
			try{
				try{
					$dt_teste = new DateTime($client['DateF']);
					$tm_teste = new DateTime($client['TimeF']);
				}
				catch(Exception $dt_err){ return 4;}
				if(strlen($client['Name']) <= 0) return 4;
				else if((int) $client['Port'] <= 0) return 4;
				else if(strlen($client['AccountKey']) <= 0) return 4;
				else if($client['AccountType'] != "prop" && $client['AccountType'] != "false") return 4;
				else if(strlen($client['LogsFPath']) <= 4) return 4;
				else ; // do nothing
			}
			catch(Exception $field_err){ return 3;}
			unset($client);
			// AppConfig validation
			$app = $doc['AppConfig'];
			try{
				try{
					$dt_teste = new DateTime($app['DateF']);
					$tm_teste = new DateTime($app['TimeF']);
				}
				catch(Exception $date_er){ return 4;}
				if(strlen($app['Name']) <= 0) return 4;
				else if((int) $app['Port'] <= 0) return 4;
				else if($app['RootMode'] != 'prop' && $app['RootMode'] != "usr") return 4;
				else if(strlen($app['Token']) <= 0) return 4;
				else if($app['AutoInsertTK'] != "true" && $app['AutoInsertTK'] != "false") return 4;
				else if(strlen($app['LogsFPath']) <= 4) return 4;
			}
			catch(Exception $e){ return 3;}
			unset($app);
			// CLI configurations validation
			$cli = $doc['CLIConfig'];
			try{
				if($cli['UseLogin'] != "true" && $cli['UseLogin'] != "false") return 4;
				else if(strlen($cli['Username']) <= 0) return 4;
				else if(strlen($cli['Passwd']) <= 0) return 4;
				else if(strlen($cli['LogsFPath']) <= 4) return 4;
				else if(!is_string(base64_decode($cli['Username']))) return 4;
				else if(!is_string(base64_decode($cli['Passwd']))) return 4;
				try{
					$dt_teste = new DateTime($cli['DateF']);
					$tm_teste = new DateTime($cli['TimeF']);
					// check the base64 encoding of the password and username
				}
				catch(Exception $dt_err) { return 4;}
			}
			catch(Exception $field_err){ return 3;}
			unset($cli);
		}
		catch(Exception $e) { return 2;}  // missing block.
		return 0;
	}

	/**
	 * Load a configurations file, verifing if the file's valid and if there's no other configurations file loaded yet.
	 *
	 * @param string $file The configurations file to load.
	 * @throws FileError If there's another configurations file loaded.
	 * @throws InvalidFile If the configurations file selected is invalid.
	 * @return void
	 */
	final public function parseConfig(string $file = GLOBAL_CONFIG){
		if($this->gotFile) throw new FileError("There's a configurations file loaded already!", 1);
		if($this->ckFile($file) != 0){
			$vl = $this->ckFile($file);
			switch ($vl) {
				case 1:
					throw new InvalidFile("The file '$file' is invalid [NotJSONFile received error]", 1);
					break;
				case 2:
					throw new InvalidFile("The file '$file' is invalid [Missing configurations blocks]", 1);
					break;
				case 3:
					throw new InvalidFile("This file '$file' is invalid [Missing fields]", 1);
					break;
				case 4:
					throw new InvalidFile("This file '$file' is invalid [Invalid values]", 1);
					break;
				default:
					throw new InvalidFile("This file '$file' is invalid! [Internal Error]", 1);
					break;
			}
		}
		$this->file_loaded = $file;
		$this->document = json_decode(file_get_contents($file), true);
		$this->gotFile = true;
	}

	/**
	 * Commit all the changes at the document to the file.
	 * @throws PermissionError If the file have the readonly option, in that case the original content will be verified the original content.
	 * @throws FileError If there's no configurations file loaded.
	 * @return void
	 */
	final public function commit(){
		if(!$this->gotFile) throw new FileError("There's no configurations file loaded!", 1);
		// checks the file permissions
		$now_doc = json_decode(file_get_contents($this->file_loaded), true);
		if($now_doc['LocalInfo']['ReadOnly'] == "true") throw new PermissionError($this->file_loaded);
		unset($now_doc);
		$dumped = json_encode($this->document);
		file_put_contents($this->file_loaded, $dumped);
		unset($dumped);
	}

	/**
	 * That method force the class to parse a configurations file, even if there's another configurations file loaded or if the file
	 * selected is invalid. 
	 * __Warning: That method is used only for the SDK debugging, it's not projected to be used in official applications or even for other process that uses the SDK__
	 *
	 * @param string $file The configurations file to parse.
	 * @return void
	 */
	final public function force_parse(string $file){
		if($this->gotFile) $this->unparse();
		$this->file_loaded = $file;
		$this->document = json_decode(file_get_contents($file), true);
		$this->gotFile = true;
	}

	/**
	 * Unparse and unset the configurations file loaded, after trying to commit the changes.
	 * @throws FileError If there's no configurations file loaded.
	 * @return void
	 */
	final public function unparse(){
		if(!$this->gotFile) throw new FileError("There's no configurations file loaded!", 1);
		try {
			$this->commit();
		}
		catch(PermissionError $e){} // do nothing
		$this->document = null;
		$this->file_loaded = null;
		$this->gotFile = false;
	}

	/**
	 * Starts the class object, with a new configurations file to load.
	 * @param string|null $file The configurations file to load, if it's null will do nothing
	 * @return void
	 */
	final public function __construct(string $file = null){
		if(!is_null($file)) $this->parseConfig($file);
	}

	/**
	 * Garbage collection function called when the object is deleted.
	 * @return void
	 */
	final public function __destruct(){
		if($this->gotFile) $this->unparse();
	}

	/**
	 * Returns the configurations document with the PHP loaded values. The raw document can be received too.
	 *
	 * @param bool $raw If the class will return the configurations array with the string based values.
	 * @throws FileError If there's no configurations file loaded yet;
	 * @return array
	 */
	final public function getConfig(bool $raw = false){
		if(!$this->gotFile) throw new FileError("There's no configurations file loaded yet!", 1);
		if($raw) return $this->document;
		else{
			$rt_arr = array();
			$rt_arr['LocalInfo'] = array(
				"ReadOnly" => (bool) $this->document['LocalInfo']['ReadOnly'] == "true",
				"MainPath" => $this->document['LocalInfo']['MainPath'],
				"GeneralLogsF" => $this->document['LocalInfo']['GeneralLogsF']
			);
			$rt_arr['ServerConfig'] = array(
				"DateF" => $this->document['ServerConfig']['DateF'],
				"TimeF" => $this->document['ServerConfig']['TimeF'],
				"LogsFPath" => $this->document['ServerConfig']['LogsFPath'],
				"Token" => $this->document['ServerConfig']['LogsFPath'],
				"AutoStart" => $this->document['ServerConfig']['AutoStart'] == "true"
			);
			$rt_arr['ClientConfig'] = array(
				"DateF" => $this->document['ClientConfig']['DateF'],
				"TimeF" => $this->document['ClientConfig']['TimeF'],
				"Name" => $this->document['ClientConfig']['Name'],
				"Port" => (int) $this->document['ClientConfig']['Port'],
				"AccountKey" => $this->document['ClientConfig']['AccountKey'],
				"AccountType" => $this->document['ClientConfig']['AccoutType'] == "prp" ? 1: 0,
				"LogsFPath" => $this->document['ClientConfig']['LogsFPath']
			);
			$rt_arr['AppConfig'] = array(
				"DateF" => $this->document['AppConfig']['DateF'],
				"TimeF" => $this->document['AppConfig']['TimeF'],
				"LogsFPath" => $this->document['AppConfig']['LogsFPath'],
				"RootMode" => (bool) $this->document['AppConfig']['RootMode'] == "true",
				"Token" => $this->document['AppConfig']['Token'],
				"AutoInsertTK" => (bool) $this->document['AppConfig']['AutoInsertTK'] == "true"
			);
			$rt_arr['CLIConfig'] = array(
				"UseLogin" => (bool) $this->document['CLIConfig']['UseLogin'] == "true",
				"Username" => $this->document['CLIConfig']['Username'],
				"Passwd" => $this->document['CLIConfig']['Passwd'],
				"LogsFPath" => $this->document['CLIConfig']['LogsFPath']
			);
			return $rt_arr;
		}
	}
}