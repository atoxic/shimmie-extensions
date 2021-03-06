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
	var $db = "slext_progress_cache";
	
	// regex for matching page tags
	static $page_regex = '.*_c[[:digit:]]+_.*';
	static $page_regex_exp = '/.*_c[[:digit:]]+_.*/';
	
	// regex for matching stages
	static $stage_regex_exp = '/stage_.+/';
	
	/* ======================================================
		DATABASE FUNCTIONS
	   ====================================================== */
	
	// get array of tags from image (or returns null)
	public static function getTags($image_id)
	{
		$image = Image::by_id($image_id);
		if(!isset($image))
			return(null);
		$tags = $image->get_tag_array();
		return($tags);
	}
	// gets first match of regex from array of tags
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
	// combines the two previous functions
	public static function getTagFromId($regex, $image_id)
	{
		$tags = SLExt::getTags($image_id);
		if(!isset($tags))
			return(null);
		return(SLExt::getTag($regex, $tags));
	}
	
	public static function getChapterFromPage($page_tag)
	{
		return(substr($page_tag, 0, strrpos($page_tag, "_")));
	}
	
	// gets other versions of the same image (has same page tag) as array (or returns null)
	public function getOtherVersions($image_id)
	{
		$chapter_tag = $this->getTagFromId(SLExt::$page_regex_exp, $image_id);
		if(!isset($chapter_tag))
			return(null);
			
		global $database;
		$image_list = $database->get_all("SELECT * FROM `image_tags` WHERE tag_id=(SELECT id FROM `tags` WHERE tag=?)",
										array($chapter_tag));
		
		$array = array();
		foreach($image_list as $image)
		{
			$stage_tag = $this->getTagFromId(SLExt::$stage_regex_exp, $image['image_id']);
			if(!is_null($stage_tag))
				$array[$image['image_id']] = $stage_tag;
		}
		
		return($array);
	}
	
	// deletes the progress cache and inserts all images into it again
	public function initProgressCache()
	{
		global $database;
		$database->execute("TRUNCATE " . $this->db);
		$images = Image::find_images(0, 10000000000);
		foreach($images as $image)
		{
			$this->insertImageIntoCache($image);
		}
		
		// generate automatic pools
		$pages = $database->get_all("SELECT page FROM " . $this->db . " GROUP BY page");
		$chapters = array();
		foreach($pages as $page)
		{
			$chapter = SLExt::getChapterFromPage($page['page']);
			if(!isset($chapters[$chapter]))
			{
				$chapters[$chapter] = array();
				$chapters[$chapter]['count'] = 0;
				$id = $database->get_row("SELECT id FROM pools WHERE title=?", array($chapter));
				if(isset($id) && isset($id["id"]))
				{
					$id = $id["id"];
					$database->execute("DELETE FROM pool_images WHERE pool_id=?", array($id));
					$chapters[$chapter]['id'] = $id;
				}
				else
				{
					$chapters[$chapter]['id'] = $this->add_pool($chapter);
				}
			}
			$database->execute("INSERT INTO pool_images (pool_id, image_id, image_order) VALUES (?, (SELECT image_id FROM " . $this->db . " WHERE page=? ORDER BY stage DESC, image_id LIMIT 1), ?)",
							array($chapters[$chapter]['id'], $page['page'], substr($page['page'], strrpos($page['page'], "_") + 1)));
			$chapters[$chapter]['count']++;
		}
		
		foreach($chapters as $chapter => $attr)
		{
			$database->execute("UPDATE pools SET posts=?, description=? WHERE id=?", array($attr['count'], "Automatically generated from the latest stages of $chapter on " . date('F jS\, Y h:i:s A'), $attr['id']));
		}
	}
	
	// tries to insert an image into the state progress cache
	public function insertImageIntoCache($image, $tags = null)
	{
		global $database;
		if(!isset($tags))
			$tags = $image->get_tag_array();
		$stage = SLExt::getTag(SLExt::$stage_regex_exp, $tags);
		$page = SLExt::getTag(SLExt::$page_regex_exp, $tags);
		if(isset($stage) && in_array($stage, SLExt::$stages) && isset($page))
		{
			$database->execute("INSERT INTO " . $this->db . " (image_id, stage, page) VALUES (?, ?, ?)", 
										array($image->id, array_search($stage, SLExt::$stages), $page));
		}
	}
	
	/* Fetches the progress cache
	 * Returned data structure:
	 * "pools" => array of pool title to id
	 * "pages" => array of page to (array of stage to (array of image_id))
	 */
	public function getProgressCache($include_pools = true)
	{
		global $database;
		$table = $database->get_all("SELECT * FROM " . $this->db . " ORDER BY page, stage, image_id");
		$row = null;
		$pages = array();
		$page_array = array();
		$prev_page = null;
		$prev_id = null;
		foreach($table as $row)
		{
			if($prev_page != $row["page"])
			{
				if(isset($prev_page))
				{
					// thumbnail is set to the last one by stage
					$page_array["th_src"] = make_link("/thumb/" . $prev_id);
					$page_array["th_id"] = $prev_id;
					$pages[$prev_page] = $page_array;
					
					$page_array = array();
				}
				$prev_page = $row["page"];
			}
			$prev_id = $row["image_id"];
			if(!array_key_exists($row["stage"], $page_array))
				$page_array[$row["stage"]] = array();
			$page_array[$row["stage"]][] = $row["image_id"];
		}
		$page_array["th_src"] = make_link("/thumb/" . $prev_id);
		$page_array["th_id"] = $prev_id;
		$pages[$prev_page] = $page_array;
		
		if($include_pools)
		{
			// ===================
			// get pools
			// ===================
			$pools_db = $database->get_all("SELECT id, title FROM pools");
			$pools = array();
			foreach($pools_db as $pool)
			{
				$pools[$pool["title"]] = $pool["id"];
			}
			// ===================
			// finish up
			// ===================
			$cache = array();
			$cache["pages"] = $pages;
			$cache["pools"] = $pools;
			return($cache);
		}
		return($pages);
	}
	
	/*
		Either packs the latest stages of each page into a zip file named "stage_dl.zip",
		or copies them into a directory called "stage_dl".  Both appears at the root of the shimmie directory
	 */
	public function stageDL()
	{
		global $database;
			
		$pages = $database->get_all("SELECT * FROM (SELECT * FROM " . $this->db . " ORDER BY stage DESC) AS table_tmp GROUP BY page ORDER BY page");
		
		if(phpversion('phar'))
		{
			$phar_fn = 'stage_dl';
			unlink($phar_fn);
			unlink($phar_fn . ".zip");
			$p = new Phar($phar_fn, 0, $phar_fn);
			$p = $p->convertToExecutable(Phar::ZIP);
			$p->startBuffering();
			foreach($pages as $page_rec)
			{
				$img = Image::by_id($page_rec["image_id"]);
				$filename = $page_rec["page"] . "." . $img->get_ext();
				$p[$filename] = file_get_contents($img->get_image_filename());
				$p[$filename]->setMetaData(array('mime-type' => $img->get_mime_type()));
			}
			$p->stopBuffering();
		}
		else
		{
			$dir = 'stage_dl';
			if(file_exists($dir) && !is_dir($dir))
				unlink($dir);
			if(!is_dir($dir) && !mkdir($dir))
				$this->theme->displayStageDLError($page);
			foreach($pages as $page_rec)
			{
				$img = Image::by_id($page_rec["image_id"]);
				$filename = $dir . "/" . $page_rec["page"] . "." . $img->get_ext();
				copy($img->get_image_filename(), $filename);
			}
		}
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
					page varchar(64) NOT NULL
					");
					
			log_info("SLExt", "Installed tables for the SLExt extension at " . $this->db . ".");
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
		else if($event->page_matches("stage_progress"))
		{
			$array = $this->getProgressCache();
			$this->theme->displayStageProgessCache($page, $array);
		}
		else if($event->page_matches("stage_progress_init"))
		{
			if(!$user->is_admin())
			{
				$this->theme->display_permission_denied($page);
				return;
			}
			$this->initProgressCache();
			$this->theme->displayStageProgessCacheInit($page);
		}
		else if($event->page_matches("stage_dl"))
		{
			if(!$user->is_admin())
			{
				$this->theme->display_permission_denied($page);
				return;
			}
			
			$this->stageDL();
			$this->theme->displayStageDL($page);
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
			
			send_event(new TagSetEvent($image, $new_tags));
			
			$page->set_mode("redirect");
			$page->set_redirect(make_link("/post/view/" . $_POST["image_id"]));
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
				!in_array($_POST["stage"], SLExt::$stages) || $_FILES['file']['error'])
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
				$page->set_redirect(make_link("/post/view/" . $image_id));
			}
			else
			{
				$this->theme->displayStageUploadError($page);
			}
		}
		else if($event->page_matches("manga_reader"))
		{
			global $database;
			$arg = $event->get_arg(0);
			if($event->count_args() == 0 || empty($arg))
			{
				$result = $database->get_all("SELECT * FROM (SELECT * FROM " . $this->db . " ORDER BY stage DESC) AS table_tmp GROUP BY page ORDER BY page");
				$this->theme->displayReaderPage($page, $result, null);
			}
			else
			{
				// since ordering the page takes some computational power, check that the page exists first	
				$result = $database->get_row("SELECT * FROM " . $this->db . " WHERE page=? LIMIT 1", array($event->get_arg(0)));
				if(!isset($result))
				{
					$this->theme->displayPageNotFound($page, $arg);
				}
				$result = $database->get_all("SELECT * FROM (SELECT * FROM " . $this->db . " ORDER BY stage DESC) AS table_tmp GROUP BY page ORDER BY page");
				$this->theme->displayReaderPage($page, $result, $arg);
			}
		}
	}
	
	public function onTagSet(TagSetEvent $event)
	{
		global $database;
		$database->execute("DELETE FROM " . $this->db . " WHERE image_id=?", array($event->image->id));
		$this->insertImageIntoCache($event->image, $event->tags);
	}
	
	public function onImageDeletion(ImageDeletionEvent $event)
	{
		global $database;
		$database->execute("DELETE FROM " . $this->db . " WHERE image_id=?", array($event->image->id));
	}
	
	public function onUserBlockBuilding(UserBlockBuildingEvent $event)
	{
		$event->add_link("Stage Progress", make_link("stage_progress"));
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
	
	/* ======================================================
		COPIED FUNCTIONS
	   ====================================================== */
	
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
	
	/*
	 * HERE WE CREATE A NEW POOL
	 */
	private function add_pool($pool)
	{
		global $user, $database;

		if($user->is_anonymous())
		{
			throw new PoolCreationException("You must be registered and logged in to add a image.");
		}
		if(empty($pool))
		{
			throw new PoolCreationException("Pool needs a title");
		}

		$public = "Y";
		$database->execute("
				INSERT INTO pools (user_id, public, title, description, date)
				VALUES (?, ?, ?, ?, now())",
				array($user->id, $public, $pool, "Automatically generated from the latest stages of $pool on " . date('F jS\, Y h:i:s A')));

		$result = $database->get_row("SELECT LAST_INSERT_ID() AS poolID"); # FIXME database specific?

		log_info("pools", "Pool {$result["poolID"]} created by {$user->name}");

		return($result["poolID"]);
	}
}
?>