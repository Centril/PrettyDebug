<?php
/**
 * PrettyDebug\DocComment is used for parsing & formating DocComments (eg. PHPDoc).
 *
 * @package PrettyDebug
 * @subpackage DocComment
 * @category Debug
 * @version 1.2
 * @since 1.1
 * @author Mazdak Farrokhzad <twingoow@gmail.com>
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GNU Public License 3.0
 * @copyright Copyright (C) 2011, Mazdak Farrokhzad
 *
 * This file is part of PrettyDebug.
 * PrettyDebug is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * PrettyDebug is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with PrettyDebug. If not, see <http://www.gnu.org/licenses/>.
 */

namespace PrettyDebug\DocComment {

/**
 * Exception thrown by DocComment-API.
 *
 * @since 1.1
 * @package PrettyDebug
 * @subpackage DocComment
 * @category Debug
 */
class Exception extends \Exception
{
	/**
	 * Thrown when the string passed was not a valid directive.
	 *
	 * @var int
	 */
	const	NOT_DIRECTIVE	=	100;
}

/**
 * Abstract class for all nodes in a DocComment.
 *
 * @since 1.1
 * @package PrettyDebug
 * @subpackage DocComment
 * @category Debug
 */
abstract class Node
{
	/**
	 * Stores raw data for parsing.
	 *
	 * @var string
	 */
	protected $raw;

	/**
	 * Constructor optionally takes a raw string and stores it.
	 *
	 * @param string|null $_raw [optional]
	 * @return void
	 */
	public function __construct($_raw = null)
	{
		if(!empty($_raw))
		{
			$this -> raw	=	$_raw;
		}
	}

	/**
	 * Optionally takes a raw string and stores it.
	 * Parses data using $this -> raw. Parsing is implemented by subclasses.
	 *
	 * @param string|null $_raw [optional]
	 * @return void
	 */
	public function parse($_raw = null)
	{
		if(!empty($_raw))
		{
			$this -> raw	=	$_raw;
		}
	}

	/**
	 * (Magic) Shortcut to render()
	 *
	 * @see render()
	 * @return string
	 */
	public function __toString()
	{
		return $this -> render();
	}

	abstract public function render();

	/**
	 * Clears stored raw data.
	 *
	 * @return void
	 */
	protected function clear_raw()
	{
		$this -> raw	=	null;
	}
}

/**
 * Parses & Renders a DocComment block.
 *
 * @since 1.1
 * @package PrettyDebug
 * @subpackage DocComment
 * @category Debug
 */
class Block extends Node
{
	/**
	 * Stores block nodes.
	 *
	 * @var array
	 */
	protected $nodes	=	array();

	/**
	 * Parses a DocComment block.
	 *
	 * @param string|null $_raw [optional]
	 * @return void
	 */
	public function parse($_raw = null)
	{
		parent::parse($_raw);

		// Remove comment-decoration.
		$this -> raw	=	trim(preg_replace('#\s*\*\s?#', "\n", substr(trim($this -> raw), 1, -1)));

		// Separate into sections (directives & paragraphs).
		while(preg_match("#(?:(@[a-z][\w\-]+[^\n]+)|[^\n]+)(?:\n(?!\n|@[a-z][\w\-]+)[^\n]+)*#i", $this -> raw, $match, PREG_OFFSET_CAPTURE))
		{
			// Remove the match from string.
			$this -> raw	=	substr($this -> raw, array_pop($match[0]) + strlen($text = array_pop($match[0])));

			// Make into directive / paragraph & parse it.
			$match	=	empty($match[1]) ? new Paragraph($text) : new Directive($text);
			$match -> parse();

			// Add to node list.
			$this -> nodes[]	=	$match;
		}

		$this -> clear_raw();
	}

	/**
	 * Render the DocComment Block to HTML.
	 *
	 * @return string
	 */
	public function render()
	{
		$nodes	=	'';
		foreach($this -> nodes as $i => $node)
		{
			$nodes	.=	($i !== 0 ? "\n" . ($this -> nodes[$i - 1] instanceof namespace\Paragraph ? "\n" : '') : ''). // Add the correct amount of newlines.
						(string) $node;
		}

		return "<span class=\"doc-comment-block\">/**\n * " . str_replace("\n", "\n * ", $nodes) . "\n */</span>";
	}
}

/**
 * Parses & Renders a piece of DocComment text.
 *
 * @since 1.1
 * @package PrettyDebug
 * @subpackage DocComment
 * @category Debug
 */
class Text extends Node
{
	/**
	 * Render the DocComment text to HTML.
	 *
	 * @return string
	 */
	public function render()
	{
		return \PrettyDebug\implode_replace("\n", explode("\n", $this -> raw), '<span class="text-line">$var</span>');
	}
}

/**
 * Parses & Renders a DocComment paragraph.
 *
 * @since 1.1
 * @package PrettyDebug
 * @subpackage DocComment
 * @category Debug
 */
class Paragraph extends Node
{
	public $nodes	=	array();

