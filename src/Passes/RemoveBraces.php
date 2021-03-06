<?php

/**
* @package   s9e\SourceOptimizer
* @copyright Copyright (c) 2014-2018 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\SourceOptimizer\Passes;

/**
* Optimizes the control structures of a script.
*
* Removes braces in control structures wherever possible. Prevents the generation of EXT_STMT
* opcodes where they're not strictly required.
*/
class RemoveBraces extends AbstractPass
{
	/**
	* @var array Offsets of braces that need to be preserved (offsets used as keys)
	*/
	protected $preservedBraces;

	/**
	* {@inheritdoc}
	*/
	protected function optimizeStream()
	{
		$this->preservedBraces = [];
		$structures = [];
		while ($this->stream->valid())
		{
			if ($this->isControlStructure())
			{
				$structure = $this->parseControlStructure();
				if ($structure)
				{
					$structures[] = $structure;
				}
			}
			$this->stream->next();
		}

		$this->optimizeStructures($structures);
	}

	/**
	* Test whether the given structure's braces can be removed
	*
	* @param  array $structure
	* @return bool
	*/
	protected function canRemoveBracesFrom(array $structure)
	{
		return ($structure['statements'] <= 1 && !isset($this->preservedBraces[$structure['offsetRightBrace']]));
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
	* Test whether the token at current offset is an open curly brace
	*
	* @return bool
	*/
	protected function isCurlyOpen()
	{
		return ($this->stream->current() === '{' || $this->stream->isAny([T_CURLY_OPEN, T_DOLLAR_OPEN_CURLY_BRACES]));
	}

	/**
	* Test whether current token is followed by a function or class-related declaration
	*
	* @return bool
	*/
	protected function isFollowedByDeclaraction()
	{
		$keywords = [T_ABSTRACT, T_CLASS, T_FINAL, T_FUNCTION, T_INTERFACE, T_TRAIT];

		$offset = $this->stream->key();
		$this->stream->next();
		$this->stream->skipNoise();
		$isFollowedByFunction = ($this->stream->valid() && $this->stream->isAny($keywords));
		$this->stream->seek($offset);

		return $isFollowedByFunction;
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
		$this->optimizeStructures($structure['structures']);

		if ($structure['isIf'] || $structure['isElseif'])
		{
			$this->markPreservedBraces($structure['offsetRightBrace']);
		}

		if ($this->canRemoveBracesFrom($structure))
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
	* @return array|false
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

		if ($this->stream->current() !== '{' || $this->isFollowedByDeclaraction())
		{
			return false;
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
			elseif ($token === '}')
			{
				--$braces;
				if (!$braces)
				{
					break;
				}
			}
			elseif ($this->isCurlyOpen())
			{
				++$braces;
			}
			elseif ($this->isControlStructure())
			{
				if (!$this->stream->isAny([T_ELSE, T_ELSEIF]))
				{
					++$structure['statements'];
				}

				$innerStructure = $this->parseControlStructure();
				if ($innerStructure)
				{
					$structure['structures'][] = $innerStructure;
				}
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
		$anchor = "\n";
		while ($this->stream->key() <= $end)
		{
			$token = $this->stream->current();
			if ($this->stream->isNoise())
			{
				$token[1] = preg_replace('((' . $anchor . ")(?:    |\t))", '$1', $token[1]);
				$this->stream->replace($token);
			}
			$anchor = ($token[0] === T_COMMENT) ? "^|\n" : "\n";
			$this->stream->next();
		}
	}
}