<?php

require '_bootstrap.php';

class SomeObject {
	public $id = 5;
	public $name = 'test';
}

//a basic procedural form

$form = new k\form\Form;
$form->input('id');
$form->input('name');
$form->populate(new SomeObject);
echo $form;
echo '<hr/>';

//an extension form

class ContactForm extends k\form\Form {

	public function __construct() {
		$this->input('firstname');
		$this->input('lastname');
		$this->email();
		$this->textarea('message');
	}
}
$contactForm = new ContactForm;
echo $contactForm;
echo '<hr/>';

//a more complex form mixed with html and the validator





	