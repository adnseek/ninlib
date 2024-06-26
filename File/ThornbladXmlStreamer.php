<?php
// oskar.thornblad@gmail.com
// Free for everyone for everything, attribution voluntary
//
// extended by Valiton GmbH, SEP/2012

abstract class ThornbladXmlStreamer
{
	public $customChildNode;
	/**
	 * To see the amount of processed records
	 * @var int
	 */
	public $counter = 0;
	private $handle;
	private $totalBytes;
	private $readBytes = 0;
	private $nodeIndex = 0;
	private $chunk = "";
	private $chunkSize;
	private $readFromChunkPos;
	private $rootNode;
	private $customRootNode;

	/**
	 * @param string $mixed Path to XML file OR file handle
	 * @param Bytes|int $chunkSize Bytes to read per cycle (Optional, default is 16 KiB)
	 * @param string $customRootNode Specific root node to use (Optional)
	 * @param int $totalBytes Xml file size - Required if supplied file handle
	 * @param string $customChildNode
	 * @throws Exception
	 */
	public function __construct($mixed, $chunkSize = 16384, $customRootNode = null, $totalBytes = null, $customChildNode = null)
	{
		if (is_string($mixed)) {
			$this->handle = fopen($mixed, "r");
			if (isset($totalBytes)) {
				$this->totalBytes = $totalBytes;
			} else {
				$this->totalBytes = filesize($mixed);
			}
		} elseif (is_resource($mixed)) {
			$this->handle = $mixed;
			if (!isset($totalBytes)) {
				throw new Exception("totalBytes parameter required when supplying a file handle.");
			}
			$this->totalBytes = $totalBytes;
		}

		$this->chunkSize = $chunkSize;
		$this->customRootNode = $customRootNode;
		$this->customChildNode = $customChildNode;
	}

	/**
	 * Gets called for every XML node that is found as a child to the root node
	 * @param string $xmlString Complete XML tree of the node as a string
	 * @param string $elementName Name of the node for easy access
	 * @param int $nodeIndex Zero-based index that increments for every node
	 * @return boolean|void If false is returned, the streaming will stop
	 */
	abstract public function processNode($xmlString, $elementName, $nodeIndex);

	/**
	 * Gets the total read bytes so far
	 */
	public function getReadBytes()
	{
		return $this->readBytes;
	}

	/**
	 * Gets the total file size of the xml
	 */
	public function getTotalBytes()
	{
		return $this->totalBytes;
	}

	/**
	 * Starts the streaming and parsing of the XML file
	 */
	public function parse()
	{
		$counter = 0;
		$continue = true;
		while ($continue) {
			$continue = $this->readNextChunk();

			$counter++;
			if (!isset($this->rootNode)) {
				// Find root node
				if (isset($this->customRootNode)) {
					$customRootNodePos = strpos($this->chunk, "<{$this->customRootNode}");
					if ($customRootNodePos !== false) {
						// Found custom root node
						// Support attributes
						$closer = strpos(substr($this->chunk, $customRootNodePos), ">");
						$readFromChunkPos = $customRootNodePos + $closer + 1;

						// Custom child node?
						if (isset($this->customChildNode)) {
							// Find it in the chunk
							$customChildNodePos = strpos(substr($this->chunk, $readFromChunkPos), "<{$this->customChildNode}");
							if ($customChildNodePos !== false) {
								// Found it!
								$readFromChunkPos = $readFromChunkPos + $customChildNodePos;
							} else {
								// Didn't find it - read a larger chunk and do everything again
								continue;
							}
						}

						$this->rootNode = $this->customRootNode;
						$this->readFromChunkPos = $readFromChunkPos;
					} else {
						// Clear chunk to save memory, it doesn't contain the root anyway
						$this->readFromChunkPos = 0;
						$this->chunk = "";
						continue;
					}
				} else {
					// $$-- Valiton change: changed pattern. XML1.0 standard allows almost all
					//                      Unicode characters even Chinese and Cyrillic.
					//                      see:
					//                      http://en.wikipedia.org/wiki/XML#International_use
					preg_match('/<([^>\?]+)>/', $this->chunk, $matches);
					//  --$$
					if (isset($matches[1])) {
						// Found root node
						$this->rootNode = $matches[1];
						$this->readFromChunkPos = strpos($this->chunk, $matches[0]) + strlen($matches[0]);
					} else {
						// Clear chunk to save memory, it doesn't contain the root anyway
						$this->readFromChunkPos = 0;
						$this->chunk = "";
						continue;
					}
				}
			}


			while (true) {
				$fromChunkPos = substr($this->chunk, $this->readFromChunkPos);

				// Find element

				// $$-- Valiton change: changed pattern. XML1.0 standard allows almost all
				//                      Unicode characters even Chinese and Cyrillic.
				//                      see:
				//                      http://en.wikipedia.org/wiki/XML#International_use
				preg_match('/<([^>]+)>/', $fromChunkPos, $matches);
				//  --$$
				if (isset($matches[1])) {

					// Found element
					$element = $matches[1];


					// $$-- Valiton change: handle attributes inside elements. aswell as
					//                      when they are distributed over multiple lines.

					// Is there an end to this element tag?
					$spacePos = strpos($element, " ");
					$crPos = strpos($element, "\r");
					$lfPos = strpos($element, "\n");
					$tabPos = strpos($element, "\t");

					$aPositions = [];
					// find min. (exclude false, as it would convert to int 0)
					$aPositionsIn = [$spacePos, $crPos, $lfPos, $tabPos];
					foreach ($aPositionsIn as $iPos) {
						if ($iPos !== false) {
							$aPositions[] = $iPos;
						}
					}

					$minPos = min($aPositions);

					if ($minPos !== false && $minPos != 0) {
						$sElementName = substr($element, 0, $minPos);
						$endTag = "</" . $sElementName . ">";
					} else {
						$sElementName = $element;
						$endTag = "</$sElementName>";
					}

					$endTagPos = false;

					// try selfclosing first!
					// NOTE: selfclosing is inside the element
					$lastCharPos = strlen($element) - 1;
					if (substr($element, $lastCharPos) == "/") {
						$endTag = "/>";
						$endTagPos = $lastCharPos;

						$iPos = strpos($fromChunkPos, "<");
						if ($iPos !== false) {
							// correct difference between $element and $fromChunkPos
							// "+1" is for the missing '<' in $element
							$endTagPos += $iPos + 1;
						}

					}

					if ($endTagPos === false) {
						$endTagPos = strpos($fromChunkPos, $endTag);
					}

					// --$$


					if ($endTagPos !== false) {
						// Found end tag
						$endTagEndPos = $endTagPos + strlen($endTag);
						$elementWithChildren = substr($fromChunkPos, 0, $endTagEndPos);

						// $$-- Valiton change
						$elementWithChildren = trim($elementWithChildren);
						// --$$

						$continueParsing = $this->processNode($elementWithChildren, $sElementName, $this->nodeIndex++);
						$this->chunk = substr($this->chunk, strpos($this->chunk, $endTag) + strlen($endTag));
						$this->readFromChunkPos = 0;

						if (!$continueParsing) {
							break(2);
						}
					} else {
						break;
					}
				} else {
					break;
				}
			}
		}
		$this->counter = $counter;
		return $this->rootNode;
//		fclose($this->handle);
	}

	private function readNextChunk()
	{
		$this->chunk .= fread($this->handle, $this->chunkSize);
		$this->readBytes += $this->chunkSize;
		if ($this->readBytes >= $this->totalBytes) {
			$this->readBytes = $this->totalBytes;
			return false;
		}
		return true;
	}

}
