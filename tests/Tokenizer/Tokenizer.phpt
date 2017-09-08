<?php

/**
 * Test: Nette\Tokenizer\Tokenizer::tokenize
 */

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
Assert::equal([
	new Token('say', T_STRING, 0),
	new Token(" \n", T_WHITESPACE, 3),
	new Token('123', T_DNUMBER, 5),
], $stream->tokens);

Assert::exception(function () use ($tokenizer) {
	$tokenizer->tokenize('say 123;');
}, 'Nette\Tokenizer\Exception', "Unexpected ';' on line 1, column 8.");
