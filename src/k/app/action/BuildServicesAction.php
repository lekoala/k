<?php

namespace k\app\action;

/**
 * BuildClasses
 *
 * @author lekoala
 */
class BuildServicesAction extends ActionAbstract {
	public function run() {
		$this->getController()->getApp()->setUseLayout(false);
//		$this->streamResponse();
		
		$layout = $this->getViewDir() . '/layout.phtml';
		$filename = $this->getViewDir() . '/action.phtml';
		
		$messages = array();
		echo 'Building classes into ' . $this->getApp()->getDir();
		$i = 10;
for($i = 0; $i <= 25; $i += 1){ 
    echo $i;
    flush();
    sleep(1);
}
		//send end response
		
	}
}