<?php

/**
 * This file is part of the Nette Framework (http://nette.org)
 * Copyright (c) 2004 David Grudl (http://davidgrudl.com)
 */

namespace Nette\Utils;

use Nette;


/**
 * Simple lexical analyser.
 *
 * @author     David Grudl
 */
class Tokenizer
{
	const VALUE = 0,
		OFFSET = 1,
		TYPE = 2;

	/** @var string */
	private $re;

	/** @var array|FALSE */
	private $types;


	/**
	 * @param  array of [(int|string) token type => (string) pattern]
	 * @param  string  regular expression flags
	 */
	public function __construct(array $patterns, $flags = '')
	{
		$this->re = '~(' . implode(')|(', $patterns) . ')~A' . $flags;
		$keys = array_keys($patterns);
		$this->types = $keys === range(0, count($patterns) - 1) ? FALSE : $keys;
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
			foreach ($tokens as & $match) {
				$type = NULL;
				for ($i = 1; $i <= $count; $i++) {
					if (!isset($match[$i])) {
						break;
					} elseif ($match[$i] != NULL) {
						$type = $this->types[$i - 1]; break;
					}
				}
				$match = array(self::VALUE => $match[0], self::OFFSET => $len, self::TYPE => $type);
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
		return array(substr_count($text, "\n") + 1, $offset - strrpos("\n" . $text, "\n") + 1);
	}

}
