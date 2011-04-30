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
	
	private static function outputTable($table, $stage)
	{
		$string = <<<HTML
<td>
<form id="stage_form">
HTML;
		foreach($table as $key => $value)
		{
			$string .= <<<HTML
<input type="radio" name="stage" value="$key" />$value<br />
HTML;
		}
		$string .= <<<HTML
<input type="button" name="change" value="Change" onClick="javascript:change_stage(this.form);"/>
</form></td>
HTML;
		return(str_replace("$stage\"", "$stage\" checked='checked' ", $string));
	}
	
	public function displayVersionManagement(Page $page, User $user, $image_id)
	{
		$tags = SLExt::getTags($image_id);
		$stage = SLExt::getTag('/stage_.+/', $tags);
		$string = <<<HTML
<script type="text/javascript">
function change_stage(form)
{
	for(var i = 0; i < form.stage.length; i++)
	{
        if(form.stage[i].checked)
		{
			break;
        }
    }
	window.location.href = "?q=/stage_change/$image_id/" + form.stage[i].value;
}
</script>
<style type="text/css">
table.management td
{
	vertical-align: middle;
	border: 1px solid; 
	padding: 10px;
}
</style>
<table style='border: 1px solid; height: 100px;' class='management'><tr><td style='font-weight: bold; '>Stage: 
HTML;
		
		switch($stage)
		{
			case "stage_raw":
			case "stage_tl":
			case "stage_tlc":
			case "stage_pr":
				$string .= "Raw/Translation</td>" . SLExtTheme::outputTable(SLExtTheme::$stages_raw, $stage);
				break;
			case "stage_clean":
				$string .= "Cleaned</td>";
				break;
			case "stage_ts":
			case "stage_alpha":
			case "stage_beta":
			case "stage_gold":
				$string .= "Typesetted/QC</td>" . SLExtTheme::outputTable(SLExtTheme::$stages_ts, $stage);
				break;
		}
		
		$string .= <<<HTML
			</tr></table>
			<a href="?q=/versions/$image_id">View all versions</a>
HTML;
		$page->add_block(new Block("Version Management", $string, "main", 20));
	}
}
SLExtTheme::$stages_html = array_merge(SLExtTheme::$stages_raw, SLExtTheme::$stages_clean, SLExtTheme::$stages_ts);
?>