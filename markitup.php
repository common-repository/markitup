<?php

/*
Plugin Name: Mark it Up
Plugin URI: http://kennydude.me
Description: Markdown for your Wordpress
Version: 1.3a
Author: kennydude
Author URI: http://kennydude.me/
*/

// DO NOT USE THESE IN WPCONFIG
// IF YOU DO I WILL NOT BE HAPPY
define("MARKITUP_ALIGN_DEFAULT", "left");

function markitup_process_gallery($matches){
	$images = $matches[0];
	$images = trim( preg_replace( '/!?\[(.+?)\]\((.+?)\)/m', '<img src="$2" title="$1" />', $images ));

	// Okay we now have our contents done, now wrap it up!
	$value = get_option("markitup_imagealign");
	if($value === false){ $value = MARKITUP_ALIGN_DEFAULT; }
	
	return "\n\n<div class=\"images\" style=\"text-align:$value\">\n$images\n</div>\n\n";
}

function markitup_postcontent($content){
	$use_markdown = @get_post_meta(@get_the_ID(), "markdown", true);
	if($use_markdown === "1"){
		try{
			// Filters
			$content = apply_filters( "markitup_pre_content", $content );
			// Pre-process
			$content = preg_replace_callback( '/\r\n((!\[.+?\]\(.+?\)\r\n)+)\r\n/m', "markitup_process_gallery", $content );	
			
			require_once( dirname(__FILE__) . "/lib/marked.php" );
			$content = marked("$content");
		} catch(Exception $e){
			echo "Error rendering content: $e";
		}
	}
	return $content;
}
add_filter('the_content', 'markitup_postcontent');

function markitup_init(){
	$use_markdown = @get_post_meta(@get_the_ID(), "markdown", true);
	if($use_markdown === "1"){
		remove_filter('the_content', 'wptexturize');
		remove_filter('the_content', 'wpautop');
	}
}

add_action('wp_head', 'markitup_init');

function markitup_savepost($post_id){
	if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) {
		return $post_id;
	}

	if ( isset( $_REQUEST['using_markdown'] ) ) {
		update_post_meta($post_id, 'markdown', TRUE);
	} else {
		//update_post_meta($post_id, 'markdown', FALSE);
	}
}
add_action( 'save_post', 'markitup_savepost');

function markitup_admin_head(){
	if(isset($_GET['post'])){
		$use_markdown = get_post_meta(get_the_ID(), "markdown", true);
	} else{
		$use_markdown = get_the_author_meta( "use_markdown", get_current_user_id() );
	}
	if($use_markdown === "1" or $use_markdown === "on"){
		require "markitup_editor.php";
	}
}
add_action( 'admin_head', 'markitup_admin_head' );

function markitup_settings_desc(){
	echo '<p>Settings regarding MarkItUp</p>';
}

function markitup_imagealign(){
	$value = get_option("markitup_imagealign");
	if($value === false){ $value = MARKITUP_ALIGN_DEFAULT; }
	
	$choices = array(
		"center",
		"left",
		"right"
	);	

	echo "<select id='markitup_imagealign' class='select' name='markitup_imagealign'>";  
	foreach($choices as $item) {  
		$val  = esc_attr($item, 'markitup');  
		$item   = esc_html($item, 'markitup');  

		$selected = ($value==$val) ? 'selected="selected"' : '';  
		echo "<option value='$val' $selected>$item</option>";  
	}  
	echo "</select>";  
}

function markitup_admin_init(){
	add_settings_section('markitup',
		'MarkItUp! Settings',
		'markitup_settings_desc',
		'writing');

	// Image Alignment
	add_settings_field('markitup_imagealign',
		'Image Alignment',
		'markitup_imagealign',
		'writing',
		'markitup');
	register_setting('writing','markitup_imagealign');
}
add_action( 'admin_init', 'markitup_admin_init' );

// Profile
add_action( 'show_user_profile', 'markitup_extra_user_profile_fields' );
add_action( 'edit_user_profile', 'markitup_extra_user_profile_fields' );

function markitup_extra_user_profile_fields( $user ) {
	$markdown = get_the_author_meta( "use_markdown", $user->ID );
	?>
	<h3><?php _e("Editing", "blank"); ?></h3>
	<p>
		<h4><label for="use_markdown">
			<input type="checkbox" name="use_markdown" id="use_markdown" <?php if($markdown === "on"){ echo ' checked="checked"'; } ?>/>
			Use Markdown
		</label></h4>
		Markdown is a syntax for writing and seperates HTML and your writing. Enable this to automatically switch to using Markdown for new posts (existing posts will not be touched)
	</p>
	<?php
}

add_action( 'personal_options_update', 'markitup_save_extra_user_profile_fields' );
add_action( 'edit_user_profile_update', 'markitup_save_extra_user_profile_fields' );

function markitup_save_extra_user_profile_fields( $user_id ) {

	if ( !current_user_can( 'edit_user', $user_id ) ) { return false; }

	update_user_meta( $user_id, 'use_markdown', $_POST['use_markdown'] );
}
