<?php
/*
Plugin Name: Topher's Weight Tracker
Description: Uses data from Google Sheets to track weight
Version: 1.0
Author: Topher
*/

add_action( 'wp_enqueue_scripts', 'topher_charts_loader' );

function topher_charts_loader() {
    global $post;

    wp_register_script( 'charts-loader', 'https://www.gstatic.com/charts/loader.js', array( 'jquery' ), '1.0', false );

    if( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'topher_weight_loss_chart') ) {
        wp_enqueue_script( 'charts-loader' );
    }
}

function get_weight_data() {

    // declare the output car
    $output = '';

    // put slides URL here
    $source_url = '';

    // set the transient name, including weight ID
    $transient_name = 'weight_data';

    // get the raw data from a transient
    $weight_data = '';
    //$weight_data = get_transient( $transient_name );

    // if the get works properly, I should have an object in $weight_data.
    if ( ! is_object( $weight_data ) ) {
    
        // go get the raw data
        $weight_data = wp_safe_remote_get( $source_url );

        set_transient( $transient_name, $weight_data, DAY_IN_SECONDS );
        //set_transient( $transient_name, $weight_data, 1 );

    }

    // strip out the body
    $weight_body = wp_remote_retrieve_body( $weight_data );

    // the body is JSON, so convery to a PHP Object
    $weight_body_array = json_decode( $weight_body, 1 );

    $col_1 = $col_2 = [];
    foreach( $weight_body_array['feed']['entry'] as $i => $item ) {
        if( 1 == $i % 2 ) {
            $col_1[] = $item;
        } else {
            $col_2[] = $item;
        }
    }

    foreach ( $col_2 as $col ) {
        $dates[] = $col['content']['$t'];
    }

    foreach ( $col_1 as $col ) {
         $weights[] = $col['content']['$t'];
    }

    $weight_array = [];

    foreach ( $dates as $key => $date ) {
        $weight_array[ $key ][ 'date' ] = $date;
        $weight_array[ $key ][ 'weight' ] = $weights[ $key ];
    }

    return json_encode( array_reverse( $weight_array ) );

}

function topher_total_loss() {

    $data   = json_decode( get_weight_data(), 1 );
    $output = '';
    $first  = $data[0]['weight'];
    $last   = end( $data );

    return $first - $last['weight'];

}
add_shortcode( 'topher_total_loss', 'topher_total_loss' );

function topher_loss_timespan() {

    $data        = json_decode( get_weight_data(), 1 );
    $output      = '';
    $first_date  = date_create( $data[0]['date'] );
    $last        = end( $data );
    $last_date   = date_create( $last['date'] );
    $interval    = date_diff( $first_date, $last_date );

    return $interval->format( '%a' );

}
add_shortcode( 'topher_loss_timespan', 'topher_loss_timespan' );

function topher_weight_loss_chart() {

    $data = json_decode( get_weight_data(), 1 );

    foreach( $data as $key => $info ) {
        $js_data .= "    ['" . $info['date'] . "',  " . $info['weight'] . "],";
    }

    $js_data = trim( $js_data, ',' );

        $output = '';

        $output .= '<script>' . "\n";
        $output .= "google.charts.load('current', {'packages':['corechart']});" . "\n";
        $output .= "google.charts.setOnLoadCallback(drawChart);" . "\n";

        $output .= "function drawChart() {" . "\n";
        $output .= "  var data = google.visualization.arrayToDataTable([" . "\n";
        $output .= "    ['Date', 'Weight']," . "\n";
        $output .= $js_data;
        $output .= "  ]);" . "\n\n";

        $output .= "  var options = {" . "\n";
        $output .= "    hAxis: {" . "\n";
        $output .= "      title: 'Dates'" . "\n";
        $output .= "    }," . "\n";
        $output .= "    vAxis: {" . "\n";
        $output .= "      title: 'Weight'" . "\n";
        $output .= "    }," . "\n";
        $output .= "    colors: ['#0000ff']" . "\n";
        $output .= "  };" . "\n\n";

        $output .= "var chart = new google.visualization.LineChart(document.getElementById('curve_chart'));" . "\n";

        $output .= "chart.draw(data, options);" . "\n";
        $output .= "}" . "\n";
        $output .= '</script>' . "\n\n";
        $output .= '<div id="curve_chart" style="width: 700px; height: 389px"></div>' . "\n";

        return $output;

}
add_shortcode( 'topher_weight_loss_chart', 'topher_weight_loss_chart' );
