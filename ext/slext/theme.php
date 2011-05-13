<?php
/*
	Handles view and controller
 */
class SLExtTheme extends Themelet
{
	static $stages_raw = array
	(
		"stage_raw" 	=> "Raw",
		"stage_tl" 		=> "Translated",
		"stage_tlc" 	=> "Translation Checked",
		"stage_pr" 		=> "Proofread and Edited"
	);
	static $stages_clean = array
	(
		"stage_clean" 	=> "Cleaned"
	);
	static $stages_ts = array
	(
		"stage_ts" 		=> "Typesetted",
		"stage_alpha" 	=> "<span style='color: red;'>Alpha</span>",
		"stage_beta" 	=> "<span style='color: blue;'>Beta</span>",
		"stage_gold" 	=> "<span style='color: green;'>Gold</span>"
	);
	static $stages_html;
	static $stages_break = array
	(
		4,
		10
	);
	
	public function displayStageUploadError(Page $page, $string = null)
	{
		$upload = make_link("/upload");
		$this->display_error($page, "Stage Upload Error", "Error: " . (isset($string) ? $string : "problem uploading a new stage.") . "<br/>Try uploading again or using the <a href='$upload'>normal uploader</a>");
	}
	
	public function displayVersionsError(Page $page)
	{
		$this->display_error($page, "Error fetching versions", "Error: Could not get versions of the image specified");
	}
	
	public function displayStageDLError(Page $page)
	{
		$this->display_error($page, "Error generating stage DL", "Error: Could not generate the stage DL");
	}
	
	public function displayStageDL(Page $page)
	{
		$page->set_title("Stage Download Generated");
		$page->add_block(new Block("Stage DL Generated", "Stage DL Generated."));
	}
	
	public function displayStageProgessCacheInit(Page $page)
	{
		$page->set_title("Stage Progress Cache Initialized");
		$page->add_block(new Block("Stage Progress Cache Initialized", "Initialization of stage progress cache db is a success."));
	}
	
	public function displayStageChangeError(Page $page)
	{
		$this->display_error($page, "Stage Change Error", "Error: Could not change stages");
	}
	
	private function cacheTableHeading($chap, $pools)
	{
		$pool_html = "";
		if(isset($pools[$chap]))
			$pool_html = "<a class='view_pool' href='" . make_link("pool/view/" . $pools[$chap]) . "'>View Pool</a>";
		
		$chap = html_escape($chap);
		$hashed = hash("md5", html_escape($chap));
		$string = <<<HEADER
<a name="$hashed"><a href="javascript:;" class="cache_table_hide_link" id="$hashed">$chap</a>
$pool_html
<div class='stage_div' id="${hashed}_div"><table class='stage_table' id="${hashed}_table"><tr><td>Page</td>
HEADER;
		foreach(SLExt::$stages as $stage)
		{
			$string .= "<td>" . SLExtTheme::$stages_html[$stage] . "</td>";
		}
		$string .= "</tr>";
		return($string);
	}
	
	private function common(Page $page)
	{
		$data_href = get_base_href();
		
		$page->add_header(<<<HTML
<!-- For common styles -->
<link rel="stylesheet" type="text/css" href="$data_href/lib/ext_slext/ext_slext.css" />
HTML
);
	}
	
	// displays stage progress from cache
	public function displayStageProgessCache(Page $page, $cache)
	{
		$this->common($page);
		
		$data_href = get_base_href();
		
		$string = <<<HTML
<!-- For common functions -->
<script type="text/javascript" src="$data_href/lib/ext_slext/ext_slext.js"> </script>
HTML;
		$prev_chap = null;
		$cur_chap = null;
		foreach($cache["pages"] as $page_tag => $list)
		{
			$cur_chap = SLExt::getChapterFromPage($page_tag);
			if(!isset($prev_chap))
			{
				$string .= $this->cacheTableHeading($cur_chap, $cache["pools"]);
			}
			else if($prev_chap != $cur_chap)
			{
				$string .= "</tr></table></div><br/><br/>" . $this->cacheTableHeading($cur_chap, $cache["pools"]);
			}
			$prev_chap = $cur_chap;
			
			$thumb = $list["th_src"];
			$th_link = make_link("post/view/" . $list["th_id"]);
			$string .= "<tr><td>$page_tag<br/><a href='$th_link' target='_blank'><img class='pv_thumb' src='$thumb'></img></a></td>";
			$break = 0;
			for($i = 0; $i < count(SLExt::$stages); $i++)
			{
				$style = "background: red;";
				if($i >= SLExtTheme::$stages_break[$break])
				{
					$break++;
				}
				for($j = $i; $j < SLExtTheme::$stages_break[$break]; $j++)
				{
					if(array_key_exists($j, $list))
					{
						$style = "";
						break;
					}
				}
				$string .= "<td style='$style'>";
				if(array_key_exists($i, $list))
				{
					foreach($list[$i] as $image_id)
					{
						$link = make_link("/post/view/$image_id");
						$string .= "<div class='image_link'><a href='$link' target='_blank'>$image_id</a></div>";
					}
				}
				$string .= "</td>";
			}
			$string .= "</tr>";
		}
		$string .= <<<JS
</table></div>
JS;
		$page->set_title("Stage Progress");
		$page->add_block(new Block("Stage Progress", $string));
	}
	
