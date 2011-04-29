<?php
/*
	Handles view and controller
 */
class NotesTheme extends Themelet
{
	public function displayNoteHistory(Page $page, User $user, $notes, $image_id)
	{
		$page->add_block(new Block("Note History", $this->generateCommon($page, $user, $notes, $image_id) . 
													$this->generateAdvanced($page, $user, $notes, $image_id)));
	}
	public function displayNotes(Page $page, User $user, $notes, $image_id)
	{
		$page->add_block(new Block("Notes (Javascript)", $this->generateCommon($page, $user, $notes, $image_id). 
										$this->generateNotes($page, $user, $notes, $image_id), "main"));
		if(!$user->is_anonymous())
			$page->add_block(new Block("Note Controls",
							$this->generateControls($page, $user, $notes, $image_id), "left"));
	}
	
	private function userPermission(User $user)
	{
		if($user->is_admin())
			return(2);
		if($user->is_anonymous())
			return(0);
		return(1);
	}
	
	/*
		Common JS code and CSS styles
	*/
	public function generateCommon(Page $page, User $user, $notes, $image_id)
	{
		$permission = $this->userPermission($user);
		$data_href = get_base_href();
		
		$string = <<<JS

<!-- For Photo Note GUI -->	
<link rel="stylesheet" type="text/css" href="$data_href/lib/ext_notes/PhotoNotes.v1.css" />
<script type="text/javascript" src="$data_href/lib/ext_notes/PhotoNotes.v1.js"> </script>

<!-- For AJAX functions -->
<script type="text/javascript" src="$data_href/lib/ext_notes/shimmie.js"> </script>

<!-- For common function and styles -->
<link rel="stylesheet" type="text/css" href="$data_href/lib/ext_notes/ext_notes.css" />
<script type="text/javascript" src="$data_href/lib/ext_notes/ext_notes.js"> </script>

<script type="text/javascript">
function add_note_init()
{
	callback = function(response)
	{
		add_note(response, "new note", 30, 30, 30, 30, $permission);
	};
	
	ajaxRequest("?q=/note_add/$image_id", callback);
}
</script>

JS;
		return($string);
	}
	/*
		Controls on the side
	 */
	public function generateControls(Page $page, User $user, $notes, $image_id)
	{
		$string = <<<JS
		
<form>
<input type="button" value="New Note" name="button1" onClick="javascript:add_note_init();">
</form> 

JS;
		return($string);
	}
	
	/*
		Normal view with photo notes
	 */
	public function generateNotes(Page $page, User $user, $notes, $image_id)
	{
		$permission = $this->userPermission($user);
	
		$string = <<<JS
<script type="text/javascript">

JS;
		
		foreach($notes as $note)
		{
			$text = json_encode($note["text"]);
			$string .= "add_note($note[id], $text, $note[x], $note[y], $note[w], $note[h], $permission);\n";
		}
		
		$string .= <<<JS

</script>

JS;
		
		return($string);
	}
	/*
		Advanced view
		
		Only admins get to see the ID and note group of a note,
		because it's presumably bad for users to see database stuff
	 */
	public function generateAdvanced(Page $page, User $user, $notes, $image_id)
	{
		$string = <<<HTML
		
<div class="note_div">
HTML;
		if($user->is_admin())
		{
			$string .= <<<HTML
<span name="id">ID</span>
HTML;
		}
		$string .= <<<HTML
<span name="text">Text</span>
<span name="user">User</span>
<span name="date">Date</span>
HTML;
		if($user->is_admin())
		{
			$string .= <<<HTML
<span name="group">Group</span>
HTML;
		}
		$string .= <<<HTML
<span name="pos">Position</span>
<span name="dim">Dimensions</span>

</div>
<br/>

HTML;

		foreach($notes as $note)
		{
			$string .= <<<HTML
<form><div class="note_div">
HTML;
			if($user->is_admin())
			{
				$string .= <<<HTML
			
<input type="text" name="id" value="$note[id]" />
HTML;
			}
			
			$text = htmlentities($note["text"]);
			
			$string .= <<<HTML
<textarea type="text" id="note$note[id]_text" name="text">$text</textarea>
<input type="text" name="user" value="$note[user]" />
<input type="text" name="date" value="$note[date]" />
HTML;

			if($user->is_admin())
			{
				$string .= <<<HTML
<input type="text" name="group" value="$note[note_group]" />
HTML;
			}
			
			$string .= <<<HTML
<span name="pos">
(
	<input type="text" id="note$note[id]_x" name="x" value="$note[x]" />
,&nbsp;
	<input type="text" id="note$note[id]_y" name="y" value="$note[y]" />
)
</span>
<span name="dim">
	<input type="text" id="note$note[id]_w" name="w" value="$note[w]" />
X
	<input type="text" id="note$note[id]_h" name="h" value="$note[h]" />
</span>

<input type="button" value="Save" name="save" onClick="javascript:change_note($note[id],
																document.getElementById('note$note[id]_text').value,
																document.getElementById('note$note[id]_x').value,
																document.getElementById('note$note[id]_y').value,
																document.getElementById('note$note[id]_w').value,
																document.getElementById('note$note[id]_h').value);">

</div>
</form>
<br/>

HTML;
		}
		
		return($string);
	}
}
?>