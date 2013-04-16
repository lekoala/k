<html lang="en">
	<head>
		<meta charset="utf-8" />
		<title>{title}</title>
		<meta name="keywords" content="" />
		<meta name="author" content="LeKoala" />
		<meta name="viewport" content="width=device-width, initial-scale=1" />
		<link href="//netdna.bootstrapcdn.com/twitter-bootstrap/2.1.1/css/bootstrap-combined.min.css" rel="stylesheet">
		<script src="//netdna.bootstrapcdn.com/twitter-bootstrap/2.1.1/js/bootstrap.min.js"></script>
		<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"></script>
		<style type="text/css">
			legend {
				margin:0;
			}
		</style>
	</head>
	<body>
		<div class="container">
			<header>
				<h1>Config man</h1>
			</header>
			<section id="main" role="main"><?php
define('SRC_PATH',realpath('../src'));
require SRC_PATH . '/K/init.php';

$config = new K\Config('data/man.config.php');
$man = new K\ConfigManager($config,true);
echo $man;?></section>
			<footer></footer>
		</div>
	</body>
</html>
