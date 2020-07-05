<?php

class SearchResults  {

	public $query;
	public $limit;
	public $location;
	public $results = [];

	private $lastPosition;
	private $uule;

	const ENDPOINT = 'http://www.google.com/search?q=';
	const LOCATION_SECRET = 'w+CAIQICI';
	const LOCATION_PARAM = '&uule=';
	const RESULTS_PER_PAGE = 10;

	private $scraper = null;
	private $rules = [
			'results' => [
				'@selector' => ".g",
				'@each' => [
					'title'=> ['@selector' => "h3"],
					'description' => ["@selector" => ".st"],
					'link'=> [
						'@selector' => "a@href"
						]
					]
				]
		];
	private $locationKeys = [
			4 => "E",5 => "F",6 => "G",7 => "H",8 => "I",
			9 => "J",10 => "K",11 => "L",12 => "M",13 => "N",
			14 => "O",15 => "P",16 => "Q",17 => "R",18 => "S",
			19 => "T",20 => "U",21 => "V",22 => "W",23 => "X",
			24 => "Y",25 => "Z",26 => "a",27 => "b",28 => "c",
			29 => "d",30 => "e",31 => "f",32 => "g",33 => "h",
			34 => "i",35 => "j",36 => "k",37 => "l",38 => "m",
			39 => "n",40 => "o",41 => "p",42 => "q",43 => "r",
			44 => "s",45 => "t",46 => "u",47 => "v",48 => "w",
			49 => "x",50 => "y",51 => "z",52 => "0",53 => "1",
			54 => "2",55 => "3",56 => "4",57 => "5",58 => "6",
			59 => "7",60 => "8",61 => "9",62 => "-",63 => " ",
			64 => "A",65 => "B",66 => "C",67 => "D",68 => "E",
			69 => "F",70 => "G",71 => "H",72 => "I",73 => "J",
			74 => "K",75 => "L",76 => "M",77 => "N",78 => "O",
			79 => "P",80 => "Q",81 => "R",82 => "S",83 => "T",
			84 => "U",85 => "V",86 => "W",87 => "X",88 => "Y",
			89 => "Z"
		];

	public function __construct($opts) {
		$this->query = isset($opts['query']) ? $opts['query'] : null;
		$this->limit = isset($opts['limit']) ? $opts['limit'] : 1;
		$this->location = isset($opts['location']) ? $opts['location'] : null;
		$this->lastPosition = 1;
		$this->uule = $this->getLocationString($this->location);
		$this->scraper = new WebScraper($this->rules);
		$this->getResultsParallel();
	}

	public function get() {
		return $this->results;
	}

	private function getResultsParallel() {
		$requests = $this->getRequestOptions();
		$response = $this->scraper->start($requests);
		$this->processResults($response);
	}

	private function getRequestOptions() {
		$opts = [['URL' => self::ENDPOINT.urlencode($this->query).$this->getLocationString()]];
		for ($i=1; $i < $this->limit; $i++) {
			$start = $i*self::RESULTS_PER_PAGE;
			$opts[$i] = ['URL' => self::ENDPOINT.urlencode($this->query).'&start='.$start.$this->getLocationString()];
		}
		return $opts;
	}

	private function getLocationString() {
		$key = $this->getLocationKey();
		if ($key) {
			return self::LOCATION_PARAM.self::LOCATION_SECRET.$key.base64_encode($this->location);
		}
		return '';
	}

	private function getLocationKey() {
		if (is_null($this->location)) {
			return false;
		}
		if (isset($this->locationKeys[strlen($this->location)])) {
			return $this->locationKeys[strlen($this->location)];
		}
		return false;
	}

	private function processResults($data) {
		foreach ($data as $page) {
			foreach ($page['results'] as $result) {
				$result['position'] = $this->lastPosition;
				if (is_array($result['link']) AND count($result['link'])) {
					$result['link'] = $result['link'][0];
				}
				$this->results[$this->lastPosition] = $result;
				$this->lastPosition++;
			}
		}
	}

	public static function getMultiple($keywords) {
		$result = array();
		foreach ($keywords as $opts) {
			$result[$opts['keyword']] = new self($opts);
		}
		return $result;
	}

}

?>