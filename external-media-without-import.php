<?php
/** 
 *  External Media without Import
 *	
 *  Add external images to the media library without importing, i.e. uploading them to your WordPress site.
 * 
 *  @see https://github.com/jrxpress/external-media-without-import
 *  @see https://github.com/zzxiang/external-media-without-import
 *  @see https://jrxpress.com
 * 
 *  @package jrXtube
 *  @subpackage Extensions
 *  @since jrXtube 1.1
 *
 */

namespace emwi;

add_action( 'admin_enqueue_scripts', 'emwi\init_emwi' );
add_action( 'admin_menu', 'emwi\add_submenu' );
add_action( 'post-plupload-upload-ui', 'emwi\post_upload_ui' );
add_action( 'post-html-upload-ui', 'emwi\post_upload_ui' );
add_action( 'wp_ajax_add_external_media_without_import', 'emwi\wp_ajax_add_external_media_without_import' );
add_action( 'admin_post_add_external_media_without_import', 'emwi\admin_post_add_external_media_without_import' );

function init_emwi() {	
	$style = 'emwi-css';
  #	$css_file = plugins_url( '/external-media-without-import.css', __FILE__ );
	$css_file = get_template_directory_uri().'/external-media-without-import.css';
	wp_register_style( $style, $css_file );
  #	wp_register_style( 'emwi-css', trailingslashit(get_template_directory_uri()).'css/external-media-without-import.css' );
	wp_enqueue_style( $style );
	$script = 'emwi-js';
  #	$js_file = plugins_url( '/external-media-without-import.js', __FILE__ );
	$js_file = get_template_directory_uri().'/external-media-without-import.js';
	wp_register_script( $script, $js_file, array( 'jquery' ) );
  #	wp_register_script( 'emwi-js', trailingslashit(get_template_directory_uri()).'js/external-media-without-import.js', array('jquery') );
	wp_enqueue_script( $script );	
}

function add_submenu() {
	add_submenu_page(
		'upload.php',
		__( 'Add External Media without Import' ),
		__( 'Add External Media without Import' ),
		'manage_options',
		'add-external-media-without-import',
		'emwi\print_submenu_page'
	);
}

function post_upload_ui() {
	$media_library_mode = get_user_option( 'media_library_mode', get_current_user_id() );
?>
	<div id="emwi-in-upload-ui">
	  <div class="row1">
		<?php echo __('or'); ?>
	  </div>
	  <div class="row2">
<?php /*
		<?php if ( 'grid' === $media_library_mode ) : // FIXME: seems that media_library_mode being empty also means grid mode ?>
		  <button id="emwi-show" class="button button-large">
			<?php echo __('Add External Media without Import'); ?>
		  </button>
		  <?php print_media_new_panel( true ); ?>
		<?php else : ?>
 */ ?>
		  <a class="button button-large" href="<?php echo esc_url( admin_url( '/upload.php?page=add-external-media-without-import', __FILE__ ) ); ?>">
			<?php echo __('Add External Media without Import'); ?>
		  </a>
		<?php // endif; ?>
	  </div>
	</div>
<?php
}

function print_submenu_page() {
?>
	<form action="<?php echo esc_url( admin_url('admin-post.php') ); ?>" method="post">
	  <?php print_media_new_panel( false ); ?>
	</form>
<?php
}

function print_media_new_panel( $is_in_upload_ui ) {
?>
	<div id="emwi-media-new-panel" <?php if ( $is_in_upload_ui  ) : ?>style="display: none"<?php endif; ?>>
      <label id="emwi-urls-label"><?php echo __('Add medias from URLs'); ?></label>
      <textarea id="emwi-urls" rows="<?php echo $is_in_upload_ui ? 3 : 10 ?>" name="urls" required placeholder="<?php echo __("Please fill in the media URLs.\nMultiple URLs are supported with each URL specified in one line.");?>" value="<?php echo esc_url( $_GET['urls'] ); ?>"></textarea>
	  <div id="emwi-hidden" <?php if ( $is_in_upload_ui || empty( $_GET['error'] ) ) : ?>style="display: none"<?php endif; ?>>
		<div>
		  <span id="emwi-error"><?php echo esc_html( $_GET['error'] ); ?></span>
		  <?php echo _('Please fill in the following properties manually. If you leave the fields blank (or 0 for width/height), the plugin will try to resolve them automatically'); ?>
		</div>
		<div id="emwi-properties">
		  <label><?php echo __('Width'); ?></label>
		  <input id="emwi-width" name="width" type="number" value="<?php echo esc_html( $_GET['width'] ); ?>">
		  <label><?php echo __('Height'); ?></label>
		  <input id="emwi-height" name="height" type="number" value="<?php echo esc_html( $_GET['height'] ); ?>">
		  <label><?php echo __('MIME Type'); ?></label>
		  <input id="emwi-mime-type" name="mime-type" type="text" value="<?php echo esc_html( $_GET['mime-type'] ); ?>">
		</div>
	  </div>
	  <div id="emwi-buttons-row">
		<input type="hidden" name="action" value="add_external_media_without_import">
		<span class="spinner"></span>
		<input type="button" id="emwi-clear" class="button" value="<?php echo __('Clear') ?>">
		<input type="submit" id="emwi-add" class="button button-primary" value="<?php echo __('Add') ?>">
		<?php if ( $is_in_upload_ui ) : ?>
		  <input type="button" id="emwi-cancel" class="button" value="<?php echo __('Cancel') ?>">
		<?php endif; ?>
	  </div>
	</div>
<?php
}

