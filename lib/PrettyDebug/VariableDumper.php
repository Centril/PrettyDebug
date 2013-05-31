<?php
/**
 * PrettyDebug\VariableDumper is used to dump data in a pretty and useful
 * manner. It is the central piece of the package and will tell you about
 * the type of your data, if it has constants (if it's an object), and so on.
 *
 * @package PrettyDebug
 * @category Debug
 * @version 1.2
 * @since 1.2
 * @author Mazdak Farrokhzad <twingoow@gmail.com>
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GNU Public License 3.0
 * @copyright Copyright (C) 2012, Mazdak Farrokhzad
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

namespace PrettyDebug {

/**
 * Translate local path to "http" path.
 *
 * @param string $_path The path to translate.
 * @return string
 */
function urlpath($_path = __DIR__)
{
	$dir	=	str_replace("\\", "/", $_path);
	$root	=	str_replace("\\", "/", $_SERVER["DOCUMENT_ROOT"]);

	return stripos($dir, $root) === false ? $_path : 'http://' . $_SERVER["HTTP_HOST"] . substr($dir, strlen($root));
}

/**
 * Define if not already define.
 *
 * @param string $_name Constant name.
 * @param string $_value Constant value to use if not defined.
 * @return void
 */
function define_ifnot($_name, $_value)
{
	if(!defined($_name))
	{
		define($_name, $_value);
	}
}

/*
 * Should PrettyDebug use JavaScript altogether?
 * =>	DISABLED = false,
 * =>	ENABLED = true.
 *
 * If you disable it, then make sure that you either supply your own javascript for toggling, etc.
 * Or that you remove the display:none; style from .prettydebug .hide_initially in your CSS.
 */
define_ifnot(__NAMESPACE__ . '\USE_JAVASCRIPT', true); 

/*
 * PrettyDebug depends on jQuery for some features, and loads it (by default) from the Google CDN.
 * If you wish to use a different location for jQuery - then set PrettyDebug\STATIC_DEPENDENCY_URL to
 * a different url. If you set it to FALSE then jQuery is not supplied.
 */
define_ifnot(__NAMESPACE__ . '\STATIC_DEPENDENCY_JQUERY', "https://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js");

/*
 * PrettyDebug depends on CSS and JavaScript for toggling, and loads it (by default)
 * from the directory wherein you have placed PrettyDebug. If you wish to use a different location
 * for these files - then set PrettyDebug\STATIC_DEPENDENCY_URL to a different url.
 * If you set it to FALSE then you have to supply the JavaScript & CSS yourself.
 */
define_ifnot(__NAMESPACE__ . '\STATIC_DEPENDENCY_URL', urlpath() . '/static');

/*
 * Maximum amount of ancestors to show (parent, parent of parent, ...)? By default -1 is used.
 * You can change this by setting PrettyDebug\OBJECT_ANCESTOR_LIMIT to any number.
 * A negative number will make it show all ancestors. 0 (Zero) won't let it show any.
 */
define_ifnot(__NAMESPACE__ . '\OBJECT_ANCESTOR_LIMIT', -1);

/*
 * What character(s) should VariableDumper use for whitespace when indenting? By default \t [tab] is used.
 * You can change this by setting PrettyDebug\INDENT_WHITESPACE_CHARACTER to 4 or 8 " " [space(s)] or something else.
 */
define_ifnot(__NAMESPACE__ . '\INDENT_WHITESPACE_CHARACTER', "\t");

/*
 * What base-url for php documentation should be used for links? By default php.net is used.
 * You can change this by setting PrettyDebug\PHP_DOCUMENTATION to any base-url.
 */
define_ifnot(__NAMESPACE__ . '\PHP_DOCUMENTATION', "http://www.php.net/");

/**
 * Has the sequence been run before?
 * The function acts to help as an inclusion guard in functions.
 *
 * @param string $_name A for the sequence unique in program name.
 * @return bool true if sequence has been run before.
 * @example
 *
 *		if(is_init(__FUNCTION__))
 *		{
 *			return;
 *		}
 */
function is_init($_name)
{
	static $is_init	=	array();

	if(empty($is_init[$_name]))
	{
		$is_init[$_name]	=	true;
		return false;
	}

	return true;
}

