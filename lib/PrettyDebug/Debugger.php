<?php
/**
 * PrettyDebug\Debugger is used to output debug messages
 * with output of the calls your PHP program has made.
 *
 * @package PrettyDebug
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

namespace PrettyDebug {

require_once 'VariableDumper.php';

use PrettyDebug\DocComment\Block as DocCommentParser;

/*
 * The maximum strlen when "previewing" a string given as parameter to a call. By default 15 is used.
 * You can change this by setting PrettyDebug\DEBUGGER_PARAM_PREVIEW_STRLEN_MAX to any number.
 */
define_ifnot(__NAMESPACE__ . '\DEBUGGER_PARAM_PREVIEW_STRLEN_MAX', 15);

/*
 * The order in which to show the call stack when displaying a debug trace. By default the last function called.
 * will be displayed first. True means that the last function called will be displayed first - false = reverse order.
 */
define_ifnot(__NAMESPACE__ . '\DEBUGGER_CALL_ORDER', true);

/*
 * Should we use named call-parameters or should we use a numerical index? By default we use named parameters.
 * True means that we use named parameters.
 */
define_ifnot(__NAMESPACE__ . '\DEBUGGER_NAMED_PARAMETERS', true);

/*
 * Should we show where a call (function/method) was made from or should we omit that information? By default we show.
 * True means that the information is shown. (applies to all constants until and including PrettyDebug\DEBUGGER_CI_DOCCOMMENT)
 */
define_ifnot(__NAMESPACE__ . '\DEBUGGER_CI_CALLED_FROM', true);

/*
 * Should we show that a call IS internal (PHP-builtin) when it is or should we omit that information? By default we show.
 */
define_ifnot(__NAMESPACE__ . '\DEBUGGER_CI_INTERNAL_TRUE', true);

/*
 * Should we show that a call IS NOT internal (PHP-builtin) when it isn't or should we omit that information? By default we DON'T show.
 */
define_ifnot(__NAMESPACE__ . '\DEBUGGER_CI_INTERNAL_FALSE', false);

/*
 * Should WE show that a call IS deprecated when it is or if should we omit that information? By default we show.
 */
define_ifnot(__NAMESPACE__ . '\DEBUGGER_CI_DEPRECATED_TRUE', true);

/*
 * Should show that a call IS NOT deprecated when it isn't or should we omit that information? By default we DON'T show.
 */
define_ifnot(__NAMESPACE__ . '\DEBUGGER_CI_DEPRECATED_FALSE', false);

/*
 * Should we show where the call was defined (procedure body) or should we omit that information? By default we show.
 */
define_ifnot(__NAMESPACE__ . '\DEBUGGER_CI_DEFINEDIN', true);

/*
 * Should we show the doccomment (if available) of user-defined calls or should we omit that information? By default we show.
 */
define_ifnot(__NAMESPACE__ . '\DEBUGGER_CI_DOCCOMMENT', true);

/**
 * For each element in array, process $_wrap replacing all occurences of "$var" with element and "$key" with its key.
 * Join all different versions of $_wrap with $_glue string.
 *
 * @param string $_glue String to glue each processed item with.
 * @param array $_pieces List of elements to process
 * @param string $_wrap String to use to form each item with - "$var" is replaced with element and "$key" with its key.
 * @return string Returns a string containing a string representation of all processed items in the same order, with the glue string between each item. 
 */
function implode_replace($_glue, $_pieces, $_wrap)
{
	$sum	=	'';
	foreach($_pieces as $key => $piece)
	{
		$sum	.=	(empty($sum) ? '' : $_glue) . str_replace('$key', $key, str_replace('$var', $piece, $_wrap));
	}
	return $sum;
}

/**
 * Debugger - Outputs an error (exception)
 *
 * @package PrettyDebug
 * @subpackage Debugger
 */
class Debugger
{
	/**
	 * Holds #REFERENCE# to error object
	 *
	 * @var object Error object
	 */
	protected $error;

	/**
	 * Holds 'HTML Title'
	 *
	 * @var string
	 */
	protected $title;

	/**
	 * Holds special info - Subclasses can populate this
	 *
	 * @var string|null
	 */
	protected $extras	=	null;

	/**
	 * Does nothing (for autoloading.)
	 *
	 * @return void
	 */
	public static function get() {}

	/**
	 * Run statically.
	 *
	 * @param object $_error Error object.
	 * @param string $_title [optional]
	 * @return void
	 */
	public static function run(&$_error, $_title = null)
	{
		new self($_error, $_title);
	}

