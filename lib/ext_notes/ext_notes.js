(function($) {
	var cache = [];
	// Arguments are image paths relative to the current page.
	$.preLoadImages = function()
	{
		var args_len = arguments.length;
		var i;
		for(i = args_len; i--;)
		{
			var cacheImage = document.createElement('img');
			cacheImage.src = arguments[i];
			cache.push(cacheImage);
		}
	};
	
	$.note_history_callback = function(note)
	{
		window.open("?q=/note_history/" + note.id);
	};

	// adding a new note to the coordinates specified
	$.add_note_init = function(image_id, x, y)
	{
		var image = $('#Imagemain');
		var annotations = image.data("annotations");
		if(annotations.mode == 'view')
		{
			var view = new $.fn.annotateView(annotations, {top: y - 25, left: x - 25, width: 50, height: 50, id: image_id, text: "", editable: true, new_note: true});
			view.edit();
		}
	};

	// adding a new note to the center of visible image
	$.add_note_init_center = function(image_id)
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
			
		$.add_note_init(image_id, x, y);
	};

})(jQuery);

