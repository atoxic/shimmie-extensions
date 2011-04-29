<?php
/**
 * Name: SLExt
 * Author: /a/non <anonymousscanlations@gmail.com>
 * Link: https://github.com/atoxic/shimmie-extensions
 * License: BSD
 * Description: Basic photo notes
 * Documentation:
 *    Basic photo notes like in Flickr and Gel/Danbooru.
 *    Make sure to include the 5 or so JS and CSS files in the lib folder.
 */

class SLExt extends SimpleExtension
{
	// table for caching progress
	var $progress = "sl_progess";
	
	// regex for matching page tags
	var $page_regex = '.*_c[[:digit:]]+_.*';
	var $page_regex_exp = '/.*_c[[:digit:]]+_.*/';

	public function getChapterTag($image_id)
	{
		$image = Image::by_id($image_id);
		$tags = $image->get_tag_array();
		$chapter_tag = NULL;
		foreach($tags as $tag)
		{
			if(preg_match($this->page_regex_exp, $tag) == 1)
			{
				$chapter_tag = $tag;
				break;
			}
		}
		return($chapter_tag);
	}
	/*
		
	 */
	public function getOtherVersions($image_id)
	{
		$chapter_tag = $this->getChapterTag($image_id);
		if(is_null($chapter_tag))
			return("no chapter tag");
			
		global $database;
		$tag_record = $database->get_row("SELECT * FROM `tags` WHERE tag=?", array($chapter_tag));
		$tag_id = $tag_record['id'];
		$image_list = $database->get_rows("SELECT * FROM `image_tags` WHERE tag_id=?", array($tag_id));
		
		return("chapter tag: " . $chapter_tag . ", id: " . $tag_id);
	}
	public function onPageRequest(PageRequestEvent $event)
	{
		global $page, $user;
		global $database;
		if($event->page_matches("post"))
		{
			// viewing an image
			if($event->get_arg(0) == "view")
			{			
				$str = $this->getOtherVersions($event->get_arg(1));
				$this->theme->displayVersions($page, $user, $str);
			}
		}
	}
}
?>