<?php

/**
* @package   s9e\SourceOptimizer
* @copyright Copyright (c) 2014-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\SourceOptimizer\Passes;

use s9e\SourceOptimizer\Pass;
use s9e\SourceOptimizer\TokenStream;

/**
* Optimizes the control structures of a script.
*
* Removes brackets in control structures wherever possible. Prevents the generation of EXT_STMT
* opcodes where they're not strictly required.
*/
class OptimizeControlStructures extends Pass
{
	/**
	* @var array Offsets of braces that need to be preserved (offsets used as keys)
	*/
	protected $preservedBraces;

	/**
	* @var TokenStream Token stream of the source being processed
	*/
	protected $stream;

	/**
	* {@inheritdoc}
	*/
	public function optimize(TokenStream $stream)
	{
		$this->preservedBraces = [];
		$this->stream = $stream;
		$structures = [];
		while ($this->stream->valid())
		{
			if ($this->isControlStructure())
			{
				$structures[] = $this->parseControlStructure();
			}
			$this->stream->next();
		}

		$this->optimizeStructures($structures);
	}

	/**
	* Test whether the token at current offset is a control structure
	*
	* NOTE: we ignore T_DO since the braces are not optional
	*
	* @return bool
	*/
	protected function isControlStructure()
	{
		return $this->stream->isAny([T_ELSE, T_ELSEIF, T_FOR, T_FOREACH, T_IF, T_WHILE]);
	}

	/**
	* Optimize given T_ELSE structure
	*
	* @param  array $structure
	* @return void
	*/
	protected function optimizeElse(array $structure)
	{
		$this->stream->seek($structure['offsetLeftBrace']);
		$this->stream->next();
		$this->stream->skipNoise();
		if (!$this->stream->is(T_IF))
		{
			return;
		}
		$this->stream->replace([T_ELSEIF, 'elseif']);
		$this->unindentBlock($structure['offsetLeftBrace'] + 1, $structure['offsetRightBrace'] - 1);
		$this->stream->seek($structure['offsetConstruct']);
		$this->stream->remove();
		$this->removeWhitespaceBefore($this->stream->key());
	}

	/**
	* Optimize given structure and the other structures it contains
	*
	* @param  array $structure
	* @return void
	*/
	protected function optimizeStructure(array $structure)
	{
		if ($structure['structures'])
		{
			$this->optimizeStructures($structure['structures']);
		}

		if ($structure['statements'] <= 1
		 && isset($structure['offsetRightBrace'])
		 && !isset($this->preservedBraces[$structure['offsetRightBrace']]))
		{
			if ($structure['isElse'])
			{
				$this->optimizeElse($structure);
			}
			$this->removeBraces($structure);
		}
	}

	/**
	* Optimize a list of parsed structures
	*
	* @param  array[] $structures
	* @return void
	*/
	protected function optimizeStructures(array $structures)
	{
		foreach ($structures as $structure)
		{
			$this->optimizeStructure($structure);
		}
	}

	/**
	* Parse the control structure starting at current offset
	*
	* @return array
	*/
	protected function parseControlStructure()
	{
		$structure = [
			'isElse'           => $this->stream->is(T_ELSE),
			'isElseif'         => $this->stream->is(T_ELSEIF),
			'isIf'             => $this->stream->is(T_IF),
			'offsetConstruct'  => $this->stream->key(),
			'offsetLeftBrace'  => null,
			'offsetRightBrace' => null,
			'statements'       => 0,
			'structures'       => []
		];

		if ($structure['isElse'])
		{
			$this->stream->next();
		}
		else
		{
			$this->skipParenthesizedExpression();
		}
		$this->stream->skipNoise();

		if ($this->stream->current() !== '{')
		{
			return $structure;
		}

		$braces = 0;
		$structure['offsetLeftBrace'] = $this->stream->key();
		while ($this->stream->valid())
		{
			$token = $this->stream->current();
			if ($token === ';')
			{
				++$structure['statements'];
			}
			elseif ($token === '{')
			{
				++$braces;
			}
			elseif ($token === '}')
			{
				--$braces;
				if (!$braces)
				{
					break;
				}
			}
			elseif ($this->isControlStructure())
			{
				if (!$this->stream->isAny([T_ELSE, T_ELSEIF]))
				{
					++$structure['statements'];
				}

				$structure['structures'][] = $this->parseControlStructure();
			}

			$this->stream->next();
		}
		$structure['offsetRightBrace'] = $this->stream->key();

		return $structure;
	}

