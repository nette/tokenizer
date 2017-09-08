<?php

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

namespace Nette\Utils;


/**
 * Simple lexical analyser.
 * @deprecated use similar Nette\Tokenizer\Tokenizer
 */
class Tokenizer
{
	const VALUE = 0,
		OFFSET = 1,
		TYPE = 2;

	/** @var string */
	private $re;

	/** @var array|false */
	private $types;


	/**
	 * @param  array of [(int|string) token type => (string) pattern]
	 * @param  string  regular expression flags
	 */
	public function __construct(array $patterns, $flags = '')
	{
		$this->re = '~(' . implode(')|(', $patterns) . ')~A' . $flags;
		$keys = array_keys($patterns);
		$this->types = $keys === range(0, count($patterns) - 1) ? false : $keys;
	}


	/**
	 * Tokenizes string.
	 * @param  string
	 * @return array
	 * @throws TokenizerException
	 */
	public function tokenize($input)
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
					} elseif ($match[$i] != null) {
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
			list($line, $col) = $this->getCoordinates($input, $errorOffset);
			$token = str_replace("\n", '\n', substr($input, $errorOffset, 10));
			throw new TokenizerException("Unexpected '$token' on line $line, column $col.");
		}
		return $tokens;
	}


	/**
	 * Returns position of token in input string.
	 * @param  string
	 * @param  int
	 * @return array of [line, column]
	 */
	public static function getCoordinates($text, $offset)
	{
		$text = substr($text, 0, $offset);
		return [substr_count($text, "\n") + 1, $offset - strrpos("\n" . $text, "\n") + 1];
	}
}