/**
 * Return a link to php documentation.
 *
 * @param string $_link What part of php documentation?
 * @param string $_text Link text.
 * @return string HTML.
 */
function internal_action($_link, $_text)
{
	return '<a target="blank" class="nodec" href="' . namespace\PHP_DOCUMENTATION . $_link . '">' . $_text . '</a>';
}

/**
 * Return HREF-attribute with a call to javascript object prettydebug.
 *
 * @param string $_method Method in prettydebug to call.
 * @return string Attribute.
 */
function javascript_action($_method)
{
	if(namespace\USE_JAVASCRIPT !== false)
	{
		return 'href="javascript:prettydebug.' . $_method . ';"';
	}
}

/**
 * Output Module Dependencies
 * (Javascript & CSS)
 *
 * @return void
 */
function head_dependencies()
{
	if(is_init(__FUNCTION__))
	{
		return;
	}

	if(namespace\STATIC_DEPENDENCY_JQUERY !== false)
	{
?>
	<script type="text/javascript" src="<?php echo namespace\STATIC_DEPENDENCY_JQUERY ?>"></script>
<?php
	}

	if(namespace\STATIC_DEPENDENCY_URL !== false)
	{
?>
	<script type="text/javascript" src="<?php echo namespace\STATIC_DEPENDENCY_URL ?>/prettydebug.js"></script>
	<link href="<?php echo namespace\STATIC_DEPENDENCY_URL ?>/prettydebug.css" rel="stylesheet" type="text/css" />
<?php
	}
}

/**
 * Output Module Dependencies
 * Controler.
 *
 * @return void
 */
function body_dependencies()
{
	if(is_init(__FUNCTION__))
	{
		return;
	}
?>
	<pre id="prettydebug-toggle-control" class="prettydebug">
level: <input class="prettydebug-toggle-level" type="text" value="all" /> <a <?php echo javascript_action("level_clear('')") ?> class="nodec">clear</a>
in:    <input class="prettydebug-toggle-in" type="text" /> <a <?php echo javascript_action("copy('')") ?> class="nodec">clear</a>
	    <a <?php echo javascript_action("control(1)") ?> class="nodec">show</a> / <a <?php echo javascript_action("control(0)") ?> class="nodec">hide</a></pre>
<?php
}

/**
 * Output Module Dependencies
 *
 * @return void
 */
function dependencies()
{
	head_dependencies();
	body_dependencies();
}

/**
 * Toggler - Link + Collapser/Expander
 *
 * @package PrettyDebug
 * @subpackage Toggler
 */
class Toggler
{
	/**
	* Use HTML name attribute for anchor
	*
	* @var int
	*/
	const EXTRAS_NAME	=	1;

	/**
	 * Holds UNIQUE toggle ID
	 *
	 * @var string
	 */
	protected $id;

	/**
	 * Show as Block or Inline?
	 *
	 * @var bool
	 */
	public $is_block	=	false;

	/**
	 * Constructor - Generates a UNIQUE toggle ID
	 *
	 *
	 * @return void
	 */
	public function __construct($_is_block = null)
	{
		$this -> id	=	uniqid(mt_rand());

		if(isset($_is_block))
		{
			$this -> is_block	=	$_is_block;
		}
	}

	/**
	 * Returns: HTML Anchor
	 *
	 * HREF = javascript:toggle({id})
	 *
	 * @param string $_text Text of anchor
	 * @param string $_classes CSS classes, separated by space
	 * @param int $_extras See EXTRAS_ constants
	 * @return string The HTML link
	 */
	public function anchor($_text, $_classes = null, $_extras = null)
	{
		$name	=	$_extras === self::EXTRAS_NAME ? ' name="' . $this -> id() . '"' : null;

		return	'<span class="anchor">'.
					'<a' . $name . ' class="' . $_classes . ' nodec" title="Show / Hide" ' . javascript_action("toggle('{$this -> id()}')") . ">{$_text}</a> ".
					'<a ' . javascript_action("copy('{$this -> id()}')") . ' class="nodec">copy</a>'.
				'</span>';
	}

