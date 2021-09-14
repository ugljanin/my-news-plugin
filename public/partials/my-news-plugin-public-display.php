<?php
require_once(ABSPATH . 'wp-admin/includes/media.php');
require_once(ABSPATH . 'wp-admin/includes/file.php');
require_once(ABSPATH . 'wp-admin/includes/image.php');
/**
 * Provide a public-facing view for the plugin
 *
 * This file is used to markup the public-facing aspects of the plugin.
 *
 * @link       https://www.emirugljanin.com
 * @since      1.0.0
 *
 * @package    My_News_Plugin
 * @subpackage My_News_Plugin/public/partials
 */

$api_key = get_option('my_news_plugin_api_key', 1);
$search_phrase = get_option('my_news_plugin_search_phrase', 1);


$api_url = 'https://newsapi.org/v2/everything?q=' . $search_phrase . '&sortBy=publishedAt&apiKey=' . $api_key;

if (false === $body) {
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
}
$array = json_decode($body, true);
echo '<pre>';
print_r($array['articles']);
echo '</pre>';
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

/*
$array = json_decode($body, true);
if (is_array($array)) {
    ?>
    <div id="my-lovely-users-list">
        <div class="plugin-container">
            <div class="users-list">
                <h1>News</h1>
                <table>
                    <thead>
                        <tr>
                            <td>ID</td>
                            <td>Name</td>
                            <td>Username</td>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        foreach ($array as $user) {
                            echo '<tr data-id="' . $user['id'] . '">';
                            echo '<td>';
                            echo '<a href="#">' . $user['id'] . '</a>';
                            echo '</td>';
                            echo '<td>';
                            echo '<a href="#">' . $user['name'] . '</a>';
                            echo '</td>';
                            echo '<td>';
                            echo '<a href="#">' . $user['username'] . '</a>';
                            echo '</td>';
                            echo '</tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>

            <div class="user-details">
                <div class="title">Selected user details</div>
                <div id="status">
                    Please click on a user from the list to obtain user's details.
                </div>
                <div id="my-lovely-user-details">
                    <ul>
                        <li>
                            <strong>ID:</strong> <span id="user-id"></span>
                        </li>
                        <li>
                            <strong>Name:</strong>
                            <span id="user-name"></span>
                        </li>
                        <li>
                            <strong>Username:</strong>
                            <span id="user-username"></span>
                        </li>
                        <li>
                            <strong>Email:</strong>
                            <span id="user-email"></span>
                        </li>
                        <li>
                            <strong>Street:</strong>
                            <span id="user-street"></span>
                        </li>
                        <li>
                            <strong>Suite:</strong>
                            <span id="user-suite"></span>
                        </li>
                        <li>
                            <strong>City:</strong>
                            <span id="user-city"></span>
                        </li>
                        <li>
                            <strong>Phone:</strong>
                            <span id="user-phone"></span>
                        </li>
                        <li>
                            <strong>Company:</strong>
                            <span id="user-company"></span>
                        </li>
                    </ul>
                </div>

            </div>
        </div>
    </div>
<?php
}
*/