	/**
	 * Constructor - init the debugger
	 *
	 * Starts output control
	 *
	 * @param object $_error Error object.
	 * @param string $_title [optional]
	 * @return void
	 */
	public function __construct(&$_error, $_title = null)
	{
		if(isset($_title))
		{
			$this -> set_title($_title);
		}

		$this -> error	=&	$_error;
		ob_start();
	}

	/**
	 * Destruct & Output & Flush
	 *
	 * @return void
	 */
	public function __destruct()
	{
		$this -> render();
		ob_end_flush();
	}

	/**
	 * Set Title
	 *
	 * @param string $_title
	 * @return void
	 */
	public function set_title($_title)
	{
		$this -> title	=	$_title;
	}

	/**
	 * Returns title ready for rendering
	 *
	 * @return string
	 */
	protected function render_title()
	{
		return is_null($this -> title) ? get_class($this -> error) : $this -> title;
	}

	/**
	 * Renders the debugged error
	 *
	 * @return void
	 */
	public function render()
	{
		$title		=	$this -> render_title();
		$message	=	$this -> error -> getMessage();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title><?php echo $title . (empty($message) ? null : " :: {$message}") ?></title>
<?php namespace\head_dependencies() ?>
</head>
<body id="prettydebug-debugger-wrapper">
<?php namespace\body_dependencies() ?>
	<div class="prettydebug prettydebug-debugger">
		<div class="title"><?php echo $title ?></div>
<?php
		if(!empty($message))
		{
?>
		<div class="message"><?php echo $message ?></div>
<?php
		}
?>
<br/>
<?php
		if(!empty($this -> extras))
		{
			echo $this -> extras . "<br/>";
		}
?>
		<div class="trace">
			<div class="key">TRACE</div>
<?php $this -> render_trace() ?>
		</div>
	</div>
</body>
</html>
<?php
	}

