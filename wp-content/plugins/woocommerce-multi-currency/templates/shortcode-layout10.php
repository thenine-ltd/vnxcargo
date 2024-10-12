<?php
/** @var  $dropdown_icon */
/** @var  $data_flag_size */
/** @var  $custom_format */
/** @var  $country_code */
/** @var  $flag_size */
/** @var  $symbol */
?>
    <div id="<?php echo esc_attr( $id ) ?>"
         class="woocommerce-multi-currency wmc-shortcode plain-vertical layout10 <?php echo esc_attr( $class ) ?>"
         data-layout="layout10" data-flag_size="<?php echo esc_attr( $data_flag_size ) ?>"
         data-dropdown_icon="<?php echo esc_attr( $dropdown_icon ) ?>"
         data-custom_format="<?php echo esc_attr( $custom_format ) ?>">
        <input type="hidden" class="wmc-current-url" value="<?php echo esc_attr( $current_url ) ?>">
        <div class="wmc-currency-wrapper">
				<span class="wmc-current-currency" style="line-height: <?php echo esc_attr( $line_height ) ?>">
                   <span>
                    <?php
                    echo "<i style='{$flag_size}' class='wmc-current-flag vi-flag-64 flag-{$country_code}'></i>";
                    if ( $custom_format ) {
	                    ?>
                        <span class="<?php echo esc_attr( "wmc-text wmc-text-{$current_currency}" ) ?>">
                            <?php
                            echo str_replace( array(
	                            '{currency_name}',
	                            '{currency_code}',
	                            '{currency_symbol}'
                            ), array(
	                            '<span class="wmc-currency-name">' . $countries[ $current_currency ] . '</span>',
	                            '<span class="wmc-currency-code">' . $current_currency . '</span>',
	                            '<span class="wmc-currency-symbol">' . $symbol . '</span>'
                            ), $custom_format );
                            ?>
                        </span>
	                    <?php
                    } else {
	                    echo "<span class='wmc-text wmc-text-{$current_currency}'>
                                <span class='wmc-text-currency-text'>({$current_currency}) </span>
                                <span class='wmc-text-currency-symbol'>{$symbol}</span>
                            </span>";
                    }
                    ?>
                    </span>
                    <?php echo $arrow ?>
                </span>
            <div class="wmc-sub-currency">
				<?php
				foreach ( $links as $k => $link ) {
					$sub_class = array( 'wmc-currency' );
					if ( $current_currency == $k ) {
						$sub_class[] = 'wmc-hidden';
					}
					$country = $settings->get_country_data( $k );
					?>
                    <div class="<?php echo esc_attr( implode( ' ', $sub_class ) ) ?>" data-currency="<?php echo esc_attr( $k ) ?>">
						<?php
						$html = '';
						if ( $settings->enable_switch_currency_by_js() ) {
							$link = '#';
						}

						$symbol = get_woocommerce_currency_symbol( $k );
						$html   .= sprintf( "<a rel='nofollow' class='wmc-currency-redirect' href='%1s' style='line-height:%2s' data-currency='%3s' data-currency_symbol='%4s'>",
							esc_url( $link ), $line_height, $k, $symbol );
						$html   .= sprintf( "<i style='%1s' class='vi-flag-64 flag-%2s'></i>", $flag_size, strtolower( $country['code'] ) );

						if ( $custom_format ) {
							$html .= '<span>' . str_replace(
									[
										'{currency_name}',
										'{currency_code}',
										'{currency_symbol}'
									],
									[
										'<span class="wmc-sub-currency-name">' . $countries[ $k ] . '</span>',
										'<span class="wmc-sub-currency-code">' . $k . '</span>',
										'<span class="wmc-sub-currency-symbol">' . $symbol . '</span>'
									], $custom_format ) . '</span>';
						} else {
							$html .= sprintf( "<span class='wmc-sub-currency-name'>%1s</span>", esc_html( $countries[ $k ] ) );
							$html .= sprintf( "<span class='wmc-sub-currency-symbol'>(%1s)</span>", esc_html( $symbol ) );
						}
						$html .= '</a>';
						echo $html;
						?>
                    </div>
					<?php
				}
				?>
            </div>
        </div>
    </div>
<?php