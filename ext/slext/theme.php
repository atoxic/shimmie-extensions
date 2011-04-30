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
			$string .= <<<HTML
<tr><td><a href="?q=/post/view/$key" />$key</a></td><td>$value</td></tr>
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
			<a href="?q=/versions/$image_id">View all versions</a>
HTML;
		$page->add_block(new Block("Version Management", $string, "main", 20));
	}
}
SLExtTheme::$stages_html = array_merge(SLExtTheme::$stages_raw, SLExtTheme::$stages_clean, SLExtTheme::$stages_ts);
?>