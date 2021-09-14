<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://www.emirugljanin.com
 * @since      1.0.0
 *
 * @package    My_News_Plugin
 * @subpackage My_News_Plugin/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    My_News_Plugin
 * @subpackage My_News_Plugin/public
 * @author     Emir Ugljanin <emirugljanin@gmail.com>
 */
class My_News_Plugin_Public
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
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct($plugin_name, $version)
	{

		$this->plugin_name = $plugin_name;
		$this->version = $version;

		add_shortcode('news-list', array($this, 'my_news_plugin_create_shortcode'));
	}

	// >> Create Shortcode to Display Movies Post Types

	function my_news_plugin_create_shortcode()
	{
		$posts_per_page = get_option('my_news_plugin_news_per_page', 1);
		if (isset($_GET['sort'])) {
			$sort = $_GET['sort'];
		} else
			$sort = 'asc';

		$args = array(
			'post_type'      => 'my-news-plugin',
			'publish_status' => 'published',
			'posts_per_page' => $posts_per_page,
			'orderby' => 'title',
			'order' => $sort,
			'paged'          => get_query_var('paged') ? get_query_var('paged') : 1,
			// 'paged' => $paged
		);

		$query = new WP_Query($args);

		$total_found_posts = $query->found_posts;
		$total_page = ceil($total_found_posts / $posts_per_page);
		ob_start();

?>
		<script>
			var pathname = window.location.pathname;
			var origin = window.location.origin;
			jQuery(document).ready(function($) {
				$("#sort-latest-news").on('change', function() {
					var sort = $(this).val();
					var sort_url = origin + pathname + '/?sort=' + sort;
					window.location.href = sort_url;
				});
			});
		</script>
		<div class="text-center">
			<select id="sort-latest-news">
				<option value="asc" <?php echo ($sort == 'asc' ? 'selected' : ''); ?>>ASC</option>
				<option value="desc" <?php echo ($sort == 'desc' ? 'selected' : ''); ?>>DESC</option>
			</select>
		</div>
		<?php

		if ($query->have_posts()) :
			echo '<table class="table">';
			echo '<tr><th>Title</th><th>Content</th><th>Image</th></tr>';
			while ($query->have_posts()) :

				$query->the_post();

				echo  '<tr>';
				echo  '<td class="title"><a href="' . get_post_meta(get_the_ID(), 'wpt_news_url', true) . '" target="_blank">' . get_the_title() . '</a></td>';
				echo  '<td class="content">' . get_the_content() . '</td>';
				echo  '<td class="image">' . get_the_post_thumbnail() . '</td>';
				echo  '</tr>';

			endwhile;
			echo '</table>';
		?>
			<div class="pagination">
				<?php
				echo paginate_links(array(
					'base'         => str_replace(999999999, '%#%', esc_url(get_pagenum_link(999999999))),
					'total'        => $total_page,
					'current'      => max(1, get_query_var('paged')),
					'format'       => '?paged=%#%',
					'show_all'     => false,
					'type'         => 'plain',
					'end_size'     => 2,
					'mid_size'     => 1,
					'prev_next'    => true,
					'prev_text'    => sprintf('<i></i> %1$s', __('Newer Posts', 'text-domain')),
					'next_text'    => sprintf('%1$s <i></i>', __('Older Posts', 'text-domain')),
					'add_args'     => false,
					'add_fragment' => '',
				));
				?>
			</div>
<?php

			$result = ob_get_clean();

			wp_reset_postdata();

		endif;

		return $result;
	}


	// shortcode code ends here

	/**
	 * Custom Plugin Redirect
	 *
	 * @since    1.0.0
	 */
	public function register_custom_template_redirect()
	{
		// Show all users
		if (get_query_var('my_news_plugin')) {
			add_filter('template_include', function () {
				$plugin_path = 'my-news-plugin/';
				$file_name = 'my-news-plugin-public-display.php';
				//search for template override in themes directory
				if (locate_template($plugin_path . $file_name)) {
					$template = locate_template($plugin_path . $file_name);
				} else {
					// Template not found in theme's folder, use plugin's template as a fallback
					$template = plugin_dir_path(__FILE__) . 'partials/' . $file_name;
				}
				return $template;
			});
		}
	}


	/**
	 * Register Query Values for Custom Plugin
	 *
	 * Filters that are needed for rendering the custom plugin page
	 *
	 * @since    1.0.0
	 */
	public function register_query_values($vars)
	{
		$vars[] = 'my_news_plugin';

		return $vars;
	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
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

		wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/my-news-plugin-public.css', array(), $this->version, 'all');
	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
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

		wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/my-news-plugin-public.js', array('jquery'), $this->version, false);
		wp_localize_script($this->plugin_name, 'the_ajax_script', array(
			'ajaxurl' => admin_url('admin-ajax.php'),
			'nonce' => wp_create_nonce('ajax-nonce')
		));
	}
}
