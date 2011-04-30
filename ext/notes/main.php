<?php
/**
 * Name: Notes
 * Author: /a/non <anonymousscanlations@gmail.com>
 * Link: https://github.com/atoxic/shimmie-extensions
 * License: BSD
 * Description: Basic photo notes
 * Documentation:
 *    Basic photo notes like in Flickr and Gel/Danbooru.
 *    Make sure to include the 5 or so JS and CSS files in the lib folder.
 */

class Notes extends SimpleExtension
{
	/*
		$name is the name of the main DB
		$link_name is the name of the DB associating images and notes
	 */
	var $name = "notes";
	var $link_name = "image_notes";
	
	/* ======================================================
		DATABASE FUNCTIONS
	   ====================================================== */
	
	/*
		Adds a new note
		
		1) adds note
		2) gets id of note added
		3) set the group id to the note id, which should be unused
		4) link the group id to the image
		
		usage: $this->addNote("testing", 1, 10, 10, 10, 10, $event->get_arg(1));
		
		Returns: Id of new note
	 */
	public function addNote($text, $user, $x, $y, $w, $h,
							$image_id)
	{
		global $database;
		$database->Execute("INSERT INTO " . $this->name .
							" 		(text,	user,	date,	note_group,					x,	y,	w,	h) " .
							"VALUES	(?, 	?, 		now(),	0, 							?,	?,	?,	?)",
							array	($text,	$user, 										$x, $y, $w,	$h));
		$result = $database->get_row("SELECT LAST_INSERT_ID() AS noteID");
		$note_id = $result["noteID"];
		$database->Execute("UPDATE " . $this->name . " SET note_group=? WHERE id=?", array($note_id, $note_id));
		$database->Execute("INSERT INTO " . $this->link_name . " (image_id, note_id) " .
							"VALUES (?, 		?)",
							array	($image_id,	$note_id));
		
		return($note_id);
	}
	/*
		Adds a new version of a note into the note group
		
		1) gets the old note with the given id
		2) inserts a new note with the same note group as the old note
			
		usage: $this->changeNote("testing 2", 1, 10, 10, 10, 10, 1);
		
		Returns: Id of new note
	 */
	public function	changeNote($text, $user, $x, $y, $w, $h,
							$old_note_id)
	{
		global $database;
		$old_note = $database->get_row("SELECT * FROM " . $this->name . " WHERE id=?", array($old_note_id));
		$database->Execute("INSERT INTO " . $this->name .
							" 		(text, 	user, 	date, 	note_group, 				x,	y,	w,	h) " .
							"VALUES	(?, 	?, 		now(), 	?, 							?,	?,	?,	?)",
							array	($text,	$user, 			$old_note["note_group"],	$x,	$y,	$w,	$h));
		$result = $database->get_row("SELECT LAST_INSERT_ID() AS noteID");
		$new_note_id = $result["noteID"];
		return($new_note_id);
	}
	
	/*
		Get all notes associated with the image id
		
		1) get group ids associated with image id
		2) get the latest notes (highest id) associated with the group ids
		3) return them
		
		Returns: Array of associative arrays representing the notes
	 */
	public function getNotes($image_id)
	{
		global $database;
		// get all of notes associated with the image
		$result = $database->get_all("SELECT * FROM " . $this->name . " WHERE note_group IN (SELECT note_id FROM " . $this->link_name . " WHERE image_id=?)", array($image_id));
		$notes = array();
		foreach($result as $note)
		{
			if(!array_key_exists($note["note_group"], $notes) || $notes[$note["note_group"]]["date"] < $note["date"])
			{
				$notes[$note["note_group"]] = $note;
			}
		}
		return($notes);
	}
	
	/*
		Gets all versions of the note
		
		Return: Array of associative arrays representing all of the notes in the history group
	 */
	public function getNoteHistory($note_id)
	{
		global $database;
		// get all of the notes
		$notes = $database->get_all("SELECT * FROM " . $this->name . " WHERE note_group=(SELECT note_group FROM " . $this->name . " WHERE id=?)", array($note_id));
		return($notes);
	}
	
	/*
		It actually doesn't remove the note; it just removes the links
		
		Returns: Nothing
	 */
	public function removeNote($note_id)
	{
		global $database;
		$database->execute("DELETE FROM " . $this->link_name . " WHERE note_id=?", array($note_id));
	}
	
	/* ======================================================
		EVENT FUNCTIONS
	   ====================================================== */
	
	/*
		Installer: initializes the database if it isn't initialized already
	 */
	public function onInitExt(Event $event)
	{	
		global $config;
		$version = $config->get_int($this->name . "_version", 0);
		if($version < 1)
		{
			global $database, $config;
			$database->create_table($this->name, "
					id SCORE_AIPK,
					text TEXT NOT NULL,
					user INTEGER NOT NULL,
					date SCORE_DATETIME NOT NULL DEFAULT SCORE_NOW,
					note_group INTEGER NOT NULL DEFAULT 1,
					x INTEGER NOT NULL DEFAULT 10,
					y INTEGER NOT NULL DEFAULT 10,
					w INTEGER NOT NULL DEFAULT 10,
					h INTEGER NOT NULL DEFAULT 10
					");
			$database->create_table($this->link_name,"
					image_id INTEGER NOT NULL,
					note_id INTEGER NOT NULL
					");
			log_info($this->name, "Installed tables for the Notes extension at " . $name . ".");
			$config->set_int($this->name. "_version", 1);
		}
	}
	/*
		Handles page gets
		
		Anonymous users cannot:
			-add new note
			-change note
			
		Only admins can:
			-remove note
		
		Anyone can:
			-look at note history
	 */
	public function onPageRequest(PageRequestEvent $event)
	{
		global $page, $user;
	
		if($event->page_matches("post"))
		{
			// viewing an image
			if($event->count_args() == 2 && $event->get_arg(0) == "view")
			{
				$notes = $this->getNotes($event->get_arg(1));
				$this->theme->displayNotes($page, $user, $notes, $event->get_arg(1));
			}
		}
		else if($event->page_matches("note_history"))
		{
			if($event->count_args() == 1)
			{
				$page->set_title("Note History");
				
				$notes = $this->getNoteHistory($event->get_arg(0));
				$this->theme->displayNoteHistory($page, $user, $notes, $event->get_arg(0));
			}
		}
		else if($event->page_matches("note_add"))
		{
			$page->set_mode("data");
			if($event->count_args() == 1 && !$user->is_anonymous())
			{
				$id = $this->addNote("new note", $user->id, 30, 30, 30, 30, $event->get_arg(0));
				$page->set_data($id);
			}
			else
				$page->set_data(-1);
		}
		else if($event->page_matches("note_change"))
		{
			$page->set_mode("data");
			if($event->count_args() >= 6 && !$user->is_anonymous())
			{
				$text = $event->get_arg(5);
				for($i = 6; $i < $event->count_args(); $i++)
					$text .= "/" . $event->get_arg($i);
				$id = $this->changeNote($text, $user->id, $event->get_arg(1), $event->get_arg(2), $event->get_arg(3), $event->get_arg(4), $event->get_arg(0));
				$page->set_data($id);
			}
			else
				$page->set_data(-1);
		}
		else if($event->page_matches("note_remove"))
		{
			$page->set_mode("data");
			if($event->count_args() == 1 && $user->is_admin())
			{
				$this->removeNote($event->get_arg(0));
				$page->set_data(1);
			}
			else
				$page->set_data(-1);
		}
	}
}
?>
