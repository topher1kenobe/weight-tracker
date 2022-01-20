<?php
/*
Plugin Name: Topher's Weight Tracker
Description: Uses data from Google Sheets to track weight
Version: 3.0
Author: Topher
*/

/**
 * Creates a mechanism for using a blog as a cypher, similar to an Ottendorf cypher
 *
 * @package T1k_Weight_Tracker
 * @since T1k_Weight_Tracker 2.0
 * @author Topher
 */

/**
 * Instantiate the T1k_Weight_Tracker instance
 * @since T1k_Weight_Tracker 2.0
 */
add_action( 'plugins_loaded', array( 'T1k_Weight_Tracker', 'instance' ) );

/**
 * Main T1K Blog Cypher Class
 *
 * Contains the main functions for the admin side of T1K Blog Cypher
 *
 * @class T1k_Weight_Tracker
 * @version 1.0.0
 * @since 1.0
 * @package T1k_Weight_Tracker
 * @author Topher
 */
class T1k_Weight_Tracker {

	/**
	* Instance handle
	*
	* @static
	* @since 1.2
	* @var string
	*/
	private static $__instance = null;

	/**
	* Holds the source URL
	*
	* @since 2.0
	* @var text
	*/
	public $default_url = 'https://spreadsheets.google.com/feeds/cells/1f_sIAhWUElvSP1AyzRBJPjhQCDv23nW6fPNldzR-JWk/1/public/full?alt=json';


	/**
	* Holds the data retrieved from the spreadsheet
	*
	* @since 2.0
	* @var text
	*/
	private $loss_data = null;

	/**
	 * T1k_Weight_Tracker Constructor, actually contains nothing
	 *
	 * @access public
	 * @return void
	 */
	private function __construct() {}

	/**
	 * Instance initiator, runs setup etc.
	 *
	 * @access public
	 * @return self
	 */
	public static function instance() {
		if ( ! is_a( self::$__instance, __CLASS__ ) ) {
			self::$__instance = new self;
			self::$__instance->setup();
		}
		
		return self::$__instance;
	}

	/**
	 * Runs things that would normally be in __construct
	 *
	 * @access private
	 * @return void
	 */
	private function setup() {

		add_action( 'wp_enqueue_scripts', [ $this, 'topher_charts_loader' ] );

		add_shortcode( 'topher_weight_loss_chart', [ $this, 'topher_weight_loss_chart' ] );
		add_shortcode( 'topher_total_loss',        [ $this, 'topher_total_loss' ] );
		add_shortcode( 'topher_loss_timespan',	   [ $this, 'topher_loss_timespan' ] );

	}

	public function topher_charts_loader() {

		wp_register_script( 'charts-loader', 'https://www.gstatic.com/charts/loader.js', array( 'jquery' ), '1.0', false );

		wp_enqueue_script( 'charts-loader' );
	}

	private function get_weight_data( $url = '' ) {

		if ( empty( $url ) ) {
			$url = $this->default_url;
		}

		// declare the output car
		$output = [];

		// make a unique key for each URL
		$transient_key = substr( md5( $url ), 0, -24);

		// declare the container for the weight data
		$weight_data = '';

		// set the transient name, including weight ID
		$transient_name = 'weight_data_' . $transient_key;

		// get the raw data from a transient
		if ( ! empty( $_GET['rw'] ) && 'yes' === $_GET['rw'] ) {
			delete_transient( $transient_name );
		}

		$weight_data = get_transient( $transient_name );

		// if the get works properly, I should have an object in $weight_data.
		if ( ! is_object( $weight_data ) ) {

			$args = array(
        			'timeout'     => 45,
			);

			// go get the raw data
			$weight_data = wp_safe_remote_get( esc_url( $url ), $args );

			set_transient( $transient_name, $weight_data, HOUR_IN_SECONDS );

		}

		// strip out the body
		$weight_body = wp_remote_retrieve_body( $weight_data );

		// the body is JSON, so convery to a PHP Object
		$weight_body_array = json_decode( $weight_body, 1 );

		// filter out the dates
		$dates = wp_list_pluck( $weight_body_array['values'], 0 );

		// filter out the weights
		$weights = wp_list_pluck( $weight_body_array['values'], 1 );

		// make a new array of dates and weights
		$data = array_reverse( array_combine( $dates, $weights ) );

		$output['key']  = $transient_key;
		$output['data'] = $data;

		return $output;

	}


	public function topher_total_loss( $atts ) {

		if ( empty( $atts['url'] ) ) {
			$url = '';
		} else {
			$url = $atts['url'];
		}

		$loss_data = $this->get_weight_data( esc_url( $url ) );

		$first	= current( $loss_data['data'] );
		$last	= end( $loss_data['data'] );

		return $first - $last;

	}

	public function topher_loss_timespan( $atts ) {

		if ( empty( $atts['url'] ) ) {
			$url = '';
		} else {
			$url = $atts['url'];
		}

		$loss_data = $this->get_weight_data( esc_url( $url ) );

		$first_date = date_create( key( $loss_data['data'] ) );
		$last_date  = date_create( array_key_last( $loss_data['data'] ) );
		$interval   = date_diff( $first_date, $last_date );

		return $interval->format( '%a' );

	}


	/**
	 * Make shortcode for rendering chart
	 *
	 * @access	public
	 * @return	string	$output;
	 */
	public function topher_weight_loss_chart( $atts = '' ) {

		if ( empty( $atts['url'] ) ) {
			$url = '';
		} else {
			$url = $atts['url'];
		}

		$loss_data = $this->get_weight_data( esc_url( $url ) );
		$js_data   = '';

		foreach( $loss_data['data'] as $date => $weight ) {
			$js_data .= "['" . $date . "', " . $weight . "],";
		}

		$js_data = trim( $js_data, ',' );

		$output = '';

		$output .= '<script>' . "\n";
		$output .= "google.charts.load('current', {'packages':['corechart']});" . "\n";
		$output .= "google.charts.setOnLoadCallback(drawChart);" . "\n";

		$output .= "function drawChart() {" . "\n";
		$output .= "  var data = google.visualization.arrayToDataTable([" . "\n";
		$output .= "	['Date', 'Weight']," . "\n";
		$output .= $js_data;
		$output .= "  ]);" . "\n\n";

		$output .= "  var options = {" . "\n";
		$output .= "	hAxis: {" . "\n";
		$output .= "	  title: 'Dates'" . "\n";
		$output .= "	}," . "\n";
		$output .= "	vAxis: {" . "\n";
		$output .= "	  title: 'Weight'" . "\n";
		$output .= "	}," . "\n";
		$output .= "	colors: ['#0000ff']" . "\n";
		$output .= "  };" . "\n\n";

		$output .= "var chart = new google.visualization.LineChart(document.getElementById('curve_chart_" . $loss_data['key'] . "'));" . "\n";

		$output .= "chart.draw(data, options);" . "\n";
		$output .= "}" . "\n";
		$output .= '</script>' . "\n\n";
		$output .= '<div id="curve_chart_' . $loss_data['key'] . '" style="width: 900px; height: 389px"></div>' . "\n";

		return $output;

	}

	// end class
}

?>
