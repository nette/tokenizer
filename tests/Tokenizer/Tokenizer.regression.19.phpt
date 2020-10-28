<?php

/**
 * Test: Nette\Tokenizer\Tokenizer::tokenize
 */

declare(strict_types=1);

use Nette\Tokenizer\Token;
use Nette\Tokenizer\Tokenizer;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


$tokenizer = new Tokenizer([
	T_DNUMBER => '\d+',
	T_WHITESPACE => '\s+',
	T_STRING => '\w+',
]);
$stream = $tokenizer->tokenize("say \n123");

$expectedTokens = [
	new Token('say', T_STRING, 0),
	new Token(" \n", T_WHITESPACE, 3),
	new Token('123', T_DNUMBER, 5),
];



test('process tokens with while() and nextToken()', function () use ($expectedTokens, $stream) {
	$stream->reset();
	$accumulator = [];
	while ($token = $stream->nextToken()) {
		$accumulator[] = $token;
	}

	Assert::equal($accumulator, $expectedTokens);
});





test('reading with currentToken(), moving with nextToken()', function () use ($expectedTokens, $stream) {
	$stream->reset();

	// position -1
	Assert::null($stream->currentToken());

	// position 0
	$stream->nextToken();
	Assert::equal($expectedTokens[0], $stream->currentToken());

	// position 1
	$stream->nextToken();
	Assert::equal($expectedTokens[1], $stream->currentToken());

	// position 2
	$stream->nextToken();
	Assert::equal($expectedTokens[2], $stream->currentToken());

	// position 3
	$stream->nextToken();
	Assert::null($stream->currentToken());
});

// process token with while() and currentToken()
test('(more real world use-case, does the same thing like linearized example above)', function () use ($expectedTokens, $stream) {
	$stream->reset();
	$accumulator = [];
	$stream->nextToken();
	while ($token = $stream->currentToken()) {
		$accumulator[] = $token;
		$stream->nextToken();
	}

	Assert::equal($accumulator, $expectedTokens);
});
