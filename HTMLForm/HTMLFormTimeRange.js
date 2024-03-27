function showSliders() {
	console.log('.time-scale', $(".time-scale").length);
	$(".time-scale").each(function () {
		var $slider = $(this);
		$slider.slider({
			range: true,
			min: parseInt($slider.attr('min')),
			max: parseInt($slider.attr('max')),
			values: [
				parseInt($slider.attr('start')),
				parseInt($slider.attr('end'))
			],
			step: parseInt($slider.attr('step')),
			slide: function (event, ui) {
				var start = ui.values[0];
				start = new Date(start * 60 * 1000);
				start = String(start.getUTCHours()).zf() + ':' + String(start.getUTCMinutes()).zf();
				var end = ui.values[1];
				end = new Date(end * 60 * 1000);
				end = String(end.getUTCHours()).zf() + ':' + String(end.getUTCMinutes()).zf();
				var $input = $("#" + $(this).attr('field'));
				$input.val(start + '-' + end);
			}
		});
	});
	//$("#<?= $this->field ?>").val($("#slider-range-<?= $this->div ?>").slider("values", 0) + '-' + $("#slider-range-<?= $this->div ?>").slider("values", 1));
}

$(document).ready(showSliders);