function wp_ajax_add_external_media_without_import() {
	$info = add_external_media_without_import();
	$attachment_ids = $info['attachment_ids'];
	if ( $attachment = wp_prepare_attachment_for_js( $info['id'] ) ) {
		wp_send_json_success( $attachment );
	}
	else {
		$info['error'] = _('Failed to prepare attachment for js');
		wp_send_json_error( $info );
	}
}
function admin_post_add_external_media_without_import() {
	$info = add_external_media_without_import();
	$redirect_url = 'upload.php';
	$urls = $info['urls'];
	if ( ! empty( $urls ) ) {
		$redirect_url = $redirect_url . '?page=add-external-media-without-import&urls=' . urlencode( $urls );
		$redirect_url = $redirect_url . '&error=' . urlencode( $info['error'] );
		$redirect_url = $redirect_url . '&width=' . urlencode( $info['width'] );
		$redirect_url = $redirect_url . '&height=' . urlencode( $info['height'] );
		$redirect_url = $redirect_url . '&mime-type=' . urlencode( $info['mime-type'] );
	}
	wp_redirect( admin_url( $redirect_url ) );
	exit;
}

function sanitize_and_validate_input() {
	$raw_urls = explode( "\n", $_POST['urls'] );
	$urls = array();
	foreach ( $raw_urls as $i => $raw_url ) {
		// Don't call sanitize_text_field on url because it removes '%20'.
		// Always use esc_url/esc_url_raw when sanitizing URLs. See:
		// https://codex.wordpress.org/Function_Reference/esc_url
		$urls[$i] = esc_url_raw( trim( $raw_url ) );
	}
    unset( $url );  // break the reference with the last element
	$input = array(
		'urls' =>  $urls,
		'width' => sanitize_text_field( $_POST['width'] ),
		'height' => sanitize_text_field( $_POST['height'] ),
		'mime-type' => sanitize_mime_type( $_POST['mime-type'] )
	);
	$width_str = $input['width'];
	$width_int = intval( $width_str );
	if ( ! empty( $width_str ) && $width_int <= 0 ) {
		$input['error'] = _('Width and height must be non-negative integers.');
		return $input;
	}
	$height_str = $input['height'];
	$height_int = intval( $height_str );
	if ( ! empty( $height_str ) && $height_int <= 0 ) {
		$input['error'] = _('Width and height must be non-negative integers.');
		return $input;
	}
	$input['width'] = $width_int;
	$input['height'] = $height_int;
	return $input;
}

function add_external_media_without_import() {
	$input = sanitize_and_validate_input();
	if ( isset( $input['error'] ) ) {
		return $input;
	}
	$urls = $input['urls'];
	$width = $input['width'];
	$height = $input['height'];
	$mime_type = $input['mime-type'];
	$attachment_ids = array();
	$failed_urls = array();
	foreach ( $urls as $url ) {
		if ( empty( $width ) || empty( $height ) ) {
			$image_size = @getimagesize( $url );
			if ( empty( $image_size ) ) {
				array_push( $failed_urls, $url );
				continue;
			}
			$width_of_the_image = empty( $width ) ? $image_size[0] : $width;
			$height_of_the_image = empty( $height ) ? $image_size[1] : $height;
			$mime_type_of_the_image = empty( $mime_type ) ? $image_size['mime'] : $mime_type;
		} elseif ( empty( $mime_type ) ) {
			$response = wp_remote_head( $url );
			if ( is_array( $response ) && isset( $response['headers']['content-type'] ) ) {
				$width_of_the_image = $width;
				$height_of_the_image = $height;
				$mime_type_of_the_image = $response['headers']['content-type'];
			} else {
				continue;
			}
		}
		$filename = wp_basename( $url );
		$attachment = array(
			'guid' => $url,
			'post_mime_type' => $mime_type_of_the_image,
			'post_title' => preg_replace( '/\.[^.]+$/', '', $filename ),
		);
		$attachment_metadata = array(
			'width' => $width_of_the_image,
			'height' => $height_of_the_image,
			'file' => $filename );
		$attachment_metadata['sizes'] = array( 'full' => $attachment_metadata );
		$attachment_id = wp_insert_attachment( $attachment );
		wp_update_attachment_metadata( $attachment_id, $attachment_metadata );
		array_push( $attachment_ids, $attachment_id );
	}
	$input['attachment_ids'] = $attachment_ids;
	$failed_urls_string = implode( "\n", $failed_urls );
	$input['urls'] = $failed_urls_string;
	if ( ! empty( $failed_urls_string ) ) {
		$input['error'] = 'Failed to get info of the images.';
	}
	return $input;
}