	// displays list of versions in /versions/image_id
	public function displayVersions(Page $page, User $user, $array)
	{
		$string = <<<HTML
<table>
	<tr style="font-weight: bold;"><td style="width: 100px;">Image Id</td><td>Stage</td></tr>
HTML;
		foreach($array as $key => $value)
		{
			if(array_key_exists($value, SLExtTheme::$stages_html))
			{
				$value = SLExtTheme::$stages_html[$value];
			}
			else
			{
				$value = json_encode($value);
			}
			$link = make_link("/post/view/$key");
			$string .= <<<HTML
<tr><td><a href="$link" />$key</a></td><td>$value</td></tr>
HTML;
		}
		$string .= "</table>";
		$page->add_block(new Block("Other Versions", $string));
	}
	
	// produces stage change table
	private static function outputTable($table, $stage, $image_id)
	{
		$form = make_form(make_link("stage_change"), "POST", $multipart=True);
		$string = <<<HTML
<td>
$form
HTML;
		foreach($table as $key => $value)
		{
			$string .= <<<HTML
<label for="sctable_$key">
<input id="sctable_$key" type="radio" name="stage" value="$key" />$value
</label><br />
HTML;
		}
		$string .= <<<HTML
<input type='hidden' name='image_id' value='$image_id' />
<input type='submit' value='Change'>
</form></td>
HTML;
		return(str_replace("$stage\"", "$stage\" checked='checked' ", $string));
	}
	
	// version management table below an image
	public function displayVersionManagement(Page $page, User $user, $image_id)
	{
		$tags = SLExt::getTags($image_id);
		$stage = SLExt::getTag('/stage_.+/', $tags);
		if(is_null($stage))
			return;
		
		$this->common($page);
		$string = <<<HTML
<table style='border: 1px solid; height: 100px;' class='management'><tr style='font-weight: bold;'><td>Stage: 
HTML;
		
		switch($stage)
		{
			case "stage_raw":
			case "stage_tl":
			case "stage_tlc":
			case "stage_pr":
				$string .= "Raw/Translation</td><td>Upload New Version:</td></tr><tr>" . SLExtTheme::outputTable(SLExtTheme::$stages_raw, $stage, $image_id);
				break;
			case "stage_clean":
				$string .= "Cleaned</td><td>Upload New Version:</td></tr><tr><td></td>";
				break;
			case "stage_ts":
			case "stage_alpha":
			case "stage_beta":
			case "stage_gold":
				$string .= "Typesetted/QC</td><td>Upload New Version:</td></tr><tr>" . SLExtTheme::outputTable(SLExtTheme::$stages_ts, $stage, $image_id);
				break;
		}
		
		$form = make_form(make_link("stage_upload"), "POST", $multipart=True);
		$link = make_link("/versions/$image_id");
		$string .=  <<<HTML
			<td>
			$form
				<input type='hidden' name='image_id' value='$image_id' />
				File: <input accept='image/jpeg,image/png,image/gif' size='10' id='file' name='file' type='file'><br/>
				<label for="sutable_raw">	<input id="sutable_raw" type="radio" name="stage" value="stage_raw" />		Raw			</label><br />
				<label for="sutable_clean">	<input id="sutable_clean" type="radio" name="stage" value="stage_clean" />	Cleaned		</label><br />
				<label for="sutable_ts">	<input id="sutable_ts" type="radio" name="stage" value="stage_ts" />		Typesetted	</label><br />
				<input type='submit' value='Post'>
			</form>
			</td>
			</tr></table>
			<a href="$link">View all versions</a>
HTML;
		$page->add_block(new Block("Version Management", $string, "main", 20));
	}
}
SLExtTheme::$stages_html = array_merge(SLExtTheme::$stages_raw, SLExtTheme::$stages_clean, SLExtTheme::$stages_ts);
?>