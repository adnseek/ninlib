/// <reference path="../typings/index.d.ts" />

function outputUpdate(input) {
	jQuery(input).closest('tr,div').find('output').val(input.value);
}
