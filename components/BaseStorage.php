<?php

namespace storage\components;

use Yii;
use yii\base\Component;

/**
 * Base class implementing StorageInterface for sore files.
 */
abstract class BaseStorage extends Component implements StorageInterface
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
	protected function readContents($id);

	/**
	 * Write file contents into storage.
	 * @param mixed $contents File contents
	 * @return string|false Stored file identifier
	 */
	protected function writeContents($contents);

	/**
	 * Delete contents of stored file.
	 * @param string $id Stored file identifier
	 * @return boolean
	 */
	protected function removeContents($id);

	/**
	 * Generate unique name for file.
	 * @return string
	 */
	protected function generateUniqueName()
	{
		return uniqid('', true);
	}

	/**
	 * Generate temporary file name for files upload.
	 * @param string $name Original name of uploaded file.
	 * @return string
	 */
	public function generateTmpName($name)
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
		$id = pathinfo($name, PATHINFO_BASENAME);

		return $this->removeContents($id);
	}

	/**
	 * @inheritdoc
	 */
	public function cache($name)
	{
		$id = pathinfo($name, PATHINFO_BASENAME);

		$contents = $this->readContents($id);

		if ($contents === false)
			return false;

		@file_put_contents(Yii::getAlias('@webroot') . $name, $contents);

		return $contents;
	}

}
