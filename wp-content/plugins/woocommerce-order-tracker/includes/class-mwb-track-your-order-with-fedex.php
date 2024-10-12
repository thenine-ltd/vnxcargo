<?php
/**
 * Enable api.
 *
 * @version  1.0.0
 * @package  Woocommece_Order_Tracker/Include
 *  
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( ! class_exists( 'MWB_Track_Your_Order_With_FedEx' ) ) {

	/**
	 * This is class for tracking order With FedEx Services .
	 *
	 * @name    MWB_Track_Your_Order_With_FedEx
	 *  
	 * 
	 */
	class MWB_Track_Your_Order_With_FedEx {

		/**
		 * This is construct of class
		 *
		 * @link http://www.wpswings.com/
		 */
		public function __construct() {
			require_once( 'fedex-common.php5' );
			ini_set( 'soap.wsdl_cache_enabled', 0 );
			ini_set( 'soap.wsdl_cache_ttl', 0 );
		}

		/**
		 * Fedex request.
		 *
		 * @param integer $order_id contains order id.
		 * @return void
		 */
		public function fedex_request( $order_id ) {
			$request = array();
			$path_to_wsdl = MWB_TRACK_YOUR_ORDER_PATH . 'includes/TrackService_v10.wsdl';
			$mwb_tyo_fedex_tracking_enable = get_option( 'mwb_tyo_enable_track_order_using_api', 'no' );

			$mwb_fedex_track_number = wps_order_tracker_get_meta_data( $order_id, 'mwb_tyo_package_tracking_number', true );

			if ( isset( $mwb_tyo_fedex_tracking_enable ) && ( 'yes' == $mwb_tyo_fedex_tracking_enable ) ) {

				$mwb_user_key = get_option( 'mwb_fedex_userkey', false );

				$mwb_user_password = get_option( 'mwb_fedex_userpassword', false );

				$mwb_user_account_number = get_option( 'mwb_fedex_account_number', false );

				$mwb_user_meter_number = get_option( 'mwb_fedex_meter_number', false );

				$order = wc_get_order( $order_id );
			}

			if ( ! empty( $order ) ) {
				$order_billing_details = $order->get_formatted_billing_address();
				$client = new SoapClient(
					$path_to_wsdl,
					array(

						'stream_context' => stream_context_create(
							array(
								'ssl' => array(
									'verify_peer' => false,
									'verify_peer_name' => false,
									'allow_self_signed' => true,
								),
							)
						),
					)
				);

				if ( ( isset( $mwb_user_key ) && ! empty( $mwb_user_key ) ) && ( isset( $mwb_user_password ) && ! empty( $mwb_user_password ) ) ) {
					$request['WebAuthenticationDetail'] = array(
						'UserCredential' => array(
							'Key' => getuserkey( $mwb_user_key ),
							'Password' => getuserPass( $mwb_user_password ),
						),
					);
				}

				if ( '' == $mwb_fedex_track_number || null == $mwb_fedex_track_number ) {?>

				<div class="mwb_tyo_error_processing_transaction">
					<?php esc_html_e( 'Please provide Tracking number For track your package', 'woocommerce-order-tracker' ); ?>
				</div>
					<?php
					return;
				} else {
					if ( ( isset( $mwb_user_account_number ) && ! empty( $mwb_user_account_number ) ) && ( isset( $mwb_user_meter_number ) && ! empty( $mwb_user_meter_number ) ) && ( isset( $mwb_fedex_track_number ) && ! empty( $mwb_fedex_track_number ) ) ) {

						$request['ClientDetail'] = array(
							'AccountNumber' => getAccountNumber( $mwb_user_account_number ),
							'MeterNumber' => getMeterNumber( $mwb_user_meter_number ),
						);
						$request['TransactionDetail'] = array( 'CustomerTransactionId' => '*** Track Request using PHP ***' );
						$request['Version'] = array(
							'ServiceId' => 'trck',
							'Major' => '10',
							'Intermediate' => '0',
							'Minor' => '0',
						);
						$request['SelectionDetails'] = array(
							'PackageIdentifier' => array(
								'Type' => 'TRACKING_NUMBER_OR_DOORTAG',
								'Value' => getTrackingNumber( $mwb_fedex_track_number ),
							),
						);
						$request['ProcessingOptions'] = 'INCLUDE_DETAILED_SCANS';

					}
				}
				if ( is_array( $request ) && ! empty( $request ) ) {
					try {
						$response = $client->track( $request );

						$fedex_response = $response->CompletedTrackDetails;
						$mwb_counter = 0;
						$f = 0;
						if ( isset( $fedex_response->TrackDetails->Events ) && ! empty( $fedex_response->TrackDetails->Events ) ) {
							$mwb_fedex_total_event = count( $fedex_response->TrackDetails->Events );
							$f = 1;
							$fedex_tracking_details = array_reverse( $fedex_response->TrackDetails->Events );

							?>
						<div class="mwb-tyo-main-data-wrapper-template-fedex">
							<div class="mwb-tyo-order-tracking-section-template-fedex">
								<?php
								foreach ( $fedex_tracking_details as $one_event ) {

									$status = isset( $one_event->EventDescription ) ? $one_event->EventDescription : '';
									if ( 'Delivered' == $status ) {
										$mwb_counter = 1;
									}
									?>
									<div class="mwb-tooltip-template-fedex" id="mwb-temp-tooltip_fedex">
										
										<p><?php esc_html_e( ' Your Order is ', 'woocommerce-order-tracker' ); ?><?php echo esc_html( $one_event->EventDescription ); ?><?php esc_html_e( ' on', 'woocommerce-order-tracker' ); ?></p>
										<span><?php echo esc_html( date_i18n( 'F d, g:i a', strtotime( $one_event->Timestamp ) ) ); ?></span>
									</div>
									<?php
								}
								?>
							</div>
							<div class="mwb-tyo-order-progress-data-field-template-fedex">
								<div class="mwb-tyo-small-circle1-template-fedex mwb-tyo-circle-template-fedex">
									<div class="sub-circle-template-fedex mwb-tyo-sub-circle1-template-fedex">
									</div>
								</div>
								<div class="mwb-tyo-skill-template-fedex">
									<div class="mwb-tyo-outer-template-fedex" data-progress="<?php echo esc_attr( $mwb_fedex_total_event ); ?>" data-progress-bar-height="<?php echo esc_attr( $mwb_fedex_total_event ); ?>" data-template_no="fedex">
										<div class="mwb-tyo-inner-template-fedex" >
										</div>        
									</div>
								</div>
								<div class="mwb-tyo-small-circle2-template-fedex mwb-tyo-circle-template-fedex">
									<div class="mwb-tyo-sub-circle-template-fedex mwb-tyo-sub-circle2-template-fedex">
									</div>
								</div>
							</div>
						</div>
							<?php
						}

						if ( 1 != $f ) {
							?>
						<div class="mwb_tyo_error_processing_transaction"><?php esc_html_e( 'Error in processing transaction', 'woocommerce-order-tracker' ); ?></div>
							<?php
							return;
						}
						?>
					<section id="mwb_tyo_wrapper_third_party">
						<div id="mwb-tyo-main-wrapper">
							<?php
							if ( 'FAILURE' == $response->HighestSeverity && 'ERROR' == $response->HighestSeverity ) {
								?>
								<div class="mwb_tyo_error_processing_transaction"><?php esc_html_e( 'Error in processing transaction', 'woocommerce-order-tracker' ); ?></div>
								<?php
							}
							?>
						</div>
					</section>
						<?php

					} catch ( SoapFault $exception ) {
						?>
					<div class="mwb_tyo_error_processing_transaction"><?php esc_html_e( 'Please enter correct orderId/Fedex tracking number', 'woocommerce-order-tracker' ); ?></div>
						<?php
					}
				} else {
					?>
				<div class="mwb_tyo_error_processing_transaction"><?php esc_html_e( 'Please provide credentials For tracking your package with fedex tracking services', 'woocommerce-order-tracker' ); ?></div>
					<?php
				}
			} else {
				?>
			<div class="mwb_tyo_error_processing_transaction"><?php esc_html_e( 'Service not avilable', 'woocommerce-order-tracker' ); ?></div>
				<?php
			}
		}

		/**
		 * Canada post request.
		 *
		 * @return void
		 */
		public function canadapost_request() {
			$mwb_tyo_canadapost_tracking_enable = get_option( 'mwb_tyo_enable_canadapost_tracking', 'no' );

			$mwb_tyo_canadapost_userkey = get_option( 'mwb_tyo_canadapost_tracking_user_key', false );
			$mwb_tyo_canadapost_password = get_option( 'mwb_tyo_canadapost_tracking_user_password', false );

			$mwb_tyo_order_id = get_option( 'mwb_tyo_user_order_id', false );
			$mwb_tyo_pin_no = wps_order_tracker_get_meta_data( $mwb_tyo_order_id, 'mwb_tyo_package_tracking_number', true );

			if ( isset( $mwb_tyo_canadapost_tracking_enable ) && ( 'yes' == $mwb_tyo_canadapost_tracking_enable ) ) {
				$user_order = wc_get_order( $mwb_tyo_order_id );
			}

			if ( ! empty( $user_order ) ) {
				$order_billing_details = $user_order->get_formatted_billing_address();
			}
			if ( ( isset( $mwb_tyo_canadapost_userkey ) && ( ! empty( $mwb_tyo_canadapost_userkey ) || '' != $mwb_tyo_canadapost_userkey ) && ( isset( $mwb_tyo_canadapost_password ) && ( ! empty( $mwb_tyo_canadapost_password ) || '' != $mwb_tyo_canadapost_password ) ) ) ) {

				$wsdl = realpath( dirname( isset( $_SERVER['SCRIPT_FILENAME'] ) ? sanitize_text_field( wp_unslash( $_SERVER['SCRIPT_FILENAME'] ) ) : '' ) ) . '/wp-content/plugins/woocommerce-order-tracker/includes/wsdl/track.wsdl';

				$host_name = 'ct.soa-gw.canadapost.ca';

				// SOAP URI.
				$location = 'https://' . $host_name . '/vis/soap/track';

				// SSL Options.
				$opts = array(
					'ssl' =>
										array(
											'verify_peer' => false,
											'verify_peer_name' => false,
											'cafile' => realpath( dirname( isset( $_SERVER['SCRIPT_FILENAME'] ) ? sanitize_text_field( wp_unslash( $_SERVER['SCRIPT_FILENAME'] ) ) : '' ) ) . '/wp-content/plugins/woocommerce-order-tracker/third-party/cert/cacert.pem',

										),
				);

				$ctx = stream_context_create( $opts );
				$client = new SoapClient(
					$wsdl,
					array(
						'location' => $location,
						'features' => SOAP_SINGLE_ELEMENT_ARRAYS,
						'stream_context' => $ctx,
					)
				);

				// Set WS Security username_token.
				$w_s_s_e_n_s = 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd';
				$username_token = new stdClass();
				$username_token->Username = new SoapVar( $mwb_tyo_canadapost_userkey, XSD_STRING, null, null, null, $w_s_s_e_n_s );
				$username_token->Password = new SoapVar( $mwb_tyo_canadapost_password, XSD_STRING, null, null, null, $w_s_s_e_n_s );

				$content = new stdClass();
				$content->username_token = new SoapVar( $username_token, SOAP_ENC_OBJECT, null, null, null, $w_s_s_e_n_s );
				$header = new SOAPHeader( $w_s_s_e_n_s, 'Security', $content );
				$client->__setSoapHeaders( $header );

				try {

					// Execute Request.
					if ( isset( $mwb_tyo_pin_no ) && ! empty( $mwb_tyo_pin_no ) ) {
						$result = $client->__soapCall(
							'GetTrackingDetail',
							array(
								'get-tracking-detail-request' => array(
									'locale'    => 'FR',
									// PIN or DNC Choice.
																'pin'       => $mwb_tyo_pin_no,

								),
							),
							null,
							null
						);

						if ( isset( $result->{'tracking-detail'} ) && ! empty( $result->{'tracking-detail'} ) ) {
							foreach ( $result->{'tracking-detail'} as $tracking_kety => $tracking_value ) {
								if ( 'significant-events' == $tracking_kety ) {
									if ( isset( $tracking_value->{'occurrence'} ) && ! empty( $tracking_value->{'occurrence'} ) ) {

										$mwb_tyo_progressbar_width = count( $tracking_value->{'occurrence'} );
									}
								}
							}
							if ( isset( $mwb_tyo_progressbar_width ) && ! empty( $mwb_tyo_progressbar_width ) ) {
								$mwb_tyo_width = ( 100 / 13 ) * $mwb_tyo_progressbar_width;

							} else {

								$mwb_tyo_width = 10;
							}
							?>

							<?php
							foreach ( $result->{'tracking-detail'} as $tracking_kety => $tracking_value ) {
								if ( 'significant-events' == $tracking_kety ) {
									if ( isset( $tracking_value->{'occurrence'} ) && ! empty( $tracking_value->{'occurrence'} ) ) {
										$mwb_tyo_progressbar_width = count( $tracking_value->{'occurrence'} );
										?>
									<div class="mwb-tyo-main-data-wrapper-canadapost">
										<div class="mwb-tyo-order-progress-data-field-template-canadapost">
											<div class="mwb-tyo-small-circle1-template-canadapost mwb-tyo-circle-template-canadapost">
												<div class="mwb-tyo-sub-circle-template-canadapost mwb-tyo-sub-circle1-template-canadapost">
												</div>
											</div>
											<div class="mwb-tyo-skill-template-canadapost">
												<div class="mwb-tyo-outer-template-canadapost" data-progress="<?php echo count( $tracking_value->{'occurrence'} ); ?>" data-progress-bar-height="<?php echo count( $tracking_value->{'occurrence'} ); ?>" data-template_no="canadapost">
													<div class="mwb-tyo-inner-template-canadapost" ></div>        
												</div>
											</div>
											<div class="mwb-tyo-small-circle2-template-canadapost mwb-tyo-circle-template-canadapost">
												<div class="mwb-tyo-sub-circle-template-canadapost mwb-tyo-sub-circle2-template-canadapost">
												</div>
											</div>
										</div>
										<div class="mwb-tyo-order-tracking-section-canadapost">
											<?php
											foreach ( $tracking_value->{'occurrence'} as $occurence_key => $occurence_value ) {
												?>
												<div class="mwb-tooltip-canadapost">
													<h4><?php echo esc_html( $occurence_value->{'event-description'} ); ?></h4>
													<p><?php esc_html_e( 'your order reached at ', 'woocommerce-order-tracker' ); ?></p>
													<p><?php echo esc_html( $occurence_value->{'event-site'} ); ?></p>
													<span><?php echo esc_html( date_i18n( 'F d', strtotime( $occurence_value->{'event-date'} ) ) ); ?><?php echo ' ' . esc_html( date_i18n( 'Y h:i', strtotime( $occurence_value->{'event-time'} ) ) ); ?></span>
												</div>
												<?php
											}
											?>
										</div>
									</div>
										<?php
									}
								}
							}
						} else {
							?>
						<div class="mwb_tyo_error_tracking_message">
							<h4><?php esc_html_e( 'Service Unavailable', 'woocommerce-order-tracker' ); ?></h4>
						</div>
							<?php
						}
					} else {
						?>
					<div class="mwb_tyo_error_transaction"><?php esc_html_e( 'Please provide tracking number For tracking your package with canada post tracking services', 'woocommerce-order-tracker' ); ?></div>
						<?php
					}
				} catch ( SoapFault $exception ) {
					echo 'Fault Code: ' . esc_html( trim( $exception->faultcode ) ) . "\n";
					echo 'Fault Reason: ' . esc_html( trim( $exception->getMessage() ) ) . "\n";
					echo '<h3>Enter your canada post authorization details correctly</h3>';
				}
			} else {
				?>
			<div class="mwb_tyo_error_processing_transaction"><?php esc_html_e( 'Please provide credentials For tracking your package with canada post tracking services', 'woocommerce-order-tracker' ); ?></div>
				<?php
			}
		}

		/**
		 * Function for handling usps request.
		 *
		 * @param int $order_id is the id of order.
		 * @return void
		 */
		public function mwb_tyo_usps_tracking_request( $order_id ) {

			$mwb_tyo_usps_user_key = get_option( 'mwb_tyo_usps_tracking_user_key', false );
			$mwb_tyo_usps_user_password = get_option( 'mwb_tyo_usps_tracking_user_password', false );
			$mwb_tyo_order_tracking_no = wps_order_tracker_get_meta_data( $order_id, 'mwb_tyo_package_tracking_number', true );

			if ( ( isset( $mwb_tyo_usps_user_key ) && isset( $mwb_tyo_order_tracking_no ) ) && ( ' ' != $mwb_tyo_usps_user_key && ' ' != $mwb_tyo_order_tracking_no ) ) {

				$mwb_tyo_xml_response = simplexml_load_file( 'http://production.shippingapis.com/ShippingAPI.dll?API=TrackV2&XML=<TrackRequest USERID="' . $mwb_tyo_usps_user_key . '"><TrackID ID="' . $mwb_tyo_order_tracking_no . '"></TrackID></TrackRequest>' ) || die( 'Error' );

				$mwb_tyo_xml_response_to_array = json_decode( json_encode( $mwb_tyo_xml_response ), 1 );

				if ( is_array( $mwb_tyo_xml_response_to_array ) && ! empty( $mwb_tyo_xml_response_to_array ) ) {
					foreach ( $mwb_tyo_xml_response_to_array as $xml_key => $xml_value ) {
						if ( is_array( $xml_value ) && ! empty( $xml_value ) ) {
							if ( array_key_exists( 'TrackDetail', $xml_value ) ) {

								$mwb_tyo_usps_track_details = $xml_value['TrackDetail'];
							} else {
								$mwb_tyo_usps_track_details = $xml_value['TrackSummary'];
							}
						}
					}
				}
				if ( is_array( $mwb_tyo_usps_track_details ) && ! empty( $mwb_tyo_usps_track_details ) ) {

					$mwb_tyo_usps_track_details = array_reverse( $mwb_tyo_usps_track_details );
					$mwb_tyo_usps_place = '';
					$mwb_tyo_place_msg = __( ' On ', 'woocommerce-order-tracker' );
					?>
				<div class="mwb-tyo-main-data-wrapper-usps">
					<div class="mwb-tyo-order-progress-data-field-template-usps">
						<div class="mwb-tyo-small-circle1-template-usps mwb-tyo-circle-template-usps">
							<div class="mwb-tyo-sub-circle-template-usps mwb-tyo-sub-circle1-template-usps">
							</div>
						</div>
						<div class="mwb-tyo-skill-template-usps">
							<div class="mwb-tyo-outer-template-usps" data-progress="<?php echo esc_attr( count( $mwb_tyo_usps_track_details ) ); ?>" data-progress-bar-height="<?php echo esc_attr( count( $mwb_tyo_usps_track_details ) ); ?>" data-template_no="usps">
								<div class="mwb-tyo-inner-template-usps" ></div>        
							</div>
						</div>
						<div class="mwb-tyo-small-circle2-template-usps mwb-tyo-circle-template-usps">
							<div class="mwb-tyo-sub-circle-template-usps mwb-tyo-sub-circle2-template-usps">
							</div>
						</div>
					</div>
					<div class="mwb-tyo-order-tracking-section-usps">
						<?php
						foreach ( $mwb_tyo_usps_track_details as $usps_keys => $usps_values ) {
							$usps_values = explode( ',', $usps_values );
							$mwb_tyo_usps_status = $usps_values[0];
							$mwb_tyo_usps_date = $usps_values[1] . $usps_values[2];
							$mwb_tyo_usps_time = $usps_values[3];
							unset( $usps_values[0] );
							unset( $usps_values[1] );
							unset( $usps_values[2] );
							unset( $usps_values[3] );
							foreach ( $usps_values as $keys1 => $values1 ) {

								$mwb_tyo_usps_place .= $values1;
							}
							?>
							<div class="mwb-tooltip-usps">
								<h4><?php echo esc_html( $mwb_tyo_usps_status ); ?></h4>
								<p><?php esc_html_e( 'your order reached at ', 'woocommerce-order-tracker' ); ?></p>
								<p><?php echo esc_html( $mwb_tyo_usps_place ) . esc_html( $mwb_tyo_place_msg ); ?></p>
								<span><?php echo esc_html( $mwb_tyo_usps_date ) . esc_html( $mwb_tyo_usps_time ); ?></span>
							</div>
							<?php
							$mwb_tyo_usps_place = '';
						}

						?>
					</div>
				</div>
					<?php

				} else {
					echo esc_html( $mwb_tyo_usps_track_details );
				}
			} else {
				?>
			<div class="mwb_tyo_error_processing_transaction"><?php esc_html_e( 'UserID Or Tracking Number Not Found', 'woocommerce-order-tracker' ); ?></div>
				<?php
			}

		}

		/**
		 * Function for error request.
		 *
		 * @return void
		 */
		public function mwb_tyo_error_request() {
			?>
		<div class="mwb_tyo_error_processing_transaction"><?php esc_html_e( 'Please Enter Correct OrderID and Tracking Number To Track Your Package', 'woocommerce-order-tracker' ); ?></div>
			<?php
		}
	}
}
