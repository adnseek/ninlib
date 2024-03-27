<?php

/**
 * @property string $table
 * @property array $thead
 * @property array $tbody
 * @property array $tfoot
 */
class HTMLTableBuf extends MergedContent
{

	public $curPart = 'tbody';
	public $thead;
	public $tbody;
	public $tfoot;

	public function __construct()
	{
		parent::__construct([
			'table' => '',
			'thead' => '',
			'tbody' => '',
		]);
	}

	public function table(array $more = [])
	{
		$this['table'] = "<table " . HTMLTag::renderAttr($more) . ">\n";
	}

	public function tablee()
	{
		$this['/table'] = "</table>\n";
	}

	public function tr(array $more = [])
	{
		$this->addSub('tbody', new HTMLTag('tr', $more) . "\n");
	}

	public function tre()
	{
		$this->addSub('tbody', "</tr>\n");
	}

	public function ftr(array $more = [])
	{
		$this->addSub('tfoot', new HTMLTag('tr', $more) . "\n");
	}

	public function ftre()
	{
		$this->addSub('tfoot', "</tr>\n");
	}

	public function the()
	{
		$this->addSub('thead', "</th>\n");
	}

	public function addTHead($text)
	{
		$this->addSub('thead', $text);
	}

	public function tfoot($text)
	{
		$this->addSub('tfoot', $text);
	}

	public function cell($a, array $more = [])
	{
		$this->td($more);
		$this->text($a);
		$this->tde();
	}

	public function td(array $more = [])
	{
		$this->addSub($this->curPart, "<td " . HTMLTag::renderAttr($more) . ">");
	}

	public function text($text)
	{
		$this->addSub($this->curPart, $text);
	}

	public function tde()
	{
		$this->addSub($this->curPart, "</td>\n");
	}

	/**
	 * @param array $aCaption - array of names
	 * @param array $thMore - more on each column TH
	 * @param array $trMore - more on the whole row
	 */
	public function thes(array $aCaption, $thMore = [], $trMore = [])
	{
		$this->htr($trMore);
		foreach ($aCaption as $i => $caption) {
			if ($caption instanceof HTMLTag) {
				$this->thead[] = $caption;
			} else {
				$more = $thMore[$i] ?? [];
				$this->th($more, $caption);
			}
		}
		$this->htre();
//		llog('thead after thes', $this->thead);
	}

	public function htr(array $more = [])
	{
		$this->addSub('thead', new HTMLTag('tr', $more) . "\n");
	}

	public function th(array $more = [], $content = '')
	{
		$this->addSub('thead', new HTMLTag('th', $more, $content) . "\n");
	}

	public function htre()
	{
		$this->addSub('thead', "</tr>\n");
	}

	public function render()
	{
		print($this->getContent());
	}

	public function tag(HTMLTag $tag)
	{
		$this->addSub($this->curPart, $tag . '');
	}

	public function isDone()
	{
		return isset($this['/table']);
	}

	public function &__get($key)
	{
//		echo __METHOD__, '(', $key, ')', BR;
		if (!isset($this[$key])) {
			$this->offsetSet($key, []);
		}
		return $this->content[$key];
	}

	/**
	 * @param mixed $offset
	 * @return null
	 */
	public function offsetGet(mixed $offset): mixed
	{
		return ifsetor($this->content[$offset], []);
	}

	public function reset()
	{
		$this['table'] = [];
		$this['thead'] = [];
		$this['tbody'] = [];
	}

	public function __debugInfo()
	{
		return [
			'table' => $this['table'],
			'thead' => $this['thead'],
			'tbody' => $this['tbody'],
			'content' => $this->content,
		];
	}

}
