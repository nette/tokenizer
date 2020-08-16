<?php

/**
 * Test: Nette\Tokenizer\Stream traversing
 */

declare(strict_types=1);

use Nette\Tokenizer\Token;
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

	Assert::false($stream->isPrev());
	Assert::true($stream->isNext());
	Assert::same([], $stream->nextAll(T_DNUMBER));
	Assert::equal([
		new Token('say', T_STRING, 0),
		new Token(' ', T_WHITESPACE, 3),
	], $stream->nextUntil(T_DNUMBER));
	Assert::true($stream->isCurrent(T_WHITESPACE));
	Assert::true($stream->isPrev());
	Assert::true($stream->isNext());
	Assert::true($stream->isPrev(T_STRING));
	Assert::false($stream->isPrev(T_DNUMBER));
	Assert::true($stream->isNext(T_DNUMBER));
	Assert::true($stream->isNext(T_STRING, T_DNUMBER));
	Assert::same([], $stream->nextUntil(T_STRING, T_DNUMBER, T_WHITESPACE));
	Assert::equal([new Token('123', T_DNUMBER, 4)], $stream->nextAll());
	Assert::true($stream->isPrev());
	Assert::false($stream->isNext());
});


test('', function () {
	$tokenizer = new Tokenizer([
		T_DNUMBER => '\d+',
		T_WHITESPACE => '\s+',
		T_STRING => '\w+',
	]);
	$stream = $tokenizer->tokenize('say 123');
	$stream->ignored[] = T_WHITESPACE;

	Assert::same(-1, $stream->position);
	Assert::equal(new Token('say', T_STRING, 0), $stream->nextToken());
	Assert::same(0, $stream->position);

	$stream->position = -1;
	Assert::null($stream->nextToken(T_DNUMBER));
	Assert::same(-1, $stream->position);
	Assert::equal(new Token('say', T_STRING, 0), $stream->nextToken(T_STRING));
	Assert::same(0, $stream->position);

	$stream->position = -1;
	Assert::same([], $stream->nextAll(T_DNUMBER));
	Assert::same(-1, $stream->position);
	Assert::equal([new Token('say', T_STRING, 0)], $stream->nextAll(T_STRING));
	Assert::same(0, $stream->position);

	$stream->position = -1;
	Assert::same([], $stream->nextUntil(T_STRING));
	Assert::same(-1, $stream->position);
	Assert::equal([new Token('say', T_STRING, 0)], $stream->nextUntil(T_WHITESPACE));
	Assert::same(0, $stream->position);

	$stream->position = -1;
	Assert::same(-1, $stream->position);
	Assert::equal([
		new Token('say', T_STRING, 0),
		new Token(' ', T_WHITESPACE, 3),
	], $stream->nextUntil(T_DNUMBER));
	Assert::same(1, $stream->position);


	$stream->position = 0;
	Assert::null($stream->nextToken(T_STRING));
	Assert::same(0, $stream->position);
	Assert::equal(new Token('123', T_DNUMBER, 4), $stream->nextToken(T_STRING, T_DNUMBER));
	Assert::same(2, $stream->position);

	$stream->position = 0;
	Assert::same([], $stream->nextAll(T_STRING));
	Assert::same(0, $stream->position);
	Assert::equal([new Token('123', T_DNUMBER, 4)], $stream->nextAll(T_STRING, T_DNUMBER));
	Assert::same(2, $stream->position);

	$stream->position = 0;
	Assert::same([], $stream->nextUntil(T_WHITESPACE));
	Assert::same(0, $stream->position);
	Assert::equal([new Token(' ', T_WHITESPACE, 3)], $stream->nextUntil(T_STRING, T_DNUMBER));
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


test('', function () {
	$tokenizer = new Tokenizer([
		T_DNUMBER => '\d+',
		T_WHITESPACE => '\s+',
		T_STRING => '\w+',
	]);
	$stream = $tokenizer->tokenize("\nsay\n 123");
	$stream->ignored[] = T_WHITESPACE;

	$stream->position = -1;
	Assert::equal(new Token("\n", T_WHITESPACE, 0), $stream->consumeToken());
	Assert::same(0, $stream->position);

	$stream->position = -1;
	Assert::exception(function () use ($stream) {
		$stream->consumeToken(T_DNUMBER);
	}, Nette\Tokenizer\Exception::class, "Unexpected 'say' on line 2, column 1.");
	Assert::same(-1, $stream->position);
	Assert::equal(new Token('say', T_STRING, 1), $stream->consumeToken(T_STRING));
	Assert::same(1, $stream->position);

	$stream->position = 2;
	Assert::exception(function () use ($stream) {
		$stream->consumeToken(T_STRING);
	}, Nette\Tokenizer\Exception::class, "Unexpected '123' on line 3, column 2.");
	Assert::same(2, $stream->position);

	$stream->position = 3;
	Assert::exception(function () use ($stream) {
		$stream->consumeToken();
	}, Nette\Tokenizer\Exception::class, 'Unexpected end of string');
	Assert::same(4, $stream->position);

	$stream->position = 3;
	Assert::exception(function () use ($stream) {
		$stream->consumeToken(T_STRING, T_DNUMBER, T_WHITESPACE);
	}, Nette\Tokenizer\Exception::class, 'Unexpected end of string');
	Assert::same(3, $stream->position);
});
