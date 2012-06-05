<?php
/**
 * Dynamically convert coffeescript to javascript.
 * 
 * @author Simon Samtleben <web@lemmingzshadow.net>
 */
include_once 'coffeescript.php';

class JsToCoffee
{
	const CACHING = true;
	const CACHING_TYPE = 'file'; // @todo support for memcache
	
	private $_cacheDir = '';
	private $_coffeeDir = array();

	public function makeJavascript($coffeeFile)
	{
		if(false === ($coffeeFile = $this->_getCoffeefilePath($coffeeFile)))
		{
			return false;
		}
		
		return $this->_getJs($coffeeFile);		
	}
	
	private function _getJs($coffeeFile)
	{
		// get file from cache if possible:
		if(self::CACHING === true)
		{			
			$coffeeHash = sha1_file($coffeeFile);
			if(file_exists($this->_cacheDir . $coffeeHash))
			{
				return file_get_contents($this->_cacheDir . $coffeeHash);			
			}
		}
		
		// file not cached or cache disabled -> covert, cache, return:
		$coffee = file_get_contents($coffeeFile);
		try
		{
			$js = CoffeeScript\compile($coffee);
			if(self::CACHING === true)
			{
				$this->_cacheJs($js, $coffeeFile, $coffeeHash);
			}
			return $js;
		}
		catch (Exception $e)
		{
			var_dump($e);
		}
				
		return false;
	}
	
	private function _cacheJs($js, $coffeeFile, $coffeeHash)
	{
		// save to cache:
		file_put_contents($this->_cacheDir . $coffeeHash, $js);
		
		// register in cache-content and delete possible old version:
		if(!file_exists($this->_cacheDir . 'cache_content'))
		{
			$cacheConent = array();
			file_put_contents($this->_cacheDir . 'cache_content', serialize($cacheConent));
		}
		$temp = file_get_contents($this->_cacheDir . 'cache_content');
		$cacheConent = unserialize($temp);
		unset($temp);
		
		if(isset($cacheConent[$coffeeFile]))
		{
			unlink($this->_cacheDir . $cacheConent[$coffeeFile]);
			unset($cacheConent[$coffeeFile]);
		}
		$cacheConent[$coffeeFile] = $coffeeHash;
		file_put_contents($this->_cacheDir . 'cache_content', serialize($cacheConent));
		unset($cacheConent);
	}

	private function _getCoffeefilePath($coffeeFile)
	{
		$coffeeFile = preg_replace('#[^a-z0-9\.]#', '', $coffeeFile);
		foreach($this->_coffeeDir as $coffeeDir)
		{
			if(file_exists($coffeeDir . $coffeeFile))
			{
				return $coffeeDir . $coffeeFile;
			}
		}
		return false;
	}


	/**
	 * Sets cache folder.
	 * 
	 * @param string $path Path to cache folder.
	 * @return bool True if cache path could be set.
	 */
	public function setCacheDir($path)
	{
		if(file_exists($path) && is_dir($path))
		{
			if(substr($path, 0, -1) !== '/')
			{
				$path = $path . '/';
			}
			$this->_cacheDir = $path;
			return true;
		}
		return false;
	}
	
	/**
	 * Adds a path with is searched for coffeescripts.
	 * 
	 * @param string $path Path containing coffeescript.
	 * @return bool True if path was added.
	 */
	public function setAllowedCoffeeDir($path)
	{
		if(file_exists($path) && is_dir($path))
		{
			if(substr($path, 0, -1) !== '/')
			{
				$path = $path . '/';
			}
			$this->_coffeeDir[] = $path;
			return true;
		}
		return false;
	}
}