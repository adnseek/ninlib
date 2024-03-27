<?php

/**
 * General HTML Tag representation.
 * @phpstan-consistent-constructor
 */
class HTMLTag implements ArrayAccess, ToStringable
{
	public $tag;
	public $attr = [];
	public $content;
	public $isHTML = false;
	public $closingTag = true;

	public function __construct($tag, array $attr = [], $content = '', $isHTML = false)
	{
		$this->tag = $tag;
		$this->attr = $attr;
		$this->content = $content;
		$this->isHTML = $isHTML;
	}

	public static function key($candidate)
	{
		$key = $candidate;
		$key = str_replace('<', 'lt', $key);
		$key = str_replace('>', 'gt', $key);
		if ($key !== '' && is_numeric($key[0])) {
			$key = '_' . $key;
		}
		return $key;
	}

	public static function __callStatic(string $name, array $arguments)
	{
		llog('HTMLTag', $name, $arguments);
		return new static($name, $arguments[0], $arguments[1], $arguments[2]);
	}

	public static function div($content, array $param = [])
	{
		return new HTMLTag('div', $param, $content);
	}

	public static function span($content, array $param = [])
	{
		return new HTMLTag('span', $param, $content);
	}

	public static function pre($content, array $param = [])
	{
		return new HTMLTag('pre', $param, $content);
	}

	public static function renderAttr(array $attr)
	{
		$set = [];
		foreach ($attr as $key => $val) {
			if (is_array($val) && $key == 'style') {
				$style = ArrayPlus::create($val);
				$style = $style->getHeaders('; ');
				$val = $style;                        // for style="a: b; c: d"
			} elseif (is_array($val)) {
				if (ArrayPlus::isRecursive($val)) {
					debug($val);
				}
				$val = implode(' ', $val);        // for class="a b c"
			}
			$set[] = $key . '="' . htmlspecialchars($val ?? '', ENT_QUOTES | PHP_QUERY_RFC3986) . '"';
		}
		return implode(' ', $set);
	}

	/**
	 * jQuery style
	 * @param string $name
	 * @param null|string|mixed $value
	 * @return mixed
	 */
	public function attr($name, $value = null)
	{
		if ($value) {
			$this->attr[$name] = $value;
			return $this;
		} else {
			return ifsetor($this->attr[$name]);
		}
	}

	public function setAttr($name, $value)
	{
		$this->attr[$name] = $value;
		return $this;
	}

	public function hasAttr($name)
	{
		return isset($this->attr[$name]);
	}

	public function getAttr($name)
	{
		return ifsetor($this->attr[$name]);
	}

	/**
	 * <a href="file/20131128/Animal-Planet.xml" target="_blank" class="nolink">32</a>
	 * @param string $str
	 * @param bool $recursive
	 * @return HTMLTag|null
	 */
	public static function parse($str, $recursive = false)
	{
		$str = trim($str);
		if ($str !== '' && $str[0] !== '<') {
			return null;
		}
		preg_match('/^(<[^>]*>)(.*?)?(<\/[^>]*>)?$/m', $str, $matches);
		//debug($matches);
		if (!isset($matches[1])) {
			return null;
		}

		$tagAndAttributes = trimExplode(' ', ifsetor($matches[1]));
		$tag = first($tagAndAttributes);
//		echo $tag, BR;
		//$attributes = trimExplode(' ', $matches[1]);	// rest of the string
		$attributes = implode(' ', array_slice($tagAndAttributes, 1));
		$tag = substr($tag, 1);
		if (str_endsWith($tag, '>')) {
			$tag = substr($tag, 0, -1);
		}
//		echo $tag, BR;
		$obj = new HTMLTag($tag);
		$obj->attr = self::parseAttributes($attributes);
		if ($recursive) {
			// http://stackoverflow.com/a/28671566/417153
			//$innerHTML = preg_replace('/<[^>]*>([\s\S]*)<\/[^>]*>/', '$1', $str);
			//debug($matches, []);
			$innerHTML = ifsetor($matches[2]);
			if ($innerHTML) {
				$obj->content = self::parseDOM($innerHTML);
			}
		} else {
			$obj->content = strip_tags($str);
		}
		return $obj;
	}

