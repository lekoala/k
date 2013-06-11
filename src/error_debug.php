<?php
/* @var $e Exception */
/* @var $this k\ErrorHandler */
?>
<!DOCTYPE html>
<html>
	<head>
		<style type="text/css">
			body {
				background: #ddd; /* Old browsers */
				background: -moz-linear-gradient(top,  #ffffff 0%, #e5e5e5 100%); /* FF3.6+ */
				background: -webkit-gradient(linear, left top, left bottom, color-stop(0%,#ffffff), color-stop(100%,#e5e5e5)); /* Chrome,Safari4+ */
				background: -webkit-linear-gradient(top,  #ffffff 0%,#e5e5e5 100%); /* Chrome10+,Safari5.1+ */
				background: -o-linear-gradient(top,  #ffffff 0%,#e5e5e5 100%); /* Opera 11.10+ */
				background: -ms-linear-gradient(top,  #ffffff 0%,#e5e5e5 100%); /* IE10+ */
				background: linear-gradient(to bottom,  #ffffff 0%,#e5e5e5 100%); /* W3C */
				filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='#ffffff', endColorstr='#e5e5e5',GradientType=0 ); /* IE6-9 */
				color:#222;
				font-family: "HelveticaNeue-Light", "Helvetica Neue Light", "Helvetica Neue", Helvetica, Arial, "Lucida Grande", sans-serif; 
				font-weight: 300;
				font-size:20px;
				margin:50px;
			}
			h1 {
				font-size:24px;
			}
			h2 {
				font-size:20px;
			}
			a {
				display: inline-block;
				margin:20px 0;
				color:#fff;
				background: #333; 
				text-decoration:none;
				padding:10px;
			}
			a:hover {
				color:#ddd;
			}
			table {
				width:100%;
				background:#eaeaea;
				-webkit-box-shadow: 0px 3px 5px rgba(50, 50, 50, 0.2);
				-moz-box-shadow:    0px 3px 5px rgba(50, 50, 50, 0.2);
				box-shadow:         0px 3px 5px rgba(50, 50, 50, 0.2);
				font-size:13px;
			}
			th {
				text-align: right;
			}
			.zebra tr:nth-child(2n) {
				background: #fcfcfc;
			}
			.current, .zebra tr:hover {
				background-color:#ffffcc;
			}
			.num {
				color:#999;
				text-align:right;
			}
			.separator {
				color:silver;
			}
			.parenthesis {
				color:magenta;
			}
			.operator {
				color:#3e9d15;
			}
			.odd {
				background:#fcfcfc;
			}
			code {
				font-family: Consolas, "Andale Mono WT", "Andale Mono", "Lucida Console", "Lucida Sans Typewriter", "DejaVu Sans Mono", "Bitstream Vera Sans Mono", "Liberation Mono", "Nimbus Mono L", Monaco, "Courier New", Courier, monospace;
				white-space: pre;
			}
			.function {
				color:#be354c;
			}
			.comment {
				color:orange;
			}
			.var {
				color:#2191ad;
			}
		</style>
	</head>
	<body>
		<h1><?= get_class($e) . ' : ' . $e->getMessage() ?></h1>
		<p>in <code><?= $e->getFile() ?></code> at line <?= $e->getLine() ?></p>
		<?= $this->highlightCode($e->getFile(), $e->getLine()) ?>
		<h2>Trace</h2>
		<?= $this->formatTrace($e) ?>
		<?php if (class_exists('App')) : ?>
			<h2>App log</h2>
			<table class="zebra">
				<?php foreach (App::getInstance()->getLog() as $k => $v) : ?>
					<tr>
						<td><?= $v ?></td>
					</tr>
				<?php endforeach; ?>
			</table>
		<?php endif; ?>
		<h2>Environment</h2>
		<table class="zebra">
			<?php foreach ($_SERVER as $k => $v) : ?>
				<tr>
					<th><?= $k ?></th>
					<td><?= $v ?></td>
				</tr>
			<?php endforeach; ?>
		</table>
		<?php if (isset($_SESSION)) : ?>
			<h2>Session</h2>
			<table class="zebra">
				<?php foreach ($_SESSION as $k => $v) : ?>
					<tr>
						<th><?= $k ?></th>
						<td><?= $v ?></td>
					</tr>
				<?php endforeach; ?>
			</table>
		<?php endif; ?>
		<?php if (!empty($_COOKIE)) : ?>
			<h2>Cookies</h2>
			<table class="zebra">
				<?php foreach ($_COOKIE as $k => $v) : ?>
					<tr>
						<th><?= $k ?></th>
						<td><?= $v ?></td>
					</tr>
				<?php endforeach; ?>
			</table>
		<?php endif; ?>

	</body>
</html>