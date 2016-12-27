<?php

namespace dkhlystov\storage\components;

/**
 * Interface of objects that may to store.
 */
interface StoredInterface
{

	/**
	 * Return files that conform to object earlier.
	 * @return string[]
	 */
	public function getOldFiles();

	/**
	 * Return files that conform to object now.
	 * @return string[]
	 */
	public function getFiles();

	/**
	 * Replaces files that had moved to storage.
	 * @param array $files Keys is old file name and value is new file name.
	 * @return void
	 */
	public function setFiles($files);

}
