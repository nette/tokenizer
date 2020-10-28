<?php

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Nette\Utils;


/**
 * Simple lexical analyser.
 * @deprecated use similar Nette\Tokenizer\Tokenizer
 */
class Tokenizer
{
	public const
		VALUE = 0,
		OFFSET = 1,
		TYPE = 2;

	/** @var string */
	private $re;

	/** @var array|false */
	private $types;


	/**
	 * @param  array  $patterns  of [(int|string) token type => (string) pattern]
	 * @param  string  $flags  regular expression flags
	 */
	public function __construct(array $patterns, string $flags = '')
	{
		trigger_error(self::class . ' is deprecated, use similar Nette\Tokenizer\Tokenizer', E_USER_DEPRECATED);
		$this->re = '~(' . implode(')|(', $patterns) . ')~A' . $flags;
		$keys = array_keys($patterns);
		$this->types = $keys === range(0, count($patterns) - 1) ? false : $keys;
	}


	/**
	 * Tokenizes string.
	 * @throws TokenizerException
	 */
	public function tokenize(string $input): array
	{
		if ($this->types) {
			preg_match_all($this->re, $input, $tokens, PREG_SET_ORDER);
			$len = 0;
			$count = count($this->types);
			foreach ($tokens as &$match) {
				$type = null;
				for ($i = 1; $i <= $count; $i++) {
					if (!isset($match[$i])) {
						break;
					} elseif ($match[$i] !== '') {
						$type = $this->types[$i - 1];
						break;
					}
				}

				$match = [self::VALUE => $match[0], self::OFFSET => $len, self::TYPE => $type];
				$len += strlen($match[self::VALUE]);
			}

			if ($len !== strlen($input)) {
				$errorOffset = $len;
			}

		} else {
			$tokens = preg_split($this->re, $input, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_OFFSET_CAPTURE | PREG_SPLIT_DELIM_CAPTURE);
			$last = end($tokens);
			if ($tokens && !preg_match($this->re, $last[0])) {
				$errorOffset = $last[1];
			}
		}

		if (isset($errorOffset)) {
			[$line, $col] = $this->getCoordinates($input, $errorOffset);
			$token = str_replace("\n", '\n', substr($input, $errorOffset, 10));
			throw new TokenizerException("Unexpected '$token' on line $line, column $col.");
		}
		return $tokens;
	}


	/**
	 * Returns position of token in input string.
	 * @return array of [line, column]
	 */
	public static function getCoordinates(string $text, int $offset): array
	{
		$text = substr($text, 0, $offset);
		return [substr_count($text, "\n") + 1, $offset - strrpos("\n" . $text, "\n") + 1];
	}
}
