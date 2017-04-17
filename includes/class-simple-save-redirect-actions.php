<?php
/**
 * The file that defines the core plugin class
 */

/**
 * Use $_SESSION store actions / URL
 */
add_action('init', 'session_on');
function session_on(){
	if ( session_id() == '' || (function_exists('session_status') && PHP_SESSION_NONE == session_status()) ) {
	    session_start();
	}
}

class Simple_Save_Redirect_Button_Actions {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name 	= $plugin_name;
		$this->version 		= $version;
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {
		wp_enqueue_style( $this->plugin_name, plugin_dir_url( dirname(__FILE__) ) . 'css/style.css', array(), $this->version, 'all' );
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
		wp_enqueue_script( $this->plugin_name, plugin_dir_url( dirname(__FILE__) ) . 'js/app.js', array( 'jquery' ), $this->version, false );
		wp_enqueue_script( $this->plugin_name . '_scroll', plugin_dir_url( dirname(__FILE__) ) . 'js/scroll.js', array( 'jquery' ), $this->version, false );
		
		// Assign JavaScript Object
		wp_localize_script( $this->plugin_name, 'ssrb_actions', array( 'dropdown_menu' => $this->front_end_dropdown_menu() ) );
	}

	/**
	 * "Save and List" action: when saved post redirect to the post list page
	 */
	private function get_post_list_direct_url($post_id){

		if ( $_SESSION['ssrb_last_post_list_url'] ){

			parse_str( parse_url( $_SESSION['ssrb_last_post_list_url'] , PHP_URL_QUERY ), $query_string );

			if ( isset($query_string['post_type']) && $query_string['post_type'] == get_post_type($post_id) ){
				return $new_location = $_SESSION['ssrb_last_post_list_url'];
			}else if ( !isset($query_string['post_type']) && get_post_type($post_id) == 'post' ){
				return $new_location = $_SESSION['ssrb_last_post_list_url'];
			}

		}

		return $new_location = admin_url( 'edit.php?post_type=' . get_post_type($post_id) );
	}

	/**
	 * "Save and Previous" action: when saved post redirect to the previous post/page
	 * "Save and Next" action: when saved post redirect to the next post/page
	 */
	private function get_adjacent_post_direct_url($post_id, $dir) {

		$query_string = $this->get_session_query_string();

		if ( 'page' == get_post_type( $post_id ) ){
			return $this->get_adjacent_page_url($post_id, $dir, $query_string['orderby'], $query_string['order']);
		}else{
			return $this->get_orderby_post_url($post_id, $dir, $query_string['orderby'], $query_string['order']);
		}
	}

	/**
	 * "Save and New" action: when saved post redirect to new post
	 */
	private function get_new_post_direct_url($post_id) {
		
		$post_type = get_post_type( $post_id );

		if ( 'post' != $post_type ) {
			return $new_location = admin_url("post-new.php?post_type=$post_type");
		}else{
			return $new_location = admin_url('post-new.php');
		}
	}

	/**
	 * "Save and Scroll" action: when saved post redirect to post list and scroll to last post
	 */
	private function get_post_list_scroll_direct_url($post_id) {
		$list_url = $this->get_post_list_direct_url($post_id);

		if ( $list_url ){
			parse_str( parse_url( $list_url , PHP_URL_QUERY ), $query_string );
			if ( $query_string ){
				$list_url .= '&scrollto='.$post_id;
			}else{
				$list_url .= '?scrollto='.$post_id;
			}
			
		}else{
			$list_url = FALSE;
		}

		return $list_url;
	}

	/**
	 * Provide a admin area view for the plugin
	 * This function is used to markup the admin-facing aspects of the plugin.
	 *
	 * @since    1.0.0
	 */
	private function front_end_dropdown_menu(){

		$ret = array();
		$dropdown_menus = array( 'list'=>'%s and List', 'scroll'=>'%s and Scroll', 'new'=>'%s and New', 'prev'=>'%s and Previous', 'next'=>'%s and Next' );

		$last_action = '';
		if ( isset($_SESSION['ssrb_last_action']) && $_SESSION['ssrb_last_action'] ){
			$last_action = $_SESSION['ssrb_last_action'];
		}
		
		foreach ($dropdown_menus as $k => $v){

			$is_last_action = 0;

			if ( $last_action && $last_action == $k ){
				$is_last_action = 1;
			}
			else if ( !$last_action && $last_action == 'list' ){
				$is_last_action = 1;
			}

			$classes = $this->is_available($k) === 0 ? 'disabled' : '';

			$ret[$k] = '<li class="'.$classes.'" data-ssrb-action="'.$k.'" data-ssrb-last-action="'.$is_last_action.'">'.$v.'</li>';
		}

		return $ret;
	}

