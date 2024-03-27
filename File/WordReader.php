<?php

class WordReader
{

	protected $filename;

	protected $content = '';

	/**
	 * @var Wrap
	 */
	public $inputNameWrap;

	public $inputValues = [];

	/**
	 * How many next table rows should collapse.
	 * @var int
	 */
	protected $collapseRows;

	protected $closeLabelAfterText;

	public $struct = [];

	protected $structText = '';    // temp

	protected $wasHeader;

	protected $wasLabel;

	protected $label;

	public function __construct($filename)
	{
		$this->filename = $filename;
		$this->inputNameWrap = new Wrap('|');
	}

	public function __toString()
	{
		return $this->content;
	}

	public function parse()
	{
		$s = simplexml_load_file($this->filename);
		//$ns = $s->getNamespaces();
		//debug($ns);
		$sect = $s->xpath('/w:wordDocument/w:body/wx:sect/*');
		$content = '<div class="xmlWord">' . $this->parseSections($sect) . '</div>';
		$this->content = $content;
	}

	protected function parseSections(array $sect)
	{
		$content = '';
		foreach ($sect as $s) {
			//debug($s->getName());
			switch ($s->getName()) {
				case 'p':
					//debug($s->asXML());
					if ($this->wasHeader) {
						$content .= '<p>' . $this->parseSections($s->xpath('*')) . '</p>';
						if ($this->wasLabel) {
							$content .= '</label>';
						}
					} else {
						$content .= '<h3>' . $this->parseSections($s->xpath('*')) . '</h3>';
						$this->wasHeader = true;
					}
					break;
				case 'pBdrGroup':
					//debug($s->asXML());
					$content .= '<fieldset>' . $this->parseSections($s->xpath('*')) . '</fieldset>';
					break;
				case 'fldData':
					$wide = base64_decode($s . '');
					//$wide = mb_convert_encoding($wide, 'UTF-8', 'UTF-16');
					$parts = explode(chr(1), $wide);
					//$content .= debug($parts, true);
					$this->collapseRows = mb_convert_encoding($parts[2], 'UTF-8', 'UTF-16');
					$this->collapseRows = intval($this->collapseRows);
					//$content .= 'cr: '.$this->collapseRows;
					break;
				case 'instrText':
					switch (trim($s)) {
						case 'FORMCHECKBOX':
							$content .= '<input
							type="hidden"
							name="' . $this->inputNameWrap->wrap($this->label) . '"
							value="0">';
							break;
					}
					// delayed label
					if ($this->label) {
						$content .= '<label name="' . $this->inputNameWrap->wrap($this->label) . '">';
						$this->wasLabel = true;
						//debug($s.'');
						switch (trim($s)) {
							case 'FORMCHECKBOX':
								$content .= '<input
									type="checkbox"
									name="' . $this->inputNameWrap->wrap($this->label) . '"
									title="' . $this->inputNameWrap->wrap($this->label) . '"
									value="1"
									' . ($this->inputValues[strtolower($this->label)] ? 'checked' : '') .
									($this->collapseRows ? ' class="collapseRows' . $this->collapseRows . '"' : '') .
									'>';
								break;
							case 'FORMTEXT':
								$content .= '<input
									name="' . $this->inputNameWrap->wrap($this->label) . '"
									title="' . $this->inputNameWrap->wrap($this->label) . '"
									value="' . htmlspecialchars($this->inputValues[strtolower($this->label)]) . '">';
								break;
						}
					}
					break;
				case 't':
					$content .= $s . '';
					$this->structText[] = $s;
					if ($this->closeLabelAfterText) {
						$this->struct[$this->closeLabelAfterText] = implode(' ', $this->structText);
						$this->closeLabelAfterText = false;
						$content .= '</label>';
						$this->structText = '';
					}
					break;
				case 'tab':
					$wx = $s->attributes('wx', true);
					$width = $wx['wTab'] + 0;
					$content .= '<span style="display: inline-block; width: ' . ($width / 10) . 'px;"></span>';
					break;
				case 'ind':
					$wx = $s->attributes('w', true);
					$width = $wx['first-line'] + 0;
					$content .= '<span style="display: inline-block; width: ' . ($width / 10) . 'px;"></span>';
					break;
				case 'annotation':
					$wx = $s->attributes('w', true);
					//debug($s->asXML());
					//debug($wx);
					if ($wx['type'] == 'Word.Bookmark.Start') {
						$name = $wx['name'] . '';
						$this->label = $name;
						//$content .= '<label name="'.$this->label.'">';
					} elseif ($wx['type'] == 'Word.Bookmark.End') {
						$this->closeLabelAfterText = $this->label; // need to close AFTER text, but annotation closes immed.
						$this->label = null;
						//$content .= '</label>';
					} else {
						debug($s->asXML());
					}
					break;
				case 'tbl':
					$content .= '<table class="xmlTable">' . $this->parseSections($s->xpath('*')) . '</table>';
					break;
				case 'tr':
					$content .= '<tr>' . $this->parseSections($s->xpath('*')) . '</tr>';
					break;
				case 'tc':
					$content .= '<td>' . $this->parseSections($s->xpath('*')) . '</td>';
					break;
				default:
					$content .= $this->parseSections($s->xpath('*'));
					break;
			}
		}
		return $content;
	}

}
