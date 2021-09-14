<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://www.emirugljanin.com
 * @since      1.0.0
 *
 * @package    My_News_Plugin
 * @subpackage My_News_Plugin/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    My_News_Plugin
 * @subpackage My_News_Plugin/admin
 * @author     Emir Ugljanin <emirugljanin@gmail.com>
 */
class My_News_Plugin_Admin
{

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
	public function __construct($plugin_name, $version)
	{

		$this->plugin_name = $plugin_name;
		$this->version = $version;

		// Add an action to setup the admin menu in the left nav
		add_action('admin_menu', array($this, 'add_admin_menu'));
		// Add some actions to setup the settings we want on the wp admin page
		add_action('admin_init', array($this, 'setup_sections'));
		add_action('admin_init', array($this, 'setup_fields'));
		add_action('init', array($this, 'add_cpt'));

		add_filter('cron_schedules', array($this, 'add_custom_cron_interval'));

		if (!wp_next_scheduled('add_custom_cron_interval')) {
			wp_schedule_event(time(), 'every_three_minutes', 'add_custom_cron_interval');
		}
		add_action('add_custom_cron_interval', array($this, 'custom_cron_interval'));


		add_action('pre_get_posts', array($this, 'gp_add_cpt_post_names_to_main_query'));
	}

	public function add_custom_cron_interval($schedules)
	{

		$cron_interval = get_option('my_news_plugin_cron_interval', 1);
		$schedules['every_three_minutes'] = array(
			'interval'  => $cron_interval,
			'display'   => __('Cron interval', 'my-news-plugin')
		);
		return $schedules;
	}

	public function custom_cron_interval()
	{
		$this->fetchNews();
	}

	function gp_add_cpt_post_names_to_main_query($query)
	{

		if (is_admin() || !$query->is_main_query()) return;

		if (!is_admin() && in_array($query->get('post_type'), array('my-news-plugin'))) {
			return;
		}

		$query->set('post_type', array('post', 'page', 'my-news-plugin'));
	}

	public function fetchNews()
	{
		require_once(ABSPATH . 'wp-admin/includes/media.php');
		require_once(ABSPATH . 'wp-admin/includes/file.php');
		require_once(ABSPATH . 'wp-admin/includes/image.php');

		$api_key = get_option('my_news_plugin_api_key', 1);
		$search_phrase = get_option('my_news_plugin_search_phrase', 1);

		$api_url = 'https://newsapi.org/v2/everything?q=' . $search_phrase . '&sortBy=publishedAt&apiKey=' . $api_key;


		$response = wp_remote_get($api_url);
		if (is_wp_error($response)) {
			$error_message = $response->get_error_message();
			echo "Something went wrong: $error_message";
		} else {
			if (200 == wp_remote_retrieve_response_code($response)) {
				//if transient does not exist, load from url
				$body = wp_remote_retrieve_body($response);
				// set_transient('my_news_plugin_api_request', $body, intval($caching_time) * MINUTE_IN_SECONDS);
			} else {
?>
				<h1>An error occured while fetching users, please try again</h1>
		<?php
			}
		}

		$array = json_decode($body, true);

		if (is_array($array['articles'])) {
			foreach ($array['articles'] as $article) {
				$post_id = wp_insert_post(
					array(
						'post_title' => $article['title'],
						'post_content' => $article['description'],
						'post_status' => 'publish',
						'post_type'  => 'my-news-plugin',
						'meta_input'   => array(
							'wpt_source_name' => $article['source']['name'],
							'wpt_news_author' => $article['author'],
							'wpt_news_url' => $article['url'],
						),
					),
					true
				);
				$description = $article['title'];
				$result = media_sideload_image($article['urlToImage'], $post_id, $description);

				// then find the last image added to the post attachments
				$attachments = get_posts(array('numberposts' => '1', 'post_parent' => $post_id, 'post_type' => 'attachment', 'post_mime_type' => 'image', 'order' => 'ASC'));

				if (sizeof($attachments) > 0) {
					// set image as the post thumbnail
					set_post_thumbnail($post_id, $attachments[0]->ID);
				}
			}
		}
	}


