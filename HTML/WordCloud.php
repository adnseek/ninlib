<?php

/************************************************************\
 *
 *    wordCloud Copyright 2007 Derek Harvey
 *    www.lotsofcode.com
 *
 *    This file is part of wordCloud.
 *
 *    wordCloud v2 is free software; you can redistribute it and/or modify
 *    it under the terms of the GNU General Public License as published by
 *    the Free Software Foundation; either version 2 of the License, or
 *    (at your option) any later version.
 *
 *    wordCloud v2 is distributed in the hope that it will be useful,
 *    but WITHOUT ANY WARRANTY; without even the implied warranty of
 *    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.    See the
 *    GNU General Public License for more details.
 *
 *    You should have received a copy of the GNU General Public License
 *    along with wordCloud; if not, write to the Free Software
 *    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA    02111-1307    USA
 *
 * \************************************************************/

class WordCloud
{
	public $version = '2.0';
	public $wordsArray = [];
	/**
	 * @var array
	 */
	public $removeWords = [];

	protected $limitAmount = 0;

	protected $max;

	protected $orderBy = [];

	/**
	 * PHP 5 Constructor
	 *
	 * @param array|bool $words
	 *
	 * @return void
	 */
	public function __construct($words = false)
	{
		// If we are trying to parse some works, in any format / type
		if ($words !== false) {
			// If we have a string
			if (is_string($words)) {
				$this->addString($words);
			} elseif (count($words)) {
				foreach ($words as $key => $value) {
					$this->addWord($value);
				}
			}
		}
		return;
	}

	/*
	 * PHP 4 Constructor
	 *
	 * @param array $words
	 *
	 * @return void
	 */

	public function wordCloud($words = false)
	{
		$this->__construct($words);
		return $this;
	}

	/*
	 * Convert a string into a cloud
	 *
	 * @param string $string
	 * @return void
	 */

	public function addString($string)
	{
		// remove all other chars apart from a-z
		$string = preg_replace('[^a-z]', '', strip_tags(strtolower($string)));
		$words = [];
		$words = explode(' ', $string);
		if (count($words)) {
			foreach ($words as $key => $value) {
				$this->addWord($value);
			}
		}
	}

	/*
	 * Display user friendly message, for debugging mainly
	 *
	 * @param string $string
	 * @param array $value
 *
	 * @return bool
	 */

	public function notify($string, $value)
	{
		echo '<pre>';
		print_r($string);
		print_r($value);
		echo '</pre>';
		return false;
	}

	/*
	 * Assign word to array
	 *
	 * @param string $word
	 *
	 * @return string
	 */

	public function addWord($wordAttributes = [])
	{
		if (is_string($wordAttributes)) {
			$wordAttributes = ['word' => $wordAttributes];
		}
		if (!array_key_exists('size', $wordAttributes)) {
			$wordAttributes = array_merge($wordAttributes, ['size' => 1]);
		}
		if (!array_key_exists('word', $wordAttributes)) {
			return $this->notify('no word attribute', print_r($wordAttributes, true));
		}
		$word = strtolower($wordAttributes['word']);
		if (empty($this->wordsArray[$word])) {
			$this->wordsArray[$word] = [];
		}
		if (!empty($this->wordsArray[$word]['size']) && !empty($wordAttributes['size'])) {
			$wordAttributes['size'] = ($this->wordsArray[$word]['size'] + $wordAttributes['size']);
		} elseif (!empty($this->wordsArray[$word]['size'])) {
			$wordAttributes['size'] = $this->wordsArray[$word]['size'];
		}
		$this->wordsArray[$word] = $wordAttributes;
		return $this->wordsArray[$word];
	}

	/*
	 * Shuffle associated names in array
	 *
	 * @return array $this->wordsArray
	 */

	public function shuffleCloud()
	{
		$keys = array_keys($this->wordsArray);
		shuffle($keys);
		if (count($keys) && is_array($keys)) {
			$tmpArray = $this->wordsArray;
			$this->wordsArray = [];
			foreach ($keys as $key => $value) {
				$this->wordsArray[$value] = $tmpArray[$value];
			}
		}
		return $this->wordsArray;
	}

	/*
	 * Get the class range using a percentage
	 *
	 * @returns int $class
	 */

	public function getClassFromPercent($percent)
	{
		if ($percent >= 99)
			$class = 9;
		elseif ($percent >= 70)
			$class = 8;
		elseif ($percent >= 60)
			$class = 7;
		elseif ($percent >= 50)
			$class = 6;
		elseif ($percent >= 40)
			$class = 5;
		elseif ($percent >= 30)
			$class = 4;
		elseif ($percent >= 20)
			$class = 3;
		elseif ($percent >= 10)
			$class = 2;
		elseif ($percent >= 5)
			$class = 1;
		else
			$class = 0;
		return $class;
	}

