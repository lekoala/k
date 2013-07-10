<?php

require '_bootstrap.php';

class GraphPicture extends k\data\Model {
	public $url;
	public $is_silhouette;
}

$data = file_get_contents('https://graph.facebook.com/shaverm/picture?redirect=false');
$data = json_decode($data);
$data = $data->data;

$model = new GraphPicture();
$model->setFields($data);

echo '<pre>';
var_dump($model);

echo 'you can encode models in json<br/>';
echo json_encode($model,JSON_UNESCAPED_SLASHES);