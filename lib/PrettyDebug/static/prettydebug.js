var prettydebug	=	new function()
{
	/**
	 * Toggles a debug segment
	 *
	 * @param int index index of segment
	 * @return void
	 */
	this.toggle	=	function(index) { $('#prettydebug-toggle-' + index).toggle() }

	/**
	 * Run a toggle-control command
	 *
	 * @param bool show show or hide?
	 * @return void
	 */
	this.control	=	function(show)
	{
		/*
		 * Init Variables
		 */
		var control			=	$('#prettydebug-toggle-control'),	// control
			all				=	[],									// storage: segments to toggle
			level			=	parseInt($.trim(control.children('input.prettydebug-toggle-level').val())),
			seek_in,												// storage: what to seek in
			seek_in_text	=	$.trim(control.children('input.prettydebug-toggle-in').val()); // filter: which?

		/*
		 * Seek in ALL or Special Segment?
		 */
		if(seek_in_text == '')	// ALL
			seek_in	=	$('pre.prettydebug:not(#prettydebug-toggle-control), .prettydebug-debugger > .trace');
		else					// Special Segment
		{
			all[0]	=	seek_in	= $('#prettydebug-toggle-' + seek_in_text);

			--level;
		}

		// If Special Segment: does it exist? not: ERROR!
		if(this.error(!seek_in.length, 'in', 'Could not find <em>{#prettydebug-toggle-' + seek_in_text + '}</em>'))
			return;

		/*
		 * Seek - Deep
		 */
		if(isNaN(level))	// No level supplied -> All below
			all		=	$.merge(all, seek_in.find('span[id^="prettydebug-toggle-"]'));
		else
		{
			// Make sure level is high enough
			var lowest	=	seek_in_text == '' ? 1 : 0;
			if(this.error(level < lowest, 'level', 'Need more than <em>' + lowest + '</em> for <em>${level}</em></span>'))
				return;

			// Seek
			this.seek(seek_in, all, 0, level);
		}

		/*
		 * Apply
		 */
		$.each(all, function()
		{
			show ? $(this).show() : $(this).hide();
		});
	}

	/**
	 * Try for error, display message on error
	 *
	 * @param bool predicate error-test to try
	 * @param string id "error-id"
	 * @param string message message to display
	 * @return bool true: terminate command, false: continue
	 */
	this.error	=	function(predicate, id, message)
	{
		var message	=	$('#prettydebug-toggle-error-' + id);

		// did it result in an error : predicate -> true?
		if(predicate)
		{
			// if error message already exists, quit...
			if(message.length)
				return true;

			// append error
			$('#prettydebug-toggle-control')
				.append('<span id="prettydebug-toggle-error-' + id + '" class="error">' + "\n" + message + '</span>')
				.children('.error').fadeIn(500);

			return true;
		}
		else
		{
			// nope, clean up error message if exists, quit...
			message.remove();
			return false;
		}
	}

	/**
	 * Seek for more debug segments, add if found
	 *
	 * @param jQuery seek_in Debug segment to seek deeper in
	 * @param jQuery all Collection to add to
	 * @param int level current level
	 * @param int stop level to stop at
	 * @return void
	 */
	this.seek	=	function(seek_in, all, level, stop)
	{
		// reached level? -> quit
		if(level == stop)
			return;

		// seek in debug segment provided for more
		var found	=	seek_in.children('span[id^="prettydebug-toggle-"]');

		// did we find any?
		if(found.length)
		{
			// add to collection
			all		=	$.merge(all, found);

			// recursion: redo this function on newly found segments
			found.each(function()
			{
				// add one level
				this.seek($(this), all, level + 1, stop);
			});
		}
	}

	/**
	 * Modify value of a toggle-control input and focus it
	 *
	 * @param string what name of input
	 * @param string val value to replace with
	 * @return void
	 */
	this.input	=	function(what, val) { $('#prettydebug-toggle-control > input.prettydebug-toggle-' + what).val(val).focus() }

	/**
	 * Clear value of toggle-control-level
	 *
	 * @return void
	 */
	this.level_clear	=	function() { this.input('level', 'All') }

	/**
	 * Copy an id to toggle-control-in
	 *
	 * @param string id
	 * @return void
	 */
	this.copy	=	function(id) { this.input('in', id) }
}