<?php
/**
 * Admin Class
 *
 * Handles the Admin side functionality of plugin
 *
 * @package WP Logo Showcase Responsive Slider and Carousel
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
} ?>

<div id="wpls_themes_tabs" class="wpls-vtab-cnt wpls_themes_tabs wpls-clearfix">
	<div class="wpls-features-section">
		<div class="wpls-center">
			<h1 class="wpls-heading" style="font-size: 28px;font-weight: 700;letter-spacing: -1px;text-align: center;margin-top: 30px;margin-bottom: 5px;">Powerful Pro Features, Simplified</h1>
			<div class="wpls-sf-cont">Convert Your Visitors into Subscribers & Customers.</div>
		</div>

		<div class="wpls-sf-welcome-wrap wpls-sf-center">
			<div class="wpls-features-box-wrap">
				<ul class="wpls-features-box-grid">
					<li>
					<div class="wpls-popup-icon"><img src="<?php echo esc_url(WPLS_URL); ?>/assets/images/popup-icon/grid.png" /></div>
					Logo Grid View</li>
					<li>
					<div class="wpls-popup-icon"><img src="<?php echo esc_url(WPLS_URL); ?>/assets/images/popup-icon/slider-carousel.png" /></div>
					Logo Slider View</li>
					<li>
					<div class="wpls-popup-icon"><img src="<?php echo esc_url(WPLS_URL); ?>/assets/images/popup-icon/center-mode.png" /></div>
					Logo Slider Center Mode</li>
					<li>
					<div class="wpls-popup-icon"><img src="<?php echo esc_url(WPLS_URL); ?>/assets/images/popup-icon/filter.png" /></div>
					Logo Filter View</li>
					<li>
					<div class="wpls-popup-icon"><img src="<?php echo esc_url(WPLS_URL); ?>/assets/images/popup-icon/slider-carousel.png" /></div>
					Logo Ticker View</li>
					<li>
					<div class="wpls-popup-icon"><img src="<?php echo esc_url(WPLS_URL); ?>/assets/images/popup-icon/list.png" /></div>
					Logo List View</li>
				</ul>
			</div>
		</div>
	</div>

	<div class="wpls-deal-offer-wrap">
		<div class="wpls-deal-offer"> 
			<div class="wpls-inn-deal-offer">
				<h3 class="wpls-inn-deal-hedding"><span>Buy Logo Showcase Pro</span> today and unlock all the powerful features.</h3>
				<h4 class="wpls-inn-deal-sub-hedding"><span style="color:red;">Extra Bonus: </span>Users will get <span>extra best discount</span> on the regular price using this coupon code.</h4>
			</div>
			<div class="wpls-inn-deal-offer-btn">
				<div class="wpls-inn-deal-code"><span>EPSEXTRA</span></div>
				<a href="<?php echo esc_url(WPLS_PLUGIN_BUNDLE_LINK); ?>"  target="_blank" class="wpls-sf-btn wpls-sf-btn-orange"><span class="dashicons dashicons-cart"></span> Get Essential Bundle Now</a>
				<em class="risk-free-guarantee"><span class="heading">Risk-Free Guarantee </span> - We offer a <span>30-day money back guarantee on all purchases</span>. If you are not happy with your purchases, we will refund your purchase. No questions asked!</em>
			</div>
		</div>
	</div>
</div>