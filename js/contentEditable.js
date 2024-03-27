jQuery(document).ready(function () {
	jQuery(".editable").hallo({
		editable: false
	}).bind("hallodeactivated", function (e) {
		//console.log(this);
		//console.log(e);
		var $this = $(this);
		//console.log($this);
		var data = $this.html();	// .text() hides the line breaks
		//console.log(data);
		//console.log($this.data("save-url"));
		$this.effect("highlight", {"color": "#ff0"}, 1000);
		$this.load($this.data("save-url"), {body: data}, function (json) {
			$this.effect("highlight", {"color": "#0f0"}, 100);
		})
	});
});
