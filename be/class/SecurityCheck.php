<?php

class SecurityCheck extends AppControllerBE
{

	public function render()
	{
		$content = [];
		$folder = AutoLoad::getInstance()->getAppRoot();
		$content[] = $this->h1('class');
		$content[] = $this->checkFolder($folder . 'class/');
		$sub = glob($folder . 'class/*', GLOB_ONLYDIR);
		foreach ($sub as $folder) {
			$content[] = $this->h1(basename($folder));
			$content[] = $this->checkFolder($folder . '/');
			break;
		}
		return $content;
	}

	public function checkFolder($folder)
	{
		$content = [];
		$files = glob($folder . '*.php');
		foreach ($files as $file) {
			$content[] = $this->h2(basename($file));
			$fileContent = file($file);
			$class = $this->findClass($fileContent);
			if ($class) {
				$content[] = $this->h3($this->a(
					$this->request->getLocation() . $class, $class));
				try {
					$rc = new ReflectionClass($class);
					$content[] = $this->checkClass($rc);
				} catch (ReflectionException $e) {
					// ignore
				}
			}
		}
		return $content;
	}

	public function findClass(array $lines)
	{
		$lines = array_map(function ($line) {
			if (str_startsWith($line, 'class')) {
				$words = trimExplode(' ', $line);
				return $words[1];    // class Something
			}
			return null;
		}, $lines);
		$lines = array_filter($lines);
		return first($lines);
	}

	public function checkClass(ReflectionClass $rc)
	{
		$content = [];
		$constructor = $rc->getConstructor();
		if ($constructor) {
			$content[] = $this->checkMethod($constructor);

			do {
				$pc = $rc->getParentClass();
				if ($pc && $pc->getName() != $rc->getName()) {
					$constructor = $pc->getConstructor();
					if ($constructor) {
						$contentParent = $this->checkMethod($constructor);
						$content[] = BR;
						$content[] = $pc->getName() . '::' . $constructor->getName() . ': ';
						$content[] = ifsetor($contentParent[1]['checks']);
						$content[] = ifsetor($contentParent[1]['throws']);
					}
				}
				$rc = $pc;
			} while ($pc);
		}
		return $content;
	}

	/**
	 * @param ReflectionMethod $constructor
	 * @return array
	 */
	public function checkMethod(ReflectionMethod $constructor)
	{
		$from = $constructor->getStartLine();
		$till = $constructor->getEndLine();
		$file = $constructor->getFileName();
		$fileContent = file($file);
		$fileContent = array_slice($fileContent, $from - 1, $till - $from + 1);
		$content[] = $constructor->getDeclaringClass()->getName() . '::' . $constructor->getName() . ': ';
		//$content[] = ['<pre>', $this->e($fileContent), '</pre>'];
		list($checks, $throws) = $this->findCheckInFunction($fileContent);
		if ($checks) {
			$content['checks'] = ArrayPlus::create($checks)
				->wrap('<div class="label label-danger">', '</div>');
		}
		if ($throws) {
			$content['throws'] = ArrayPlus::create($throws)
				->wrap('<div class="label label-success">', '</div>');
		}
		$content = ['<h4>', $content, '</h4>'];
		return $content;
	}

	public function findCheckInFunction(array $lines)
	{
		$checks = [];
		$throws = [];
		foreach ($lines as $line) {
			if (str_contains($line, '->can(')) {
				preg_match('/->can\(([^)])\)/', $line, $match);
				$checks[] = unquote($match[1]);
			}
			if (str_contains($line, '->isAuth()')) {
				$checks[] = 'isAuth';
			}
			if (str_contains($line, '->isAdmin()')) {
				$checks[] = 'isAdmin';
			}
			if (str_contains($line, 'DEVELOPMENT')) {
				$checks[] = 'DEVELOPMENT';
			}
			if (str_contains($line, 'throw new')) {
				$words = trimExplode(' ', $line);
				$exception = trimExplode('(', $words[2]);
				$throws[] = $exception[0];
			}
		}
		return [$checks, $throws];
	}

}
