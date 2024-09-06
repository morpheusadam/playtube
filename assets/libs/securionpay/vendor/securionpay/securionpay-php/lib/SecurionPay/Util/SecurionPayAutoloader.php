<?php
namespace SecurionPay\Util;

/**
 * Implementation of autoloader that only loads classes from package "SecurionPay"
 *
 * Usage example:
 * <code>
 * require_once 'SecurionPay/Util/SecurionPayAutoloader.php';
 * \SecurionPay\Util\SecurionPayAutoloader::register();
 * </code>
 */
class SecurionPayAutoloader {
	private $classPrefix = 'SecurionPay\\';
	private $baseDir;

	private function __construct() {
		$this->baseDir = realpath(__DIR__ . '/../../') . '/';
		
		spl_autoload_register(array($this, 'autoload'));
	}

	public function autoload($class) {
		if (!$this->startsWithPrefix($class)) {
			return;
		}
		
		$file = $this->baseDir . str_replace('\\', '/', $class) . '.php';
		
		if (file_exists($file)) {
			require_once $file;
		}
	}

	private function startsWithPrefix($class) {
		$len = strlen($this->classPrefix);
		return strncmp($this->classPrefix, $class, $len) === 0;
	}

	public static function register() {
		new SecurionPayAutoloader();
	}
}
