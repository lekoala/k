<?php

namespace k\app\action;

/**
 * BuildClasses
 *
 * @author lekoala
 */
class ListActionsAction extends ActionAbstract {
	public function run() {
		$this->getController()->getApp()->setUseLayout(false);
		$layout = $this->getViewDir() . '/layout.phtml';
		$filename = $this->getViewDir() . '/action.phtml';
		
		$di = new \DirectoryIterator($this->getBaseDir());
		$ignore = array('ActionAbstract','ListActionsAction');
		
		$messages = array(
			'Please select an action'
		);
		foreach($di as $fi) {
			$name = $fi->getBasename('.php');
			if($fi->isFile() && !in_array($name, $ignore)) {
				$name = preg_replace('/^(.*)Action$/',"$1",$name);
				$messages[] = '<a href="'.$name.'">'.$name.'</a>';
			}
		}
		
		$v = new \k\html\View(array($filename,$layout),compact('messages'));
		return $v;
	}
}