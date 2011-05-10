$(document).ready(function()
{
	$(".cache_table_hide_link").click(function()
	{
		var id = $(this).attr("id");
		$("#" + id + "_div").slideToggle("slow", function()
		{
			if($("#" + id + "_div").is(":hidden"))
			{
				$.cookie("hide_cache_table_" + id, 'true', {path: '/'});
			}
			else
			{
				$.cookie("hide_cache_table_" + id, 'false', {path: '/'});
			} 
		});
	});

	$(".cache_table_hide_link").each(function()
	{
		var id = $(this).attr("id");
		if($.cookie("hide_cache_table_" + id) == 'true')
		{
			$("#" + id + "_div").hide();
		} 
	});
});