	/**
	 * https://gist.github.com/rodneyrehm/3070128
	 * @param string $text
	 * @return array
	 */
	public static function parseAttributes(string $text)
	{
		if (is_array($text)) {
			return $text;
		}
		$attributes = [];
		$pattern = '#(?(DEFINE)
(?<name>[a-zA-Z][a-zA-Z0-9-:]*)
(?<value_double>"[^"]+")
(?<value_single>\'[^\']+\')
(?<value_none>[^\s>]+)
(?<value>((?&value_double)|(?&value_single)|(?&value_none)))
)
(?<n>(?&name))(=(?<v>(?&value)))?#xs';

		if (preg_match_all($pattern, $text, $matches, PREG_SET_ORDER)) {
			foreach ($matches as $match) {
				$attributes[$match['n']] = isset($match['v'])
					? trim($match['v'], '\'"')
					: null;
			}
		}
		return $attributes;
	}

	public static function parseDOM($html)
	{
		$content = [];
		if (is_string($html)) {
			$doc = new DOMDocument();
			$doc->loadHTML($html);
			$doc = $doc->getElementsByTagName('body')->item(0);
		} elseif ($html instanceof DOMElement) {
			$doc = $html;
		} else {
			debug($html);
			return $content;
		}
		/** @var DOMElement $child */
		foreach ($doc->childNodes as $child) {
			//echo gettype2($child), BR;
			if ($child instanceof DOMElement) {
				$attributes = [];
				foreach ($child->attributes as $attribute_name => $attribute_node) {
					/** @var DOMNode $attribute_node */
					echo $attribute_name, ': ', typ($attribute_node), BR;
					$attributes[$attribute_name] = $attribute_node->nodeValue;
				}

				//$hasChildNodes = $child->hasChildNodes();	// incl Text
				$hasChildNodes = 0;
				foreach ($child->childNodes as $node) {
					if (!($node instanceof DomText)) {
						$hasChildNodes++;
					}
				}

				if ($hasChildNodes) {
					$content[] = new HTMLTag(
						$child->tagName,
						$attributes,
						self::parseDOM($child));
				} else {
					$content[] = new HTMLTag(
						$child->tagName,
						$attributes,
						$child->textContent
					);
				}
			}
		}
		return $content;
	}

	public static function __set_state(array $properties)
	{
		$a = new static($properties['tag']);
		foreach ($properties as $key => $val) {
			$a->$key = $val;
		}
		return $a;
	}

	public static function a($href, $name, array $more = [], $isHTML = false)
	{
		return new self('a', ['href' => $href] + $more, $name, $isHTML);
	}

	public static function img($src, array $params = [])
	{
		return new self('img', ['src' => $src] + $params);
	}

	public function __toString()
	{
		return $this->render();
	}

	public function render()
	{
		if (is_array($this->content) || $this->content instanceof MergedContent) {
			$content = MergedContent::mergeStringArrayRecursive($this->content);
		} else {
			$content = ($this->isHTML
				|| $this->content instanceof HTMLTag
				|| $this->content instanceof HtmlString)
				? $this->content
				: htmlspecialchars($this->content, ENT_QUOTES);
		}
		$attribs = $this->renderAttr($this->attr);
		$xmlClose = $this->closingTag ? '' : '/';
		$tag = '<' . trim($this->tag . ' ' . $attribs) . $xmlClose . '>';
		$tag .= $content;
		if ($this->closingTag) {
			$tag .= '</' . $this->tag . '>' . "\n";
		}
		return $tag;
	}

	public function getContent()
	{
		return $this->content;
	}

	public function offsetExists(mixed $offset): bool
	{
		return isset($this->attr[$offset]);
	}

	public function offsetGet(mixed $offset): mixed
	{
		return $this->getAttr($offset);
	}

	public function offsetSet(mixed $offset, mixed $value): void
	{
		$this->setAttr($offset, $value);
	}

	public function offsetUnset(mixed $offset): void
	{
		unset($this->attr[$offset]);
	}

	public function getHash($length = null)
	{
		$hash = spl_object_hash($this);
		if ($length) {
			$hash = sha1($hash);
			$hash = substr($hash, 0, $length);
		}
		return '#' . $hash;
	}

	public function cli()
	{
		return trim(strip_tags($this->render()));
	}

}
