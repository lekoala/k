<?php

namespace k\app\action;

/**
 * BuildClasses
 *
 * @author lekoala
 */
class BuildClassesAction extends ActionAbstract {
	public function run() {
		$this->getController()->getApp()->setUseLayout(false);
		
		$layout = $this->getViewDir() . '/layout.phtml';
		$filename = $this->getViewDir() . '/action.phtml';
		
		$messages = array();
		$messages[] = 'Building classes into ' . $this->getApp()->getDir();
		$messages[] = 'Classes built';
		$v = new \k\html\View(array($filename,$layout),compact('messages'));
		return $v;
	}
}