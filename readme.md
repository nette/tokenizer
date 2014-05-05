Nette Tokenizer
===============

[![Downloads this Month](https://img.shields.io/packagist/dm/nette/tokenizer.svg)](https://packagist.org/packages/nette/tokenizer)
[![Build Status](https://travis-ci.org/nette/tokenizer.svg?branch=v2.2)](https://travis-ci.org/nette/tokenizer)

Tokenizer is a tool that uses regular expressions to split given string into tokens. What the hell is that good for, you might ask? Well, you can create your own languages! Tokenizer is used in [Latte](https://github.com/nette/latte) for example.


## String tokenization

Let's create a simple tokenizer that separates strings to numbers, whitespaces and letters.

```php
$tokenizer = new Tokenizer(array(
	T_DNUMBER => '\d+',
	T_WHITESPACE => '\s+',
	T_STRING => '\w+',
));
```

*Hint: In case you are wondering where the T_ constants come from, they are [internal type](http://php.net/manual/tokens.php) used for parsing code. They cover most of the common token names we usually need. Keep in mind their value is not guaranteed so don't use numbers for comparison.*

Now when we give it a string, it will return array of tokens.

```php
$tokens = $tokenizer->tokenize("say \n123");
```

The resulting array of tokens would look like this.

```php
array(
	array('say', 0, T_STRING),
	array(" \n", 3, T_WHITESPACE),
	array('123', 5, T_DNUMBER),
)
```

Also, you should use constants from `Tokenizer` to access the individual values or expand them using `list()`.

```php
$firstToken = $tokens[0];
echo $firstToken[Tokenizer::VALUE]; // say
echo $firstToken[Tokenizer::OFFSET]; // 0
echo $firstToken[Tokenizer::TYPE]; // 308, which is the value of T_STRING

// or shorter
list($value, $offset, $type) = $tokens[0];
```

Simple, isn't it?


## Processing the tokens

Now we know how to create tokens from string. Let's effectively process them using `TokenIterator`. The `TokenIterator` is not a standard iterator. You cannot `foreach` over it, it doesn't implement `Traversable` interface. But it has a lot of really awesome methods if you need to traverse tokens!

Let's try to parse a simple annotation from PHPDoc and create an object from it. What regular expressions do we need for tokens? All the annotations start with `@`, then there is a name, whitespace and it's value.

- `@` for the annotation start
- `\\s+` for whitespaces
- `\\w+` for strings

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
$input = "
	@author David Grudl
	@package Nette
";
```

Let's create a `Parser` class that will accept the string and return an array of objects. It will be very naive and simple.

```php
class Parser
{
	const T_AT = 1;
	const T_WHITESPACE = 2;
	const T_STRING = 3;

	/** @var \Nette\Utils\Tokenizer */
	private $tokenizer;

	/** @var \Nette\Utils\TokenIterator */
	private $iterator;

	public function __construct()
	{
		$this->tokenizer = new Tokenizer(array(
			self::T_AT => '@',
			self::T_WHITESPACE => '\\s+',
			self::T_STRING => '\\w+',
		));
	}

	public function parse($input)
	{
		$this->iterator = new TokenIterator($this->tokenizer->tokenize($input));

		$result = array();
		while ($this->iterator->nextToken()) {
			if ($this->iterator->isCurrent(self::T_AT)) {
				$result[] = $this->parseAnnotation();
			}
		}

		return $result;
	}

	protected function parseAnnotation()
	{
		$name = $this->iterator->joinUntil(self::T_WHITESPACE);
		$this->iterator->nextUntil(self::T_STRING);
		$content = $this->iterator->joinUntil(self::T_AT);

		return new $name(trim($content));
	}
}
```

```php
$parser = new Parser();
$annotations = $parser->parse($input);
```

So what the `parse()` method does? It iterates over the tokens and searches for `@` which is the symbol annotations start with. Calling `nextToken()` moves the cursor to the next token. Method `isCurrent()` checks if the current token at the cursor is the given type. Then, if the `@` is found, the `parse()` method calls `parseAnnotation()` which expects the annotations to be in a very speficic format.

First, using the method `joinUntil()`, the iterator keeps moving the cursor and appending the string to the buffer until it finds token of required type, then stops and returns the buffer output. Because there is only one token of type `T_STRING` at that given position and it's `'name'`, there will be value `'name'` in variable `$name`.

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

## TokenIterator methods

The iterator can return current token using method `currentToken()` or only it's value using `currentValue()`.

`nextToken()` moves the cursor and returns the token. If you give it no arguments, it simply returns next token.

`nextValue()` is just like `nextToken()` but it only returns the token value.

Most of the methods also accept multiple arguments so you can search for multiple types at once.

```php
// iterate until a string or a whitespace is found, then stop and return the following token
$token = $iterator->nextToken(T_STRING, T_WHITESPACE);

// give me next token
$token = $iterator->nextToken();
```

You can also search by the token value.

```php
// move the cursor until you find token containing only '@', then stop and return it
$token = $iterator->nextToken('@');
```

`nextUntil()` moves the cursor and returns the array of all the tokens it sees until it finds the desired token, but it stops before the token. It can accept multiple arguments.

`joinUntil()` is similar to `nextUntil()`, but joins the token values in a buffer and when it finds the desired token, it stops before it and returns the buffer contents.

`joinAll()` simply concatenates all the remaining token values and returns it. It moves the cursor to the end of the token stream

`nextAll()` is just like `joinAll()`, but it returns array of the tokens.

`isCurrent()` checks if the current token or the current token's value is equal to one of given arguments.

```php
// is the current token '@' or type of T_AT?
$iterator->isCurrent(T_AT, '@');
```

`isNext()` is just like `isCurrent()` but it checks the next token.

`isPrev()` is just like `isCurrent()` but it checks the previous token.

And the last method `reset()` resets the cursor, so you can iterate the token stream again.
