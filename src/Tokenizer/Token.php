<?php

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

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


	public function __construct(string $value, $type, int $offset)
	{
		$this->value = $value;
		$this->type = $type;
		$this->offset = $offset;
	}


	/** @param  int|string  ...$args */
	public function is(...$args): bool
	{
		return in_array($this->value, $args, true)
			|| in_array($this->type, $args, true);
	}
}
