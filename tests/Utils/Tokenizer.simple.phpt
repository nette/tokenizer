<?php

/**
 * Test: Nette\Utils\Tokenizer::tokenize simple
 */

declare(strict_types=1);

use Nette\Utils\Tokenizer;
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
}, Nette\Utils\TokenizerException::class, "Unexpected ';' on line 1, column 8.");
