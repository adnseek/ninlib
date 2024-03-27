<?php

define('LOWERCASE', 3);
define('UPPERCASE', 1);

use nadlib\Proxy;
use Symfony\Component\CssSelector\CssSelector;
use Symfony\Component\CssSelector\CssSelectorConverter;

class Syndicator
{

	/**
	 * @var string
	 */
	public $url;
	/**
	 * @var bool|int enabled or seconds for caching
	 */
	public $isCaching = false;

	/**
	 * @var string
	 */
	public $html;

	public $tidy;

	/**
	 * @var SimpleXMLElement
	 */
	public $xml;

	/**
	 * @var array
	 */
	public $json;

	public $xpath;    // last used, for what?

	public $recodeUTF8;

	/**
	 *
	 * @var FileCache
	 */
	public $cache;

	/**
	 * @var Proxy|bool
	 */
	public $useProxy = null;

	public $input = 'HTML';

	/**
	 * @var array
	 */
	public $log = [];

	/**
	 * @var callable callback to check that downloaded file is what is expected
	 */
	public $validateDownload;

	public function __construct($url = null, $caching = true, $recodeUTF8 = 'utf-8')
	{
		TaylorProfiler::start(__METHOD__);
		if ($url) {
			$this->url = $url;
			$this->isCaching = $caching;
			$this->recodeUTF8 = $recodeUTF8;
			$this->detectProxy();
		}
		TaylorProfiler::stop(__METHOD__);
	}

	public function detectProxy()
	{
		$proxy = null;
		if (str_startsWith($this->url, 'https')) {
			$proxy = getenv('https_proxy');
			$proxy = $proxy ?: getenv('http_proxy');
		} elseif (str_startsWith($this->url, 'http')) {
			$proxy = getenv('http_proxy');
		}
		if ($proxy) {
			$this->useProxy = new Proxy();
			$this->useProxy->setProxy($proxy);
		}
	}

	/**
	 * @param string $url
	 * @param bool $caching
	 * @param string $recodeUTF8
	 * @return Syndicator
	 */
	public static function readAndParseHTML($url, $caching = true, $recodeUTF8 = 'utf-8')
	{
		$s = new self($url, $caching, $recodeUTF8);
		$s->input = 'HTML';
		$s->html = $s->retrieveFile();
		$s->xml = $s->processFile($s->html);
		return $s;
	}

	public function retrieveFile($retries = 1)
	{
		TaylorProfiler::start(__METHOD__);
		$c = Index::getInstance()->controller;
		if ($this->isCaching) {
			$this->cache = new FileCache();
			if ($this->cache->hasKey($this->url)) {
				$html = $this->cache->get($this->url);
				$this->log(__METHOD__, '<a href="' . $this->cache->map($this->url) . '">' . $this->cache->map($this->url) . '</a> Size: ' . strlen($html));
			} else {
				$this->log(__METHOD__, 'No cache. Download File.');
				$html = $this->downloadFile($this->url, $retries);
				if (is_callable($this->validateDownload)) {
					$ok = call_user_func($this->validateDownload, $html);
					if ($ok) {
						$this->cache->set($this->url, $html);
						$this->proxyOK();
					} else {
						$this->proxyFail();
					}
				} else {
					if (strlen($html)) {
						$this->cache->set($this->url, $html);
						$this->proxyOK();
					} else {
						$this->proxyFail();
					}
				}
				//debug($cache->map($this->url).' Size: '.strlen($html), 'Set cache');
			}
		} else {
			$html = $this->downloadFile($this->url, $retries);
		}
		TaylorProfiler::stop(__METHOD__);
		return $html;
	}

	public function get($string)
	{
		$elements = $this->getElements($string);
		//debug($string, $elements);
		foreach ($elements as &$e) {
			$e = trim($e);
		}
		$elements = array_filter($elements);
		if (sizeof($elements) == 0) {
			return null;
		}

		if (sizeof($elements) == 1) {
			return first($elements);
		} else {
			return $elements;
		}
	}

	/**
	 * @param $xpath
	 * @return null|SimpleXMLElement[]
	 */
	public function getElements($xpath)
	{
		TaylorProfiler::start(__METHOD__);
		$target = null;
		if ($this->xml) {
			//debug($this->xml);
			$this->xpath = $xpath;
			$target = $this->xml->xpath($this->xpath);
		}
		TaylorProfiler::stop(__METHOD__);
		return $target;
	}

