$(document).ready(function () {
	var so = new SWFObject(
		"lib/wp-cumulus/tagcloud.swf",
		"tagcloud",
		$("#flashcontent").width(),
		$("#flashcontent").width() * 3 / 4,
		"7", "#336699");
	so.addParam("wmode", "transparent");
	so.addVariable("mode", "tags");
	so.addVariable("distr", "true");
	so.addVariable("tcolor", "0xff0000");
	so.addVariable("hicolor", "0x000000");
	so.addVariable("tagcloud", $("#flashxml").text());
	so.write("flashcontent");
	//console.log($("#flashxml").text());
});
