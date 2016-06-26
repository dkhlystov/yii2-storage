<?php

namespace storage\components;

/**
 * Interface that provides to save uploaded files in store.
 */
interface StorageInterface
{

	/**
	 * Saving uploaded file in temporary directory.
	 * @param string $name Form name of uploaded file.
	 * @param string[]|null $types File types allowed to uploading. If null - type of uploaded file is not checked.
	 * @return string|false Public name of uploaded file in temporary directory.
	 */
	public function prepare($name, $types = null);

	/**
	 * Sore file with $name into storage.
	 * @param string $name File name relative to web root.
	 * @return string|false Name under which the file will be available in application.
	 */
	public function store($name);

	/**
	 * Remove file from storage and cache.
	 * @param string $name Name under which the file available in application relative to web root.
	 * @return boolean
	 */
	public function remove($name);

	/**
	 * File caching from storage.
	 * @param string $name Name under which the file available in application relative to web root.
	 * @return mixed|false
	 */
	public function cache($name);

	/**
	 * Store new files from temporary directory and remove deleted files from storage.
	 * @param string[] $old Old files.
	 * @param string[] $new Current files.
	 * @return void
	 */
	public function update($old, $new);

}
