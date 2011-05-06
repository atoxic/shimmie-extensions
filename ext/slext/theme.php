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
	
	public function displayStageProgessCacheInit(Page $page)
	{
		$page->set_title("Stage Progress Cache Initialized");
		$page->add_block(new Block("Stage Progress Cache Initialized", "Initialization of stage progress cache db is a success."));
	}
	
	public function displayStageChangeError(Page $page)
	{
		$this->display_error($page, "Stage Change Error", "Error: Could not change stages");
	}
	
	public function displayStageProgessCache(Page $page, $array)
	{
		$string = <<<HTML
<style type="text/css">
table.stage_table tr:first-child td
{
	max-width: 100px;
	min-width: 100px;
}
table.stage_table
{
	border: 1px solid;
}
table.stage_table td
{
	border: 1px solid;
	padding: 2px;
}
</style>
<table class="stage_table"><tr><td>Page</td>
HTML;
		foreach(SLExt::$stages as $stage)
		{
			$string .= "<td style='$style'>" . SLExtTheme::$stages_html[$stage] . "</td>";
		}
		$string .= "</tr>";
		$break = 0;
		foreach($array as $page_tag => $list)
		{
			$string .= "<tr><td>$page_tag</td>";
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
						$style = "background: green;";
						break;
					}
				}
				$string .= "<td style='$style'>";
				if(array_key_exists($i, $list))
				{
					foreach($list[$i] as $image_id)
					{
						$link = make_link("/post/view/$image_id");
						$string .= "<a href='$link'>$image_id</a><br/>";
					}
				}
				$string .= "</td>";
			}
			$string .= "</tr>";
		}
		$string .= "</table>";
		$page->set_title("Stage Progress");
		$page->add_block(new Block("Stage Progress", $string));
	}
	
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
<input type="radio" name="stage" value="$key" />$value<br />
HTML;
		}
		$string .= <<<HTML
<input type='hidden' name='image_id' value='$image_id' />
<input type='submit' value='Change'>
</form></td>
HTML;
		return(str_replace("$stage\"", "$stage\" checked='checked' ", $string));
	}
	
	public function displayVersionManagement(Page $page, User $user, $image_id)
	{
		$tags = SLExt::getTags($image_id);
		$stage = SLExt::getTag('/stage_.+/', $tags);
		if(is_null($stage))
			return;
		$string = <<<HTML
<style type="text/css">
table.management td
{
	vertical-align: middle;
	border: 1px solid; 
	padding: 10px;
}
table.management input
{
	margin: 3px;
}
</style>
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
				<input type="radio" name="stage" value="stage_raw" />Raw<br />
				<input type="radio" name="stage" value="stage_clean" />Cleaned<br />
				<input type="radio" name="stage" value="stage_ts" />Typesetted<br />
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