	/**
	 * Parse the Paragraph.
	 *
	 * @param string $_raw Raw string to parse.
	 * @return void
	 */
	public function parse($_raw = null)
	{
		parent::parse($_raw);

		while(true)
		{
			if(preg_match('/\{(@[a-z][\w\-]+[^\}]*)\}/', $this -> raw, $m, PREG_OFFSET_CAPTURE))
			{
				// Extract text before directive & add it to node-list.
				$this -> nodes[]	=	new Text(substr($this -> raw, 0, $before_length = array_pop(array_shift($m))));

				// Extract directive and remove the directive + the text before it from raw string.
				$this -> raw	=	substr($this -> raw, $before_length + strlen($directive = array_shift(array_pop($m))) + 2);

				// Add directive to node-list.
				$directive	=	new Directive($directive);
				$directive -> parse();
				$this -> nodes[]	=	$directive;
			}
			else
			{
				// If there's any remaining text, make it a node & get us out of loop.
				if(!empty($this -> raw))
				{
					$this -> nodes[]	=	new Text($this -> raw);
				}

				break;
			}
		}

		$this -> clear_raw();
	}

	/**
	 * Render the DocComment Paragraph to HTML.
	 *
	 * @return string
	 */
	public function render()
	{
		$out	=	'';
		foreach($this -> nodes as $node)
		{
			$out	.=	$node instanceof namespace\Directive ? $node -> render('inline') : $node;
		}

		return '<span class="doc-comment-paragraph">' . $out . '</span>';
	}
}

/**
 * Parses & Renders a DocComment directive.
 *
 * @since 1.1
 * @package PrettyDebug
 * @subpackage DocComment
 * @category Debug
 */
class Directive extends Node
{
	/**
	 * Whats the directive/tag?
	 *
	 * @var string
	 */
	protected $directive;

	/**
	 * Stores directive info.
	 *
	 * @var array
	 */
	protected $info	=	array();

	/* ================ *|
	|* 		PARSING		*|
	|* ================ */

	/**
	 * Parse the directive.
	 *
	 * @param string $_raw Raw string to parse.
	 * @return void
	 */
	public function parse($_raw = null)
	{
		parent::parse($_raw);

		$this -> raw	=	trim($this -> raw);

		/*
		 * Figure out directive.
		 */
		$this -> split_ws($this -> raw);
		if(empty($this -> raw[0]))
		{
			// Couldn't find a directive, something is fucked up...
			throw new Exception('The string you passed is not a DocComment directive.', Exception::NOT_DIRECTIVE);
		}

		$this -> directive	=	strtolower(substr(ltrim(array_shift($this -> raw)), 1));
		$this -> raw		=	array_pop($this -> raw);

		/*
		 * Do things depending on directive.
		 */
		switch($this -> directive)
		{
			case 'abstract':
			case 'filesource':
			case 'final':
			case 'ignore':
			case 'static':
				break;

			case 'access':
				$this -> split_ws($this -> raw);
				$this -> info['access']	=	empty($this -> raw[0]) ? null : $this -> raw[0];

			case 'author':
				$this -> parse_author();
				break;

			case 'global':
				$this -> parse_param(true, true);
				break;

			case 'license':
				$this -> parse_link('license', false);
				break;

			case 'link':
				$this -> parse_link();
				break;

			case 'method':
			case 'return':
			case 'staticvar':
			case 'var':
				$this -> parse_param();
				break;

			case 'name':
				$this -> parse_param(true, false, true);
				break;

			case 'param':
			case 'property':
			case 'property-read':
			case 'property-write':
				$this -> parse_param(true);
				break;

			default:
				if(!empty($this -> raw))
				{
					$this -> info['text']	=	new Text(ltrim($this -> raw));
				}
				break;
		}

		$this -> clear_raw();
	}

	/**
	 * Split a string by whitespace.
	 *
	 * @param string $_raw [in, out] String to split into array.
	 * @param int $_pieces Max number of splits.
	 * @return void
	 */
	protected function split_ws(&$_raw, $_pieces = 2)
	{
		$_raw	=	preg_split('#\s#', $_raw, $_pieces);
	}

