var container = document.getElementById('Imagemain');
var notes = new PhotoNoteContainer(container);

function ajaxPost(url, callback, params)
{
	$.post(url, params, callback);
}

function change_note(id, text, x, y, w, h)
{
	ajaxPost("?q=/note_change/", null, "note_id=" + id + "&x=" + x + "&y=" + y + "&w=" + w + "&h=" + h + "&text=" + encodeURIComponent(text));
}

function note_save_callback(note)
{
	change_note(note.id, note.gui.TextBox.value, note.rect.left, note.rect.top, note.rect.width, note.rect.height);
	return(1);
};

function note_delete_callback(note)
{
	ajaxPost("?q=/note_remove/", null, "note_id=" + note.id);
	return(true);
};

function note_history_callback(note)
{
	window.open("?q=/note_history/" + note.id);
};

// note that is already initialized
function add_note(id, text, x, y, w, h, permission)
{
	var size = new PhotoNoteRect(x, y, w, h);
	var note = new PhotoNote(text, id, size, permission);
	
	note.onsave = note_save_callback;
	note.ondelete = note_delete_callback;
	note.onhistory = note_history_callback;
	
	notes.AddNote(note);
}

// adding a new note
function add_note_init(image_id, permission)
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
		y = height / 2;
		
	var left = $(document).scrollLeft() - offset.left;						// left of browser relative to left of image
	var right = $(document).scrollLeft() + window.innerWidth - offset.left;	// right of browser relative to top of image
	
	x = (Math.max(50, left) + Math.min(width - 50, right)) / 2;
	if(x <= 0 || x >= width)
		x = width / 2;
		
	add_note_init(image_id, permission, x, y);
}

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
	}
	note.ondelete = function(note)
	{
		return(true);
	}
	note.onhistory = note_history_callback;
	notes.AddNote(note);
	note.Select();
}
