<?php

namespace storage\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

/**
 * Storage caching controller
 */
class PublicController extends Controller
{

	public function actionIndex($name)
	{
		$storage = Yii::$app->storage;

		$name = $storage->publicPath . '/' . $name;

		$contents = $storage->cache($name);

		if ($contents === false)
			throw new NotFoundHttpException(Yii::t('yii', 'Page not found.'));

		$finfo = finfo_open(FILEINFO_MIME_TYPE);
		$mime = @finfo_file($finfo, Yii::getAlias('@webroot') . $name);
		finfo_close($finfo);

		if ($mime !== false) 
			header('Content-Type: ' . $mime);

		echo $contents;

		die();
	}

}
