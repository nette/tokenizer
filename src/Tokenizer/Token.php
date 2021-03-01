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
	public string $value;

	public int|string $type;

	public int $offset;


	public function __construct(string $value, $type, int $offset)
	{
		$this->value = $value;
		$this->type = $type;
		$this->offset = $offset;
	}
}