	/**
	 * The content to expand/collapse goes AFTER this
	 *
	 * @param bool $_hide_initially Are we hiding content initially?
	 * @param string $_classes CSS classes, separated by space
	 * @return string The "HTML opener"
	 */
	public function open($_hide_initially = true, $_classes = null)
	{
		$_hide_initially	=	$_hide_initially ? 'hide_initially ' : null;

		$_classes	=	empty($_classes) && empty($_hide_initially) ? null : " class=\"{$_hide_initially}{$_classes}\"";

		return "<{$this -> render_tag()} id=\"prettydebug-toggle-{$this -> id()}\"{$_classes}>";
	}

	/**
	 * No content after this
	 *
	 * @return string
	 */
	public function close()
	{
		return "</{$this -> render_tag()}>";
	}

	/**
	 * Returns the UNIQUE toggle ID
	 *
	 * @return int
	 */
	public function id()
	{
		return $this -> id;
	}

	/**
	 * Renders the tag for the content that can be toggled.
	 *
	 * @return string The tag.
	 */
	protected function render_tag()
	{
		return $this -> is_block ? 'div' : 'span';
	}
}

/**
 * VariableDumper - Dumps a variable, elegantly
 *
 * @package PrettyDebug
 * @subpackage VariableDumper
 */
class VariableDumper
{
	/**
	 * Are we showing initially?
	 *
	 * @var bool
	 */
	public $show_initially;

	/**
	 * Should type and such info be displayed?
	 *
	 * @var bool
	 */
	public $type_info;

	/**
	 * References of objects/arrays.
	 * Used for avoiding infinite loop & tracking recursion.
	 *
	 * @var array[string] => object/array
	 */
	protected $references	=	array();

	/**
	 * Entry point, use this to dump an array
	 *
	 * @param mixed $_var [in] The variable/data
	 * @param string|array|null $_label [optional] Label for this dump.
	 * If an array is passed, the first index will be used for text, and the index named "class" will be used for css-class.
	 * @param bool $_show_initially [optional] Show the first? (Only applies to arrays and objects)
	 * @param bool $_type_info [optional] Include info about types?
	 * @return string The dump.
	 */
	public function run(&$_var, $_label = null, $_show_initially = true, $_type_info = true)
	{
		$this -> show_initially	=	$_show_initially;
		$this -> type_info		=	$_type_info;

		// Output dependencies.
		namespace\dependencies();

		// Dump variable/data.
		ob_start();
		$this -> route($_var);
		$dump	=	ob_get_clean();

		// Merge it together & return.
		$out	=	'<pre class="prettydebug">' . $this -> label($_label, $_var) . $dump . '</pre>';
		return $out;
	}

	/**
	 * Outputs contents of run()
	 *
	 * @see VariableDumper::run()
	 *
	 * @param mixed $_var [in] The variable/data
	 * @param string|array|null $_label [optional] Label for this dump.
	 * If an array is passed, the first index will be used for text, and the index named "class" will be used for css-class.
	 * @param bool $_show_initially [optional] Show the first? (Only applies to arrays and objects)
	 * @param bool $_type_info [optional] Include info about types?
	 * @return void
	 */
	public function output(&$_var, $_label = null, $_show_initially = true, $_type_info = true)
	{
		echo $this -> run($_var, $_label, $_show_initially);
	}

	/**
	 * Alias of output()
	 *
	 * @see VariableDumper::output()
	 *
	 * @param mixed $_var [in] The variable/data
	 * @param string|array|null $_label [optional] Label for this dump.
	 * If an array is passed, the first index will be used for text, and the index named "class" will be used for css-class.
	 * @param bool $_show_initially [optional] Show the first? (Only applies to arrays and objects)
	 * @param bool $_type_info [optional] Include info about types?
	 * @return void
	 */
	public function __invoke(&$_var, $_label = null, $_show_initially = true, $_type_info = true)
	{
		$this -> output($_var, $_label, $_show_initially);
	}

	/**
	 * Reset "reference counter" of dumper.
	 *
	 * @return void
	 */
	public function clear()
	{
		$this -> references	=	array();
	}

