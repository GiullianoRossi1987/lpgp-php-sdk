<?php
namespace ConfigExceptions{
	use Exception;
	
	/**
	 * That exception is thrown when the configurations loader try to load a invalid XML file.
	 */
	class InvalidFile extends Exception{}

	/**
	 * That exception is thrown when the configurations loader try to write a readonly configurations file.
	 */
	class PermissionError extends Exception{
		const ERR = "You don't have enough permissions to change the '%fl' configurations file";

		public function __construct(string $file){
			parent::__construct(str_replace("%fl", $file, self::ERR));
		}
	}

	/**
	 * That exception's thrown when the configurations loader try to load configurations file, but it already have another
	 * configurations file loaded! Or when the configurations loader try to do any action, but there's no configurations 
	 * file loaded.
	 */
	class FileError extends Exception{}
}