	/**
	 * Parse an author directive.
	 *
	 * @return void
	 */
	protected function parse_author()
	{
		if(preg_match('/((?: ?[\w\.]+)+)?\s?(?:<(.*)>)?/', trim($this -> raw), $m) && !(empty($m[1]) && empty($m[2])))
		{
			if(!empty($m[1]))
			{
				$this -> info['name']	=	$m[1];
			}

			if($this -> parse_uri($m[2]))
			{
				$this -> info['email']	=	$m[2];
			}
		}
	}

	/**
	 * Parse a structure that looks like (? = optional): data_type? $var[dim][dim][...]? extra_text?
	 *
	 * @param bool $_check_var Check for variable?
	 * @param bool $_check_dims When checking for variable, should we check for array-dimensions?
	 * @param bool $_check_type Check for data_type?
	 * @return void
	 */
	protected function parse_param($_check_var = false, $_check_dims = false, $_check_type = true)
	{
		$pattern	=	'#^'.
							($_check_type ? '(?:\s*((?:\w+\|?)+))?' : ''). // Check for type?
							($_check_var ? '(?:\s*\$(\w+' . ($_check_dims ? '(?:\[[\'"][^\'"]+[\'"]\])*' : '') . '))?' : ''). // Check for variable (and dimensions?)?
						'(.*)#is';

		if(preg_match($pattern, $this -> raw, $m))
		{
			$i	=	0;
			if($_check_type && isset($m[$i + 1]))
			{
				// Parse & save found type.
				$this -> parse_types($m[++$i]);
			}

			if($_check_var && isset($m[$i + 1]))
			{
				// Save found variable.
				$this -> info['var']	=	$m[++$i];
			}

			$this -> raw	=&	$m[++$i];
		}

		// Save leftover text if any.
		if(!empty($this -> raw))
		{
			$this -> info['text']	=	new Text($this -> raw);
		}
	}

	/**
	 * Parse a directive containing one or many URIs.
	 *
	 * @param string $_text_to [optional] Where should the leftover text be saved?
	 * @param bool $_multiple Check for multiple or just one URIs?
	 * @return void
	 */
	protected function parse_link($_text_to = 'text', $_multiple = true)
	{
		/*
		 * Separate URI & text.
		 */
		if($_multiple)
		{
			// Split commas so as to find multiple URIs.
			$this -> raw	=	preg_split('/,\s?/', $this -> raw);
			$last			=&	$this -> raw[count($this -> raw) - 1];
		}
		else
		{
			// Don't split (only 1 URI is accepted).
			$this -> raw	=	array($this -> raw);
			$last	=&	$this -> raw[0];
		}

		// Possibly separate URI & text of the last element.
		$text	=	'';
		$this -> split_ws($last);
		if(isset($last[1]))
		{
			list($last, $text)	=	$last;
		}

		/*
		 * Try each suspected URI and add to list if convicted.
		 */
		$rest	=	'';
		foreach($this -> raw as $i => $raw_link)
		{
			if($this -> parse_uri($raw_link))
			{
				// Convicted!
				$this -> info['uri'][]	=	$raw_link;
				unset($this -> raw[$i]);
			}
			else
			{
				// It wasn't an URI, stop loop & join the rest.
				$rest	=	implode(', ', $this -> raw);
				break;
			}
		}

		/*
		 * If there's any non-URI text left, join & save it!
		 */
		$text	=	array_filter(array($rest, $text));
		if(!empty($text))
		{
			$this -> info[$_text_to]	=	new Text(implode($text));
		}
	}

