<?php
/**
 * Name: SLExt
 * Author: /a/non <anonymousscanlations@gmail.com>
 * Link: https://github.com/atoxic/shimmie-extensions
 * License: BSD
 * Description: Scanlation extensions
 * Documentation:
 *    Extension for managing scanlation
 */

class SLExt extends SimpleExtension
{
	// list of valid stages
	static $stages = array
	(
		"stage_raw",
		"stage_tl",
		"stage_tlc",
		"stage_pr",
		"stage_clean",
		"stage_ts",
		"stage_alpha",
		"stage_beta",
		"stage_gold"
	);

	// table for caching progress
	var $db = "slext_progess_cache";
	
	// regex for matching page tags
	static $page_regex = '.*_c[[:digit:]]+_.*';
	static $page_regex_exp = '/.*_c[[:digit:]]+_.*/';
	
	// regex for matching stages
	static $stage_regex_exp = '/stage_.+/';
	
	/* ======================================================
		DATABASE FUNCTIONS
	   ====================================================== */
	
	public static function getTags($image_id)
	{
		$image = Image::by_id($image_id);
		if(!isset($image))
			return(null);
		$tags = $image->get_tag_array();
		return($tags);
	}
	public static function getTagFromId($regex, $image_id)
	{
		$tags = SLExt::getTags($image_id);
		if(!isset($tags))
			return(null);
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
		if(!isset($chapter_tag))
			return(null);
			
		global $database;
		$tag_record = $database->get_row("SELECT * FROM `tags` WHERE tag=?", array($chapter_tag));
		$tag_id = $tag_record['id'];
		$image_list = $database->get_all("SELECT * FROM `image_tags` WHERE tag_id=?", array($tag_id));
		
		$array = array();
		foreach($image_list as $image)
		{
			$stage_tag = $this->getTagFromId(SLExt::$stage_regex_exp, $image['image_id']);
			if(!is_null($stage_tag))
				$array[$image['image_id']] = $stage_tag;
		}
		
		return($array);
	}
	
	/* ======================================================
		EVENT FUNCTIONS
	   ====================================================== */
	
	
	public function onInitExt(Event $event)
	{	
		global $database, $config;
		$version = $config->get_int($this->db . "_version", 0);
		if($version < 1)
		{
			$database->create_table($this->db, "
					image_id INTEGER NOT NULL DEFAULT 0,
					stage TINYINT NOT NULL DEFAULT 0,
					chapter_tag varchar(64) NOT NULL
					");
					
			log_info($this->db, "Installed tables for the SLExt extension at " . $this->db . ".");
			$config->set_int($this->db . "_version", 1);
		}
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
			if($event->count_args() != 1)
			{
				$this->theme->displayVersionsError($page);
				return;
			}
			
			$str = $this->getOtherVersions($event->get_arg(0));
			
			if(!isset($str))
			{
				$this->theme->displayVersionsError($page);
				return;
			}
			
			$page->set_title("Versions of image " . $event->get_arg(0));
			$this->theme->displayVersions($page, $user, $str);
		}
		else if($event->page_matches("stage_change"))
		{
			if($user->is_anonymous())
			{
				$this->theme->display_permission_denied($page);
				return;
			}
			// make sure that we've been passed the right arguments
			if($event->count_args() != 0 ||
				count($_POST) <= 0 || !array_key_exists("stage", $_POST) || !array_key_exists("image_id", $_POST) ||
				!in_array($_POST["stage"], SLExt::$stages))
			{
				$this->theme->displayStageChangeError($page);
				return;
			}
			$image = Image::by_id($_POST["image_id"]);
			if(!isset($image))
			{
				$this->theme->displayStageChangeError($page);
				return;
			}
			$tags = $image->get_tag_array();
			$new_tags = Tag::explode($_POST["stage"]);
			foreach($tags as $tag)
			{
				if(!preg_match(SLExt::$stage_regex_exp, $tag))
				{
					$new_tags[] = $tag;
				}
			}
			$image->set_tags($new_tags);
			
			$page->set_mode("redirect");
			$page->set_redirect("?q=/post/view/" . $_POST["image_id"]);
		}
		else if($event->page_matches("stage_upload"))
		{
			if(!$this->can_upload($user))
			{
				$this->theme->display_permission_denied($page);
				return;
			}
			if($event->count_args() != 0 ||
				count($_POST) <= 0 || !array_key_exists("image_id", $_POST) || !array_key_exists("stage", $_POST) || !isset($_FILES['file']) ||
				!in_array($_POST["stage"], SLExt::$stages))
			{
				$this->theme->displayStageUploadError($page);
				return;
			}
			
			$image_id = $_POST['image_id'];
			$file = $_FILES['file'];
			
			$old_tags = SLExt::getTags($image_id);
			if(!isset($old_tags))
			{
				$this->theme->displayStageUploadError($page);
				return;
			}
			$new_tags = Tag::explode($_POST['stage']);
			foreach($old_tags as $tag)
			{
				if(!preg_match(SLExt::$stage_regex_exp, $tag))
				{
					$new_tags[] = $tag;
				}
			}
			
			if($this->try_upload($file, $new_tags, SLExt::scriptName() . "?q=/post/view/" . $image_id))
			{
				$page->set_mode("redirect");
				$page->set_redirect("?q=/post/view/" . $image_id);
			}
		}
	}
	
	/* Gets the URL of the index script of shimmie
	 */
	private static function scriptName()
	{
		$pageURL = 'http';
		if(isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on") { $pageURL .= "s"; }
		$pageURL .= "://" . $_SERVER["SERVER_NAME"] . $_SERVER["SCRIPT_NAME"];
		return $pageURL;
	}
	
	/* Can the user upload?
	 * Copied from the Upload extension
	 */
	private function can_upload($user)
	{
		global $config;
		return($config->get_bool("upload_anon") || !$user->is_anonymous());
	}
	
	/* Attempt an upload
	 * Copied from the Upload extension
	 */
	private function try_upload($file, $tags, $source)
	{
		global $page;
		global $config;

		if(empty($source)) $source = null;

		$ok = true;

		// blank file boxes cause empty uploads, no need for error message
		if(file_exists($file['tmp_name']))
		{
			global $user;
			$pathinfo = pathinfo($file['name']);
			$metadata['filename'] = $pathinfo['basename'];
			$metadata['extension'] = $pathinfo['extension'];
			$metadata['tags'] = $tags;
			$metadata['source'] = $source;
			$event = new DataUploadEvent($user, $file['tmp_name'], $metadata);
			try
			{
				send_event($event);
				if($event->image_id == -1)
				{
					throw new UploadException("File type not recognised");
				}
				header("X-Shimmie-Image-ID: ".int_escape($event->image_id));
			}
			catch(UploadException $ex)
			{
				$this->theme->displayStageUploadError($page, $ex->getMessage());
				$ok = false;
			}
		}

		return $ok;
	}
}
?>