	public function add_cpt()
	{

		$labels = array(
			'name'                  => _x('News', 'Post Type General Name', 'text_domain'),
			'singular_name'         => _x('News', 'Post Type Singular Name', 'text_domain'),
			'menu_name'             => __('News', 'text_domain'),
			'name_admin_bar'        => __('Edit Team', 'text_domain'),
			'archives'              => __('Аrchive', 'text_domain'),
			'parent_item_colon'     => __('Parent Item:', 'text_domain'),
			'all_items'             => __('All News', 'text_domain'),
			'add_new_item'          => __('Add New News', 'text_domain'),
			'add_new'               => __('Add New News', 'text_domain'),
			'new_item'              => __('New News', 'text_domain'),
			'edit_item'             => __('Edit News', 'text_domain'),
			'update_item'           => __('Update News', 'text_domain'),
			'view_item'             => __('View News', 'text_domain'),
			'search_items'          => __('Search', 'text_domain'),
			'not_found'             => __('Not Found', 'text_domain'),
			'not_found_in_trash'    => __('Not Found', 'text_domain'),
			'featured_image'        => __('Featured Image', 'text_domain'),
			'set_featured_image'    => __('Set featured image', 'text_domain'),
			'remove_featured_image' => __('Remove featured image', 'text_domain'),
			'use_featured_image'    => __('Use as featured image', 'text_domain'),
			'insert_into_item'      => __('Insert into item', 'text_domain'),
			'uploaded_to_this_item' => __('Uploaded to this item', 'text_domain'),
			'items_list'            => __('List of Cards', 'text_domain'),
			'items_list_navigation' => __('Items list navigation', 'text_domain'),
			'filter_items_list'     => __('Filter items list', 'text_domain'),
		);
		$args = array(
			'label'                 => __('Management Profiles', 'text_domain'),
			'description'           => __('anagement Profiles', 'text_domain'),
			'labels'                => $labels,
			'supports'              => array('title', 'editor', 'thumbnail',  'page-attributes'),
			'hierarchical'          => false,
			'public'                => true,
			'show_ui'               => true,
			'show_in_menu'          => true,
			'menu_position'         => 5,
			'menu_icon'             => 'dashicons-clipboard',
			'show_in_admin_bar'     => true,
			'show_in_nav_menus'     => true,
			'can_export'            => true,
			'has_archive'           => true,
			'exclude_from_search'   => false,
			'publicly_queryable'    => true,
			'capability_type'       => 'post',
			'capabilities' => array(
				'create_posts' => false, // Removes support for the "Add New" function ( use 'do_not_allow' instead of false for multisite set ups )
			),
			'map_meta_cap' => true, // Set to `false`, if users are not allowed to edit/delete existing posts
			'rewrite' 				=> array('slug' => '/news', 'with_front' => false),
			// 'register_meta_box_cb' => 'add_news_plugin_metaboxes',
			'register_meta_box_cb' => array($this, 'add_news_plugin_metaboxes'),
		);
		register_post_type('my-news-plugin', $args);
	}

	public function add_news_plugin_metaboxes()
	{

		add_meta_box(
			'wpt_news_author',
			'Author',
			array($this, 'wpt_news_author'),
			'my-news-plugin',
			'side',
			'default'
		);

		add_meta_box(
			'wpt_news_url',
			'URL',
			array($this, 'wpt_news_url'),
			'my-news-plugin',
			'normal',
			'high'
		);

		add_meta_box(
			'wpt_source_name',
			'Source name',
			array($this, 'wpt_news_source_name'),
			'my-news-plugin',
			'normal',
			'high'
		);
	}

	public function wpt_news_source_name($post)
	{ ?>

		<p>
			<label for="smashing-post-class"><?php _e("Source of the news", 'my-news-plugin'); ?></label>
			<br />
			<input class="widefat" type="text" name="wpt_source_name" id="wpt_source_name" value="<?php echo esc_attr(get_post_meta($post->ID, 'wpt_source_name', true)); ?>" size="30" />
		</p>
	<?php
	}

	public function wpt_news_url($post)
	{ ?>

		<p>
			<label for="smashing-post-class"><?php _e("News URL address", 'my-news-plugin'); ?></label>
			<br />
			<input class="widefat" type="text" name="wpt_source_name" id="wpt_source_name" value="<?php echo esc_attr(get_post_meta($post->ID, 'wpt_news_url', true)); ?>" size="30" />
		</p>
	<?php
	}

	public function wpt_news_author($post)
	{ ?>

		<p>
			<label for="smashing-post-class"><?php _e("News author", 'my-news-plugin'); ?></label>
			<br />
			<input class="widefat" type="text" name="wpt_source_name" id="wpt_source_name" value="<?php echo esc_attr(get_post_meta($post->ID, 'wpt_news_author', true)); ?>" size="30" />
		</p>
	<?php
	}


	public function add_cron_interval($schedules)
	{
		$schedules['five_minutes'] = array(
			'interval' => 300,
			'display'  => esc_html__('Every Five Minutes'),
		);
		return $schedules;
	}


