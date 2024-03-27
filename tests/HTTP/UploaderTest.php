<?php
/**
 * Created by PhpStorm.
 * User: Slawa
 * Date: 2017-08-03
 * Time: 22:28
 */


class UploaderTest extends PHPUnit\Framework\TestCase
{

	public function test_GetPostedFiles_single()
	{
		$source = [
			'file' => [
				'name' => 'pocketshare_windows.bat',
				'type' => 'application/octet-stream',
				'tmp_name' => 'C:\\wamp\\vdrive\\.tmp\\phpDF4C.tmp',
				'error' => 0,
				'size' => 255,
			],
		];
		$u = new Uploader();
		$result = $u->GetPostedFiles($source);
		$this->assertEquals($source, $result);
	}

	public function test_GetPostedFiles()
	{
		$source = [
			'files' => [
				0 => [
					'name' => 'desktop.ini',
					'type' => 'application/octet-stream',
					'tmp_name' => 'C:\\wamp\\vdrive\\.tmp\\phpDF4B.tmp',
					'error' => 0,
					'size' => 282,
				],
				1 => [
					'name' => 'pocketshare_windows.bat',
					'type' => 'application/octet-stream',
					'tmp_name' => 'C:\\wamp\\vdrive\\.tmp\\phpDF4C.tmp',
					'error' => 0,
					'size' => 255,
				],
			],
		];
		$u = new Uploader();
		$result = $u->GetPostedFiles($source);
//		debug($result);
		$this->assertEquals($source, $result);
	}

	public function test_GetPostedFiles_on_broken()
	{
		$source = [
			'files' => [
				'name' => [
					0 => 'desktop.ini',
					1 => 'pocketshare_windows.bat',
				],
				'type' => [
					0 => 'application/octet-stream',
					1 => 'application/octet-stream',
				],
				'tmp_name' => [
					0 => 'C:\\wamp\\vdrive\\.tmp\\phpDF4B.tmp',
					1 => 'C:\\wamp\\vdrive\\.tmp\\phpDF4C.tmp',
				],
				'error' => [
					0 => 0,
					1 => 0,
				],
				'size' => [
					0 => 282,
					1 => 255,
				],
			],
		];
		$must = [
			'files' => [
				0 => [
					'name' => 'desktop.ini',
					'type' => 'application/octet-stream',
					'tmp_name' => 'C:\\wamp\\vdrive\\.tmp\\phpDF4B.tmp',
					'error' => 0,
					'size' => 282,
				],
				1 => [
					'name' => 'pocketshare_windows.bat',
					'type' => 'application/octet-stream',
					'tmp_name' => 'C:\\wamp\\vdrive\\.tmp\\phpDF4C.tmp',
					'error' => 0,
					'size' => 255,
				],
			],
		];
		$_FILES = $source;
		$u = new Uploader();
		$result = $u->GetPostedFiles();
		$this->assertEquals($must, $result);
	}

	public function test_moveUploadFly()
	{
		if (!class_exists('League\Flysystem\Filesystem')) {
			$this->markTestSkipped('League\Flysystem\Filesystem not installed');
		}
		if (getenv('USER') == 'jenkins') {
			$this->markTestSkipped('Fill fail when run from Jenkins anyway');
		}
		$u = new Uploader();
		$fly = new League\Flysystem\Filesystem(new League\Flysystem\InMemory\InMemoryFilesystemAdapter());
		$_FILES['test'] = [
			'name' => 'desktop.png',
			'type' => 'application/octet-stream',
			'tmp_name' => __FILE__,
			'error' => 0,
			'size' => 282,
		];
		try {
			$result = $u->moveUploadFly('test', $fly, 'desktop.png');
			$this->assertTrue($result);
		} catch (UploadException $e) {
			$this->fail($e->getMessage());
		}
	}

}
