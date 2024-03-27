<?php

class TagCloud extends Controller
{
	protected $words = [];
	protected $count;

	public function __construct()
	{
		parent::__construct();
		$words = $this->db->perform('SELECT id, name, count(*) AS count FROM app_tag GROUP BY name ORDER BY name');
		$words = $this->db->fetchAll($words);
		$words = ArrayPlus::create($words)->each([$this, 'parseWords'])->getData();
		//debug($words);
		$this->words = $words;

		$ap = new ArrayPlus($words);
		$count = $ap->column('count')->getData();
		$count = max($count);
		//debug($count);
		$this->count = $count;
	}

	public function parseWords(array $row)
	{
		//debug($row);
		$url = "Companies?sword=" . urlencode($row['name']);
		$row['url'] = $url;
		return $row;
	}

	public function renderHTML($url =  null)
	{
		$content = '';
		$cloud = new WordCloud();
		foreach ($this->words as $row) {
			$size = round($row['count'] / $this->count * 9);
			$cloud->addWord([
				'word' => $row['name'],// . ' ('.$row['count'].')',
				'size' => $size,
				'url' => $url,
			]);
		}
		//$content .= $cloud->showCloud();
		$cloud = $cloud->showCloud('array');
		foreach ($cloud as $cloudArray) {
			$content .= ' &nbsp; <a href="' . $cloudArray['url'] . '" class="word size' . $cloudArray['range'] . '">' . $cloudArray['word'] . '</a> &nbsp;';
		}
		return $content;
	}

	public function renderXML()
	{
		$content = '';
		foreach ($this->words as $row) {
			$size = 8 + round($row['count'] / $this->count * 10); // 8...18
			$content .= "<a href='" . $row['url'] . "' style='" . $size . "'>" . htmlspecialchars($row['name']) . "</a>";
		}
		$content = '<tags>' . $content . '</tags>';
		return $content;
	}

	public function renderHTMLandFlash()
	{
		$this->index->addCSS('css/wordcloud.css');
		$this->index->addJS('lib/wp-cumulus/swfobject.js');
		$this->index->addJS(AutoLoad::getInstance()->nadlibFromDocRoot . 'js/tagCloud.js');
		return '
		<div id="flashcontent">
			' . $this->renderHTML() . '
		</div>
		<div id="flashxml" style="display: none;">
			' . htmlspecialchars($this->renderXML()) . '
		</div>';
		/*		'
				<script src="lib/wp-cumulus/swfobject.js"></script>
				<script type="text/javascript">
					var so = new SWFObject(
						"lib/wp-cumulus/tagcloud.swf",
						"tagcloud",
						$("#flashcontent").width(),
						$("#flashcontent").width()*3/4,
						"7", "#336699");
					so.addParam("wmode", "transparent");
					so.addVariable("mode", "tags");
					so.addVariable("distr", "true");
					so.addVariable("tcolor", "0xff0000");
					so.addVariable("hicolor", "0x000000");
					so.addVariable("tagcloud", "'.$this->renderXML().'");
					so.write("flashcontent");
				</script>';
		*/
	}


}
