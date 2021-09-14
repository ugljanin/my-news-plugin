<?php
get_header();
global $paged;
$posts_per_page = 6;

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
);

$query = new WP_Query($args);

$total_found_posts = $query->found_posts;
$total_page = ceil($total_found_posts / $posts_per_page);

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


    wp_reset_postdata();

endif;
get_footer();
?>