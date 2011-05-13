function ajaxPost(url, callback, params)
{
	$.post(url, params, callback);
}

function note_history_callback(note)
{
	window.open("?q=/note_history/" + note.id);
}

// note that is already initialized
function add_note(id, text, x, y, w, h)
{
}

// adding a new note to the coordinates specified
function add_note_init(image_id, permission, x, y)
{
	var size = new PhotoNoteRect(x - 25, y - 25, 50, 50);
	var note = new PhotoNote("new note", image_id, size, permission);
	note.onsave = function(note)
	{
		callback = function(response)
		{
			note.id = response;
		};
		ajaxPost("?q=/note_add/", callback, "image_id=" + note.id + "&x=" + note.rect.left + "&y=" + note.rect.top
									+ "&w=" + note.rect.width + "&h=" + note.rect.height + "&text=" + encodeURIComponent(note.gui.TextBox.value));
		note.id = -1;
		note.onsave = note_save_callback;
		note.ondelete = note_delete_callback;
		return(1);
	};
	note.ondelete = function(note)
	{
		return(true);
	};
	note.onhistory = note_history_callback;
	notes.AddNote(note);
	note.Select();
}

// adding a new note to the center of visible image
function add_note_init_center(image_id, permission)
{
	var x = 35;
	var y = 35;
	var img = $("#main_image");
	var width = img.outerWidth();
	var height = img.outerHeight();
	var offset = img.offset();
	
	var top = $(document).scrollTop() - offset.top;						// top of browser relative to top of image
	var bot = $(document).scrollTop() + window.innerHeight - offset.top;	// bottom of browser relative to top of image
	
	y = (Math.max(50, top) + Math.min(height - 50, bot)) / 2;
	if(y <= 0 || y >= height)
	{
		y = height / 2;
	}
		
	var left = $(document).scrollLeft() - offset.left;						// left of browser relative to left of image
	var right = $(document).scrollLeft() + window.innerWidth - offset.left;	// right of browser relative to top of image
	
	x = (Math.max(50, left) + Math.min(width - 50, right)) / 2;
	if(x <= 0 || x >= width)
	{
		x = width / 2;
	}
		
	add_note_init(image_id, permission, x, y);
}
