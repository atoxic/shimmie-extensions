
var container = document.getElementById('Imagemain');
var notes = new PhotoNoteContainer(container);

function change_note(id, text, x, y, w, h)
{
	ajaxRequest("?q=/note_change/" + encodeURI(id + "/" + x + "/" + y + "/" + w + "/" + h + "/" + text), null);
}

// note that is already initialized
function add_note(id, text, x, y, w, h, permission)
{
	var size = new PhotoNoteRect(x, y, w, h);
	var note = new PhotoNote(text, 1, size, permission);
	
	note.onsave = function(note)
	{
		change_note(id, note.gui.TextBox.value, size.left, size.top, size.width, size.height);
		/*
		ajaxRequest("?q=/note_change/" + encodeURI(id + "/" + size.left + "/" + size.top + "/" + size.width + "/" 
													+ size.height + "/" + note.gui.TextBox.value), null);
		// */
		return(1);
	};
	note.ondelete = function(note)
	{
		ajaxRequest("?q=/note_remove/" + id);
		return(true);
	};
	note.onhistory = function(note)
	{
		window.open("?q=/note_history/" + id);
	};
	
	notes.AddNote(note);
}
