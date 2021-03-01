<?php

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Nette\Tokenizer;


/**
 * Stream of tokens.
 */
class Stream
{
	/** @var Token[] */
	public array $tokens;

	public int $position = -1;

	public array $ignored = [];


	/**
	 * @param  Token[]  $tokens
	 */
	public function __construct(array $tokens)
	{
		$this->tokens = $tokens;
	}


	/**
	 * Returns current token.
	 */
	public function currentToken(): ?Token
	{
		return $this->tokens[$this->position] ?? null;
	}


	/**
	 * Returns current token value.
	 */
	public function currentValue(): ?string
	{
		return $this->tokens[$this->position]->value ?? null;
	}


	/**
	 * Returns next token.
	 * @param  int|string  ...$args  desired token type or value
	 */
	public function nextToken(int|string ...$args): ?Token
	{
		return $this->scan($args, true, true); // onlyFirst, advance
	}


	/**
	 * Returns next token value.
	 * @param  int|string  ...$args  desired token type or value
	 */
	public function nextValue(int|string ...$args): ?string
	{
		return $this->scan($args, true, true, true); // onlyFirst, advance, strings
	}


	/**
	 * Returns all next tokens.
	 * @param  int|string  ...$args  desired token type or value
	 * @return Token[]
	 */
	public function nextAll(int|string ...$args): array
	{
		return $this->scan($args, false, true); // advance
	}


	/**
	 * Returns all next tokens until it sees a given token type or value.
	 * @param  int|string  ...$args  token type or value to stop before (required)
	 * @return Token[]
	 */
	public function nextUntil(int|string ...$args): array
	{
		return $this->scan($args, false, true, false, true); // advance, until
	}


	/**
	 * Returns next token or throws exception.
	 * @param  int|string  ...$args  desired token type or value
	 * @throws Exception
	 */
	public function consumeToken(int|string ...$args): Token
	{
		if ($token = $this->scan($args, true, true)) { // onlyFirst, advance
			return $token;
		}

		$pos = $this->position + 1;
		while (($next = $this->tokens[$pos] ?? null) && in_array($next->type, $this->ignored, true)) {
			$pos++; // skip ignored
		}
		if (!$next) {
			throw new Exception('Unexpected end of string');
		}

		$s = '';
		do {
			$s = $this->tokens[$pos]->value . $s;
		} while ($pos--);
		[$line, $col] = Tokenizer::getCoordinates($s, $next->offset);
		throw new Exception("Unexpected '$next->value' on line $line, column $col.");
	}


	/**
	 * Returns next token value or throws exception.
	 * @param  int|string  ...$args  desired token type or value
	 * @throws Exception
	 */
	public function consumeValue(int|string ...$args): string
	{
		return $this->consumeToken(...$args)->value;
	}


	/**
	 * Returns concatenation of all next token values.
	 * @param  int|string  ...$args  token type or value to be joined
	 */
	public function joinAll(int|string ...$args): string
	{
		return $this->scan($args, false, true, true); // advance, strings
	}


	/**
	 * Returns concatenation of all next tokens until it sees a given token type or value.
	 * @param  int|string  ...$args  token type or value to stop before (required)
	 */
	public function joinUntil(int|string ...$args): string
	{
		return $this->scan($args, false, true, true, true); // advance, strings, until
	}


	/**
	 * Checks the current token.
	 * @param  int|string  ...$args  token type or value
	 */
	public function isCurrent(int|string ...$args): bool
	{
		if (!isset($this->tokens[$this->position])) {
			return false;
		}
		$token = $this->tokens[$this->position];
		return in_array($token->value, $args, true)
			|| in_array($token->type, $args, true);
	}


	/**
	 * Checks the next token existence.
	 * @param  int|string  ...$args  token type or value
	 */
	public function isNext(int|string ...$args): bool
	{
		return (bool) $this->scan($args, true, false); // onlyFirst
	}


	/**
	 * Checks the previous token existence.
	 * @param  int|string  ...$args  token type or value
	 */
	public function isPrev(int|string ...$args): bool
	{
		return (bool) $this->scan($args, true, false, false, false, true); // onlyFirst, prev
	}


	public function reset(): static
	{
		$this->position = -1;
		return $this;
	}


	/**
	 * Moves cursor to next token.
	 */
	protected function next(): void
	{
		$this->position++;
	}


	/**
	 * Looks for (first) (not) wanted tokens.
	 */
	protected function scan(
		array $wanted,
		bool $onlyFirst,
		bool $advance,
		bool $strings = false,
		bool $until = false,
		bool $prev = false,
	): mixed {
		$res = $onlyFirst ? null : ($strings ? '' : []);
		$pos = $this->position + ($prev ? -1 : 1);
		do {
			if (!isset($this->tokens[$pos])) {
				if (!$wanted && $advance && !$prev && $pos <= count($this->tokens)) {
					$this->next();
				}
				return $res;
			}

			$token = $this->tokens[$pos];
			if (
				!$wanted
				|| (
					in_array($token->value, $wanted, true)
					|| in_array($token->type, $wanted, true)
				) ^ $until
			) {
				while ($advance && !$prev && $pos > $this->position) {
					$this->next();
				}

				if ($onlyFirst) {
					return $strings ? $token->value : $token;
				} elseif ($strings) {
					$res .= $token->value;
				} else {
					$res[] = $token;
				}

			} elseif ($until || !in_array($token->type, $this->ignored, true)) {
				return $res;
			}
			$pos += $prev ? -1 : 1;
		} while (true);
	}
}
