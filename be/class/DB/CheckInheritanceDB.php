<?php

class CheckInheritanceDB extends AppController
{

	public $basedOnBase = [];

	public function render()
	{
		$content[] = $this->getClassOverview();
		$content[] = $this->getMethodOverview();
		return $content;
	}

	public function getClassOverview()
	{
		$table = [];
		$folder = 'vendor/spidgorny/nadlib/DB/';
		$files = glob($folder . '*.php');
		if (class_exists('dbLayerDCI')) {
			$files[] = 'dbLayerDCI.php';
		}
		if (class_exists('dbLayerBijou')) {
			$files[] = 'dbLayerBijou.php';
		}
		if (class_exists('dbLayerBL')) {
			$files[] = 'dbLayerBL.php';
		}
		$files[] = 'SQLBuilder.php';
		foreach ($files as $file) {
			$row = [];
			$file = basename($file);
			$row['file'] = $file;
			$parts = trimExplode('.', $file);
			$class = sizeof($parts) == 2 ? $parts[0] : $parts[1];
			$row['class'] = $class;
			if ($class) {
				$rc = new ReflectionClass($class);
				if ($class == 'DBLayerBase' || $rc->isSubclassOf('DBLayerBase')
					|| $class == 'SQLBuilder') {
					$row['parent'] = $rc->getParentClass();
					$this->basedOnBase[] = $class;
				}
			}
			$table[] = $row;
		}
		$content = new slTable($table);
		return $content;
	}

	public function getMethodOverview()
	{
		$dbLayerBase = new ReflectionClass('DBLayerBase');
		$sqlBuilder = new ReflectionClass('SQLBuilder');

		$table = [];
		foreach ($this->basedOnBase as $class) {
			$rc = new ReflectionClass($class);
			$methods = $rc->getMethods();
			foreach ($methods as $rm) {
				$method = $rm->getName();
				$table[$method]['method'] = $method;
				$declaring = $rm->getDeclaringClass()->getName();
				if ($declaring == $class) {
					if ($sqlBuilder->hasMethod($method)) {
						$color = 'red';
					} elseif ($dbLayerBase->hasMethod($method)) {
						$color = 'green';
					} else {
						$color = 'inherit';
					}
					$isDeprecated = $rm->isDeprecated() ||
						contains($rm->getDocComment(), '@deprecated');
					$table[$method][$class] = new HTMLTag('td', [
						'style' => 'background: ' . $color . '; ' .
							($isDeprecated ? 'text-decoration: line-through;' : ''),
					], $class);
				}
			}
		}
		$s = new slTable($table);
		$s->setSortBy('method');
		$s->sortable = true;
		$s->sort();
		$s->generateThes();
		$s->thes['DBLayer']['more']['style'] = 'background: #AAAA00;';
		$content[] = $s;
		return $content;
	}

}
