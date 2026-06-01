function rankingsFilterByClass() {
	var delay = 500; // milliseconds
	var classList = new Array();
	
	for(var i = 0; i < arguments.length; i++) {
		classList[i] = arguments[i];
	}
	
	if($(".rankings-table").length) {
		$(".rankings-table").fadeOut().delay(delay).fadeIn();
		setTimeout(function() {
			$(".rankings-table tr").each(function() {
				if($(this).attr("data-class-id") == null) { return true; }
				if(classList.includes(parseInt($(this).attr("data-class-id"))) == false) {
					$(this).hide();
				} else {
					$(this).show();
				}
			});
		}, delay);
	}
}

function rankingsFilterRemove() {
	var delay = 500; // milliseconds
	
	$(".rankings-table").fadeOut().delay(delay).fadeIn();
	setTimeout(function() {
		if($(".rankings-table").length) {
			$(".rankings-table tr").each(function() {
					$(this).fadeIn();
				}
			);
		}
	}, delay);
}

$(function() {
	if($(".rankings-class-filter-selection").length) {
		$('a.rankings-class-filter-selection').click(function(){
			$('a.rankings-class-filter-selection').addClass("rankings-class-filter-grayscale");
			$(this).removeClass("rankings-class-filter-grayscale");
		});
	}
});