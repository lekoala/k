<?php

$app = App::getInstance();

function if_mk_dir($dir, $mode = 0775, $rec = true) {
	if (!is_dir($dir)) {
		mkdir($dir, $mode, $rec);
	}
}

function get($array, $index, $default = null) {
	$loc = &$array;
	foreach (explode('/', $index) as $step) {
		if (isset($loc[$step])) {
			$loc = &$loc[$step];
		} else {
			return $default;
		}
	}
	return $loc;
}

function checked($n) {
	global $conf;
	if (isset($conf[$n])) {
		return 'checked="checked"';
	}
}

function selected($n, $v) {
	global $conf;
	$t = get($conf,$n);
	if($t == $v) {
		return 'selected="selected"';
	}
}

function value($n,$d=null) {
	global $conf;
	return get($conf,$n,$d);
}

$conf = array();

if (!empty($_POST)) {
	//make dirs
	if_mk_dir($app->getDataDir());
	if_mk_dir($app->getTmpDir());
	if_mk_dir($app->getTmpDir() . '/sessions');
	if_mk_dir($app->getTmpDir() . '/error');

	$conf = array_merge($conf, $_POST);
	$functions = "date_default_timezone_set('" . $conf['default_timezone'] . "');
";
	unset($conf['default_timezone']);
	if ($conf['db']['dbtype'] == 'mysql' && $conf['utf8'] == 1) {
		$conf['db']['options'] = array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8');
	}
	if ($conf['utf8']) {
		$functions .= "mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');
mb_http_input('UTF-8');
mb_language('uni');
mb_regex_encoding('UTF-8');
";
	}
	unset($conf['utf8']);
	if(!isset($conf['modules'])) {
		$conf['modules'] = ['main'];
	}
	//create config file
	$content = '<?php
' . $functions . '
return ' . var_export($conf, true) . ';';

	file_put_contents(APP_DIR . '/config.php', $content);
	header('Location: /');
	exit();
}
?>
<!DOCTYPE html>
<html>
	<head>
		<link href="//netdna.bootstrapcdn.com/twitter-bootstrap/2.3.1/css/bootstrap-combined.min.css" rel="stylesheet">
	</head>
	<body>

		<div class="container" style="padding-top:20px;">
			<h1>Installer</h1>
			<h2>System information</h2>
			<p>Current php version : <span class="label"><?= phpversion() ?></span></p>
			<h2>Configuration</h2>
			<form action="" method="post">
				<div class="control-group">
					<label for="default_timezone">Default Timezone</label>
					<input type="text" name="default_timezone" value="Europe/Brussels" />
				</div>
				<input type="hidden" name="debug" value="0" />
				<label class="checkbox">Enable debug mode
					<input type="checkbox" value="1" name="debug" checked="checked" <?= checked('debug') ?> />
				</label>
				<p><small>Don't forget to disable this in production!</small></p>
				<input type="hidden" name="utf8" value="0" />
				<label class="checkbox">Use UTF-8 as default encoding
					<input type="checkbox" value="1" name="utf8" checked="checked" />
				</label>
				<label class="checkbox"><input type="checkbox" onclick="var style = this.checked ? 'block' : 'none';
						document.getElementById('db-settings').style.display = style;" <?= checked('db') ?> /> Use a database</label>
				<fieldset <?php if(!checked('db')) echo 'style="display:none"' ?> id="db-settings">
					<legend>Database settings</legend>
					<label for="db_dbtype">Type</label>
					<select name="db[dbtype]" id="db_dbtype">
						<option value=""></option>
						<option value="mysql" <?= selected('db/dbtype', 'mysql') ?>>Mysql</option>
						<option value="sqlite" <?= selected('db/dbtype', 'sqlite') ?>>Sqlite</option>
					</select>
					<label for="db_host">Host</label>
					<input type="text" name="db[host]" value="<?= value('db/host', 'localhost') ?>" />
					<label for="db_username">User</label>
					<input type="text" name="db[username]" value="<?= value('db/username', 'root') ?>" />
					<label for="db_password">Password</label>
					<input type="text" name="db[password]" value="<?= value('db/password') ?>" />
					<label for="db_dbname">Database</label>
					<input type="text" name="db[dbname]" value="<?= value('db/dbname') ?>" />
				</fieldset>
				<input type="submit" class="btn" />
			</form>
		</div>

	</body>
</html>

