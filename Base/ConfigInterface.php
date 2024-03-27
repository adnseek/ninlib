<?php

interface ConfigInterface
{
	// empty for the time being

	public function getUser();

	public function setUser(UserModelInterface $user);

	public function getDB();

	public function mergeConfig($obj);

	public function getLL();

}