	/**
	 * Check current Post available actions
	 *
	 * @since    1.0.0
	 * @param 	 string $dir previous|next
	 */
	private function is_available( $dir=false ){
		global $post;

		if ( !$post ) return 0;

		if ( in_array($dir, array('list','new', 'scroll')) ) return 1;

		$query_string = $this->get_session_query_string();

		if ( 'page' == $post->post_type ){
			$adjacent_id = $this->get_adjacent_page_id($post->ID, $dir, $query_string['orderby'], $query_string['order']);
		}else{
			if ( $query_string['orderby'] ){
				$adjacent_id = $this->get_orderby_adjacent_post_id($post->ID, $dir, $query_string['orderby'], $query_string['order']);
			}else{
				$adjacent_id = $this->get_reg_adjacent_post_id($post->ID, $dir);
			}
		}

		return $adjacent_id ? 1 : 0;
	}

	/**
	 * Save current post list url and parameters in SESSION.
	 * When user selected Save and List, it will redirect to this page if current post type match this post list.
	 *
	 * @param  WP_Screen $wp_screen
	 * WP Action current_screen
	 */
	public function current_screen( $current_screen ) {
		if( $current_screen->base == 'edit' ) {

			$admin_url = admin_url('edit.php');

			if( $_SERVER['QUERY_STRING'] ) {
				$admin_url .= '?' . $_SERVER['QUERY_STRING'];
			}
			$_SESSION['ssrb_last_post_list_url'] = $admin_url;
		}
	}

	/**
	 * Return redirect URL.
	 * If user selected action available, we will return the URL.
	 * otherwise return default URL. 
	 *
	 * @param 	$location 	string 	The destination URL.
	 * @param 	$post_id 	ini 	The post ID.
	 *
	 * WP Filter redirect_post_location:
	 * Filters the post redirect destination URL.
	 */
	public function redirect_post_location( $location, $post_id ) {

		/**
		 * @var string	Post actions: post, edit, delete, etc.
		 */
		global $action;

		/**
		* Return default URL if action isn't Save or Publish
		*/
		if( ! isset( $_POST['save'] ) && ! isset( $_POST['publish'] ) ) {
			return $location;
		}

		/**
		 * Return default URL if none $action and isn't come from edit post
		 */
		if( ! isset( $action ) || $action != 'editpost' ) {
			return $location;
		}

		/**
		 * Return default URL if not set action param
		 */
		if ( ! isset( $_POST['ssrb-action-input'] ) ) {
			return $location;
		}

		/**
		 * Return default URL if none post ID
		 */
		if ( ! $post_id || $post_id === 0 ){
			return $location;
		}

		/**
		 * @var string 		user selected actions
		 */
		$ssrb_action  = $_POST['ssrb-action-input'];

		/**
		 * @var bool 		new redirect url if has 	
		 */
		$new_location = FALSE;

		/**
		 * get new URL with user selected action
		 */
		switch ($ssrb_action) {
			case 'list' :
				$new_location = $this->get_post_list_direct_url($post_id);
				break;
			case 'prev' :
				$new_location = $this->get_adjacent_post_direct_url($post_id, 'prev');
				break;
			case 'next' :
				$new_location = $this->get_adjacent_post_direct_url($post_id, 'next');
				break;
			case 'new' :
				$new_location = $this->get_new_post_direct_url($post_id);
				break;
			case 'scroll' :
				$new_location = $this->get_post_list_scroll_direct_url($post_id);
				break;
		}

		if ( $new_location ){
			$_SESSION['ssrb_last_action'] = $ssrb_action;
			return $new_location;
		}

		return $location;
	}

	/**
	 * Return orderby, order params from post list which is stored on $_SESSION
	 *
	 * @return array
	 */
	private function get_session_query_string(){
		$query_string 	= array();
		$orderby 		= FALSE;
		$order 			= FALSE;

		if ( isset($_SESSION['ssrb_last_post_list_url']) ){
			parse_str( parse_url( $_SESSION['ssrb_last_post_list_url'] , PHP_URL_QUERY ), $query_string );

			$orderby = isset( $query_string['orderby'] ) && !empty($query_string['orderby']) ? $query_string['orderby'] : FALSE;
			$order 	 = isset( $query_string['order'] ) && !empty($query_string['order']) ? $query_string['order'] : FALSE;
		}

		return array('orderby'=>$orderby, 'order'=>$order);
	}

	/**
	 * Return previous/next post ID without sort query: orderby.
	 *
	 * If not a previous/next post, return false
	 *
	 * @return ini|false
	 */
	private function get_reg_adjacent_post_id($post_id, $dir) {

		global $post;
		$post = get_post($post_id);

		if ( $dir == 'prev' ){

			$adjacent_post = get_previous_post();

		}else if ( $dir == 'next' ){

			$adjacent_post = get_next_post();
		}

		return $adjacent_post ? $adjacent_post->ID : FALSE;
	}

