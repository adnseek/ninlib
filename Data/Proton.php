<?php

class Proton
{

	public $base = 'http://photon.komoot.de/api/?q=';

	public function get($q)
	{
		$gz = new GuzzleHttp\Client();
		$response = $gz->get($this->base . urlencode($q));
		$json = $response->getBody()->getContents();
		$features = json_decode($json);
		return $features;
	}

	public function getCities($q)
	{
		$set = [];
		$results = $this->get($q);
		foreach ($results->features as $place) {
			if (ifsetor($place->properties->name)) {
				$set[] = $place->properties->name . ' (' . $place->properties->country . ')';
			} elseif (ifsetor($place->properties->city)) {
				$set[] = $place->properties->city . ' (' . $place->properties->country . ')';
			} else {
				debug($place->properties);
			}
		}
		return $set;
	}

	public function getFirst($location)
	{
		$results = $this->get($location);
		return $results->features ? first($results->features) : null;
	}

}
