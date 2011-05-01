
var container = document.getElementById('Imagemain');
var notes = new PhotoNoteContainer(container);

function change_note(id, text, x, y, w, h)
{
	ajaxRequest("?q=/note_change/" + encodeURI(id + "/" + x + "/" + y + "/" + w + "/" + h + "/" + text), null);
}

// from: http://stackoverflow.com/questions/442404/dynamically-retrieve-html-element-x-y-position-with-javascript
function getOffset(el)
{
    var _x = 0;
    var _y = 0;
    while(el && !isNaN(el.offsetLeft) && !isNaN(el.offsetTop))
	{
        _x += el.offsetLeft - el.scrollLeft;
        _y += el.offsetTop - el.scrollTop;
        el = el.offsetParent;
    }
    return { top: _y, left: _x };
}

function note_save_callback(note)
{
	change_note(note.id, note.gui.TextBox.value, note.rect.left, note.rect.top, note.rect.width, note.rect.height);
	return(1);
};

function note_delete_callback(note)
{
	ajaxRequest("?q=/note_remove/" + note.id);
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
	var x = 30;
	var y = 30;
	if(document && document.documentElement)
	{
		var img = document.getElementById("main_image");
		y = document.documentElement.scrollTop - getOffset(img).top + 10;
		if(y <= 0 || y >= img.offsetHeight)
			y = 0;
		y = (y + img.offsetHeight) / 2;
		x = img.offsetWidth / 2;
	}
	
	var size = new PhotoNoteRect(x, y, 30, 30);
	var note = new PhotoNote("new note", image_id, size, permission);
	note.onsave = function(note)
	{
		callback = function(response)
		{
			note.id = response;
		};
		ajaxRequest("?q=/note_add/" + encodeURI(note.id + "/" + note.rect.left + "/" + note.rect.top + "/" + note.rect.width + "/" + note.rect.height + "/" + note.gui.TextBox.value), callback);
		note.id = -1;
		note.onsave = note_save_callback;
		note.ondelete = note_delete_callback;
		return(1);
	}
	note.ondelete = function(note)
	{
		notes.DeleteNote(note);
		return(true);
	}
	note.onhistory = note_history_callback;
	notes.AddNote(note);
	
	/*
	callback = function(response)
	{
		add_note(response, "new note", 30, y, 30, 30, permission);
	};
	
	ajaxRequest("?q=/note_add/" + image_id, callback);
	// */
}
