<?php

namespace K\Data;

/**
 * Description of Driver
 *
 * @author tportelange
 */
abstract class Driver_Abstract {
	protected $formatDate = 'Y-m-d';
	protected $formatTime = 'H:i:s';
	protected $formatDatetime = 'Y-m-d H:i:s';
	protected $connection;
	protected $dsn;
	protected $options;
	
	
	/**
    * @param mixed $dsn DSN string or pre-existing Mongo object
    * @param array $options
    */
    public function __construct($dsn = array(), array $options = array())
    {
		$this->dsn = $dsn;
		if(is_string($dsn)) {
			$dsn = self::parseDSN($dsn);
		}
		
		$this->dsn = $dsn;
        $this->options = $options;
    }
	
	
	/**
	 * Get database format
	 *
	 * @return string Date format for PHP's date() function
	 */
	public function dateFormat()
	{
		return $this->format_date;
	}
	
	
	/**
	 * Get database time format
	 *
	 * @return string Time format for PHP's date() function
	 */
	public function timeFormat()
	{
		return $this->format_time;
	}
	
	
	/**
	 * Get database format
	 *
	 * @return string DateTime format for PHP's date() function
	 */
	public function dateTimeFormat()
	{
		return $this->format_datetime;
	}
	
	
	/**
	 * Get date
	 *
	 * @return object DateTime
	 */
	public function date($format = null)
	{
		if(null === $format) {
			$format = $this->dateFormat();
		}
		return $this->dateTimeObject($format . ' ' . $this->timeFormat());
	}
	
	
	/**
	 * Get database time format
	 *
	 * @return object DateTime
	 */
	public function time($format = null)
	{
		if(null === $format) {
			$format = $this->timeFormat();
		}
		return $this->dateTimeObject($this->dateFormat() . ' ' . $format);
	}
	
	
	/**
	 * Get datetime
	 *
	 * @return object DateTIme
	 */
	public function dateTime($format = null)
	{
		if(null === $format) {
			$format = $this->dateTimeFormat();
		}
		return $this->dateTimeObject($format);
	}
	
	
	/**
	 * Turn formstted date into timestamp
	 * Also handles input timestamps
	 *
	 * @return DateTime object
	 */
	protected function dateTimeObject($format)
	{
		// Already a timestamp?
		if(is_int($format) || is_float($format)) { // @link http://www.php.net/manual/en/function.is-int.php#97006
			$dt = new \DateTime();
            $dt->setTimestamp($format); // Timestamps must be prefixed with '@' symbol
		} else {
            $dt = new \DateTime();
            $dt->format($format);
        }
		return $dt;
	}
	
	public static function classToTable($class) {
		return strtolower(preg_replace('/[^A-Z^a-z^0-9]+/', '_', preg_replace('/([a-zd])([A-Z])/', '1_2', preg_replace('/([A-Z]+)([A-Z][a-z])/', '1_2', $class))));
	}
	
	public static function createType($type, $field) {
		$baseTypes = array('boolean','integer','double','string','array','object');
		if(in_array($type, $baseTypes)) {
			$typeClassName = 'K\Data\Type_' . ucfirst($type);
			$typeClass = new $typeClassName($field);
		}
		else {
			if(class_exists($type)) {
				if(is_subclass_of($type, 'K\Data\Type_Abstract')) {
					$typeClass = new $type($field);
				}
				elseif($type == 'DateTime') {
					$typeClass = new Type_DateTime($field);
				}
			}
		}
		if(!isset($typeClass)) {
			throw new Exception('Unsupported type ' . $type);
		}
		return $typeClass;
	}
	
	public function create($class) {
		$fields = $class::getFields();
		$table = static::classToTable($class);
		
		$sql = "CREATE TABLE IF NOT EXISTS `" . $table . "` (\n";
		foreach($fields as $field => $type) {
			$type = static::createType($type, $field);
			$sql .= $type->forSql() . ",\n";
		}
		$sql = rtrim($sql, ",\n");
		$sql .= "\n)";
		echo '<pre>' . __LINE__ . "\n";
		print_r($sql);
		exit();
	}

}