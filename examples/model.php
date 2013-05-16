<?php

require '../init.php';
require '../autoload.php';

$model = new k\Model();
$model->value = 'test';
echo $model->value;