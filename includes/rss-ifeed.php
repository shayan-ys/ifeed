<?php
/**
 * Template Name: Custom RSS Template - ifeed
 */
header('Content-Type: '.feed_content_type('rss-http').'; charset='.get_option('blog_charset'), true);
echo '<?xml version="1.0" encoding="'.get_option('blog_charset').'"?'.'>';
?>
<rss version="2.0"
        xmlns:content="http://purl.org/rss/1.0/modules/content/"
        xmlns:wfw="http://wellformedweb.org/CommentAPI/"
        xmlns:dc="http://purl.org/dc/elements/1.1/"
        xmlns:atom="http://www.w3.org/2005/Atom"
        xmlns:sy="http://purl.org/rss/1.0/modules/syndication/"
        xmlns:slash="http://purl.org/rss/1.0/modules/slash/"
        <?php do_action('rss2_ns'); ?>>
<channel>
		<?php
		$ifeed_posts = false;
		if(isset($_GET['title']) && strlen($_GET['title'])>1) {
			$ifeed_slug = esc_sql($_GET['title']);
			if(function_exists('ifeed_get_value_db')){
				$ifeed = ifeed_get_value_db('slug', $ifeed_slug );
				$online_posts = json_decode($ifeed['online_posts'], true);
				if(is_array($online_posts) && count($online_posts)>0) {
					$ifeed_posts = new WP_Query(array(
						'order' => 'ASC',
						'post__not_in' => get_option( 'sticky_posts' ),
						'post__in' => $online_posts
					));
				}
				if(isset($ifeed['utm']) && strlen($ifeed['utm'])>0) {
					$utm = htmlentities( "?".$ifeed['utm'] );
				} else {
					$utm = null;
				}
			}
		}
		if($ifeed_posts===false) {
			// default query
			global $wp_query;
			$ifeed_posts = $wp_query;
		}
		?>
        <title><?php bloginfo_rss('name'); ?> - Feed</title>
        <atom:link href="<?php self_link(); ?>" rel="self" type="application/rss+xml" />
        <link><?php bloginfo_rss('url') ?></link>
        <description><?php bloginfo_rss('description') ?></description>
        <lastBuildDate><?php echo mysql2date('D, d M Y H:i:s +0000', get_lastpostmodified('GMT'), false); ?></lastBuildDate>
        <language><?php echo get_option('rss_language'); ?></language>
        <sy:updatePeriod><?php echo apply_filters( 'rss_update_period', 'hourly' ); ?></sy:updatePeriod>
        <sy:updateFrequency><?php echo apply_filters( 'rss_update_frequency', '1' ); ?></sy:updateFrequency>
        <?php do_action('rss2_head'); ?>
        <?php
		while($ifeed_posts->have_posts()): $ifeed_posts->the_post();
		?>
                <item>
                        <title><?php the_title_rss(); ?></title>
                        <link><?php the_permalink_rss();
								echo $utm;
						?></link>
                        <pubDate><?php echo mysql2date('D, d M Y H:i:s +0000', get_post_time('Y-m-d H:i:s', true), false); ?></pubDate>
                        <dc:creator><?php the_author(); ?></dc:creator>
                        <guid isPermaLink="false"><?php the_guid(); ?></guid>
                        <description><![CDATA[<?php the_excerpt_rss() ?>]]></description>
                        <content:encoded><![CDATA[<?php the_excerpt_rss() ?>]]></content:encoded>
                        <?php rss_enclosure(); ?>
                        <?php do_action('rss2_item'); ?>
                </item>
		<?php endwhile; ?>
</channel>
</rss>
