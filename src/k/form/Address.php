<?php
namespace k\form;

class Address extends Form {

	public function __construct($content = null, Form $form = null) {
		$this->input('street')->size(50);
		$this->input('street_no')->size(6);
		$this->input('zip')->size(6);
		$this->input('city')->size(50);
		
		return parent::__construct($content, $form);
	}
	
	public function renderField() {
		$html = '';

		$placeholder_street = 'street';
		$placeholder_number = 'number';
		$placeholder_zip = 'zip';
		$placeholder_city = 'city';

		$this->form->t($placeholder_street);
		$this->form->t($placeholder_number);
		$this->form->t($placeholder_zip);
		$this->form->t($placeholder_city);

		$street = static::makeTag('input', array(
					'type' => 'text',
					'class' => $this->class,
					'name' => $this->name . '_street',
					'style' => 'display:inline-block;margin-left:0',
					'placeholder' => $placeholder_street
				));
		$number = static::makeTag('input', array(
					'type' => 'text',
					'class' => $this->class,
					'name' => $this->name . '_street_no',
					'size' => 6,
					'style' => 'display:inline-block',
					'placeholder' => $placeholder_number
				));
		$zip = static::makeTag('input', array(
					'type' => 'text',
					'class' => $this->class,
					'name' => $this->name . '_zip',
					'size' => 6,
					'style' => 'display:inline-block;margin-left:0',
					'placeholder' => $placeholder_zip
				));
		$city = static::makeTag('input', array(
					'type' => 'text',
					'class' => $this->class,
					'name' => $this->name . '_city',
					'style' => 'display:inline-block',
					'placeholder' => $placeholder_city
				));
		
		$html .= static::makeTag('div.controls');
		$html .= $street;
		$html .= $number;
		$html .= '</div>';
		$html .= static::makeTag('div.controls');
		$html .= $zip;
		$html .= $city;
		$html .= '</div>';
		
		return $html;
	}

}
