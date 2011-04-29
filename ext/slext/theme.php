<?php
/*
	Handles view and controller
 */
class SLExtTheme extends Themelet
{
	public function displayVersions(Page $page, User $user, $string)
	{
		$page->add_block(new Block("SLExt", $string, "main"));
	}
}
?>