	/**
	 * Return a label if there is one.
	 *
	 * @param mixed $_var The variable/data.
	 * @param string| $_label Label if there is one.
	 * @return string|null Label-HTML.
	 */
	protected function label($_label, &$_var = null)
	{
		if(is_array($_label) && !empty($_label))
		{
			// If array is passed, then shift first element of stack & use the rest as extra info.
			$temp	=	array_shift($_label);
			$extras	=	$_label;
			$_label	=	$temp;
		}

		if(is_string($_label) && !empty($_label))
		{
			return	'<span class="label'.
						(isset($extras['class']) ? ' ' . $extras['class'] : '').
						(is_string($_var) && strpos($_var, "\n") !== false ? ' multiline' : null).
						(is_array($_var) || is_object($_var) ? ' block' : null).
					'"><span class="label-content">' . $_label . '</span></span>';
		}
	}

	/**
	 * Routes a child to correct type-handler
	 *
	 * @param mixed $_var The child.
	 * @param int $_level Indent level.
	 * @return void
	 */
	protected function route(&$_var, $_level = 0)
	{
		if(is_null($_var))
		{
			echo $this -> _scalar('null');
		}
		else if(is_object($_var))
		{
			$this -> is_recursive($_var, $key) ? $this -> _recursion($key) : $this -> _object($_var, $_level);
		}
		elseif(is_array($_var))
		{
			$this -> is_recursive($_var, $key) ? $this -> _recursion($key) : $this -> _array($_var, $_level);
		}
		elseif(is_string($_var))
		{
			echo $this -> _scalar
			(
				'string',
				(strpos($_var, "\n") === false ? null : "\n" /* Begin with newline if string is multiline */).
				($this -> type_info ? '"' . htmlspecialchars($_var) . '"' : $_var),
				$this -> attr('size', mb_strlen($_var))
			);
		}
		elseif(is_bool($_var))
		{
			echo $this -> _scalar('bool', $state = $_var ? 'true' : 'false', null, $state);
		}
		elseif(is_int($_var))
		{
			echo $this -> _scalar('int', $_var);
		}
		elseif(is_double($_var))
		{
			$this -> _double($_var);
		}
	}

	/**
	 * Outputs info about a recursive-reference-variable
	 *
	 * @param string $_key toggle_id
	 * @return void
	 */
	protected function _recursion(&$_key)
	{
		echo '<a href="#' . $_key . '" class="recursion">*RECURSION*</a>';
	}

	/**
	 * Is object/array a recursive-reference?
	 *
	 * @param array|object $_var The variable
	 * @param string $_key Out: toggle_id
	 * @return bool
	 */
	protected function is_recursive(&$_var, &$_key)
	{
		return ($_key = array_search($_var, $this -> references, true)) === false ? false : true;
	}

	/**
	 * Outputs info about an object
	 *
	 * @param object $_var The object.
	 * @param int $_level Indent level.
	 * @return void
	 */
	protected function _object(&$_var, $_level = 0)
	{
		$toggler	=	new Toggler;
		$this -> references[$toggler -> id()]	=	$_var;

		// Create reflection.
		$reflection	=	new \ReflectionObject($_var);

		// Get basic class info: parents, if final/abstract, if internal.
		$class_info	=	$this -> class_info($reflection) . $this -> iterate_parents($reflection -> getParentClass());

		// Output toggle link & info about the class of object.
		echo
			$toggler -> anchor
			(
				"[object: <span class=\"value\">{$reflection -> getName()}</span>]",
				'object', Toggler::EXTRAS_NAME
			).
			$class_info . "\n" . $toggler -> open($this -> show_or_hide()).
			'<span class="object paranthesis">' . ($whitespace = $this -> whitespace($_level)) . '(</span>';

		// Display all properties.
		$properties	=&	$reflection -> getProperties();
		if(count($properties))
		{
			foreach($properties as $index => $child)
			{
				$child -> setAccessible(true);

				$label	=	'';

				// What is the access? (Public / Protected / Private)
				if($child -> isPublic())
				{
					$label	.=	$this -> _scalar('public') . '   ';
				}
				elseif($child -> isProtected())
				{
					$label	.=	$this -> _scalar('protected');
				}
				else
				{
					$label	.=	$this -> _scalar('private') . '  ';
				}

				// Is the property static?
				$label	.=	$child -> isStatic() ? ' ' . $this -> _scalar('static') : null;

				// Output property.
				$this -> _child($child -> getName(), $child -> getValue($_var), $_level, $label . ' ');
			}
		}

		// Display all constants.
		$constants	=&	$reflection -> getConstants();
		if(count($constants))
		{
			foreach($constants as $index => $child)
			{
				$this -> _child($index, $child, $_level, $this -> _scalar('constant') . '  ');
			}
		}

		// "Close" this object.
		echo '<span class="object paranthesis">' . $whitespace . ')</span>' . $toggler -> close();
	}

