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
	public function __construct(
		public string $value,
		public int|string $type,
		public int $offset,
	) {
	}
}
