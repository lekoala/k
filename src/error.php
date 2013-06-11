<?php
/* @var $e Exception */
/* @var $this k\ErrorHandler */
?>
<!DOCTYPE html>
<html>
	<head>
		<style type="text/css">
			@-moz-keyframes spin {
				0% { -webkit-transform:rotate(75deg); }
				50% { -webkit-transform:rotate(105deg); }
				100% { -webkit-transform:rotate(75deg); }
			}
			@-webkit-keyframes spin {
				0% { -webkit-transform:rotate(75deg); }
				50% { -webkit-transform:rotate(105deg); }
				100% { -webkit-transform:rotate(75deg); }
			}
			@-ms-keyframes spin {
				0% { -webkit-transform:rotate(75deg); }
				50% { -webkit-transform:rotate(105deg); }
				100% { -webkit-transform:rotate(75deg); }
			}
			@keyframes spin {
				0% { -webkit-transform:rotate(75deg); }
				50% { -webkit-transform:rotate(105deg); }
				100% { -webkit-transform:rotate(75deg); }
			}
			body {
				background:#ddd;
				color:#222;
				font-family: Helvetica;
				font-size:20px;
				margin:50px;
			}
			.emoticon {
				display:inline-block;
				font-size:90px;
				line-height:90px;
			}
			.emoticon {
				-moz-animation:spin 1s infinite linear;
				-webkit-animation:spin 1s infinite linear;
				-ms-animation:spin 1s infinite linear;
				animation:spin 1s infinite linear;
			}
			h1 {
				font-size:24px;
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
			.center {
				width:320px;
				text-align:center;
				margin:0 auto;
			}
		</style>
	</head>
	<body>
		<div class="center">
			<p class="emoticon">:-(</p>
			<h1>Something went wrong</h1>
			<?php if(isset($config['admin_email'])) : ?>
			<p>Our IT team has received a detailed log about the problem</p>
			<?php endif; ?>
			<p><a href="/" onclick="history.back();">Back to previous page</a></p>
		</div>
	</body>
</html>