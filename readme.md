Nette Tokenizer
===============

[![Downloads this Month](https://img.shields.io/packagist/dm/nette/tokenizer.svg)](https://packagist.org/packages/nette/tokenizer)
[![Tests](https://github.com/nette/tokenizer/workflows/Tests/badge.svg?branch=master)](https://github.com/nette/tokenizer/actions)
[![Coverage Status](https://coveralls.io/repos/github/nette/tokenizer/badge.svg?branch=master)](https://coveralls.io/github/nette/tokenizer?branch=master)
[![Latest Stable Version](https://poser.pugx.org/nette/tokenizer/v/stable)](https://github.com/nette/tokenizer/releases)
[![License](https://img.shields.io/badge/license-New%20BSD-blue.svg)](https://github.com/nette/tokenizer/blob/master/license.md)


Introduction
------------

Tokenizer is a tool that uses regular expressions to split given string into tokens. What the hell is that good for, you might ask? Well, you can create your own languages!

Documentation can be found on the [website](https://doc.nette.org/tokenizer).

If you like Nette, **[please make a donation now](https://nette.org/donate)**. Thank you!


Installation
------------

The recommended way to install is via Composer:

```
composer require nette/tokenizer
```

The release 2.3 requires PHP version 5.4 and supports PHP up to 7.2. The release 3.0 requires PHP 7.1.


Usage
-----

Let's create a simple tokenizer that separates strings to numbers, whitespaces and letters.

```php
use Nette\Tokenizer\Tokenizer;

$tokenizer = new Tokenizer([
	T_DNUMBER => '\d+',
	T_WHITESPACE => '\s+',
	T_STRING => '\w+',
]);
```

*Hint: In case you are wondering where the T_ constants come from, they are [internal type](http://php.net/manual/tokens.php) used for parsing code. They cover most of the common token names we usually need. Keep in mind their value is not guaranteed so don't use numbers for comparison.*

Now when we give it a string, it will return stream (Nette\Tokenizer\Stream) of tokens (Nette\Tokenizer\Token).

```php
$stream = $tokenizer->tokenize("say \n123");
```

The resulting array of tokens `$stream->tokens` would look like this.

```php
[
	new Token('say', T_STRING, 0),
	new Token(" \n", T_WHITESPACE, 3),
	new Token('123', T_DNUMBER, 5),
]
```

Also, you can access the individual properties of token:

```php
$firstToken = $stream->tokens[0];
echo $firstToken->value; // say
echo $firstToken->type; // value of T_STRING
echo $firstToken->offset; // position in string: 0
```

Simple, isn't it?


Processing the tokens
---------------------

Now we know how to create tokens from string. Let's effectively process them using `Nette\Tokenizer\Stream`. It has a lot of really awesome methods if you need to traverse tokens!

Let's try to parse a simple annotation from PHPDoc and create an object from it. What regular expressions do we need for tokens? All the annotations start with `@`, then there is a name, whitespace and it's value.

- `@` for the annotation start
- `\s+` for whitespaces
- `\w+` for strings

(Never use capturing subpatterns in Tokenizer's regular expressions like `'(ab)+c'`, use only non-capturing ones `'(?:ab)+c'`.)

This should work on simple annotations, right? Now let's define few classes to demonstrate.

```php
class Author
{
	public $name;

	public function __construct($name)
	{
		$this->name = $name;
	}
}

class Package
{
	public $name;

	public function __construct($name)
	{
		$this->name = $name;
	}
}
```

and input string that we will try to parse.

```php
$input = '
	@author David Grudl
	@package Nette
';
```

Let's create a `Parser` class that will accept the string and return an array of objects. It will be very naive and simple.

```php
use Nette\Tokenizer\Tokenizer;
use Nette\Tokenizer\Stream;

class Parser
{
	const T_AT = 1;
	const T_WHITESPACE = 2;
	const T_STRING = 3;

	/** @var Tokenizer */
	private $tokenizer;

	/** @var Stream */
	private $stream;

	public function __construct()
	{
		$this->tokenizer = new Tokenizer([
			self::T_AT => '@',
			self::T_WHITESPACE => '\s+',
			self::T_STRING => '\w+',
		]);
	}

	public function parse($input)
	{
		$this->stream = $this->tokenizer->tokenize($input);

		$result = [];
		while ($this->stream->nextToken()) {
			if ($this->stream->isCurrent(self::T_AT)) {
				$result[] = $this->parseAnnotation();
			}
		}

		return $result;
	}

	protected function parseAnnotation()
	{
		$name = $this->stream->joinUntil(self::T_WHITESPACE);
		$this->stream->nextUntil(self::T_STRING);
		$content = $this->stream->joinUntil(self::T_AT);

		return new $name(trim($content));
	}
}
```

```php
$parser = new Parser;
$annotations = $parser->parse($input);
```

So what the `parse()` method does? It iterates over the tokens and searches for `@` which is the symbol annotations start with. Calling `nextToken()` moves the cursor to the next token. Method `isCurrent()` checks if the current token at the cursor is the given type. Then, if the `@` is found, the `parse()` method calls `parseAnnotation()` which expects the annotations to be in a very speficic format.

First, using the method `joinUntil()`, the stream keeps moving the cursor and appending the tokens values to the buffer until it finds token of required type, then stops and returns the buffer output. Because there is only one token of type `T_STRING` at that given position and it's `'name'`, there will be value `'name'` in variable `$name`.

Method `nextUntil()` is similar like `joinUntil()` but it has no buffer. It only moves the cursor until it finds the token. So this call simply skips all the whitespaces after annotation name.

And then, there is another `joinUntil()`, that searches for next `@`. This specific call will return `"David Grudl\n    "`.

And there we go, we've parsed one whole annotation! Now we can create an instance of class for that specific annotation and pass it the parsed value. The `$content` probably ends with whitespaces, so we have to trim it.

Try copypasting the code and running it. If you dump the `$annotations` variable it should return some similar output.

```
array (2)
   0 => Author
   |  name => "David Grudl"
   |
   1 => Package
      name => "Nette"
```

Stream methods
--------------

The stream can return current token using method `currentToken()` or only it's value using `currentValue()`.

`nextToken()` moves the cursor and returns the token. If you give it no arguments, it simply returns next token.

`nextValue()` is just like `nextToken()` but it only returns the token value.

Most of the methods also accept multiple arguments so you can search for multiple types at once.

```php
// iterate until a string or a whitespace is found, then stop and return the following token
$token = $stream->nextToken(T_STRING, T_WHITESPACE);

// give me next token
$token = $stream->nextToken();
```

You can also search by the token value.

```php
// move the cursor until you find token containing only '@', then stop and return it
$token = $stream->nextToken('@');
```

`nextUntil()` moves the cursor and returns the array of all the tokens it sees until it finds the desired token, but it stops before the token. It can accept multiple arguments.

`joinUntil()` is similar to `nextUntil()`, but concatenates all the tokens it passed through and returns string.

`joinAll()` simply concatenates all the remaining token values and returns it. It moves the cursor to the end of the token stream

`nextAll()` is just like `joinAll()`, but it returns array of the tokens.

`isCurrent()` checks if the current token or the current token's value is equal to one of given arguments.

```php
// is the current token '@' or type of T_AT?
$stream->isCurrent(T_AT, '@');
```

`isNext()` is just like `isCurrent()` but it checks the next token.

`isPrev()` is just like `isCurrent()` but it checks the previous token.

And the last method `reset()` resets the cursor, so you can iterate the token stream again.
