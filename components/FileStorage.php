<?php

namespace storage\components;

use Yii;

class FileStorage extends BaseStorage
{

	/**
	 * @var string Path of directory where files stores
	 */
	public $storagePath = '@app/storage';

	/**
	 * @inheritdoc
	 */
	protected function readContents($id)
	{
		return @file_get_contents(Yii::getAlias($this->storagePath) . '/' . $id);
	}

	/**
	 * @inheritdoc
	 */
	protected function writeContents($contents)
	{
		$id = $this->generateUniqueName();
		$r = @file_put_contents(Yii::getAlias($this->storagePath) . '/' . $id, $contents);

		if ($r === false)
			return false;

		return $id;
	}

	/**
	 * @inheritdoc
	 */
	protected function removeContents($id)
	{
		$filename = Yii::getAlias($storagePath) . '/' . $id;

		if (!file_exists($filename))
			return true;

		return @unlink($filename);
	}

}
