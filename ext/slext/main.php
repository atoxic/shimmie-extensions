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

	public function getTag($regex, $image_id)
	{
		$image = Image::by_id($image_id);
		$tags = $image->get_tag_array();
		$chapter_tag = NULL;
		foreach($tags as $tag)
		{
			if(preg_match($regex, $tag) == 1)
			{
				$chapter_tag = $tag;
				break;
			}
		}
		return($chapter_tag);
	}
	
	public function getOtherVersions($image_id)
	{
		$chapter_tag = $this->getTag($this->page_regex_exp, $image_id);
		if(is_null($chapter_tag))
			return("no chapter tag");
			
		global $database;
		$tag_record = $database->get_row("SELECT * FROM `tags` WHERE tag=?", array($chapter_tag));
		$tag_id = $tag_record['id'];
		$image_list = $database->get_all("SELECT * FROM `image_tags` WHERE tag_id=?", array($tag_id));
		
		$array = array();
		foreach($image_list as $image)
		{
			$stage_tag = $this->getTag('/stage_.+/', $image['image_id']);
			if(!is_null($stage_tag))
				$array[$image['image_id']] = $stage_tag;
		}
		
		return($array);
	}
	public function onPageRequest(PageRequestEvent $event)
	{
		global $page, $user;
		if($event->page_matches("post"))
		{
			// viewing an image
			if($event->count_args() == 2 && $event->get_arg(0) == "view" && is_numeric($event->get_arg(1)))
			{
				$this->theme->displayVersionManagement($page, $user, $event->get_arg(1));
			}
		}
		else if($event->page_matches("versions"))
		{
			if($event->count_args() == 1)
			{
				$page->set_title("Versions of image " . $event->get_arg(0));
				$str = $this->getOtherVersions($event->get_arg(0));
				$this->theme->displayVersions($page, $user, $str);
			}
		}
	}
}
?>