	/*
	 * Sets a limit for the amount of clouds
	 *
	 * @param string $limit
	 *
	 * @returns string $this->limitAmount
	 */

	public function setLimit($limit)
	{
		if (!empty($limit)) {
			$this->limitAmount = $limit;
		}
		return $this->limitAmount;
	}

	/*
	 * Gets the limited amount of clouds
	 *
	 * @returns string $wordCloud
	 */

	public function limitCloud()
	{
		$wordsArray = [];
		$i = 1;
		foreach ($this->wordsArray as $key => $value) {
			if ($this->limitAmount < $i) {
				$wordsArray[$value['word']] = $value;
			}
			$i++;
		}
		$this->wordsArray = [];
		$this->wordsArray = $wordsArray;
		return $this->wordsArray;
	}

	/*
	 * Finds the maximum value of an array
	 *
	 * @param string $word
	 *
	 * @returns void
	 */

	public function removeWord($word)
	{
		$this->removeWords[] = strtolower($word);
	}

	/*
	 * Removes tags from the whole array
	 *
	 * @returns array $this->wordsArray
	 */

	public function removeWords()
	{
		$wordsArray = [];
		foreach ($this->wordsArray as $key => $value) {
			if (!in_array($value['word'], $this->removeWords)) {
				$wordsArray[$value['word']] = $value;
			}
		}
		$this->wordsArray = $wordsArray;
		return $this->wordsArray;
	}

	/*
	 * Assign the order field and order direction of the cloud
	 *
	 * @param array $field
	 * @param string $sortway
	 *
	 * @returns void
	 */

	public function orderBy($field, $direction = 'ASC')
	{
		return $this->orderBy = ['field' => $field, 'direction' => $direction];
	}

	/*
	 * Orders the cloud by a specific field
	 *
	 * @param array $unsortedArray
	 * @param string $sortField
	 * @param string $sortWay
	 *
	 * @returns array $unsortedArray
	 */

	public function orderCloud($unsortedArray, $sortField, $sortWay = 'SORT_ASC')
	{
		$sortedArray = [];
		foreach ($unsortedArray as $uniqid => $row) {
			foreach ($row as $key => $value) {
				$sortedArray[$key][$uniqid] = strtolower($value);
			}
		}
		if ($sortWay) {
			array_multisort($sortedArray[$sortField], constant($sortWay), $unsortedArray);
		}
		return $unsortedArray;
	}

	/*
	 * Finds the maximum value of an array
	 *
	 * @returns string $max
	 */

	public function getMax()
	{
		$max = 0;
		if (!empty($this->wordsArray)) {
			$p_size = 0;
			foreach ($this->wordsArray as $cKey => $cVal) {
				$c_size = $cVal['size'];
				if ($c_size > $p_size) {
					$max = $c_size;
					/* @Thanks Morticus */
					$p_size = $c_size;
				}
			}
		}
		return $max;
	}

	/*
	 * Create the HTML code for each word and apply font size.
	 *
	 * @returns string/array $return
	 */

	public function showCloud($returnType = 'html')
	{
		if (empty($this->orderBy)) {
			$this->shuffleCloud();
		} else {
			$orderDirection = strtolower($this->orderBy['direction']) == 'desc' ? 'SORT_DESC' : 'SORT_ASC';
			$this->wordsArray = $this->orderCloud($this->wordsArray, $this->orderBy['field'], $orderDirection);
		}
		if (!empty($this->limitAmount)) {
			$this->limitCloud();
		}
		if (!empty($this->removeWords)) {
			$this->removeWords();
		}
		$this->max = $this->getMax();
		if (is_array($this->wordsArray)) {
			$return = ($returnType == 'html' ? '' : ($returnType == 'array' ? [] : ''));
			foreach ($this->wordsArray as $word => $arrayInfo) {
				$sizeRange = $this->getClassFromPercent(($arrayInfo['size'] / $this->max) * 100);
				$arrayInfo['range'] = $sizeRange;
				if ($returnType == 'array') {
					$return [$word] = $arrayInfo;
				} elseif ($returnType == 'html') {
					$return .= "<span class='word size{$sizeRange}'> &nbsp; {$arrayInfo['word']} &nbsp; </span>";
				}
			}
			return $return;
		}
	}
}
