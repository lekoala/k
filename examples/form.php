<?php

define('SRC_PATH', realpath('../src'));
require SRC_PATH . '/K/init.php';
\K\DebugBar::init(array(
	'trackedObjects' => array('K\Pdo','K\Log')
));

K\Form::create()
		//set options
		//add fields
		->openFieldset()->openGroup('infos')
			->input('firstname')
			->input('lastname')
			->input('phone')
			->email()
			->address()
		->closeFieldset()->closeGroup()
		->openFieldset()
			->file('document')
			->file('image')
		->closeFieldset()
		->radio('choices')->options(array('Choice 1', 'Choice 2'))
		->checkbox('terms',"I've read the terms")
		->openGroup('mycb')
		->multicheckbox('preferences')->options(array('This','That','There'))->class('multi')
		->closeGroup()
		->add('Thank you for completing this form')
		->openActions()->submit()->button('Cancel')->closeActions()
		->e()
;