	/**
	 * Validate URI & correct it if neccessary.
	 *
	 * @param string $_raw [out, in] URI to validate.
	 * @return bool Is the passed URI valid?
	 */
	protected function parse_uri(&$_raw)
	{
		if(empty($_raw) || ($_raw = trim($_raw)) == '')
		{
			return false;
		}

		$p_ui	=	"(?:[\w\.\-\+!$&'\(\)*,;=%]|%[0-9a-f]{2})+";
		$p		=
"~^
(?:([a-z][a-z0-9\-\.\*]*)://)?								# The scheme
(?:(?:{$p_ui}:)*{$p_ui}@)?								# Userinfo (optional)
(?:														# The domain
	(?:[a-z0-9\-\.]|%[0-9a-f]{2})+	 					# Domain name or IPv4
	|(?:\[(?:[0-9a-f]{0,4}:)*(?:[0-9a-f]{0,4})\])		# or IPv6
)
(?::[0-9]+)?											# Server port number (optional)
(?:[/|\?]												# The path (optional)
  (?:[\w#!:\.\?\+=&@!$'\~*,;/\(\)\[\]\-]|%[0-9a-f]{2})
*)?
$~xi";

		if(preg_match($p, $_raw, $m, PREG_OFFSET_CAPTURE))
		{
			if(empty($m[1][0]) && strpos($_raw, '@') === false)
			{
				// No URI-Scheme specified & not an e-mail, assume HTTP.
				$_raw	=	substr($_raw, 0, $m[0][1]) . 'http://' . substr($_raw, $m[0][1]);
			}

			return true;
		}

		return false;
	}

	/**
	 * Parse types from raw string.
	 *
	 * @param string $_raw Raw string to parse types from.
	 * @return void
	 */
	protected function parse_types($_raw)
	{
		if(empty($_raw))
		{
			return;
		}

		$this -> info['types']	=	array();

		$types	=	explode('|', trim($_raw));
		foreach($types as &$type)
		{
			// If the type is a PHP-documentation-pseudo-type or is PHP-builtin, then lowercase it.
			$lowered	=	strtolower($type);
			if(in_array($lowered, array(
				'void', 'null', 'bool', 'boolean', 'string', 'array',
				'int', 'integer', 'float', 'double', 'number',
				'callable', 'callback', 'resource', 'mixed'
			)))
			{
				$type	=	strtolower($type);
			}

			$this -> info['types'][]	=	$type;
		}
	}

	/* =================== *|
	|* 		RENDERING	   *|
	|* =================== */

	/**
	 * Render the directive/tag & return it.
	 *
	 * @return string
	 */
	public function render($_inline = false)
	{
		$_inline	=	$_inline === 'inline';

		$out	=	'<span class="doc-comment-directive doc-comment-' . $this -> directive . ($_inline ? ' inline' : '') . '">'.
						($_inline ? '{' : '').
						'<span class="directive-name">@' . $this -> directive . '</span>';

		switch($this -> directive)
		{
			case 'abstract':
			case 'filesource':
			case 'final':
			case 'ignore':
			case 'static':
				break;

			case 'access':
				if(isset($this -> info['access']))
				{
					$out	.=	' <span class="' . $this -> info['access'] . '">' . $this -> info['access'] . '</span>';
				}
				break;

			case 'author':
				$out	.=	$this -> render_author();
				break;

			case 'license':
				$out	.=	$this -> render_link('license');
				break;

			case 'link':
				$out	.=	$this -> render_link();
				break;

			case 'global':
			case 'method':
			case 'return':
			case 'staticvar':
			case 'var':
			case 'name':
			case 'param':
			case 'property':
			case 'property-read':
			case 'property-write':
				$out	.=	$this -> render_param();
				break;

			default:
				$out	.=	$this -> render_text();
				break;
		}

		return $out . ($_inline ? '}' : '') . '</span>';
	}

	/**
	 * Render a author directive.
	 *
	 * @return string HTML.
	 */
	protected function render_author()
	{
		return ' ' . implode(' ', array_filter(array
		(
			empty($this -> info['name'])	? '' : '<span class="name">' . $this -> info['name'] . '</span>',
			empty($this -> info['email'])	? '' : '<a class="link email nodec" href="mailto:' . $this -> info['email'] . '">&lt;' . $this -> info['email'] .'&gt;</a>'
		)));
	}

	/**
	 * Render a param-like directive.
	 *
	 * @return string HTML.
	 */
	protected function render_param()
	{
		$out	=	'';

		if(isset($this -> info['types']))
		{
			$out	.=	' <span class="types">' . \PrettyDebug\implode_replace('|', $this -> info['types'], '<span class="type">$var</span>') . '</span>';
		}

		if(isset($this -> info['var']))
		{
			$out	.=	' <span class="variable">$' . $this -> info['var'] . '</span>';
		}

		$out	.=	$this -> render_text();

		return $out;
	}

	/**
	 * Render a directive containing an URI.
	 *
	 * @param string $_text_in [optional] Index in $this -> info use for left-over text.
	 * @see render_text()
	 * @return string HTML.
	 */
	protected function render_link($_text_in = null)
	{
		return	' ' . (empty($this -> info['uri']) ? '' : \PrettyDebug\implode_replace(', ', $this -> info['uri'], '<a class="link nodec" href="$var">$var</a>')).
				$this -> render_text($_text_in);
	}

	/**
	 * Render a piece of text.
	 *
	 * @param null|string $_text_in Index in $this -> info to use for text.
	 * @return string HTML.
	 */
	protected function render_text($_text_in = null)
	{
		$_text_in	=	is_null($_text_in) ? 'text' : $_text_in;

		if(isset($this -> info[$_text_in]))
		{
			return ' ' . $this -> info[$_text_in] -> render();
		}
	}
}

}