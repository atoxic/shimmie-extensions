<?php
/*
	Handles view and controller
 */
class SLExtTheme extends Themelet
{
	var $stages_html = array
	(
		"stage_raw" 	=> "Raw",
		"stage_tl" 		=> "Translated",
		"stage_tlc" 	=> "Translation Checked",
		"stage_pr" 		=> "Proofread and Edited",
		"stage_clean" 	=> "Cleaned",
		"stage_ts" 		=> "Typesetted",
		"stage_alpha" 	=> "<span style='color: red;'>Alpha</span>",
		"stage_beta" 	=> "<span style='color: blue;'>Beta</span>",
		"stage_gold" 	=> "<span style='color: green;'>Gold</span>"
	);
	public function displayVersions(Page $page, User $user, $array)
	{
		$string = <<<HTML
<table>
	<tr style="font-weight: bold;"><td style="width: 100px;">Image Id</td><td>Stage</td></tr>
HTML;
		foreach($array as $key => $value)
		{
			if(array_key_exists($value, $this->stages_html))
			{
				$value = $this->stages_html[$value];
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
	
	public function displayVersionManagement(Page $page, User $user, $image_id)
	{
		$string = <<<HTML
			<a href="?q=/versions/$image_id">View all versions</a>
HTML;
		$page->add_block(new Block("Version Management", $string));
	}
}
?>