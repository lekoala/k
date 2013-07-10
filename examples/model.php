<?php

require '_bootstrap.php';

class GraphPicture extends k\data\Model {
	public $url;
	public $is_silhouette;
	public $created_at;
	public $name;
	
	protected $_some_internal_var = true;
	
	protected static $_defaults = [
		'name' => 'my name'
	];
	
	public static function setDynamicDefaults() {
		static::$_defaults['created_at'] = date('Y-m-d H:i:s');
	}


	public function get_image() {
		return basename($this->url);
	}
}

$data = file_get_contents('https://graph.facebook.com/shaverm/picture?redirect=false');
$data = json_decode($data);
$data = $data->data;

$model = new GraphPicture();
$model->setData($data);

echo '<pre>';
var_dump($model);

echo 'you can encode models in json<br/>';
echo json_encode($model,JSON_UNESCAPED_SLASHES);

echo '<hr>Export virtual fields<br/>';
echo '<pre>';
var_dump($model->getData(1));

echo '<hr>Set new properties<br/>';
$model->new = 55;
echo '<pre>';
echo 'Properties';
var_dump($model->getProperties());
echo 'Model';
var_dump($model);

echo '<hr>Array access<br/>';
var_dump($model['new']);