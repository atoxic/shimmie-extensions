/// <reference path="jquery-1.2.6-vsdoc.js" />
(function($) {

    $.fn.annotateImage = function(options) {
        ///	<summary>
        ///		Creates annotations on the given image.
        ///     Images are loaded from the "getUrl" propety passed into the options.
        ///	</summary>
        var opts = $.extend({}, $.fn.annotateImage.defaults, options);
		
		this.canvas = $(this);
		
		this.canvas.addClass("image-annotate-canvas");
        this.image = this.canvas.find("img");
		this.canvas.width(this.image.width());
		this.canvas.height(this.image.height());
        this.mode = 'view';

        // Assign defaults
        this.getUrl = opts.getUrl;
        this.addUrl = opts.addUrl;
        this.saveUrl = opts.saveUrl;
        this.deleteUrl = opts.deleteUrl;
        this.editable = opts.editable;
        this.useAjax = opts.useAjax;
        this.permission = opts.permission;
        this.notes = opts.notes;

        // Add the canvas
        this.canvas.append($('<div class="image-annotate-edit-area"></div>'));
        this.canvas.show();

        // Give the canvas and the container their size and background
		this.canvas.find('.image-annotate-edit-area').hide();
		
        // load the notes
		$.fn.annotateImage.load(this);

        return(this);
    };

    /**
    * Plugin Defaults
    **/
    $.fn.annotateImage.defaults = {
        getUrl: 'your-get.rails',
        saveUrl: 'your-save.rails',
        deleteUrl: 'your-delete.rails',
        editable: true,
        useAjax: true,
        notes: new Array()
    };

    $.fn.annotateImage.clear = function(image) {
        ///	<summary>
        ///		Clears all existing annotations from the image.
        ///	</summary>    
        for (var i = 0; i < image.notes.length; i++) {
            image.notes[image.notes[i]].destroy();
        }
        image.notes = new Array();
    };

    $.fn.annotateImage.ajaxLoad = function(image) {
        ///	<summary>
        ///		Loads the annotations from the "getUrl" property passed in on the
        ///     options object.
        ///	</summary>
        $.getJSON(image.getUrl + '?ticks=' + $.fn.annotateImage.getTicks(), function(data) {
            image.notes = data;
            $.fn.annotateImage.load(image);
        });
    };

    $.fn.annotateImage.load = function(image) {
        ///	<summary>
        ///		Loads the annotations from the notes property passed in on the
        ///     options object.
        ///	</summary>
        for (var i = 0; i < image.notes.length; i++) {
            image.notes[image.notes[i]] = new $.fn.annotateView(image, image.notes[i]);
        }
    };

    $.fn.annotateImage.getTicks = function() {
        ///	<summary>
        ///		Gets a count og the ticks for the current date.
        ///     This is used to ensure that URLs are always unique and not cached by the browser.
        ///	</summary>        
        var now = new Date();
        return now.getTime();
    };

    $.fn.annotateImage.add = function(image) {
        ///	<summary>
        ///		Adds a note to the image.
        ///	</summary>        
        if (image.mode == 'view') {
            image.mode = 'edit';

            // Create/prepare the editable note elements
            var editable = new $.fn.annotateEdit(image);

            $.fn.annotateImage.createSaveButton(editable, image);
            $.fn.annotateImage.createCancelButton(editable, image);
        }
    };

    $.fn.annotateImage.createSaveButton = function(editable, image, note) {
        ///	<summary>
        ///		Creates a Save button on the editable note.
        ///	</summary>
        var ok = $('<a class="image-annotate-edit-ok">Save</a>');
		
		if(image.permission >= 1)
		{
			ok.click(function() {
				var form = $('#image-annotate-edit-form form');
				var text = $('#image-annotate-text').val();
				$.fn.annotateImage.appendPosition(form, editable)
				image.mode = 'view';

				// Save via AJAX
				if(image.useAjax)
				{
					if(editable.note.new_note)
					{
						$.ajax({
							type: 'POST',
							url: image.addUrl, 
							data: form.serialize(),
							success: function(data, textStatus, jqXHR)
							{
								editable.note.id = data;
								editable.note.new_note = false;
							},
							dataType: "json"
						});
					}
					else
					{
						$.ajax({
							type: 'POST',
							url: image.saveUrl, 
							data: form.serialize(),
							success: function(data, textStatus, jqXHR)
							{
								editable.note.id = data;
							},
							dataType: "json"
						});
					}
				}

				// Add to canvas
				if (note) {
					note.resetPosition(editable, text);
				} else {
					editable.note.editable = true;
					note = new $.fn.annotateView(image, editable.note)
					note.resetPosition(editable, text);
					image.notes.push(editable.note);
				}
				
				var edited = image.data("edited");
				if(edited)
					edited.area.show();

				editable.destroy();
			});
		}
		else
		{
			ok.addClass("disabled");
		}
        editable.form.append(ok);
    };

    $.fn.annotateImage.createCancelButton = function(editable, image, annotation) {
        ///	<summary>
        ///		Creates a Cancel button on the editable note.
        ///	</summary>
        var cancel = $('<a class="image-annotate-edit-close">Cancel</a>');
        cancel.click(function() {
			if(editable.note.new_note)
			{
				annotation.destroy();
			}
			else
			{
				var edited = image.data("edited");
				if(edited)
					edited.area.show();
			}
			
            editable.destroy();
            image.mode = 'view';
        });
        editable.form.append(cancel);
    };

    $.fn.annotateImage.saveAsHtml = function(image, target) {
        var element = $(target);
        var html = "";
        for (var i = 0; i < image.notes.length; i++) {
            html += $.fn.annotateImage.createHiddenField("text_" + i, image.notes[i].text);
            html += $.fn.annotateImage.createHiddenField("top_" + i, image.notes[i].top);
            html += $.fn.annotateImage.createHiddenField("left_" + i, image.notes[i].left);
            html += $.fn.annotateImage.createHiddenField("height_" + i, image.notes[i].height);
            html += $.fn.annotateImage.createHiddenField("width_" + i, image.notes[i].width);
        }
        element.html(html);
    };

    $.fn.annotateImage.createHiddenField = function(name, value) {
        return '&lt;input type="hidden" name="' + name + '" value="' + value + '" /&gt;<br />';
    };

    $.fn.annotateEdit = function(image, note) {
        ///	<summary>
        ///		Defines an editable annotation area.
        ///	</summary>
        this.image = image;

        if (note) {
            this.note = note;
        } else {
            var newNote = new Object();
            newNote.id = "new";
            newNote.top = 30;
            newNote.left = 30;
            newNote.width = 30;
            newNote.height = 30;
            newNote.text = "";
            this.note = newNote;
        }

        // Set area
        var area = image.canvas.children('.image-annotate-edit-area');
        this.area = area;
        this.area.css('height', this.note.height + 'px');
        this.area.css('width', this.note.width + 'px');
        this.area.css('left', this.note.left + 'px');
        this.area.css('top', this.note.top + 'px');

        // Show the edition canvas and hide the view canvas
        image.canvas.find('.image-annotate-edit-area').show();

        // Add the note (which we'll load with the form afterwards)
        var form = $('<div id="image-annotate-edit-form"><form><textarea id="image-annotate-text" name="text" rows="3" cols="30"></textarea></form></div>');
		form.find('#image-annotate-text').val(this.note.text);
        this.form = form;

        $('body').append(this.form);
	
		var formPosition = function(e, ui)
		{
			var offset = area.offset();
			form.css('left', (parseInt(offset.left)) + 'px');
			form.css('top', (parseInt(offset.top) + parseInt(area.height()) + 7) + 'px');
		};
		
		formPosition();
		
        // Set the area as a draggable/resizable element contained in the image canvas.
        // Would be better to use the containment option for resizable but buggy
        area.resizable({
			containment: image.canvas,
            handles: 'ne, nw, se, sw',
			resize: formPosition,
			stop: formPosition
        })
        .draggable({
            containment: image.canvas,
            drag: formPosition,
            stop: formPosition
        });
        return this;
    };

    $.fn.annotateEdit.prototype.destroy = function() {
        ///	<summary>
        ///		Destroys an editable annotation area.
        ///	</summary>        
		this.image.canvas.find('.image-annotate-edit-area').hide();
        this.area.resizable('destroy');
        this.area.draggable('destroy');
        this.area.css('height', '');
        this.area.css('width', '');
        this.area.css('left', '');
        this.area.css('top', '');
        this.form.remove();
    }

    $.fn.annotateView = function(image, note) {
        ///	<summary>
        ///		Defines a annotation area.
        ///	</summary>
		
        this.image = image;
        this.note = note;
        this.editable = (note.editable && image.editable);

        // Add the area
        this.area = $('<div class="image-annotate-area' + (this.editable ? ' image-annotate-area-editable' : '') + '"><div></div></div>');
        image.canvas.prepend(this.area);

        // Add the note
        this.form = $('<div class="image-annotate-note"><textarea class="image-annotate-note-area"></textarea></div>');
		this.form.find(".image-annotate-note-area").val(note.text);
        this.form.hide();
        image.canvas.append(this.form);
        this.form.children('span.actions').hide();

        // Set the position and size of the note
        this.setPosition();

        // Add the behavior: hide/display the note when hovering the area
        var annotation = this;
        this.area.hover(function() {
            annotation.show();
        }, function() {
            annotation.hide();
        });

        // Edit a note feature
        if (this.editable) {
            var form = this;
            this.area.click(function() {
                form.edit();
            });
        }
    };

    $.fn.annotateView.prototype.setPosition = function() {
        ///	<summary>
        ///		Sets the position of an annotation.
        ///	</summary>
        this.area.children('div').height((parseInt(this.note.height) - 2) + 'px');
        this.area.children('div').width((parseInt(this.note.width) - 2) + 'px');
        this.area.css('left', (this.note.left) + 'px');
        this.area.css('top', (this.note.top) + 'px');
        this.form.css('left', (this.note.left) + 'px');
        this.form.css('top', (parseInt(this.note.top) + parseInt(this.note.height) + 7) + 'px');
    };

    $.fn.annotateView.prototype.show = function() {
        ///	<summary>
        ///		Highlights the annotation
        ///	</summary>
        this.form.show(0);
        if (!this.editable) {
            this.area.addClass('image-annotate-area-hover');
        } else {
            this.area.addClass('image-annotate-area-editable-hover');
        }
    };

    $.fn.annotateView.prototype.hide = function() {
        ///	<summary>
        ///		Removes the highlight from the annotation.
        ///	</summary>      
        this.form.hide(0);
        this.area.removeClass('image-annotate-area-hover');
        this.area.removeClass('image-annotate-area-editable-hover');
    };

    $.fn.annotateView.prototype.destroy = function() {
        ///	<summary>
        ///		Destroys the annotation.
        ///	</summary>      
        this.area.remove();
        this.form.remove();
    }

    $.fn.annotateView.prototype.edit = function() {
        ///	<summary>
        ///		Edits the annotation.
        ///	</summary>      
        if (this.image.mode == 'view') {
            this.image.mode = 'edit';
            var annotation = this;

            // Create/prepare the editable note elements
            var editable = new $.fn.annotateEdit(this.image, this.note);

            $.fn.annotateImage.createSaveButton(editable, this.image, annotation);
	
			$.fn.annotateImage.createCancelButton(editable, this.image, annotation);
			
			var history = $('<a class="image-annotate-edit-history">History</a>');
			if(!editable.note.new_note)
			{
				history.click(function()
				{
					window.open("?q=/note_history/" + editable.note.id);
				});
			}
			else
			{
				history.addClass("disabled");
			}
			editable.form.append(history);
			
            // Add the delete button
            var del = $('<a class="image-annotate-edit-delete">Delete</a>');
			if(this.image.permission >= 2)
			{
				del.click(function() {
					var form = $('#image-annotate-edit-form form');

					$.fn.annotateImage.appendPosition(form, editable)

					if (annotation.image.useAjax)
					{
						$.post(annotation.image.deleteUrl, form.serialize());
					}

					annotation.image.mode = 'view';
					editable.destroy();
					annotation.destroy();
				});
			}
			else
			{
				del.addClass("disabled");
			}
            editable.form.append(del);
			
			this.area.hide();
			this.image.data("edited", this);
        }
    };

    $.fn.annotateImage.appendPosition = function(form, editable) {
        ///	<summary>
        ///		Appends the annotations coordinates to the given form that is posted to the server.
        ///	</summary>
        var areaFields = $('<input type="hidden" value="' + editable.area.height() + '" name="height"/>' +
                           '<input type="hidden" value="' + editable.area.width() + '" name="width"/>' +
                           '<input type="hidden" value="' + editable.area.position().top + '" name="top"/>' +
                           '<input type="hidden" value="' + editable.area.position().left + '" name="left"/>' +
                           '<input type="hidden" value="' + editable.note.id + '" name="id"/>');
        form.append(areaFields);
    }

    $.fn.annotateView.prototype.resetPosition = function(editable, text) {
        ///	<summary>
        ///		Sets the position of an annotation.
        ///	</summary>
        this.form.find(".image-annotate-note-area").val(text);
		//this.form.text(text);
        this.form.hide();

        // Resize
        this.area.children('div').height(editable.area.height() + 'px');
        this.area.children('div').width((editable.area.width() - 2) + 'px');
        this.area.css('left', (editable.area.position().left) + 'px');
        this.area.css('top', (editable.area.position().top) + 'px');
        this.form.css('left', (editable.area.position().left) + 'px');
        this.form.css('top', (parseInt(editable.area.position().top) + parseInt(editable.area.height()) + 7) + 'px');

        // Save new position to note
        this.note.top = editable.area.position().top;
        this.note.left = editable.area.position().left;
        this.note.height = editable.area.height();
        this.note.width = editable.area.width();
        this.note.text = text;
        this.note.id = editable.note.id;
        this.editable = true;
    };

})(jQuery);