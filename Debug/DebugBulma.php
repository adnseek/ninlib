<?php

class DebugBulma extends DebugHTML
{

	/**
	 * @var Debug
	 */
	public $helper;

	public function __construct(Debug $helper)
	{
		$this->helper = $helper;
	}

	public static function canBulma()
	{
		return true;
	}

	public static function printStyles()
	{
//		echo '!!', __METHOD__, '!!';
		return '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bulma/0.7.1/css/bulma.min.css" />';
	}

	public function debug($params)
	{
		return call_user_func_array([$this, 'render'], func_get_args());
	}

	public function renderProps(array $props)
	{
		$rows = ['<table class="table">'];
		foreach ($props as $key => $val) {
			$rows[] = '<tr>
				<td>' . $key . '</td>
				<td>' . $val . '</td>
			</tr>';
		}
		$rows[] = '</table>';
		return implode(PHP_EOL, $rows);
	}

	public function renderHTMLView(array $db, $a, $levels)
	{
		$props = $this->getProps($db, $a);

		$backlog = Debug::getBackLog(1, 6, ' // ');
		$trace = Debug::getTraceTable2($db);
//		$trace = '<ul><li>' . Debug::getBackLog(20, 6, '<li>') . '</ul>';

		$content = '<div class="container">
			<div class="panel is-size-7">
				<div class="panel-heading">
					<div class="level">
						<div class="level-left">
							<div class="level-item">
							' . $this->renderProps($props) . '
							</div>
						</div>
						<div class="level-right">
							<div class="level-item has-text-left">
								<label class="checkbox">
									 <input type="checkbox" /> ' . $backlog . ' <span class="delete"></span>
									 <div>' . $trace . '</div>
								</label>
							</div>
						</div>
					</div>
				</div>
				<div class="panel-block">
					' . static::view_array($a, $levels) . '
				</div>
			</div>
		</div>
		<style>
			label.checkbox input:not(:checked) ~ div { display: none; }
		</style>';
		return $content;
	}

	public static function view_array($a, $levels = 1, $tableClass = 'view_array')
	{
		return parent::view_array($a, $levels, 'table is-narrow is-bordered is-size-7');
	}

}