	/**
	 * Add the menu items to the admin menu
	 *
	 * @since    1.0.0
	 */

	public function add_admin_menu()
	{

		// Main Menu Item
		add_menu_page(
			'My News Plugin',
			'My News Plugin',
			'manage_options',
			'my-news-plugin',
			array($this, 'display_my_news_plugin_page'),
			'dashicons-store',
			1
		);

		// Sub Menu Item One
		add_submenu_page(
			'my-news-plugin',
			'Settings',
			'Settings',
			'manage_options',
			'my-news-plugin',
			array($this, 'display_my_news_plugin_page')
		);
	}

	/**
	 * Callback for each section
	 *
	 * @since    1.0.0
	 */
	public function section_callback($arguments)
	{
		switch ($arguments['id']) {
			case 'settings':
				echo '<p>You can change the configuration of this plugin by changing options below.</p>';
				break;
		}
	}

	/**
	 * Field Configuration, each item in this array is one field/setting we want to capture
	 *
	 * @since    1.0.0
	 */
	public function setup_fields()
	{
		$fields = array(
			array(
				'uid' => 'my_news_plugin_endpoint',
				'label' => 'Endpoint',
				'section' => 'settings',
				'type' => 'text',
				'placeholder' => 'Add the slug that will be used to access the users page',
				'helper' => '',
				'supplemental' => 'Accepting lowercase letters and dashes.',
				'default' => "my-news-plugin",
			),
			array(
				'uid' => 'my_news_plugin_search_phrase',
				'label' => 'News must contain this search phrase',
				'section' => 'settings',
				'type' => 'text',
				'supplemental' => 'Keyword',
				'default' => "doge"
			),
			array(
				'uid' => 'my_news_plugin_api_key',
				'label' => 'news API KEY',
				'section' => 'settings',
				'type' => 'text',
				'supplemental' => 'String',
				'default' => ""
			),
			array(
				'uid' => 'my_news_plugin_cron_interval',
				'label' => 'Cron interval in seconds',
				'section' => 'settings',
				'type' => 'text',
				'supplemental' => 'Integer',
				'default' => "86400"
			)
		);
		// Lets go through each field in the array and set it up
		foreach ($fields as $field) {
			add_settings_field($field['uid'], $field['label'], array($this, 'field_callback'), 'wordpress-my-news-plugin-options', $field['section'], $field);
			register_setting('wordpress-my-news-plugin-options', $field['uid']);
		}
	}


