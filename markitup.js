(function(){
	window['$'] = jQuery;
	jQuery(".markdown_editor").each(function(){
		var EDITOR = new MarkEditor(this);
		jQuery(this).autogrow({ "extraHeight" : 40 });
		if($(this).hasClass("active")){
			window['wpActiveMarkdownEditor'] = EDITOR;
			window['wpActiveEditor'] = $(this).attr("id");
		}
	});
	
	$(".insertMD").click(function(){
		wpActiveMarkdownEditor.insertAtCursor( $(this).data("insert") );
	});

	// Editor	
	window.send_to_editor = function(content){
		var content = $("<div>").html(content);
		// We only deal with images
		wpActiveMarkdownEditor.insertAtCursor("![" + $("img",content).attr("alt") + "]("+$("img",content).attr("src")+")\n");
	};
})();
