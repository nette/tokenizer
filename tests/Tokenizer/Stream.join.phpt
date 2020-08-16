<?php

/**
 * Test: Nette\Tokenizer\Stream traversing
 */

declare(strict_types=1);

use Nette\Tokenizer\Tokenizer;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


test('', function () {
	$tokenizer = new Tokenizer([
		T_DNUMBER => '\d+',
		T_WHITESPACE => '\s+',
		T_STRING => '\w+',
	]);
	$stream = $tokenizer->tokenize('say 123');
	$stream->ignored[] = T_WHITESPACE;

	Assert::same(-1, $stream->position);
	Assert::same('say', $stream->nextValue());
	Assert::same(0, $stream->position);

	$stream->position = -1;
	Assert::null($stream->nextValue(T_DNUMBER));
	Assert::same(-1, $stream->position);
	Assert::same('say', $stream->nextValue(T_STRING));
	Assert::same(0, $stream->position);

	$stream->position = -1;
	Assert::same('', $stream->joinAll(T_DNUMBER));
	Assert::same(-1, $stream->position);
	Assert::same('say', $stream->joinAll(T_STRING));
	Assert::same(0, $stream->position);

	$stream->position = -1;
	Assert::same('', $stream->joinUntil(T_STRING));
	Assert::same(-1, $stream->position);
	Assert::same('say', $stream->joinUntil(T_WHITESPACE));
	Assert::same(0, $stream->position);

	$stream->position = -1;
	Assert::same(-1, $stream->position);
	Assert::same('say ', $stream->joinUntil(T_DNUMBER));
	Assert::same(1, $stream->position);


	$stream->position = 0;
	Assert::null($stream->nextValue(T_STRING));
	Assert::same(0, $stream->position);
	Assert::same('123', $stream->nextValue(T_STRING, T_DNUMBER));
	Assert::same(2, $stream->position);

	$stream->position = 0;
	Assert::same('', $stream->joinAll(T_STRING));
	Assert::same(0, $stream->position);
	Assert::same('123', $stream->joinAll(T_STRING, T_DNUMBER));
	Assert::same(2, $stream->position);

	$stream->position = 0;
	Assert::same('', $stream->joinUntil(T_WHITESPACE));
	Assert::same(0, $stream->position);
	Assert::same(' ', $stream->joinUntil(T_STRING, T_DNUMBER));
	Assert::same(1, $stream->position);


	$stream->position = 2;
	Assert::null($stream->nextValue());
	Assert::same(3, $stream->position);

	$stream->position = 2;
	Assert::null($stream->nextValue());
	Assert::null($stream->nextValue(T_STRING, T_DNUMBER, T_WHITESPACE));
	Assert::same(3, $stream->position);

	$stream->position = 2;
	Assert::same('', $stream->joinAll());
	Assert::same('', $stream->joinAll(T_STRING, T_DNUMBER, T_WHITESPACE));
	Assert::same(3, $stream->position);

	$stream->position = 2;
	Assert::same('', $stream->joinUntil(T_STRING, T_DNUMBER, T_WHITESPACE));
	Assert::same(2, $stream->position);
});


test('', function () {
	$tokenizer = new Tokenizer([
		'\d+',
		'\s+',
		'\w+',
	]);
	$stream = $tokenizer->tokenize('say 123');
	Assert::null($stream->nextValue('s'));
	Assert::same('say', $stream->nextValue('say'));
	Assert::same(' ', $stream->nextValue());
});


test('', function () {
	$tokenizer = new Tokenizer([
		T_DNUMBER => '\d+',
		T_WHITESPACE => '\s+',
		T_STRING => '\w+',
	]);
	$stream = $tokenizer->tokenize("\nsay\n 123");
	$stream->ignored[] = T_WHITESPACE;

	$stream->position = -1;
	Assert::equal("\n", $stream->consumeValue());
	Assert::same(0, $stream->position);

	$stream->position = -1;
	Assert::exception(function () use ($stream) {
		$stream->consumeValue(T_DNUMBER);
	}, Nette\Tokenizer\Exception::class, "Unexpected 'say' on line 2, column 1.");
	Assert::same(-1, $stream->position);
	Assert::equal('say', $stream->consumeValue(T_STRING));
	Assert::same(1, $stream->position);

	$stream->position = 2;
	Assert::exception(function () use ($stream) {
		$stream->consumeValue(T_STRING);
	}, Nette\Tokenizer\Exception::class, "Unexpected '123' on line 3, column 2.");
	Assert::same(2, $stream->position);

	$stream->position = 3;
	Assert::exception(function () use ($stream) {
		$stream->consumeValue();
	}, Nette\Tokenizer\Exception::class, 'Unexpected end of string');
	Assert::same(4, $stream->position);

	$stream->position = 3;
	Assert::exception(function () use ($stream) {
		$stream->consumeValue(T_STRING, T_DNUMBER, T_WHITESPACE);
	}, Nette\Tokenizer\Exception::class, 'Unexpected end of string');
	Assert::same(3, $stream->position);
});