	/**
	* Mark the offset of right braces that must be preserved
	*
	* Works by counting the number of consecutive braces between the starting point and the next
	* non-brace token. If the next token is a T_ELSE or T_ELSEIF and it does not immediately follow
	* current brace then its branch belongs to another conditional, which means the brace must be
	* preserved
	*
	* @param  integer $offset Offset of the first closing brace
	* @return void
	*/
	protected function markPreservedBraces($offset)
	{
		$braces = 0;
		$braceOffset = $offset;
		$isFollowedByElse = false;

		$this->stream->seek($offset);
		while ($this->stream->valid() && $this->stream->current() === '}')
		{
			++$braces;
			$braceOffset = $this->stream->key();

			$this->stream->next();
			$this->stream->skipNoise();
			if ($this->stream->valid())
			{
				$isFollowedByElse = $this->stream->isAny([T_ELSE, T_ELSEIF]);
			}
		}

		if ($isFollowedByElse && $braces > 1)
		{
			$this->preservedBraces[$braceOffset] = 1;
		}
	}

	/**
	* Remove braces from given structure
	*
	* @param  array $structure
	* @return void
	*/
	protected function removeBraces(array $structure)
	{
		if ($structure['isIf'] || $structure['isElseif'])
		{
			$this->markPreservedBraces($structure['offsetRightBrace']);
		}

		// Replace the opening brace with a semicolon if the control structure is empty, remove the
		// brace if possible or replace it with whitespace otherwise (e.g. in "else foreach")
		$this->stream->seek($structure['offsetLeftBrace']);
		if (!$structure['statements'])
		{
			$this->stream->replace(';');
		}
		elseif ($this->stream->canRemoveCurrentToken())
		{
			$this->stream->remove();
		}
		else
		{
			$this->stream->replace([T_WHITESPACE, ' ']);
		}

		// Remove the closing brace
		$this->stream->seek($structure['offsetRightBrace']);
		$this->stream->remove();

		// Remove the whitespace before the braces if possible. This is purely cosmetic
		$this->removeWhitespaceBefore($structure['offsetLeftBrace']);
		$this->removeWhitespaceBefore($structure['offsetRightBrace']);
	}

	/**
	* Remove the whitespace before given offset, if possible
	*
	* @return void
	*/
	protected function removeWhitespaceBefore($offset)
	{
		if (!isset($this->stream[$offset - 1]))
		{
			return;
		}

		$this->stream->seek($offset - 1);
		if ($this->stream->is(T_WHITESPACE) && $this->stream->canRemoveCurrentToken())
		{
			$this->stream->remove();
		}
	}

	/**
	* Skip the condition of a control structure
	*
	* @return void
	*/
	protected function skipParenthesizedExpression()
	{
		// Reach the opening parenthesis
		$this->stream->skipToToken('(');

		// Iterate through tokens until we have a match for every left parenthesis
		$parens = 0;
		$this->stream->next();
		while ($this->stream->valid())
		{
			$token = $this->stream->current();
			if ($token === ')')
			{
				if ($parens)
				{
					--$parens;
				}
				else
				{
					// Skip the last parenthesis
					$this->stream->next();
					break;
				}
			}
			elseif ($token === '(')
			{
				++$parens;
			}
			$this->stream->next();
		}
	}

	/**
	* Remove one tab's worth of indentation off a range of PHP tokens
	*
	* @param  integer $start  Offset of the first token to unindent
	* @param  integer $end    Offset of the last token to unindent
	* @return void
	*/
	protected function unindentBlock($start, $end)
	{
		$this->stream->seek($start);
		while ($this->stream->key() <= $end)
		{
			if ($this->stream->isNoise())
			{
				$token = $this->stream->current();
				$token[1] = preg_replace("/^(?:    |\t)/m", '', $token[1]);
				$this->stream->replace($token);
			}
			$this->stream->next();
		}
	}
}