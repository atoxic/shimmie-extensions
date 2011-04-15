<?php
class NotesTest extends ShimmieWebTestCase
{
	/*
		What to test:
		-basic functions:
		--adding notes
		--changing notes
		--remove notes
		(maybe test through JS as well?)
		
		-display notes:
		--photo notes
		---sanitization
		--advanced mode
		---for normal users
		---for admin
	 */
	function testNotes()
	{
		$this->log_in_as_user();
		$image_id = $this->post_image("ext/simpletest/data/pbx_screenshot.jpg", "pbx computer screenshot");
		
		// Adding through page request/event
		$note_id = $this->get_page("note_add/$image_id");
		$this->get_page("post/view/$image_id");
		$this->assert_text("new note");
		
		// Changing through page request/event
		$new_note_id = $this->get_page("note_change/$note_id/30/30/30/30/testing///");
		$this->get_page("post/view/$image_id");
		$this->assert_text("testing///");
		
		// Removal
		$this->get_page("note_remove/$note_id/");
		$this->get_page("note_remove/$new_note_id/");
		
		$this->log_out();
		$this->log_in_as_admin();
		$this->delete_image($image_id);
		$this->log_out();
	}
}
?>
