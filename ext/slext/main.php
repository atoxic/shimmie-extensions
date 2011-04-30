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
	static $page_regex = '.*_c[[:digit:]]+_.*';
	static $page_regex_exp = '/.*_c[[:digit:]]+_.*/';
	
	public static function getTags($image_id)
	{
		$image = Image::by_id($image_id);
		$tags = $image->get_tag_array();
		return($tags);
	}
	public static function getTagFromId($regex, $image_id)
	{
		$tags = SLExt::getTags($image_id);
		return(SLExt::getTag($regex, $tags));
	}
	public static function getTag($regex, $tags)
	{
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
		$chapter_tag = $this->getTagFromId(SLExt::$page_regex_exp, $image_id);
		if(is_null($chapter_tag))
			return("no chapter tag");
			
		global $database;
		$tag_record = $database->get_row("SELECT * FROM `tags` WHERE tag=?", array($chapter_tag));
		$tag_id = $tag_record['id'];
		$image_list = $database->get_all("SELECT * FROM `image_tags` WHERE tag_id=?", array($tag_id));
		
		$array = array();
		foreach($image_list as $image)
		{
			$stage_tag = $this->getTagFromId('/stage_.+/', $image['image_id']);
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
		else if($event->page_matches("stage_change"))
		{
			if(!$user->is_anonymous() &&		// is a user
				$event->count_args() == 0 && 	// no arguments
				count($_POST) > 0 && array_key_exists("stage", $_POST) && array_key_exists("image_id", $_POST) &&	// has the arguments
				array_key_exists($_POST["stage"], SLExtTheme::$stages_html))	// stage is valid
			{
				$image = Image::by_id($_POST["image_id"]);
				if(is_null($image))
					return;
				$tags = $image->get_tag_array();
				$new_tags = array($_POST["stage"]);
				foreach($tags as $tag)
				{
					if(!preg_match("/stage_.+/", $tag))
					{
						$new_tags[] = $tag;
					}
				}
				$image->set_tags($new_tags);
				
				$page->set_mode("redirect");
				$page->set_redirect("?q=/post/view/" . $_POST["image_id"]);
			}
		}
	}
}
?>