// https://gist.github.com/mlangenberg/779513
/**
 * Get the ISO week date week number
 * Matthijs: implementation is not correct!
 * Added so you can grasp the idea
 */
Date.prototype.getWeek = function () {
	// Create a copy of this date object
	var target = new Date(this.valueOf());

	// ISO week date weeks start on monday
	// so correct the day number
	var dayNr = (this.getDay() + 6) % 7;

	// Set the target to the thursday of this week so the
	// target date is in the right year
	target.setDate(target.getDate() - dayNr + 3);

	// ISO 8601 states that week 1 is the week
	// with january 4th in it
	var jan4 = new Date(target.getFullYear(), 0, 4);

	// Number of days between target date and january 4th
	var dayDiff = (target - jan4) / 86400000;

	// Calculate week number: Week 1 (january 4th) plus the
	// number of weeks between target date and january 4th
	var weekNr = 1 + Math.floor(dayDiff / 7);  // MLA removed "1 +"

	return weekNr;
};

Date.prototype.nextWeek = function () {
	var copy = new Date(this.getTime());
	return new Date(copy.setDate(copy.getDate() + 7));
};

Date.prototype.beginningOfWeek = function () {
	var copy = new Date(this.getTime());
	var monday = new Date(copy.setDate(copy.getDate() - copy.getDay() + 1))
	monday.setHours(0);
	monday.setMinutes(0);
	monday.setSeconds(0);
	return monday;
};

function ticksWeeks(axis) {
	var beginDate = new Date(axis.min),
		endDate = new Date(axis.max);

	var ticks = [];
	tickDate = beginDate.nextWeek().beginningOfWeek();
	while (tickDate < endDate) {
		ticks.push([tickDate.getTime(), "W" + tickDate.getWeek()]);
		tickDate = tickDate.nextWeek();
	}
	return ticks;
}
