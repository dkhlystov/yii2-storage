<?php

namespace storage\components;

use Yii;
use yii\base\Component;
use yii\base\BootstrapInterface;
use yii\web\UploadedFile;

/**
 * Base class implementing StorageInterface for sore files.
 */
abstract class BaseStorage extends Component implements StorageInterface, BootstrapInterface
{

	/**
	 * @var string Path of directory that contents cached files relative to web root.
	 */
	public $publicPath = '/public';

	/**
	 * @var string Path of directory that temporary stores uploaded files.
	 */
	public $tmpPath = '/upload';

	/**
	 * Read file contents from storage.
	 * @param string $id Stored file identifier
	 * @return mixed|false File contents
	 */
	abstract protected function readContents($id);

	/**
	 * Write file contents into storage.
	 * @param mixed $contents File contents
	 * @return string|false Stored file identifier
	 */
	abstract protected function writeContents($contents);

	/**
	 * Delete contents of stored file.
	 * @param string $id Stored file identifier
	 * @return boolean
	 */
	abstract protected function removeContents($id);

	/**
	 * @inheritdoc
	 */
	public function bootstrap($app)
	{
		$modules = $app->getModules();
		$modules['storage'] = 'storage\Module';
		$app->setModules($modules);

		$app->getUrlManager()->addRules([
			[
				'pattern' => $this->publicPath . '/<name:[\w\.]+>',
				'route' => '/storage/public/index',
			],
		], false);
	}

	/**
	 * Generate unique name for file.
	 * @return string
	 */
	protected function generateUniqueName()
	{
		return str_replace('.', '', uniqid('', true));
	}

	/**
	 * Parse id from name
	 * @param string $name File name
	 * @return string
	 */
	protected function name2id($name)
	{
		$id = pathinfo($name, PATHINFO_BASENAME);

		if (($i = strrpos($id, '.')) !== false) {
			$id = substr($id, 0, $i);
		}

		return $id;
	}

	/**
	 * Generate temporary file name for files upload.
	 * @param string $name Original name of uploaded file.
	 * @return string
	 */
	protected function generateTmpName($name)
	{
		$filename = $this->tmpPath . '/' . $this->generateUniqueName();

		$ext = pathinfo($name, PATHINFO_EXTENSION);
		if (!empty($ext))
			$filename .= '.' . $ext;

		return $filename;
	}

	/**
	 * @inheritdoc
	 */
	public function prepare($name, $types = null)
	{
		$file = UploadedFile::getInstanceByName($name);
		if ($file === null)
			return false;

		if (is_array($types)) {
			$type = strtolower($file->type);
			if (!in_array($type, $types))
				return false;
		}

		$filename = $this->generateTmpName($file->name);

		$file->saveAs(Yii::getAlias('@webroot') . $filename);

		return $filename;
	}

	/**
	 * @inheritdoc
	 */
	public function store($name)
	{
		$contents = @file_get_contents(Yii::getAlias('@webroot') . $name);

		if ($contents === false)
			return false;

		$id = $this->writeContents($contents);

		if ($id === false)
			return false;

		$filename = $this->publicPath . '/' . $id;

		$ext = pathinfo($name, PATHINFO_EXTENSION);
		if (!empty($ext))
			$filename .= '.' . $ext;

		return $filename;
	}

	/**
	 * @inheritdoc
	 */
	public function remove($name)
	{
		$id = $this->name2id($name);

		return $this->removeContents($id);
	}

	/**
	 * @inheritdoc
	 */
	public function cache($name)
	{
		$id = $this->name2id($name);

		$contents = $this->readContents($id);

		if ($contents === false)
			return false;

		@file_put_contents(Yii::getAlias('@webroot') . $name, $contents);

		return $contents;
	}

}