	/**
	 * Outputs info about an array
	 *
	 * @param object $_var The array.
	 * @param int $_level Indent level.
	 * @return void
	 */
	protected function _array(&$_var, $_level = 0)
	{
		$toggler	=	new Toggler;

		// Add it to reference list.
		$this -> references[$toggler -> id()]	=	$_var;

		// Attributes: Size.
		$size	=	count($_var);
		$size_full	=	@count($_var, COUNT_RECURSIVE); // @ for making it shut up about "cyclic reference"
		$size_full	=	$size_full === $size ? null : $size_full;

		// Output toggle link & info about the array itself.
		echo
			$toggler -> anchor('[array' . $this -> attr('size', $size) . $this -> attr('full size', $size_full) . ']', 'array', Toggler::EXTRAS_NAME). "\n" .
			$toggler -> open($this -> show_or_hide())  . '<span class="array paranthesis">' . ($whitespace = $this -> whitespace($_level)) . '(</span>';

		// Output each element.
		if(count($_var))
		{
			foreach($_var as $index => $child)
			{
				$this -> _child($index, $child, $_level);
			}
		}

		// "Close" this array.
		echo '<span class="array paranthesis">' . $whitespace . ')</span>' . $toggler -> close();
	}

	/**
	 * Outputs a child (helper for _array() & _object())
	 *
	 * @param int|string $_index The index of the child.
	 * @param mixed $_var The child-data.
	 * @param string $_label Info about the child in the parent.
	 * @param int $_level Indent level of parent.
	 */
	protected function _child(&$_index, &$_var, $_level, $_label = null)
	{
		echo '<span class="child">' . $this -> whitespace(++$_level) . '<span class="child-info">' . $_label . $this -> print_index($_index) . '</span>';
		$this -> route($_var, $_level);
		echo '</span>';
	}

	/**
	 * Outputs info about a double
	 *
	 * @param double $_var The double
	 * @return void
	 */
	protected function _double($_var)
	{
		// Split double into 2 integers (one with the numbers before and one with the numbers after the decimal).
		$parts	=	explode('.', $_var);
		$int	=&	$parts[0];
		$float	=	empty($parts[1]) ? 0 : $parts[1];

		// Output double with info about amount of digits in total, int and float part.
		echo $this -> _scalar
		(
			'double',
			$int . '<span class="decimal">.</span>' . $float, // Make "." (decimal) more stylish.
			$this -> attr('total', ($size_i = strlen($int)) + ($size_f = strlen($float))).
			$this -> attr('int', $size_i).
			$this -> attr('float', $size_f)
		);
	}

	/**
	 * _scalar : Do-It-All
	 *
	 * @param string $_type [optional] Type of variable or CSS selector.
	 * @param mixed $_var [optional] The variable.
	 * @param string $_attributes [optional] The attributes.
	 * @param string $_vclass [optional] CSS class for value.
	 * @return string
	 */
	protected function _scalar($_type = null, $_var = null, $_attributes = null, $_vclass = 'value')
	{
		$value	=	is_null($_var) ? null : ' <span class="' . $_vclass . '">' . $_var . '</span>';
		return is_null($_type) ? null : '<span class="' . $_type . '">' . ($this -> type_info ? "[{$_type}{$_attributes}]" : '') . $value . '</span>';	
	}

