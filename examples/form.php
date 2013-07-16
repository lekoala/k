<html>
	<head><link href="//netdna.bootstrapcdn.com/twitter-bootstrap/2.3.2/css/bootstrap-combined.min.css" rel="stylesheet">
	</head>
	<body>
		<?php
		require '_bootstrap.php';

		echo '<pre>POST';
		var_dump($_POST);
		echo '</pre></hr>';

		class SomeObject {

			public $id = 5;
			public $name = 'test';
			public $arr = [
				'val' => 'zzz'
			];

		}

		//a basic procedural form

		$form = new k\form\Form;
		$form->input('id');
		$form->input('name');
		$form->input('arr[val]');
		$form->populate(new SomeObject);
		echo $form;
		echo '<hr/>';

		//an extension form

		class ContactForm extends k\form\Form {
			
			protected $rules = [
				'email' => ['required','email'],
				'more[info]' => ['required']
			];

			protected function init() {
				$this->input('firstname');
				$this->input('lastname');
				$this->email();
				$this->textarea('message');

				$this->input('more[info]');
				$this->input('more[info2]');
				
				$this->submit('Send');
				$this->submit('Send and return');
			}
			
			protected function onSubmit() {
				die('Send');
			}
			protected function onSendAndReturn() {
				die('Send and return');
			}
		}

		$contactForm = new ContactForm;
		echo $contactForm;
		echo '<hr/>';

		//render fields one by one
		$contactForm2 = new ContactForm();
		?>

<?= $contactForm2->openTag() ?>
		<div class="row">
			<div class="span5">
				<?= $contactForm2->firstname ?>
				<?= $contactForm2->lastname ?>
<?= $contactForm2->email ?>
			</div>
			<div class="span5">
				<?= $contactForm2->message ?>
				<?= $contactForm2['more[info]'] ?>
<?= $contactForm2['more[info2]'] ?>
			</div>
		</div>
		<div>
		<?= $contactForm2->renderActions() ?>
		</div>
<?= $contactForm2->closeTag() ?>
		<hr>

		<form class="form-horizontal">
			<div class="control-group">
				<label class="control-label" for="inputEmail">Email</label>
				<div class="controls">
					<input type="text" id="inputEmail" placeholder="Email">
				</div>
			</div>
			<div class="control-group">
				<label class="control-label" for="inputPassword">Password</label>
				<div class="controls">
					<input type="password" id="inputPassword" placeholder="Password">
				</div>
			</div>
			<div class="control-group">
				<div class="controls">
					<label class="checkbox">
						<input type="checkbox"> Remember me
					</label>
					<button type="submit" class="btn">Sign in</button>
				</div>
			</div>
		</form>
	</body>
</html>