	/**
	 * Return post URL
	 *
	 * If not a previous/next post, return false
	 *
	 * @return string|false
	 */
	private function get_orderby_post_url($post_id, $dir, $orderby=false, $order=false) {

		if ( $orderby )
			$adjacent_id = $this->get_orderby_adjacent_post_id($post_id, $dir, $orderby, $order);
		else
			$adjacent_id = $this->get_reg_adjacent_post_id($post_id, $dir);

		return $adjacent_id ? admin_url('post.php?post='.$adjacent_id.'&action=edit') : FALSE;
	}

	/**
	 * Return previous/next post ID with sort query: orderby.
	 *
	 * If not a previous/next post, return false
	 *
	 * @return ini|false
	 */
	private function get_orderby_adjacent_post_id($post_id, $dir, $orderby, $order){
		global $wpdb, $post;

		$post = get_post($post_id);

		if ( $dir == 'prev' ){
			$op = ($order == 'asc') ? '>' : '<';
			$order 	= $order == 'asc' ? 'ASC' : 'DESC';
		}else{
			$op = ($order == 'asc') ? '<' : '>';
			$order 	= $order == 'asc' ? 'DESC' : 'ASC';
		}

		$query = '';

		switch ($orderby){
			case 'date':
				$query = $wpdb->prepare("SELECT p.ID FROM {$wpdb->prefix}posts AS p WHERE p.post_date $op %s AND p.post_type = %s AND p.post_status = 'publish' ORDER BY p.post_date $order LIMIT 1", $post->post_date, $post->post_type);
				break;

			case 'title':
				$query = $wpdb->prepare("SELECT p.ID FROM {$wpdb->prefix}posts AS p WHERE p.post_title $op %s AND p.post_type = %s AND p.post_status = 'publish' ORDER BY p.post_title $order LIMIT 1", $post->post_title, $post->post_type);
				break;

			default:
				return FALSE;
		}

		$result = $wpdb->get_var( $query );

		if ( null === $result ){
			$result = FALSE;
		}

		if ( $result ){
			$result = get_post( $result );
			return $result->ID;
		}

		return FALSE;
	}

	/**
	 * Check orderby query available with this plugin
	 *
	 * @return bool
	 */
	private function available_orderby($orderby) {

		if ( in_array( $orderby, array('title', 'date', 'paged') ) ){
			return TRUE;
		}

		return FALSE;
	}

	/**
	 * Return page URL
	 *
	 * If not a previous/next page, return false
	 *
	 * @return string|false
	 */
	private function get_adjacent_page_url($post_id, $dir, $orderby=false, $order=false){

		$adjacent_id = $this->get_adjacent_page_id($post_id, $dir, $orderby, $order);
		return $adjacent_id ? admin_url('post.php?post='.$adjacent_id.'&action=edit') : FALSE;
	}

	/**
	 * Return previous/next page ID
	 *
	 * If not a previous/next page, return false
	 *
	 * @return ini|false
	 */
	private function get_adjacent_page_id($post_id, $dir, $orderby=false, $order=false){
		global $wpdb, $post;

		$post = get_post($post_id);
		$result = null;

		if ( $orderby && ($orderby == 'date' || $orderby == 'title') ){
			// Order by orderby date/title
			if ( $dir == 'prev' ){
				$op = ($order == 'asc') ? '>' : '<';
				$order 	= $order == 'asc' ? 'ASC' : 'DESC';
			}else{
				$op = ($order == 'asc') ? '<' : '>';
				$order 	= $order == 'asc' ? 'DESC' : 'ASC';
			}

			if ( $orderby == 'date' )
				$query = $wpdb->prepare("SELECT p.ID FROM {$wpdb->prefix}posts AS p WHERE p.post_date $op %s AND p.post_type = %s AND p.post_status = 'publish' ORDER BY p.post_date $order LIMIT 1", $post->post_date, $post->post_type);
			else if ( $orderby == 'title' )
				$query = $wpdb->prepare("SELECT p.ID FROM {$wpdb->prefix}posts AS p WHERE p.post_title $op %s AND p.post_type = %s AND p.post_status = 'publish' ORDER BY p.post_title $order LIMIT 1", $post->post_title, $post->post_type);

		}else{
			// Order by menu_order
			$op 	= ($dir == 'prev') ? '>' : '<';
			$order 	= ($dir == 'prev') ? 'ASC' : 'DESC';
			$query 	= '';
			$query 	= $wpdb->prepare( "SELECT p.ID FROM {$wpdb->prefix}posts AS p WHERE p.menu_order $op %d AND p.post_type = 'page' AND p.post_status = 'publish' AND p.ID != %d ORDER BY p.menu_order $order, p.post_title $order LIMIT 1", $post->menu_order, $post->ID );
		}

		$result = $wpdb->get_var( $query );

		if ( null === $result ){
			$result = FALSE;
		}

		if ( $result ){
			$result = get_post( $result );
			return $result->ID;
		}

		return FALSE;
	}
}

?>