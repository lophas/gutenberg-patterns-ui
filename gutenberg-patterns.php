<?php
/*
 * Plugin Name: Gutenberg Patterns Admin UI
 * Plugin URI: https://github.com/lophas/gutenberg-patterns-ui
 * GitHub Plugin URI: https://github.com/lophas/gutenberg-patterns-ui
 * Description: Enhanced admin UI for Gutenberg Patterns (reusable blocks)
 * Version: 1.7
 * Author: Attila Seres
 * Author URI:  https://github.com/lophas
 * License: GPLv3
*/
if(!class_exists('gutenberg_patterns')) :
class gutenberg_patterns {
    const META_KEY = 'wp_pattern_sync_status';
    const POST_TYPE = 'wp_block';
    const COLUMN_NAME = 'sync-status';
    const METABOX_ID = 'patternsync';
    private $ajax_action;
    public function __construct() {
        add_action( 'init', [$this, 'init']);
        add_action( 'registered_post_type_'.self::POST_TYPE, [$this, 'registered_post_type'], 10, 2 );
        $this->ajax_action = str_replace('-', '_', self::METABOX_ID).'_update';
        add_action( 'wp_ajax_'.$this->ajax_action, [$this,'do_ajax']);
    }
    public function registered_post_type( $post_type, $post_type_object ) {
/*
        //enable main menu item
        global $wp_post_types;
        $wp_post_types[$post_type]->_builtin = false;
        $wp_post_types[$post_type]->show_ui = true;
        $wp_post_types[$post_type]->show_in_menu = true;
        $wp_post_types[$post_type]->show_in_admin_bar = true;
        $wp_post_types[$post_type]->menu_icon = 'dashicons-block-default';
        $wp_post_types[$post_type]->menu_position = 20;
*/
        add_post_type_support($post_type, 'author');
    }
    public function init (){
        if(!is_admin()) return;
        add_action( 'admin_menu', [$this, 'admin_menu'], 9); //add submenu item instead of main menu
        add_action( 'add_meta_boxes_'.self::POST_TYPE, [$this, 'add_meta_boxes'],1 );
        add_action( 'save_post_'.self::POST_TYPE, [$this, 'save_post'], 20, 3);
        add_action( 'admin_head', [$this, 'admin_head']);
        add_filter( 'manage_'.self::POST_TYPE.'_posts_columns', [$this, 'column_name'], 10);
        add_action( 'manage_'.self::POST_TYPE.'_posts_custom_column', [$this, 'column_data'], 10, 2);
    }
    public function admin_menu(){
        add_submenu_page( 'themes.php', __('Patterns'),  __('Patterns'), 'edit_theme_options', 'edit.php?post_type='.self::POST_TYPE, null, null );
    }
    private function is_synced($post_id) {
	return get_post_meta( $post_id, self::META_KEY, true ) !== 'unsynced';
    }
    public function column_name($columns){
        $columns[self::COLUMN_NAME] = __('Sync');
        return $columns;
    }
    public function column_data($column, $post_id){
        if($column !== self::COLUMN_NAME) return;
        if($this->is_synced($post_id)) echo '<span title="'.esc_attr(__('Fully synced')).'" class="dashicons dashicons-admin-links"></span>';
        else echo '<span title="'.esc_attr(__('Not synced')).'" class="dashicons dashicons-editor-unlink"></span>';
    }
    public function add_meta_boxes( $post){
        add_meta_box(
            self::METABOX_ID,
            __( 'Sync status' ),
            [$this, 'editor_checkbox'],
            self::POST_TYPE,
            'side',
            'high'
        );
        add_action( 'admin_footer', [$this, 'admin_footer']);
    }
    public function editor_checkbox($post) {
        $synced = $this->is_synced($post->ID);
        ?>
        <div class="misc-pub-section"><span class="dashicons dashicons-<?php echo ($synced ? 'admin-links' : 'editor-unlink') ?>"></span>&nbsp;<input type="checkbox" name="<?php echo self::META_KEY ?>" <?php checked($synced) ?> /> <?php echo ($synced ? __('Fully synced') : __('Not synced')) ?></div>
        <?php
    }
    public function save_post($post_id, $post, $update) {
	    if ( ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) || !isset($_REQUEST)) return;
	    if(!in_array($_REQUEST['action'], ['editpost','inline-save'])) return;
	    if(!$_REQUEST[self::META_KEY]) update_post_meta($post_id, self::META_KEY, 'unsynced');
	    else delete_post_meta($post_id, self::META_KEY);
    }
    public function admin_head() {
	    ?><style>
		    .post-type-<?php echo self::POST_TYPE ?> .column-<?php echo self::COLUMN_NAME ?> {width:50px}
		    .post-type-<?php echo self::POST_TYPE ?> .edit-post-sync-status {display:none}
	    </style><?php
    }
    public function admin_footer(){
?><script>
const <?php echo $this->ajax_action ?> = ( function(){
const isSavingMetaBoxes = wp.data.select( 'core/edit-post' ).isSavingMetaBoxes;
var wasSaving = false;
return {
    refreshMetabox: function(){
        var isSaving = isSavingMetaBoxes();
        if ( wasSaving && ! isSaving ) {
        var data = {
            post_id:  <?php echo $GLOBALS['post']->ID ?>,
            action: '<?php echo $this->ajax_action ?>',
            nonce: '<?php echo wp_create_nonce( $this->ajax_action ) ?>'
        };
        jQuery.post(ajaxurl, data, function(result) {
            if(result.length > 0) {
                jQuery('#<?php echo self::METABOX_ID ?> .inside').html(result);
            }
        }, "html");
        }
        wasSaving = isSaving;
    },
}
})();
wp.data.subscribe( <?php echo $this->ajax_action ?>.refreshMetabox );
</script><?php
    }
    public function do_ajax() {
        if (!check_ajax_referer($this->ajax_action, 'nonce', false)) {
            die('nonce error');
        }
        if($post_id = $_POST['post_id']) {
            if($post = get_post($post_id)) $this->editor_checkbox($post);
        }
     	exit(); // this is required to return a proper result & exit is faster than die();
    }
}
new gutenberg_patterns;
endif;
