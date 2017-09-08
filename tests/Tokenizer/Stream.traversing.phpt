<?php

/**
 * Test: Nette\Tokenizer\Stream traversing
 */

use Nette\Tokenizer\Stream;
use Nette\Tokenizer\Tokenizer;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


test(function () {
	$tokenizer = new Tokenizer([
		T_DNUMBER => '\d+',
		T_WHITESPACE => '\s+',
		T_STRING => '\w+',
	]);
	$stream = new Stream($tokenizer->tokenize('say 123'));

	Assert::false($stream->isPrev());
	Assert::true($stream->isNext());
	Assert::same([], $stream->nextAll(T_DNUMBER));
	Assert::same([
		['say', 0, T_STRING],
		[' ', 3, T_WHITESPACE],
	], $stream->nextUntil(T_DNUMBER));
	Assert::true($stream->isCurrent(T_WHITESPACE));
	Assert::true($stream->isPrev());
	Assert::true($stream->isNext());
	Assert::true($stream->isPrev(T_STRING));
	Assert::false($stream->isPrev(T_DNUMBER));
	Assert::true($stream->isNext(T_DNUMBER));
	Assert::true($stream->isNext(T_STRING, T_DNUMBER));
	Assert::same([], $stream->nextUntil(T_STRING, T_DNUMBER, T_WHITESPACE));
	Assert::same([['123', 4, T_DNUMBER]], $stream->nextAll());
	Assert::true($stream->isPrev());
	Assert::false($stream->isNext());
});


test(function () {
	$tokenizer = new Tokenizer([
		T_DNUMBER => '\d+',
		T_WHITESPACE => '\s+',
		T_STRING => '\w+',
	]);
	$stream = new Stream($tokenizer->tokenize('say 123'));
	$stream->ignored[] = T_WHITESPACE;

	Assert::same(-1, $stream->position);
	Assert::same(['say', 0, T_STRING], $stream->nextToken());
	Assert::same(0, $stream->position);

	$stream->position = -1;
	Assert::null($stream->nextToken(T_DNUMBER));
	Assert::same(-1, $stream->position);
	Assert::same(['say', 0, T_STRING], $stream->nextToken(T_STRING));
	Assert::same(0, $stream->position);

	$stream->position = -1;
	Assert::same([], $stream->nextAll(T_DNUMBER));
	Assert::same(-1, $stream->position);
	Assert::same([['say', 0, T_STRING]], $stream->nextAll(T_STRING));
	Assert::same(0, $stream->position);

	$stream->position = -1;
	Assert::same([], $stream->nextUntil(T_STRING));
	Assert::same(-1, $stream->position);
	Assert::same([['say', 0, T_STRING]], $stream->nextUntil(T_WHITESPACE));
	Assert::same(0, $stream->position);

	$stream->position = -1;
	Assert::same(-1, $stream->position);
	Assert::same([
		['say', 0, T_STRING],
		[' ', 3, T_WHITESPACE],
	], $stream->nextUntil(T_DNUMBER));
	Assert::same(1, $stream->position);


	$stream->position = 0;
	Assert::null($stream->nextToken(T_STRING));
	Assert::same(0, $stream->position);
	Assert::same(['123', 4, T_DNUMBER], $stream->nextToken(T_STRING, T_DNUMBER));
	Assert::same(2, $stream->position);

	$stream->position = 0;
	Assert::same([], $stream->nextAll(T_STRING));
	Assert::same(0, $stream->position);
	Assert::same([['123', 4, T_DNUMBER]], $stream->nextAll(T_STRING, T_DNUMBER));
	Assert::same(2, $stream->position);

	$stream->position = 0;
	Assert::same([], $stream->nextUntil(T_WHITESPACE));
	Assert::same(0, $stream->position);
	Assert::same([[' ', 3, T_WHITESPACE]], $stream->nextUntil(T_STRING, T_DNUMBER));
	Assert::same(1, $stream->position);


	$stream->position = 2;
	Assert::null($stream->nextToken());
	Assert::null($stream->nextToken());
	Assert::same(3, $stream->position);

	$stream->position = 2;
	Assert::null($stream->nextToken());
	Assert::null($stream->nextToken(T_STRING, T_DNUMBER, T_WHITESPACE));
	Assert::same(3, $stream->position);

	$stream->position = 2;
	Assert::same([], $stream->nextAll());
	Assert::same([], $stream->nextAll(T_STRING, T_DNUMBER, T_WHITESPACE));
	Assert::same(3, $stream->position);

	$stream->position = 2;
	Assert::same([], $stream->nextUntil(T_STRING, T_DNUMBER, T_WHITESPACE));
	Assert::same(2, $stream->position);
});
