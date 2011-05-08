
var container = document.getElementById('Imagemain');
var notes = new PhotoNoteContainer(container);

function ajaxPost(url, callback, params)
{
	var http = getHTTPObject();
	http.open("POST", url, true);
	http.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	http.setRequestHeader("Content-length", params.length);
	http.setRequestHeader("Connection", "close");
	http.onreadystatechange = function()
	{
		if(http.readyState == 4) callback(http.responseText);
	}
	http.send(params);
}

function change_note(id, text, x, y, w, h)
{
	ajaxPost("?q=/note_change/", null, "note_id=" + id + "&x=" + x + "&y=" + y + "&w=" + w + "&h=" + h + "&text=" + encodeURIComponent(text));
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
	var x = 25;
	var y = 25;
	if(document && document.documentElement)
	{
		var img = document.getElementById("main_image");
		var offset = getOffset(img);
		var top = document.documentElement.scrollTop - offset.top;						// top of browser relative to top of image
		var bot = document.documentElement.scrollTop + window.innerHeight - offset.top;	// bottom of browser relative to top of image
		
		y = (Math.max(0, top) + Math.min(img.offsetHeight, bot)) / 2;
		if(y <= 0 || y >= img.offsetHeight)
			y = img.offsetHeight / 2;
			
		var left = document.documentElement.scrollLeft - offset.left;						// left of browser relative to left of image
		var right = document.documentElement.scrollLeft + window.innerWidth - offset.left;	// right of browser relative to top of image
		
		x = (Math.max(0, left) + Math.min(img.offsetWidth, right)) / 2;
		if(x <= 0 || x >= img.offsetWidth)
			x = img.offsetWidth / 2;
	}
	
	var size = new PhotoNoteRect(x, y, 50, 50);
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