	/**
	 * Renders the trace
	 *
	 * @return void
	 */
	protected function render_trace()
	{
		// Get trace.
		$trace	=	$this -> error -> getTrace();

		// Reverse it if wanted.
		if(!namespace\DEBUGGER_CALL_ORDER)
		{
			$trace	=	array_reverse($trace);
		}

		// Display all items in trace.
		foreach($trace as $index => $cmd)
		{
			$is_closure	=	$cmd['function'] === '{closure}';
			$have_args	=	!empty($cmd['args']);

			// String representation of called procedure.
			$func	=	$is_closure
					?	'<span class="closure">' . $cmd['function'] . '</span>'
					:	(($is_method = isset($cmd['class'])) ? '<span class="class">' . $cmd['class'] . '</span><span class="type"> ' . $cmd['type'] . ' </span>' : '').
						'<span class="function">' . $cmd['function'] . '</span>';

			// String representation of where the call was made from (file, start-end).
			$from	=	namespace\DEBUGGER_CI_CALLED_FROM ? " <span class=\"at\">@</span> <span class=\"file\">{$cmd['file']}</span>({$cmd['line']})" : '';

			/*
			 * Process Call Info.
			 */
			$call_info	=	null;
			if(!$is_closure)
			{
				if($is_method)
				{
					$call	=	new \ReflectionMethod($cmd['class'], $cmd['function']);

					// Method modifiers (static, public, protected, private, final...).
					$modifiers	=	implode_replace(' ', \Reflection::getModifierNames($call -> getModifiers()) , '<span class="$var">$var</span>');
					$call_info	.=	$this -> call_info('Modifiers', $modifiers);
				}
				else
				{
					$call	=	new \ReflectionFunction($cmd['function']);
				}

				// Was the call Internal (PHP-Builtin).
				if(($is_internal = $call -> isInternal()) && namespace\DEBUGGER_CI_INTERNAL_TRUE || namespace\DEBUGGER_CI_INTERNAL_FALSE)
				{
					$internal	=	$this -> call_info('Internal', $is_internal);
					if($is_internal)
					{
						$internal	=	internal_action(($is_method ? $cmd['class'] . '.' : '') . $cmd['function'], $internal);
					}
					$call_info	.=	$internal;
				}

				// Was the call Deprecated?
				if(($is_deprecated = $call -> isDeprecated()) && namespace\DEBUGGER_CI_DEPRECATED_TRUE || namespace\DEBUGGER_CI_DEPRECATED_FALSE)
				{
					$call_info	.=	$this -> call_info('Deprecated', $is_deprecated);
				}

				// Start-End + File.
				if(namespace\DEBUGGER_CI_DEFINEDIN && $call -> isUserDefined())
				{
					$call_info	.=	$this -> call_info
					(
						'Defined',
						"{$call -> getFileName()}<span class=\"lines\">({$call -> getStartLine()}-{$call -> getEndLine()})</span>"
					);
				}

				// DocComment.
				if(namespace\DEBUGGER_CI_DOCCOMMENT && ($comment = $call -> getDocComment()))
				{
					require_once 'DocCommentParser.php';
					$block	=	new DocCommentParser($comment);
					$block -> parse();

					$call_info	.=	$this -> call_info('DocComment', $block -> render());
				}

				// Name parameters if there are any.
				if(namespace\DEBUGGER_NAMED_PARAMETERS && $have_args)
				{
					$named_parameters	=	$call -> getParameters();

					if(!empty($named_parameters))
					{
						$named_args	=	array();
						foreach($cmd['args'] as $index_arg => $arg)
						{
							$named_args[isset($named_parameters[$index_arg]) ? $named_parameters[$index_arg] -> name : $index_arg]	=	$arg;
						}

						$cmd['args']	=	$named_args;
						unset($named_args);
					}
				}
			}

			/*
			 * Process Parameter Info.
			 */
			if($have_args)
			{
				// Short preview representation of parameters.
				$params_short	=	'';
				foreach($cmd['args'] as $arg)
				{
					// Add: ', ' for first
					if(!empty($params_short))
					{
						$params_short	.=	', ';
					}

					if(is_object($arg))
					{
						$params_short	.=	'object:' . get_class($arg);
					}
					elseif(is_string($arg))
					{
						// if string - only show a partion of it.
						$params_short	.=	'"' . (mb_strlen($arg) > namespace\DEBUGGER_PARAM_PREVIEW_STRLEN_MAX
										?	mb_substr($arg, 0, namespace\DEBUGGER_PARAM_PREVIEW_STRLEN_MAX) . '...'
										:	$arg) . '"';
					}
					elseif(is_bool($arg))
					{
						$params_short	.=	$arg ? 'true' : 'false';
					}
					else
					{
						$params_short	.=	$arg;
					}
				}

				// Dump the parameters.
				$dump		=	new VariableDumper;
				$dump		=	$dump -> run($cmd['args'], 'Parameters');
			}
			else
			{
				// If there's no param, don't render any.
				$dump	=	$params_short	=	null;
			}

			/*
			 * Merge and output.
			 */
			$info	=	$call_info . $dump;

			if(empty($info))
			{
				// No additional info.
				$func	.=	'()';
			}
			else
			{
				// There is additional info.
				$toggler	=	new Toggler(true);

				// Adapt call signature (wrap a link to params around it).
				$func		=	$toggler -> anchor($func . '(' . $params_short . ')');
				$info		=	$toggler -> open(true, 'trace-call-info') . $info .  $toggler -> close();
			}

			// Output trace item.
			echo '<div class="trace-row"><div class="trace-index">#' . $index . '</div><div class="trace-info">' . $func . $from . $info . '</div></div>' . "\n";
		}
	}

	/**
	 * Return call info.
	 *
	 * @param string $_label What's the call-info about?
	 * @param mixed $_info Info about the call.
	 * @return string HTML.
	 */
	protected function call_info($_label, $_info)
	{
		$dump	=	new VariableDumper;
		return $dump -> run($_info, array($_label, 'class' => 'call-info'), true, false);
	}
}

/*
 * By default, and when included, PrettyDebug\Debugger will set itself as the default exception handler for unhandled exceptions.
 * Set PrettyDebug\NO_DEFAULT_HANDLER to TRUE if you don't want to use Debugger as your default exception handler.
 */
if(!defined(__NAMESPACE__ . '\NO_DEFAULT_HANDLER') || namespace\NO_DEFAULT_HANDLER != true)
{
	set_exception_handler(array(__NAMESPACE__ . '\Debugger', 'run'));
}

} namespace {

/**
 * Invoke Debugger (useful for trace routing).
 * Outputs trace, Terminates program.
 *
 * @param null|string|object $_error
 * 		One of:
 * 		1) null
 * 		2) message (string)
 * 		3) instance of Exception
 */
function debug($_error = null)
{
	if(!($_error instanceof Exception))
	{
		try
		{
			throw new Exception(is_string($_error) ? $_error : 'DEBUG - TRACE ROUTING');
		}
		catch(Exception $_error)
		{
			PrettyDebug\Debugger::run($_error);
		}
	}
	else
	{
		PrettyDebug\Debugger::run($_error);
	}
	die;
}

}