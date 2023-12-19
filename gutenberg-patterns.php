<?php
/*
Plugin Name: Gutenberg patterns admin UI
Description: Enhanced admin UI for Gutenberg Patterns (reusable blocks)
Version:  1.0
Plugin URI:
Author: Attila Seres
Author URI: https://github.com/lophas
*/
if(!class_exists('gutenberg_patterns')) :
class gutenberg_patterns {
	private $settings;
    const META_KEY = 'wp_pattern_sync_status';
    const POST_TYPE = 'wp_block';
	const COLUMN_NAME = 'sync-status';
	function __construct() {
        add_action('init', [$this, 'init']);
        add_action( 'registered_post_type_'.self::POST_TYPE, [$this, 'registered_post_type'], 10, 2 );
    }
    function registered_post_type( $post_type, $post_type_object ) {
        global $wp_post_types;
        $wp_post_types[$post_type]->_builtin = false;
        $wp_post_types[$post_type]->show_ui = true;
        $wp_post_types[$post_type]->show_in_menu = true;
        $wp_post_types[$post_type]->show_in_admin_bar = true;
        $wp_post_types[$post_type]->menu_icon = 'dashicons-block-default';
        $wp_post_types[$post_type]->menu_position = 20;
    }
    function init (){
        if(!is_admin()) return;
        add_action('add_meta_boxes_'.self::POST_TYPE, [$this, 'add_meta_boxes'],1 );
        add_action( 'save_post_'.self::POST_TYPE, [$this, 'save_post'], 20, 3);

        add_action( 'admin_head', [$this, 'admin_head']);
        add_filter('manage_'.self::POST_TYPE.'_posts_columns', [$this, 'column_name'], 10);
        add_action('manage_'.self::POST_TYPE.'_posts_custom_column', [$this, 'column_data'], 10, 2);
        add_filter( 'classic_editor_enabled_editors_for_post_type', [$this, 'disable_classic_editor'], 10, 2);//just in case
    }
    function disable_classic_editor($editors, $post_type ) {
        if(in_array($post_type, [self::POST_TYPE])) {
            unset($editors['classic_editor']);
        }
        return $editors;
    }
	function column_name($columns){
        $columns[self::COLUMN_NAME] = __('Sync');
        return $columns;
    }
    function column_data($column, $post_id){
        if($column !== self::COLUMN_NAME) return;
        $value = get_post_meta($post_id, self::META_KEY, true);
        if($value == 'unsynced') echo '<span title="'.esc_attr(__('Not synced')).'" class="dashicons dashicons-editor-unlink"></span>';
        else echo '<span title="'.esc_attr(__('Fully synced')).'" class="dashicons dashicons-admin-links"></span>';
    }
    function add_meta_boxes( $post){
        add_meta_box(
            'patternsync',
            __( 'Sync status' ),
            [$this, 'editor_checkbox'],
            self::POST_TYPE,
            'side',
            'high'
        );
    }
    function editor_checkbox($post) {
        $synced = get_post_meta( $post->ID, self::META_KEY, true ) !== 'unsynced';
        ?>
        <div class="misc-pub-section"><span class="dashicons dashicons-<?php echo ($synced ? 'admin-links' : 'editor-unlink') ?>"></span>&nbsp;<input type="checkbox" name="<?php echo self::META_KEY ?>" <?php checked($synced) ?> /> <?php echo ($synced ? __('Fully synced') : __('Not synced')) ?></div>
        <?php
    }
    function save_post($post_id, $post, $update) {
      if ( ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) || !isset($_REQUEST)) return;
	  if(!in_array($_REQUEST['action'], ['editpost','inline-save'])) return;
          if(!$_REQUEST[self::META_KEY]) update_post_meta($post_id, self::META_KEY, 'unsynced');
          else delete_post_meta($post_id, self::META_KEY);
    }
	function admin_head() {
		?><style>
			.post-type-<?php echo self::POST_TYPE ?> .column-<?php echo self::COLUMN_NAME ?> {width:50px}
			.post-type-<?php echo self::POST_TYPE ?> .edit-post-sync-status {display:none}
		</style><?php
	}
}
new gutenberg_patterns;
endif;

