// Editor for Markdown
// Based on http://www.strangeplanet.fr/work/jquery-highlighttextarea/#structure

var MarkEditor = function(element){
	this.area = $("<div>").addClass("editor").css({
		"position" : "relative",
		"vertical-align" : "baseline"
	}).insertAfter($(element));

	this.highlighter = $("<div>").css({
		"position" : "absolute",
		"width" : "100%",
		"top" : "0px",
		"left" : "0px",
		"color" : "transparent",
		"white-space": "pre-wrap",
		"word-wrap": "break-word"
	}).addClass("highlighter").appendTo(this.area);
	
	var self = this;

	this.textarea = $(element);
	this.textarea.css({
		"left" : "0px",
		"top" : "0px",
		"background" : "none",
		"border" : "0px",
		"padding" : "0px",
		"width" : "100%",
		"position" : "absolute"
	}).remove().appendTo(this.area).on("change", function(){
		self.render();
	}).on("keydown", function(e){
		if(e.keyCode == 9){ // tab
			e.preventDefault();
			self.insertAtCursor("\t");
		}
	}).on("input", function(){
		self.render();
	});
	
	this.highlighter.css({
		"font" : this.textarea.css("font"),
		"font-family" : this.textarea.css("font-family"),
		"line-height" : this.textarea.css("line-height"),
	});
	
	setTimeout(function(){
		self.render();
	}, 200);
};

MarkEditor.prototype.insertAtCursor = function(text){
	var start = $(this.textarea).get(0).selectionStart;
	var end = $(this.textarea).get(0).selectionEnd;

	// set textarea value to: text before caret + tab + text after caret
	$(this.textarea).val($(this.textarea).val().substring(0, start)
		+ text
		+ $(this.textarea).val().substring(end));

	// put caret at right position again
	$(this.textarea).get(0).selectionStart =
	$(this.textarea).get(0).selectionEnd = start + text.length;
};

MarkEditor.prototype.render = function(){
	var value = this.textarea.val();

	// Escape
	value = value.replace( />/mg, "&gt;" );
	value = value.replace( /</mg, "&lt;" );

	// Headings (eating the newline)
	value = value.replace( /(#{1,6} [^\n]+)\n/mg, "<div class='hl'>$1</div>");
	value = value.replace( /([^\n]+\n)(={3,}|-{3,})\n/mg, "<div class='hl'>$1$2</div>");

	value = value.replace( /__([\s\S]+?)__(?!_)|\*\*([\s\S]+?)\*\*(?!\*)/mg, "<span class='bold'>$&</span>");
	// Quotes
	value = value.replace( /(\&gt\; [^\n]+)\n/mg, "<div class='quote'>$1</div>");
	// Code
	value = value.replace( /((    |\t)[^\n]+)\n/mg, "<div class='code'>$1</div>");
	// links
	value = value.replace( /(!?\[.+?\])(\(.+?\))/mg, "<span class='label'>$1</span><span class='url'>$2</span>");

	this.highlighter.html(value);
	this.area.css("height", this.textarea.height());
};
