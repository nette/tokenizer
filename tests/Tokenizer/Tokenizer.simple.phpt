<?php

/**
 * Test: Nette\Tokenizer\Tokenizer::tokenize simple
 */

use Nette\Tokenizer\Tokenizer;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


$tokenizer = new Tokenizer([
	'\d+',
	'\s+',
	'\w+',
]);
$tokens = $tokenizer->tokenize('say 123');
Assert::same([
	['say', 0],
	[' ', 3],
	['123', 4],
], $tokens);

Assert::exception(function () use ($tokenizer) {
	$tokenizer->tokenize('say 123;');
}, 'Nette\Tokenizer\Exception', "Unexpected ';' on line 1, column 8.");
