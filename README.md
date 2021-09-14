# my-news-plugin
Wordpress plugin that downloads news from https://newsapi.org into custom post type in regular time intervals, and allows to display fetched news via shortcode in any page on the website.

After cloning the plugin to wp-content/plugins directory and activating the plugin, 2 new items will emerge in admin dashboard that are "My News Plugin" and "News".

By clicking on "My News Plugin", plugin settings will be displayed where user can configure plugin endpoint, search string to be contained in the fetched news, news API and cron interval for news fetching in seconds.

Use shortcode [news-list] to display all news.