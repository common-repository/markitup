<?php
class _WP_Editors {
	private static $has_medialib = false;
	
	/**
	 * Outputs the HTML for a single instance of the editor.
	 *
	 * @param string $content The initial content of the editor.
	 * @param string $editor_id ID for the textarea and TinyMCE and Quicktags instances (can contain only ASCII letters and numbers).
	 * @param array $settings See the _parse_settings() method for description.
	 */
	public static function editor( $content, $editor_id, $settings = array() ) {
		$set = wp_parse_args( $settings,  array(
			'wpautop' => true, // use wpautop?
			'media_buttons' => true, // show insert/upload button(s)
			'textarea_name' => $editor_id, // set the textarea name to something different, square brackets [] can be used here
			'textarea_rows' => 20,
			'tabindex' => '',
			'tabfocus_elements' => ':prev,:next', // the previous and next element ID to move the focus to when pressing the Tab key in TinyMCE
			'editor_css' => '', // intended for extra styles for both visual and Text editors buttons, needs to include the <style> tags, can use "scoped".
			'editor_class' => '', // add extra class(es) to the editor textarea
			'teeny' => false, // output the minimal editor config used in Press This
			'dfw' => false, // replace the default fullscreen with DFW (needs specific DOM elements and css)
			'tinymce' => true, // load TinyMCE, can be used to pass settings directly to TinyMCE using an array()
			'quicktags' => true // load Quicktags, can be used to pass settings directly to Quicktags using an array()
		) );
	
		// Media Buttons
		echo '<div id="wp-' . $editor_id . '-editor-tools" class="wp-editor-tools hide-if-no-js">';
		echo $buttons;

		if ( $set['media_buttons'] ) {
			self::$has_medialib = true;

			if ( !function_exists('media_buttons') )
				include(ABSPATH . 'wp-admin/includes/media.php');

			echo '<div id="wp-' . $editor_id . '-media-buttons" class="wp-media-buttons">';
			do_action('media_buttons', $editor_id);
			echo '<a class="button insertMD" title="Insert break for snippet" data-insert="<!-- more -->">more</a>';
			echo "</div>\n";
		}
		echo "</div>\n";		
		$cls = "";
		if ( 'content' === $editor_id ) {
			$cls = "active";
		}

		echo "<textarea class='markdown_editor $cls' name='$editor_id' id='$editor_id'>$content</textarea><input type='hidden' name='using_markdown' value='true' />";
		wp_enqueue_script("jquery");
		wp_enqueue_script(
			'markitup_editor',
			'/wp-content/plugins/markitup/editor/editor.js'
		);
		wp_enqueue_script(
			'markitup_autogrow',
			'/wp-content/plugins/markitup/editor/jquery.autogrow-textarea.js'
		);
		wp_enqueue_style(
			'markitup_main',
			'/wp-content/plugins/markitup/editor/editor.css'
		);
		wp_enqueue_script(
			'markitup_main',
			'/wp-content/plugins/markitup/markitup.js'
		);
	}

}
