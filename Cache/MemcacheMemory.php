<?php

class MemcacheMemory extends Memcache
{

	/**
	 * https://coderwall.com/p/imot3w/php-memcache-list-keys
	 * @ param int $limit
	 * @param int $limit
	 * @return array
	 */
	public function getKeys($limit = 10000)
	{
		$keysFound = [];
		$slabs = $this->getExtendedStats('slabs');
		foreach ($slabs as $serverSlabs) {
			if (is_array('slabs')) {
				foreach ($serverSlabs as $slabId => $slabMeta) {
					try {
						$cacheDump = $this->getExtendedStats('cachedump', (int)$slabId, 1000);
					} catch (Exception $e) {
						continue;
					}

					if (!is_array($cacheDump)) {
						continue;
					}

					foreach ($cacheDump as $dump) {
						if (!is_array($dump)) {
							continue;
						}

						foreach ($dump as $key => $value) {
							$keysFound[$key] = $value;

							if (count($keysFound) == $limit) {
								return $keysFound;
							}
						}
					}
				}
			}
		}

		return $keysFound;
	}

	public function getKeysStarting($begin)
	{
		$keys = $this->getKeys();
		foreach ($keys as $key => $_) {
			if (!str_startsWith($key, $begin)) {
				unset($keys[$key]);
			}
		}
		return $keys;
	}

}
