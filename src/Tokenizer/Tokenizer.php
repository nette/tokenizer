<?php

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

namespace Nette\Tokenizer;


/**
 * Simple lexical analyser.
 */
class Tokenizer
{
	/** @var string */
	private $re;

	/** @var array */
	private $types;


	/**
	 * @param  array of [(int|string) token type => (string) pattern]
	 * @param  string  regular expression flags
	 */
	public function __construct(array $patterns, $flags = '')
	{
		$this->re = '~(' . implode(')|(', $patterns) . ')~A' . $flags;
		$this->types = array_keys($patterns);
	}


	/**
	 * Tokenizes string.
	 * @param  string
	 * @return Stream
	 * @throws Exception
	 */
	public function tokenize($input)
	{
		preg_match_all($this->re, $input, $tokens, PREG_SET_ORDER);
		$len = 0;
		$count = count($this->types);
		foreach ($tokens as &$token) {
			$type = null;
			for ($i = 1; $i <= $count; $i++) {
				if (!isset($token[$i])) {
					break;
				} elseif ($token[$i] != null) {
					$type = $this->types[$i - 1];
					break;
				}
			}
			$token = new Token($token[0], $type, $len);
			$len += strlen($token->value);
		}
		if ($len !== strlen($input)) {
			list($line, $col) = $this->getCoordinates($input, $len);
			$token = str_replace("\n", '\n', substr($input, $len, 10));
			throw new Exception("Unexpected '$token' on line $line, column $col.");
		}
		return new Stream($tokens);
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
