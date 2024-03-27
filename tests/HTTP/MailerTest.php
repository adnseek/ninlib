<?php

/**
 * Created by PhpStorm.
 * User: DEPIDSVY
 * Date: 22.12.2015
 * Time: 13:37
 */

class MailerTest extends PHPUnit\Framework\TestCase
{

	public function test_getShortFilename()
	{
		$sut = new SwiftMailer('asd@qwe.com', 'test', 'test');
		$filename = './request/97777/RP EA3P40 The Legend of Zelda Tri Force Heroes Some Text Checklists.zip';
		$short = $sut->getShortFilename($filename);
		$this->assertEquals('RP_EA3P40_The_Legend_of_Zelda_Tri_Force_Heroes_Some_Text_Ch.zip', $short);
	}

	public function test_getShortFilename2()
	{
		$sut = new SwiftMailer('asd@qwe.com', 'test', 'test');
		$filename = './request/97777/RP EA3P40 The Рашшан Шит.zip';
		$short = $sut->getShortFilename($filename);
		$this->assertEquals('RP_EA3P40_The_.zip', $short);
	}

	public function test_getShortFilename3()
	{
		// from RequestInfoEPES?id=102865
		$fixture = [
			'VC Pokemon Local Play Compatibility.pdf' => 'VC_Pokemon_Local_Play_Compatibility.pdf',
			'CL Nintendo QBFA11 Pokémon Yellow.pdf' => 'CL_Nintendo_QBFA11_Pokmon_Yellow.pdf',
			'CP_QBFA11.zip' => 'CP_QBFA11.zip',
			'RP Nintendo QBFA11 Pokémon Yellow Version.doc' => 'RP_Nintendo_QBFA11_Pokmon_Yellow_Version.doc',
			'RP Nintendo QBFA11 Pokémon Yellow Version.pdf' => 'RP_Nintendo_QBFA11_Pokmon_Yellow_Version.pdf',
		];
		$sut = new SwiftMailer('spidgorny@gmail.com', 'test', 'test');
		foreach ($fixture as $filename => $must) {
			$short = $sut->getShortFilename($filename);
			$this->assertEquals($must, $short);
		}
	}

	public function test_to_split()
	{
		$to = ['test@asd.de', 'asd@asd.co.jp', 'asd@asd.co.jp'];
		$mailer = new Mailer($to, '', '');
		$this->assertCount(2, $mailer->to);

		$to = 'test@asd.de, asd@asd.co.jp, asd@asd.co.jp';
		$mailer = new Mailer($to, '', '');
		$this->assertCount(2, $mailer->to);

		$to = 'test@asd.de; asd@asd.co.jp; asd@asd.co.jp';
		$mailer = new Mailer($to, '', '');
		$this->assertCount(2, $mailer->to);
	}

}
