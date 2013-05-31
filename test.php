<?php
/**
 * Translate local path to "http" path.
 *
 * @param string $_path The path to translate.
 * @return string
 */
function test_urlpath($_path = __DIR__)
{
	$dir	=	str_replace("\\", "/", $_path);
	$root	=	str_replace("\\", "/", $_SERVER["DOCUMENT_ROOT"]);

	return stripos($dir, $root) === false ? $_path : 'http://' . $_SERVER["HTTP_HOST"] . substr($dir, strlen($root));
}

#define('PrettyDebug\STATIC_DEPENDENCY_JQUERY', test_urlpath() . '/jquery.js');

require 'lib/PrettyDebug/Debugger.php';

use PrettyDebug\Debugger;
use PrettyDebug\VariableDumper;

class Car
{
	const BLACK			=	1;

	public $title		=	"Volvo";
	protected $speed	=	39.19;
	private $has_stereo	=	true;

	public function start()
	{
		$this -> speed	=	50.10;
		static::set_type("abc", 1515);
	}

	public static function set_type($_dummy = array())
	{
		throw new Exception;
	}
}

class Volvo extends Car
{
	/**
	 * Do nothing interesting.
	 *
	 * @return void
	 */
	final public static function set_type($_dummy = array(), $_something = '')
	{
		$a	=	function()
		{
			$r	=	new ReflectionMethod('DateTime', 'add');
			$r -> getPrototype();
		};

		$a();
	}
}

dump_label("Testdata", array
(
	0		=>	new DateTime,
	1		=>	array(1, 2, 3),
	"volvo"	=>	new Volvo
));

dump_label("Sockerbagare", "En sockerbagare här bor i staden,
han bakar kakor mest hela dagen.
Han bakar stora, han bakar små,
han bakar några med socker på.

Och i hans fönster hänger julgranssaker
och hästar, grisar och pepparkakor.
Och är du snäller så kan du få,
men är du stygger så får du gå.", 12313);

dump(1337, 13.37 * pi());

dump(isset($meaning_of_life));
dump(isset($_SERVER));
dump_label(null);

function b() { abc(); }
function abc() { $volvo = new Volvo; $volvo -> start(); }
b();

?>