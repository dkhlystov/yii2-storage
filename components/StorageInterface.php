<?php

namespace storage\components;

/**
 * Interface that provides to save uploaded files in store.
 */
interface StorageInterface
{

	/**
	 * Sore file with $name into storage.
	 * @param string $name File name relative to web root
	 * @return string|false Name under which the file will be available in application
	 */
	public function store($name);

	/**
	 * Remove file from storage and cache.
	 * @param string $name Name under which the file available in application relative to web root
	 * @return boolean
	 */
	public function remove($name);

	/**
	 * File caching from storage
	 * @param string $name Name under which the file available in application relative to web root
	 * @return mixed|false
	 */
	public function cache($name);

}
