<?php
namespace K\Data;

/**
 * Description of Mysql
 *
 * @author tportelange
 */
class Driver_Mysql extends Driver_Pdo {
	protected $engine = 'InnoDB';
	protected $charset = 'utf8';
	protected $collation = 'utf8_unicode_ci';
}
