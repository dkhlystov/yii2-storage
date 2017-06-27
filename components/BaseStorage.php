<?php

namespace dkhlystov\storage\components;

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
	 * @var string Prefix is using when application works in subdirectory (not in root directory) on the web-server.
	 */
	public $prefix = '';

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
		$modules['storage'] = 'dkhlystov\storage\Module';
		$app->setModules($modules);

		$app->getUrlManager()->addRules([
			[
				'pattern' => $this->publicPath . '/<name:.+>',
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
		$id = pathinfo($name, PATHINFO_DIRNAME);

		if ($id == $this->publicPath)
			$id = pathinfo($name, PATHINFO_FILENAME);

		if ($s = strrchr($id, '/'))
			$id = substr($s, 1);

		return $id;
	}

	/**
	 * Generate temporary directory name for files upload.
	 * @return string
	 */
	public function generateTmpName()
	{
		$dir = $this->tmpPath . '/' . $this->generateUniqueName();

		return $dir;
	}

	/**
	 * Filter files, remove all except public
	 * @param string[] $files 
	 * @return string[]
	 */
	protected function filterPublicFiles($files)
	{
		$r = [];
		foreach ($files as $file) {
			if (strpos($file, $this->prefix . $this->publicPath . '/') === 0)
				$r[] = $file;
		}

		return array_unique($r);
	}

	/**
	 * Filter files, remove all except tmp
	 * @param string[] $files 
	 * @return string[]
	 */
	protected function filterTmpFiles($files)
	{
		$r = [];
		foreach ($files as $file) {
			if (strpos($file, $this->prefix . $this->tmpPath . '/') === 0)
				$r[] = $file;
		}

		return array_unique($r);
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

		$base = Yii::getAlias('@webroot');

		$dir = $this->generateTmpName();
		if (!file_exists($base . $dir))
			@mkdir($base . $dir);

		if (!$file->saveAs($base . $dir . '/' . $file->name))
			return false;

		return $this->prefix . $dir . '/' . rawurlencode($file->name);
	}

	/**
	 * @inheritdoc
	 */
	public function store($name, $removeOriginal = true)
	{
		$name = urldecode($name);
		$name = substr($name, mb_strlen($this->prefix));

		$base = Yii::getAlias('@webroot');

		$contents = @file_get_contents($base . $name);

		if ($contents === false)
			return false;

		$id = $this->writeContents($contents);

		if ($id === false)
			return false;

		if ($removeOriginal) {
			@unlink($base . $name);
			@rmdir($base . pathinfo($name, PATHINFO_DIRNAME));
		}

		$n = substr(strrchr($name, '/'), 1);
		return $this->prefix . $this->publicPath . '/' . $id . '/' . rawurlencode($n);
	}

	/**
	 * @inheritdoc
	 */
	public function remove($name)
	{
		$name = urldecode($name);
		$name = substr($name, mb_strlen($this->prefix));

		$base = Yii::getAlias('@webroot');

		$id = $this->name2id($name);
		$removed = $this->removeContents($id);

		if ($removed) {
			@unlink($base . $name);
			@rmdir($base . pathinfo($name, PATHINFO_DIRNAME));
		}

		return $removed;
	}

	/**
	 * @inheritdoc
	 */
	public function cache($name)
	{
		$name = urldecode($name);
		$name = substr($name, mb_strlen($this->prefix));

		$base = Yii::getAlias('@webroot');

		$id = $this->name2id($name);
		$contents = $this->readContents($id);

		if ($contents === false)
			return false;

		$dir = $base . pathinfo($name, PATHINFO_DIRNAME);
		if (!file_exists($dir))
			@mkdir($dir);

		@file_put_contents($base . $name, $contents);

		return $contents;
	}

	/**
	 * @inheritdoc
	 */
	public function storeObject(StoredInterface $object)
	{
		$old = $object->getOldFiles();
		$cur = $object->getFiles();

		$oldPublic = $this->filterPublicFiles($old);
		$curPublic = $this->filterPublicFiles($cur);


		//delete old
		$toDel = array_diff($oldPublic, $curPublic);

		foreach ($toDel as $file) {
			$this->remove($file);
		}

		//store new
		$toStore = $this->filterTmpFiles($cur);

		$new = [];
		foreach ($toStore as $file) {
			$new[$file] = $this->store($file);
		}

		$object->setFiles($new);
	}

	/**
	 * @inheritdoc
	 */
	public function removeObject(StoredInterface $object)
	{
		$old = $object->getOldFiles();
		$cur = $object->getFiles();

		$public = $this->filterPublicFiles(array_merge($old, $cur));
		$public = array_unique($public);

		//delete all public
		foreach ($public as $file) {
			$this->remove($file);
		}
	}

	/**
	 * @inheritdoc
	 */
	public function cacheObject(StoredInterface $object)
	{
		$old = $object->getOldFiles();
		$cur = $object->getFiles();

		$public = $this->filterPublicFiles(array_merge($old, $cur));
		$public = array_unique($public);

		//cache all public
		foreach ($public as $file) {
			$this->cache($file);
		}
	}

}