	/**
	 * Returns adequate amount of "whitespace" corresponding to indent level.
	 *
	 * @param int $_level Indent level.
	 * @return string
	 */
	protected function whitespace($_level)
	{
		return str_repeat(namespace\INDENT_WHITESPACE_CHARACTER, $_level);
	}

	/**
	 * Returns an attribute
	 *
	 * @param string $_key
	 * @param mixed $_value
	 * @return string
	 */
	protected function attr($_key, $_value)
	{
		return is_null($_value) ? null : " <span class=\"attr\">{$_key}:<span class=\"value\">({$_value})</span></span>";
	}

	/**
	 * Returns the Index/Name
	 *
	 * For Example: '[0] => ' if vector
	 *
	 * @param string|int $_index
	 * @return string
	 */
	protected function print_index(&$_index)
	{
		return '[ <span class="' . (is_string($_index) ? 'index_string' : 'index_int') . '">' . $_index . '</span> ] <span class="pointer">=></span> ';
	}

	/**
	 * Are we showing or hiding, initially?
	 *
	 * @return bool true: hide | false: show
	 */
	protected function show_or_hide()
	{
		if($this -> show_initially)
		{
			$this -> show_initially	=	false;
			return false;
		}
		else
		{
			return true;
		}
	}

	/**
	 * Returns info about a class
	 *
	 * @param object $_class instanceof ReflectorObject
	 * @return string
	 */
	protected function class_info(&$_class)
	{
		// Is final?
		$final	=	$_class -> isFinal() ? ' &lt;' . $this -> _scalar('final') . '&gt;' : null;

		// Is abstract?
		$abstract	=	$_class -> isAbstract() ? ' &lt;' . $this -> _scalar('abstract') . '&gt; ' : null;

		// Is internal?
		$php	=	$_class -> isInternal() ? ' ' . internal_action($_class -> getName(), $this -> _scalar('PHP')) : null;

		// Glue result & return
		return $final . $abstract . $php;
	}

	/**
	 * Recursively finds info about parents of class
	 *
	 * @param object $_parent instanceof ReflectorClass
	 * @param null|int $_limt
	 * @return string
	 */
	protected function iterate_parents(&$_parent, $_limit = namespace\OBJECT_ANCESTOR_LIMIT) /* @todo $limit... */
	{
		// If there ain't any parent or if acenstor limt as been reached, we're done.
		if(!$_parent || $_limit === 0)
		{
			return;
		}

		$toggler	=	new Toggler;

		return
			$toggler -> anchor(' ' . $this -> _scalar('extends'), 'nodec').
			$toggler -> open() . ' '.
				$_parent -> getName().
				$this -> class_info($_parent).
				$this -> iterate_parents($_parent -> getParentClass(), $_limit - 1).
			$toggler -> close();
	}
}

} namespace {

/**
 * Dump one variable with label.
 * Shorthand for: PrettyDebug\VariableDumper -> run($_var, $_label).
 * If null is supplied, then $_label is swapped with $_var.
 *
 * @param string $_label Label to use.
 * @return void
 */
function dump_label($_label /* , ... */) 
{
	$dump	=	new PrettyDebug\VariableDumper;

	$args	=	func_get_args();

	if(($count = func_num_args()) < 3)
	{
		$dump -> output(array_pop($args), $count === 1 ? null : $_label);
	}
	else
	{
		unset($args[0]);
		$i	=	1;
		foreach($args as $arg)
		{
			$dump	->	output($arg, $_label . ' {' . $i++ . '}');
		}
	}
}

/**
 * Dump one or more variables without a label.
 * Shorthand for: PrettyDebug\VariableDumper -> run([...]).
 *
 * @param mixed $_var Variable to dump.
 * @return void
 */
function dump(/* ... */)
{
	$dump	=	new PrettyDebug\VariableDumper;

	foreach(func_get_args() as $arg)
	{
		$dump	->	output($arg);
	}
}

/**
 * Dump one or more variables without a label & terminate program.
 * Shorthand for: PrettyDebug\VariableDumper -> run([...]) & die;
 *
 * @param mixed $_var Variable to dump.
 * @return void
 */
function dump_exit(/* ... */)
{
	call_user_func_array('dump', func_get_args());
	die;
}

}