<?php

/**
* WPBakery Page Builder Ohio Team Members Group Inner shortcode view
*/

?>
<li class="team-group-item active<?php echo esc_attr( $wrapper_classes ); ?>" data-item="true" id="<?php echo esc_attr( $wrapper_id ); ?>">
    <div class="item-holder">
        <div class="-fade-up">
            <div class="author">
                <h5 class="title"><?php echo $name; ?></h5>
                <div class="author-details"><?php echo $position; ?></div>
            </div>
            <p><?php echo $description; ?></p>
			<div class="social-networks -outlined -small">
	        	<?php if ( $artstation_link ) : ?>
					<a href="<?php echo $artstation_link; ?>" target="_blank" rel="nofollow" class="network -unlink artstation">
						<i class="fa-brands fa-artstation"></i>
					</a>
				<?php endif; ?>

            	<?php if ( $behance_link ) : ?>
					<a href="<?php echo $behance_link; ?>" target="_blank" rel="nofollow" class="network -unlink behance">
						<i class="fa-brands fa-behance"></i>
					</a>
				<?php endif; ?>

				<?php if ( $deviantart_link ) : ?>
					<a href="<?php echo $deviantart_link; ?>" target="_blank" rel="nofollow" class="network -unlink deviantart">
						<i class="fa-brands fa-deviantart"></i>
					</a>
				<?php endif; ?>

				<?php if ( $digg_link ) : ?>
					<a href="<?php echo $digg_link; ?>" target="_blank" rel="nofollow" class="network -unlink digg">
						<i class="fa-brands fa-digg"></i>
					</a>
				<?php endif; ?>

				<?php if ( $discord_link ) : ?>
					<a href="<?php echo $discord_link; ?>" target="_blank" rel="nofollow" class="network -unlink discord">
						<i class="fa-brands fa-discord"></i>
					</a>
				<?php endif; ?>

				<?php if ( $dribbble_link ) : ?>
					<a href="<?php echo $dribbble_link; ?>" target="_blank" rel="nofollow" class="network -unlink dribbble">
						<i class="fa-brands fa-dribbble"></i>
					</a>
				<?php endif; ?>

				<?php if ( $facebook_link ) : ?>
					<a href="<?php echo $facebook_link; ?>" target="_blank" rel="nofollow" class="network -unlink facebook">
						<i class="fa-brands fa-facebook-f"></i>
					</a>
				<?php endif; ?>

				<?php if ( $flickr_link ) : ?>
					<a href="<?php echo $flickr_link; ?>" target="_blank" rel="nofollow" class="network -unlink flickr">
						<i class="fa-brands fa-flickr"></i>
					</a>
				<?php endif; ?>

				<?php if ( $github_link ) : ?>
					<a href="<?php echo $github_link; ?>" target="_blank" rel="nofollow" class="network -unlink github">
						<i class="fa-brands fa-github"></i>
					</a>
				<?php endif; ?>

				<?php if ( $houzz_link ) : ?>
					<a href="<?php echo $houzz_link; ?>" target="_blank" rel="nofollow" class="network -unlink houzz">
						<i class="fa-brands fa-houzz"></i>
					</a>
				<?php endif; ?>

				<?php if ( $instagram_link ) : ?>
					<a href="<?php echo $instagram_link; ?>" target="_blank" rel="nofollow" class="network -unlink instagram">
						<i class="fa-brands fa-instagram"></i>
					</a>
				<?php endif; ?>

				<?php if ( $kaggle_link ) : ?>
					<a href="<?php echo $kaggle_link; ?>" target="_blank" rel="nofollow" class="network -unlink kaggle">
						<i class="fa-brands fa-kaggle"></i>
					</a>
				<?php endif; ?>

				<?php if ( $linkedin_link ) : ?>
					<a href="<?php echo $linkedin_link; ?>" target="_blank" rel="nofollow" class="network -unlink linkedin">
						<i class="fa-brands fa-linkedin"></i>
					</a>
				<?php endif; ?>

				<?php if ( $medium_link ) : ?>
					<a href="<?php echo $medium_link; ?>" target="_blank" rel="nofollow" class="network -unlink medium">
						<i class="fa-brands fa-medium-m"></i>
					</a>
				<?php endif; ?>

				<?php if ( $mixer_link ) : ?>
					<a href="<?php echo $mixer_link; ?>" target="_blank" rel="nofollow" class="network -unlink mixer">
						<i class="fa-brands fa-mixer"></i>
					</a>
				<?php endif; ?>

				<?php if ( $pinterest_link ) : ?>
					<a href="<?php echo $pinterest_link; ?>" target="_blank" rel="nofollow" class="network -unlink pinterest">
						<i class="fa-brands fa-pinterest"></i>
					</a>
				<?php endif; ?>

				<?php if ( $producthunt_link ) : ?>
					<a href="<?php echo $producthunt_link; ?>" target="_blank" rel="nofollow" class="network -unlink producthunt">
						<i class="fa-brands fa-product-hunt"></i>
					</a>
				<?php endif; ?>

				<?php if ( $quora_link ) : ?>
					<a href="<?php echo $quora_link; ?>" target="_blank" rel="nofollow" class="network -unlink quora">
						<i class="fa-brands fa-quora"></i>
					</a>
				<?php endif; ?>

				<?php if ( $reddit_link ) : ?>
					<a href="<?php echo $reddit_link; ?>" target="_blank" rel="nofollow" class="network -unlink reddit">
						<i class="fa-brands fa-reddit"></i>
					</a>
				<?php endif; ?>

				<?php if ( $snapchat_link ) : ?>
					<a href="<?php echo $snapchat_link; ?>" target="_blank" rel="nofollow" class="network -unlink snapchat">
						<i class="fa-brands fa-snapchat"></i>
					</a>
				<?php endif; ?>
				
				<?php if ( $soundcloud_link ) : ?>
					<a href="<?php echo $soundcloud_link; ?>" target="_blank" rel="nofollow" class="network -unlink soundcloud">
						<i class="fa-brands fa-soundcloud"></i>
					</a>
				<?php endif; ?>
				
				<?php if ( $spotify_link ) : ?>
					<a href="<?php echo $spotify_link; ?>" target="_blank" rel="nofollow" class="network -unlink spotify">
						<i class="fa-brands fa-spotify"></i>
					</a>
				<?php endif; ?>
				
				<?php if ( $teamspeak_link ) : ?>
					<a href="<?php echo $teamspeak_link; ?>" target="_blank" rel="nofollow" class="network -unlink teamspeak">
						<i class="fa-brands fa-teamspeak"></i>
					</a>
				<?php endif; ?>
				
				<?php if ( $telegram_link ) : ?>
					<a href="<?php echo $telegram_link; ?>" target="_blank" rel="nofollow" class="network -unlink telegram">
						<i class="fa-brands fa-telegram"></i>
					</a>
				<?php endif; ?>
				
				<?php if ( $threads_link ) : ?>
					<a href="<?php echo $threads_link; ?>" target="_blank" rel="nofollow" class="network -unlink threads">
						<i class="fa-brands fa-threads"></i>
					</a>
				<?php endif; ?>
				
				<?php if ( $tiktok_link ) : ?>
					<a href="<?php echo $tiktok_link; ?>" target="_blank" rel="nofollow" class="network -unlink tiktok">
						<i class="fa-brands fa-tiktok"></i>
					</a>
				<?php endif; ?>
				
				<?php if ( $tumblr_link ) : ?>
					<a href="<?php echo $tumblr_link; ?>" target="_blank" rel="nofollow" class="network -unlink tumblr">
						<i class="fa-brands fa-tumblr"></i>
					</a>
				<?php endif; ?>
				
				<?php if ( $twitch_link ) : ?>
					<a href="<?php echo $twitch_link; ?>" target="_blank" rel="nofollow" class="network -unlink twitch">
						<i class="fa-brands fa-twitch"></i>
					</a>
				<?php endif; ?>
				
				<?php if ( $twitter_link ) : ?>
					<a href="<?php echo $twitter_link; ?>" target="_blank" rel="nofollow" class="network -unlink twitter">
						<i class="fa-brands fa-x-twitter"></i>
					</a>
				<?php endif; ?>
				
				<?php if ( $vimeo_link ) : ?>
					<a href="<?php echo $vimeo_link; ?>" target="_blank" rel="nofollow" class="network -unlink vimeo">
						<i class="fa-brands fa-vimeo"></i>
					</a>
				<?php endif; ?>
				
				<?php if ( $vine_link ) : ?>
					<a href="<?php echo $vine_link; ?>" target="_blank" rel="nofollow" class="network -unlink vine">
						<i class="fa-brands fa-vine"></i>
					</a>
				<?php endif; ?>
				
				<?php if ( $whatsapp_link ) : ?>
					<a href="<?php echo $whatsapp_link; ?>" target="_blank" rel="nofollow" class="network -unlink whatsapp">
						<i class="fa-brands fa-whatsapp"></i>
					</a>
				<?php endif; ?>
				
				<?php if ( $xing_link ) : ?>
					<a href="<?php echo $xing_link; ?>" target="_blank" rel="nofollow" class="network -unlink xing">
						<i class="fa-brands fa-xing"></i>
					</a>
				<?php endif; ?>
				
				<?php if ( $youtube_link ) : ?>
					<a href="<?php echo $youtube_link; ?>" target="_blank" rel="nofollow" class="network -unlink youtube">
						<i class="fa-brands fa-youtube"></i>
					</a>
				<?php endif; ?>
				
				<?php if ( $fivehundred_link ) : ?>
					<a href="<?php echo $fivehundred_link; ?>" target="_blank" rel="nofollow" class="network -unlink 500px">
						<i class="fa-brands fa-500px"></i>
					</a>
				<?php endif; ?>
            </div>
        </div>
    </div>
</li>
<li class="team-group-item" data-trigger="true">
    <?php if ( $photo ) : ?>
		<img <?php echo $photo_image_atts; ?>>
	<?php endif; ?>
</li>