$(document).ready(function(){
    $("ul.tabs li a").click(function(){
		$("ul.tabs li").removeClass("selected");
		$(this).parents("li").addClass("selected");
		var index = $("ul.tabs li").index($(this).parents("li"));			
		$("div.tab").hide();
		$("div.tab").eq(index).show();         
		//return false;
	});

	//$("ul.tabs li:first a").click();
    var a = window.location.href.split('#');
	if(a[1] && $("a[name='"+a[1]+"']").html()) $("a[name='"+a[1]+"']").click();
	else $("ul.tabs li:first a").click();
});