PrettyDebug
===========

PrettyDebug is a collection of classes used for a more pretty debugging than print_r using PHP >= 5.3
It has a variable dumper to use instead of print_r, and a debugger for stack tracing.

PrettyDebug\VariableDumper is used to dump data in a pretty and useful
manner. It is the central piece of the package and will tell you about
the type of your data, if it has constants (if it's an object), and so on.

PrettyDebug\Debugger is used to output debug messages
with output of the calls your PHP program has made.

PrettyDebug\DocComment is used for parsing & formating DocComments (eg. PHPDoc).

license: GPL 3.0