	public function log($method, $msg)
	{
		$this->log[] = new LogEntry($method, $msg);
	}

	public function downloadFile($href, $retries = 1)
	{
		if (str_startsWith($href, 'http')) {
			$ug = new URLGet($href, $this);
			$ug->timeout = 10;
			$ug->fetch($retries);
			return $ug->getContent();
		} else {
			return file_get_contents($href);
		}
	}

	public function proxyOK()
	{
		if ($this->useProxy && $this->useProxy instanceof Proxy) {
			$c = Controller::getInstance();
			$c->log(__METHOD__, 'Using proxy: ' . $this->useProxy . ': OK');
			$this->useProxy->ok();
		}
	}

	public function proxyFail()
	{
		if ($this->useProxy && $this->useProxy instanceof Proxy) {
			$c = Controller::getInstance();
			$c->log(__METHOD__, 'Using proxy: ' . $this->useProxy . ': OK');
			$this->useProxy->fail();
		}
	}

	public function processFile($html)
	{
		TaylorProfiler::start(__METHOD__);
		//debug(substr($html, 0, 1000));
		if ($this->input == 'HTML' && $this->recodeUTF8 != 'pass') {
			$html = html_entity_decode($html, ENT_COMPAT, $this->recodeUTF8 === true ? null : $this->recodeUTF8);
		}

		if ($this->recodeUTF8) {
			$detect = mb_detect_encoding($html, $this->recodeUTF8 === true ? null : $this->recodeUTF8);
			$this->log("mb_detect_encoding($this->recodeUTF8)", $detect);
			if (!$detect) {
				$detect = $this->detect_cyr_charset($html);
				$this->log("detect_cyr_charset", $detect);
			}
			$utf8 = mb_convert_encoding($html, 'UTF-8', $this->recodeUTF8 === true ? 'Windows-1251' : $detect);
			//$utf8 = str_replace(0x20, ' ', $utf8);
		} else {
			$utf8 = $html;
		}
		//$utf8 = str_replace('&#151;', '-', $utf8);
		//debug(substr($utf8, 0, 1000));

		// new
		//$utf8 = $this->strip_html_tags($utf8);
		//$utf8 = str_replace('<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">', '', $utf8);
		//debug($utf8);

		$this->tidy = $this->tidy($utf8);
		//$this->tidy = $utf8;
		//debug(substr($tidy, 0, 1000));
		//exit();
		//$this->tidy = preg_replace('/<meta name="description"[^>]*>/', '', $this->tidy);

		$recode = $this->recode($this->tidy);
		//debug($recode, 'Recode');

		//$recode = preg_replace('/<option value="0">.*?<\/option>/is', '', $recode);

		$recode = str_replace('xmlns=', 'ns=', $recode);
		$xml = $this->getXML($recode);
		TaylorProfiler::stop(__METHOD__);
		return $xml;
	}

	/**
	 * http://code.google.com/p/php-excel-reader/issues/attachmentText?id=8&aid=2334947382699781699&name=val_patch.php&token=45f8ef6a787d2ab55cb821688e28142d
	 * @param $str
	 * @return mixed
	 */
	public function detect_cyr_charset($str)
	{
		$charsets = [
			'koi8-r' => 0,
			'Windows-1251' => 0,
			'CP866' => 0,
			'ISO-8859-5' => 0,
			'MAC' => 0
		];
		for ($i = 0, $length = strlen($str); $i < $length; $i++) {
			$char = ord($str[$i]);
			//non-russian characters
			if ($char < 128 || $char > 256) continue;

			//CP866
			if (($char > 159 && $char < 176) || ($char > 223 && $char < 242))
				$charsets['CP866'] += LOWERCASE;
			if (($char > 127 && $char < 160)) $charsets['CP866'] += UPPERCASE;

			//KOI8-R
			if (($char > 191 && $char < 223)) $charsets['koi8-r'] += LOWERCASE;
			if (($char > 222 && $char < 256)) $charsets['koi8-r'] += UPPERCASE;

			//WIN-1251
			if ($char > 223 && $char < 256) $charsets['Windows-1251'] += LOWERCASE;
			if ($char > 191 && $char < 224) $charsets['Windows-1251'] += UPPERCASE;

			//MAC
			if ($char > 221 && $char < 255) $charsets['MAC'] += LOWERCASE;
			if ($char > 127 && $char < 160) $charsets['MAC'] += UPPERCASE;

			//ISO-8859-5
			if ($char > 207 && $char < 240) $charsets['ISO-8859-5'] += LOWERCASE;
			if ($char > 175 && $char < 208) $charsets['ISO-8859-5'] += UPPERCASE;

		}
		arsort($charsets);
		return key($charsets);
	}