	/**
	 * This handles all types of fields for the settings
	 *
	 * @since    1.0.0
	 */
	public function field_callback($arguments)
	{
		// Set our $value to that of whats in the DB
		$value = get_option($arguments['uid']);
		// Only set it to default if we get no value from the DB and a default for the field has been set
		if (!$value) {
			$value = $arguments['default'];
		}
		// Lets do some setup based ont he type of element we are trying to display.
		switch ($arguments['type']) {
			case 'text':
			case 'password':
			case 'number':
				printf('<input name="%1$s" id="%1$s" type="%2$s" placeholder="%3$s" value="%4$s" />', $arguments['uid'], $arguments['type'], $arguments['placeholder'], $value);
				break;
			case 'textarea':
				printf('<textarea name="%1$s" id="%1$s" placeholder="%2$s" rows="5" cols="50">%3$s</textarea>', $arguments['uid'], $arguments['placeholder'], $value);
				break;
			case 'select':
			case 'multiselect':
				if (!empty($arguments['options']) && is_array($arguments['options'])) {
					$attributes = '';
					$options_markup = '';
					foreach ($arguments['options'] as $key => $label) {
						$options_markup .= sprintf('<option value="%s" %s>%s</option>', $key, selected($value[array_search($key, $value, true)], $key, false), $label);
					}
					if ($arguments['type'] === 'multiselect') {
						$attributes = ' multiple="multiple" ';
					}
					printf('<select name="%1$s[]" id="%1$s" %2$s>%3$s</select>', $arguments['uid'], $attributes, $options_markup);
				}
				break;
			case 'radio':
			case 'checkbox':
				if (!empty($arguments['options']) && is_array($arguments['options'])) {
					$options_markup = '';
					$iterator = 0;
					foreach ($arguments['options'] as $key => $label) {
						$iterator++;
						$is_checked = '';
						// This case handles if there is only one checkbox and we don't have anything saved yet.
						if (isset($value[array_search($key, $value, true)])) {
							$is_checked = checked($value[array_search($key, $value, true)], $key, false);
						} else {
							$is_checked = "";
						}
						// Lets build out the checkbox
						$options_markup .= sprintf('<label for="%1$s_%6$s"><input id="%1$s_%6$s" name="%1$s[]" type="%2$s" value="%3$s" %4$s /> %5$s</label><br/>', $arguments['uid'], $arguments['type'], $key, $is_checked, $label, $iterator);
					}
					printf('<fieldset>%s</fieldset>', $options_markup);
				}
				break;
			case 'image':
				// Some code borrowed from: https://mycyberuniverse.com/integration-wordpress-media-uploader-plugin-options-page.html
				$options_markup = '';
				$image = [];
				$image['id'] = '';
				$image['src'] = '';

				// Setting the width and height of the header iamge here
				$width = '1800';
				$height = '1068';

				// Lets get the image src
				$image_attributes = wp_get_attachment_image_src($value, array($width, $height));
				// Lets check if we have a valid image
				if (!empty($image_attributes)) {
					// We have a valid option saved
					$image['id'] = $value;
					$image['src'] = $image_attributes[0];
				} else {
					// Default
					$image['id'] = '';
					$image['src'] = $value;
				}

				// Lets build our html for the image upload option
				$options_markup .= '
				<img data-src="' . $image['src'] . '" src="' . $image['src'] . '" width="180px" height="107px" />
				<div>
					<input type="hidden" name="' . $arguments['uid'] . '" id="' . $arguments['uid'] . '" value="' . $image['id'] . '" />
					<button type="submit" class="upload_image_button button">Upload</button>
					<button type="submit" class="remove_image_button button">&times; Delete</button>
				</div>';
				printf('<div class="upload">%s</div>', $options_markup);
				break;
		}
		// If there is helper text, lets show it.
		if (array_key_exists('helper', $arguments) && $helper = $arguments['helper']) {
			printf('<span class="helper"> %s</span>', $helper);
		}
		// If there is supplemental text lets show it.
		if (array_key_exists('supplemental', $arguments) && $supplemental = $arguments['supplemental']) {
			printf('<p class="description">%s</p>', $supplemental);
		}
	}

	/**
	 * Admin Notice
	 * 
	 * This displays the notice in the admin page for the user
	 *
	 * @since    1.0.0
	 */
	public function admin_notice($message)
	{ ?>
		<div class="notice notice-success is-dismissible">
			<p><?php echo ($message); ?></p>
		</div><?php
			}

			/**
			 * This handles setting up the rewrite rules for Past Sales
			 *
			 * @since    1.0.0
			 */
			public function setup_rewrites()
			{
				//
				// $url_slug = 'my-news-plugin';
				$url_slug = get_option('my_news_plugin_endpoint', 'my-news-plugin');
				// Lets setup our rewrite rules
				add_rewrite_rule($url_slug . '/?$', 'index.php?my_news_plugin=index', 'top');

				// Lets flush rewrite rules on activation
				flush_rewrite_rules();
			}

			/**
			 * Callback function for displaying the admin settings page.
			 *
			 * @since    1.0.0
			 */
			public function display_my_news_plugin_page()
			{
				require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/my-news-plugin-admin-display.php';
			}


			/**
			 * Setup sections in the settings
			 *
			 * @since    1.0.0
			 */
			public function setup_sections()
			{
				add_settings_section('settings', 'Settings', array($this, 'section_callback'), 'wordpress-my-news-plugin-options');
			}


			/**
			 * Register the stylesheets for the admin area.
			 *
			 * @since    1.0.0
			 */
			public function enqueue_styles()
			{

				/**
				 * This function is provided for demonstration purposes only.
				 *
				 * An instance of this class should be passed to the run() function
				 * defined in My_News_Plugin_Loader as all of the hooks are defined
				 * in that particular class.
				 *
				 * The My_News_Plugin_Loader will then create the relationship
				 * between the defined hooks and the functions defined in this
				 * class.
				 */

				wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/my-news-plugin-admin.css', array(), $this->version, 'all');
			}

			/**
			 * Register the JavaScript for the admin area.
			 *
			 * @since    1.0.0
			 */
			public function enqueue_scripts()
			{

				/**
				 * This function is provided for demonstration purposes only.
				 *
				 * An instance of this class should be passed to the run() function
				 * defined in My_News_Plugin_Loader as all of the hooks are defined
				 * in that particular class.
				 *
				 * The My_News_Plugin_Loader will then create the relationship
				 * between the defined hooks and the functions defined in this
				 * class.
				 */

				wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/my-news-plugin-admin.js', array('jquery'), $this->version, false);
			}
		}
