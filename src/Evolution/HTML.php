<?php
namespace EvoPack\Evolution;

class HTML {
	protected $text = null;
	
	public function __construct(string $text = null)
	{
		if (!empty($text)) {
			$this->text = $text;
		}
	}

	public function getText()
	{
		return $this->text;
	}
}