	public function tidy($html)
	{
		TaylorProfiler::start(__METHOD__);
		$out = null;
		//debug(extension_loaded('tidy'));
		if ($this->input == 'HTML') {
			if (extension_loaded('tidy')) {
				$config = [
					'clean' => true,
					'indent' => true,
					'output-xhtml' => true,
					//'output-html'		=> true,
					//'output-xml' 		=> true,
					'wrap' => 1000,
					'numeric-entities' => true,
					'char-encoding' => 'raw',
					'input-encoding' => 'raw',
					'output-encoding' => 'raw',

				];
				$tidy = new tidy();
				$tidy->parseString($html, $config);
				$tidy->cleanRepair();
				//$out = tidy_get_output($tidy);
				$out = $tidy->value;
			} else {
				$out = htmLawed($html, [
					'valid_xhtml' => 1,
					'tidy' => 1,
				]);
			}
		} elseif ($this->input == 'XML') {
			$out = $html;    // hope that XML is valid
		}
		TaylorProfiler::stop(__METHOD__);
		return $out;
	}

	public function recode($xml)
	{
		TaylorProfiler::start(__METHOD__);
		//$enc = mb_detect_encoding($xml);
		//debug($enc);
		//$xml = mb_convert_encoding($xml, 'UTF-8', 'Windows-1251');
		$xml = mb_convert_encoding($xml, 'UTF-8', 'UTF-8');
		//$NewEncoding = new ConvertCharset('Windows-1251', 'UTF-8');
		//$xml = $NewEncoding->Convert($xml);
		//$xml2 = mb_convert_encoding($xml, 'UTF-8', 'CP1252');
		//debug(array(strlen($xml), strlen($xml2)));
		//$xml = $xml2;

		//$xml = html_entity_decode($xml);
		//$xml = str_replace('//<![CDATA[', '<![CDATA[', $xml);
		//$xml = preg_replace("/<html[^>]*>/", "<html>", $xml);
		//$xml = $this->strip_html_tags($xml);
		TaylorProfiler::stop(__METHOD__);
		return $xml;
	}

	public function getXML($recode)
	{
		TaylorProfiler::start(__METHOD__);
		if ($recode != '' && $recode[0] === '<') {
			$xml = new SimpleXMLElement($recode);
			//$xml['xmlns'] = '';
			$namespaces = $xml->getNamespaces(true);
			//debug($namespaces, 'Namespaces');
			//Register them with their prefixes
			foreach ($namespaces as $prefix => $ns) {
				$xml->registerXPathNamespace('default', $ns);
				break;
			}
		} else {
			$xml = null;
		}
		TaylorProfiler::stop(__METHOD__);
		return $xml;
	}

	/**
	 * @param $url
	 * @param bool $caching
	 * @param string $recodeUTF8
	 * @return Syndicator
	 */
	public static function readAndParseXML($url, $caching = true, $recodeUTF8 = 'utf-8')
	{
		$s = new self($url, $caching, $recodeUTF8);
		$s->input = 'XML';
		$s->html = $s->retrieveFile();
		$s->xml = $s->processFile($s->html);
		return $s;
	}

	/**
	 * @param $url
	 * @param bool $caching
	 * @param string $recodeUTF8
	 * @return Syndicator
	 */
	public static function readAndParseJSON($url, $caching = true, $recodeUTF8 = 'utf-8')
	{
		$s = new self($url, $caching, $recodeUTF8);
		$s->input = 'JSON';
		$s->html = $s->retrieveFile();
		$s->log(__METHOD__, 'Downloaded');
		$s->json = json_decode($s->html);
		$s->log(__METHOD__, 'JSON decoded');
		return $s;
	}

