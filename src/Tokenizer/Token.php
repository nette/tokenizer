<?php

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

namespace Nette\Tokenizer;


/**
 * Simple token.
 */
class Token
{
	/** @var string */
	public $value;

	/** @var int|string */
	public $type;

	/** @var int */
	public $offset;


	public function __construct($value, $type, $offset)
	{
		$this->value = $value;
		$this->type = $type;
		$this->offset = $offset;
	}
}