	/**
	 *
	 * @param string $xpath
	 * @return SimpleXMLElement
	 */
	public function getElement($xpath)
	{
		TaylorProfiler::start(__METHOD__);
		$first = null;
		$res = $this->getElements($xpath);
		if ($res) {
			reset($res);
			$first = current($res);
		}
		TaylorProfiler::stop(__METHOD__);
		return $first;
	}

	/**
	 * No XPATH
	 *
	 * @param string $xml
	 * @return SimpleXMLElement
	 */
	public function getSXML($xml)
	{
		$xml_parser = new sxml();
		$xml_parser->parse($xml);
		$xml = $xml_parser->datas;
		//debug($xml_parser);
		return $xml;
	}

	public function getDOM($xml)
	{
		$dom = domxml_xmltree($xml);
		return $dom;
	}

	/**
	 * Remove HTML tags, including invisible text such as style and
	 * script code, and embedded objects.  Add line breaks around
	 * block-level tags to prevent word joining after tag removal.
	 */
	public function strip_html_tags($text)
	{
		return preg_replace('/<script[^>]*?>.*?<\/script>/is', '', $text);
		$text = preg_replace(
			[
				// Remove invisible content
				'@<head[^>]*?>.*?</head>@siu',
				'@<style[^>]*?>.*?</style>@siu',
				'@<script[^>]*?.*?</script>@siu',
				'@<object[^>]*?.*?</object>@siu',
				'@<embed[^>]*?.*?</embed>@siu',
				'@<applet[^>]*?.*?</applet>@siu',
				'@<noframes[^>]*?.*?</noframes>@siu',
				'@<noscript[^>]*?.*?</noscript>@siu',
				'@<noembed[^>]*?.*?</noembed>@siu',
				// Add line breaks before and after blocks
				'@</?((address)|(blockquote)|(center)|(del))@iu',
				'@</?((div)|(h[1-9])|(ins)|(isindex)|(p)|(pre))@iu',
				'@</?((dir)|(dl)|(dt)|(dd)|(li)|(menu)|(ol)|(ul))@iu',
				'@</?((table)|(th)|(td)|(caption))@iu',
				'@</?((form)|(button)|(fieldset)|(legend)|(input))@iu',
				'@</?((label)|(select)|(optgroup)|(option)|(textarea))@iu',
				'@</?((frameset)|(frame)|(iframe))@iu',
			],
			[
				' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ',
				"\n\$0", "\n\$0", "\n\$0", "\n\$0", "\n\$0", "\n\$0",
				"\n\$0", "\n\$0",
			],
			$text);
		return /*strip_tags*/ ($text);
	}

	public function trimArray(array $elements)
	{
		foreach ($elements as &$e) {
			$e = trim(strip_tags($e));
		}
		return $elements;
	}

//	/**
//	 * composer require symfony/css-selector
//	 * @param $selector
//	 * @return SimpleXMLElement
//	 */
//	function select($selector)
//	{
//		$converter = new CssSelectorConverter();
//		$xpath = $converter->toXPath($selector);
//		//debug($xpath);
//		return $this->getElement($xpath);
//	}

	public function css($selector)
	{
//		CssSelector::enableHtmlExtension();
//		$xpath = CssSelector::toXPath($selector);
		//debug($xpath);
//		return $this->getElements($xpath);
	}

	public function getEncoding()
	{
		$ct = $this->get('//meta[@http-equiv="Content-Type"]/@content');
		$ctParts = trimExplode('=', $ct);    // text/html; charset=windows-1251
		$ct = ifsetor($ctParts[1]);
		if ($ct) {
			$list = mb_list_encodings();
			foreach ($list as $option) {
				if (!strcasecmp($ct, $option)) {
					$ct = $option;
					break;
				}
			}
		}
		return $ct;
	}

}

/*
		//$xml = $this->getSXML($recode);
		//$xml = $this->getDOM($recode);

/*		$html = new DOMDocument('1.0', 'UTF-8');
		// fetch drupal planet and parse it (@ suppresses warnings).
		@$html->loadHTMLFile($this->url);
		// convert DOM to SimpleXML
		$xml = simplexml_import_dom($html);
*/
//return $xml;
//debug($xml);
//debug($xml->body->pre[1]->a[14]);
/**/
