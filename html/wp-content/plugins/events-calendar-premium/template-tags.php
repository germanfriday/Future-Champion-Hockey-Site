<?php

if( class_exists( 'Events_Calendar_Pro' ) && !function_exists( 'sp_get_option' ) ) {
	/**
	 * retrieve specific key from options array, optionally provide a default return value
	 */
	function sp_get_option($optionName, $default = '') {
		global $sp_ecp;
		return $sp_ecp->getOption($optionName, $default);
	}
	/**
	 * Output function: Prints the gridded calendar table
	 *
	 * @return void
	 */
	function sp_calendar_grid() {
		global $sp_ecp;
		set_query_var( 'eventDisplay', 'bydate' );
		load_template( $sp_ecp->getTemplateHierarchy('table') );
	}
	/**
	 * Output: Prints the mini gridded calendar table
	 *
	 * @return void
	 */
	function sp_calendar_mini_grid() {
		global $sp_ecp, $wp_query;
		$old_query = $wp_query;

		$wp_query = NEW WP_Query('post_type=sp_events');
		set_query_var( 'eventDisplay', 'bydate' );
		load_template( $sp_ecp->getTemplateHierarchy('table-mini') );
	
		$wp_query = $old_query;
	}
	
	/**
	 * Maps events to days
	 *
	 * @param array of events from sp_get_events()
	 * @param string date of the 
	 * @return array days of the month with events as values
	 */
	function sp_sort_by_month( $results, $date ) {
		if( preg_match( '/(\d{4})-(\d{2})/', $date, $matches ) ) {
			$queryYear	= $matches[1];
			$queryMonth = $matches[2];
		} else {
			return false; // second argument not a date we recognize
		}
		$monthView = array();
		for( $i = 1; $i <= 31; $i++ ) {
			$monthView[$i] = array();
		}
		foreach ( $results as $event ) {
			$started = false;
			list( $startYear, $startMonth, $startDay, $garbage ) = explode( '-', $event->EventStartDate );
			list( $endYear, $endMonth, $endDay, $garbage ) = explode( '-', $event->EventEndDate );
			list( $startDay, $garbage ) = explode( ' ', $startDay );
			list( $endDay, $garbage ) = explode( ' ', $endDay );
			for( $i = 1; $i <= 31 ; $i++ ) {
				if ( ( $i == $startDay && $startMonth == $queryMonth ) ||  strtotime( $startYear.'-'.$startMonth ) < strtotime( $queryYear.'-'.$queryMonth ) ) {
					$started = true;
				}
				if ( $started ) {
					$monthView[$i][] = $event;
				}
				if( $i == $endDay && $endMonth == $queryMonth ) {
					continue 2;
				}
			}
		}
		return $monthView;
	}

	/**
	 * Template function: 
	 * @return boolean
	 */
	function sp_is_event( $postId = null ) {
		global $sp_ecp;
		return $sp_ecp->isEvent($postId);
	}
	/**
	 * Returns a link to google maps for the given event
	 *
	 * @param string $postId 
	 * @return string a fully qualified link to http://maps.google.com/ for this event
	 */
	function sp_get_map_link( $postId = null ) {
		global $sp_ecp;
		return esc_html($sp_ecp->googleMapLink( $postId ));
	}
	/**
	 * Displays a link to google maps for the given event
	 *
	 * @param string $postId 
	 * @return void
	 */
	function sp_the_map_link( $postId = null ) {
		echo esc_html(sp_get_map_link( $postId ));
	}
	/**
	 * @return string formatted event address
	 */
	function sp_get_full_address( $postId = null, $includeVenue = false ) {
		$postId = sp_post_id_helper( $postId );
		$address = '';
		if( $includeVenue ) $address .= sp_get_venue( $postId );
		if( sp_get_address( $postId ) ) {
			if( $address ) $address .= ', ';
			$address .= sp_get_address( $postId );
		}
		if( sp_get_city( $postId ) ) {
			if( $address ) $address .= ', ';
			$address .= sp_get_city( $postId );
		}
		if( sp_get_region( $postId ) ) {
			if( $address ) $address .= ', ';
			$address .= sp_get_region( $postId );
		}
		if( sp_get_country( $postId ) ) {
			if( $address ) $address .= ', ';
			$address .= sp_get_country( $postId );
		}
		if( sp_get_zip( $postId ) ) {
			if( $address ) $address .= ', ';
			$address .= sp_get_zip( $postId );
		}
		$address = str_replace(' ,', ',', $address);
		return $address;
	}
	/**
	 * Displays a formatted event address
	 *
	 * @param string $postId 
	 * @return void
	 */
	function sp_the_full_address( $postId = null ) {
		echo sp_get_full_address( $postId );
	}
	/**
	 * @return boolean true if any part of an address exists
	 */
	function sp_address_exists( $postId = null ) {
		$postId = sp_post_id_helper( $postId );
		return ( sp_get_address( $postId ) || sp_get_city( $postId ) || sp_get_region( $postId ) || sp_get_country( $postId ) || sp_get_zip( $postId ) ) ? true : false;
	}
	/**
	 * Returns an embedded google maps for the given event
	 *
	 * @param string $postId 
	 * @param int $width 
	 * @param int $height
	 * @return string - an iframe pulling http://maps.google.com/ for this event
	 */
	function sp_get_embedded_map( $postId = null, $width = '', $height = '' ) {
		global $sp_ecp;

		$postId = sp_post_id_helper( $postId );
		if ( !sp_is_event( $postId ) ) {
			return false;
		}

		$url_string = $sp_ecp->get_google_maps_args();

		if (!$height) $height = sp_get_option('embedGoogleMapsHeight','350');
		if (!$width) $width = sp_get_option('embedGoogleMapsWidth','100%');

		if ($url_string) {
			$google_iframe = '<div id="googlemaps"><iframe width="'.$width.'" height="'.$height.'" frameborder="0" scrolling="no" marginheight="0" marginwidth="0" src="http://www.google.com/maps?'.$url_string.'&amp;output=embed"></iframe><div class="view-larger-map"><a href="http://www.google.com/maps?'.$url_string.'">View Larger Map</a></div></div>';
			return $google_iframe;
		}
		else return '';
	}
	/**
	 * Displays an embedded google map for the given event
	 *
	 * @param string $postId 
	 * @param int $width 
	 * @param int $height
	 * @return void
	 */
	function sp_the_embedded_map( $postId = null, $width = null, $height = null ) {
		if (sp_get_option('embedGoogleMaps') == 'on')
			echo sp_get_embedded_map( $postId, $width, $height );
	}
	/**
	 * Prints the year & month dropdowns. JavaScript in the resources/events-admin.js file will autosubmit on the change event. 
	 *
	 * @param string a prefix to add to the ID of the calendar elements.  This allows you to reuse the calendar on the same page.
	 * @return void
	 */
	function sp_month_year_dropdowns( $prefix = '' ) {
		global $sp_ecp, $wp_query;
		if ( isset ( $wp_query->query_vars['eventDate'] ) ) { 
			$date = $wp_query->query_vars['eventDate'] . "-01";
		} else {
			$date = date_i18n( Events_Calendar_Pro::DBDATEFORMAT );
		}
		$monthOptions = $sp_ecp->getMonthOptions( $date );
		$yearOptions = $sp_ecp->getYearOptions( $date );
		include('views/datepicker.php');
	}
	/**
	 * Returns the event start date
	 *
	 * @param int post id
	 * @param bool display time?
	 * @param string date format
	 * @return string date
	 */
	function sp_get_start_date( $postId = null, $showtime = 'true', $dateFormat = '' ) {
		global $sp_ecp, $post;
		$postId = sp_post_id_helper( $postId );
		if( $dateFormat ) $format = $dateFormat;
		else $format = get_option( 'date_format', Events_Calendar_Pro::DATEONLYFORMAT );
		if( sp_get_all_day( $postId ) ) {
		    $showtime = false;
		}
		if ( $showtime ) {
			$format = $sp_ecp->getTimeFormat( $format );
		}
		$shortMonthNames = ( strstr( $format, 'M' ) ) ? true : false;
		$date = date ( $format, strtotime( getEventMeta( $postId, '_EventStartDate', true ) ) );
		return str_replace( array_keys($sp_ecp->monthNames( $shortMonthNames )), $sp_ecp->monthNames( $shortMonthNames ), $date);
	}
	/**
	 * Returns the event end date
	 *
	 * @param int post id
	 * @param bool display time?
	 * @param string date format
	 * @return string date
	 */
	function sp_get_end_date( $postId = null, $showtime = 'true', $dateFormat = '' ) {
		global $sp_ecp;
		$postId = sp_post_id_helper( $postId );
		if ( $dateFormat ) $format = $dateFormat;
		else $format = get_option( 'date_format', Events_Calendar_Pro::DATEONLYFORMAT );
		if( sp_get_all_day( $postId ) ) {
		    $showtime = false;
		}
		if ( $showtime ) {
			$format = $sp_ecp->getTimeFormat( $format );
		}
		$date = date ( $format, strtotime( getEventMeta( $postId, '_EventEndDate', true ) ) );
		return str_replace( array_keys($sp_ecp->monthNames()), $sp_ecp->monthNames(), $date);
	}
	/**
	* If EventBrite plugin is active
	* 	If the event is registered in eventbrite, and has one ticket.  Return the cost of that ticket.
	* 	If the event is registered in eventbrite, and there are many tickets, return "Varies"
	* If the event is not registered in eventbrite, and there is meta, return that.
	* If the event is not registered in eventbrite, and there is no meta, return ""
	*
	* @param mixed post id or null if used in the loop
	* @return string
	*/
	function sp_get_cost( $postId = null) {
		global $sp_ecp;
		$postId = sp_post_id_helper( $postId );
		if( class_exists( 'Eventbrite_for_Events_Calendar_Pro' ) ) {
			global $spEventBrite;
			$returned = $spEventBrite->sp_get_cost($postId);
			if($returned) {
				return esc_html($returned);
			}
		}

		$cost = getEventMeta( $postId, '_EventCost', true );

		if($cost === ''){
			return '';
		}elseif($cost == '0'){
			return "Free";
		}else{
			return esc_html($cost);
		}
	}
	/**
	 * Returns the event Organizer
	 *
	 * @return string Organizer
	 */
	function sp_has_organizer( $postId = null) {
		$postId = sp_post_id_helper( $postId );
		//echo getEventMeta( $postId, '_EventVenueID', true ).'|';
		return getEventMeta( $postId, '_EventOrganizerID', true );
	}
	/**
	 * Returns the event Organizer
	 *
	 * @return string Organizer
	 */
	function sp_get_organizer( $postId = null) {
		$postId = sp_post_id_helper( $postId );
		return esc_html(getEventMeta( sp_has_organizer(), '_OrganizerOrganizer', true ));
	}
	/**
	 * Returns the event Organizer
	 *
	 * @return string Organizer
	 */
	function sp_get_organizer_email( $postId = null) {
		$postId = sp_post_id_helper( $postId );
		return esc_html(getEventMeta( sp_has_organizer(), '_OrganizerEmail', true ));
	}
	/**
	 * Returns the event Organizer
	 *
	 * @return string Organizer
	 */
	function sp_get_organizer_website( $postId = null) {
		$postId = sp_post_id_helper( $postId );
		return esc_html(getEventMeta( sp_has_organizer(), '_OrganizerWebsite', true ));
	}
	/**
	 * Returns the event Organizer
	 *
	 * @return string Organizer
	 */
	function sp_get_organizer_link( $postId = null) {
		$postId = sp_post_id_helper( $postId );

		$link = sp_get_organizer($postId);

		if(sp_get_organizer_website($postId) != ''){
			$link = '<a href="'.sp_get_organizer_website($postId).'">'.$link.'</a>';
		}

		return $link;
	}
	/**
	 * Returns the event Organizer
	 *
	 * @return string Organizer
	 */
	function sp_get_organizer_phone( $postId = null) {
		$postId = sp_post_id_helper( $postId );
		return esc_html(getEventMeta( sp_has_organizer(), '_OrganizerPhone', true ));
	}
	/**
	 * Returns the event venue
	 *
	 * @return string venue
	 */
	function sp_has_venue( $postId = null) {
		$postId = sp_post_id_helper( $postId );
		//echo getEventMeta( $postId, '_EventVenueID', true ).'|';
		return getEventMeta( $postId, '_EventVenueID', true );
	}
	/**
	/**
	 * Returns the event venue
	 *
	 * @return string venue
	 */
	function sp_get_venue( $postId = null) {
		$postId = sp_post_id_helper( $postId );
		return esc_html((sp_has_venue()) ?  getEventMeta( sp_has_venue(), '_VenueVenue', true ) : getEventMeta( $postId, '_EventVenue', true ));
	}
	/**
	 * Returns the event country
	 *
	 * @return string country
	 */
	function sp_get_country( $postId = null) {
		$postId = sp_post_id_helper( $postId );
		return esc_html((sp_has_venue()) ?  getEventMeta( sp_has_venue(), '_VenueCountry', true ) : getEventMeta( $postId, '_EventCountry', true ));
	}
	/**
	 * Returns the event address
	 *
	 * @return string address
	 */
	function sp_get_address( $postId = null) {
		$postId = sp_post_id_helper( $postId );
		return esc_html((sp_has_venue()) ?  getEventMeta( sp_has_venue(), '_VenueAddress', true ) : getEventMeta( $postId, '_EventAddress', true ));
	}
	/**
	 * Returns the event city
	 *
	 * @return string city
	 */
	function sp_get_city( $postId = null) {
		$postId = sp_post_id_helper( $postId );
		return esc_html((sp_has_venue()) ?  getEventMeta( sp_has_venue(), '_VenueCity', true ) : getEventMeta( $postId, '_EventCity', true ));
	}
	/**
	 * Returns the event state or Province
	 *
	 * @return string state
	 */
	function sp_get_stateprovince( $postId = null) {
		$postId = sp_post_id_helper( $postId );
		return esc_html(getEventMeta( sp_has_venue(), '_VenueStateProvince', true ));
	}
	/**
	 * Returns the event state
	 *
	 * @return string state
	 */
	function sp_get_state( $postId = null) {
		$postId = sp_post_id_helper( $postId );
		return esc_html((sp_has_venue()) ?  getEventMeta( sp_has_venue(), '_VenueState', true ) : getEventMeta( $postId, '_VenueState', true ));
	}
	/**
	 * Returns the event province
	 *
	 * @return string province
	 */
	function sp_get_province( $postId = null) {
		$postId = sp_post_id_helper( $postId );
		return esc_html((sp_has_venue()) ?  getEventMeta( sp_has_venue(), '_VenueProvince', true ) : getEventMeta( $postId, '_EventProvince', true ));
	}
	/**
	 * Returns the event zip code
	 *
	 * @return string zip code 
	 */
	function sp_get_zip( $postId = null) {
		$postId = sp_post_id_helper( $postId );
		return esc_html((sp_has_venue()) ?  getEventMeta( sp_has_venue(), '_VenueZip', true ) : getEventMeta( $postId, '_EventZip', true ));
	}
	/**
	 * Returns the event phone number
	 *
	 * @return string phone number
	 */
	function sp_get_phone( $postId = null) {
		$postId = sp_post_id_helper( $postId );
		return esc_html((sp_has_venue()) ?  getEventMeta( sp_has_venue(), '_VenuePhone', true ) : getEventMeta( $postId, '_EventPhone', true ));
	}
	
	/**
	 * Displays a link to the previous post by start date for the given event
	 *
	 * @param string $postId 
	 * @return void
	 */
	function sp_previous_event_link( $anchor = "Previous Event" ) {
		global $sp_ecp, $post;

		echo $sp_ecp->get_event_link($post->ID,'previous',$anchor);
	}
	/**
	 * Displays a link to the next post by start date for the given event
	 *
	 * @param string $postId 
	 * @return void
	 */
	function sp_next_event_link( $anchor = "Next Event" ) {
		global $sp_ecp, $post;
		echo $sp_ecp->get_event_link($post->ID, 'next',$anchor);
	}
	/**
	 * Helper function to determine postId. Pulls from global $post object if null or non-numeric.
	 * 
	 * @return int postId;
	 */
	
	function sp_post_id_helper( $postId ) {
		if ( $postId === null || ! is_numeric( $postId ) ) {
			global $post;
			return $post->ID;
		}
		return (int) $postId;
	}

	/**
	 * Helper function to load XML using cURL
	 *
	 * @return array with xml data
	 */
	function load_xml($url) {/*
		TODO remove and use built-in WP functions. Used by eventbrite plugin.
	*/
        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);

        $data = simplexml_load_string(curl_exec($ch));

        curl_close($ch);

        return $data;
    }
		
	/**
	 * Called inside of the loop, returns true if the current post's meta_value (EventStartDate)
	 * is different than the previous post. Will always return true for the first event in the loop.
	 *
	 * @return bool
	 */
	function sp_is_new_event_day() {
		global $sp_ecp, $post;
		$retval = false;
		$now = time();
		$postTimestamp = strtotime( $post->EventStartDate, $now );
		$postTimestamp = strtotime( date( Events_Calendar_Pro::DBDATEFORMAT, $postTimestamp ), $now); // strip the time
		if ( $postTimestamp != $sp_ecp->currentPostTimestamp ) { 
			$retval = true;
		}
		$sp_ecp->currentPostTimestamp = $postTimestamp; 
		return $retval;
	}
	/**
	 * Call this function in a template to query the events
	 *
	 * @param int numResults number of results to display for upcoming or past modes (default 10)
	 * @param string|int eventCat Event Category: use int for term ID, string for name.
	 * @param string metaKey A meta key to query. Useful for sorting by country, venue, etc. metaValue must also be set to use.
	 * @param string metaValue The value of the queried metaKey, which also must be set.
	 * @return array results
	 */
	function sp_get_events( $args = '' ) {
		global $sp_ecp;
		return $sp_ecp->getEvents( $args );
	}
	/**
	 * Returns true if the query is set for past events, false otherwise
	 * 
	 * @return bool
	 */
	function sp_is_past() {
		global $sp_ecp;
		return ($sp_ecp->displaying == 'past') ? true : false;
	}
	/**
	 * Returns true if the query is set for upcoming events, false otherwise
	 * 
	 * @return bool
	 */
	function sp_is_upcoming() {
		global $sp_ecp;
		return ($sp_ecp->displaying == 'upcoming') ? true : false;
	}
	/**
	 * Returns true if the query is set for month display (as opposed to Upcoming / Past)
	 *
	 * @return bool
	 */
	function sp_is_month() {
		global $sp_ecp;
		return ( $sp_ecp->displaying == 'month' ) ? true : false;
	}
	/**
	 * Returns a link to the previous events in list view
	 *
	 * @return string 
	 */
	function sp_get_past_link() {
		global $sp_ecp;
		return esc_html($sp_ecp->getLink('past'));
	}
	/**
	 * Returns a link to the upcoming events in list view
	 *
	 * @return string 
	 */
	function sp_get_upcoming_link() {
		global $sp_ecp;
		return esc_html($sp_ecp->getLink('upcoming'));
	}
	/**
	 * Returns a link to the next month's events page
	 *
	 * @return string 
	 */
	function sp_get_next_month_link() {
		global $sp_ecp;
		return esc_html($sp_ecp->getLink( 'month', $sp_ecp->nextMonth( $sp_ecp->date ) ));
	}
	/**
	 * Returns a link to the previous month's events page
	 *
	 * @return string
	 */
	function sp_get_previous_month_link() {
		global $sp_ecp;
		return esc_html($sp_ecp->getLink( 'month', $sp_ecp->previousMonth( $sp_ecp->date ) ));
	}
	
	/**
	 * Returns an ical feed for a single event. Must be used in the loop.
	 * 
	 * @return string
	 */
	function sp_get_single_ical_link() {
		global $sp_ecp;
		return esc_html($sp_ecp->getLink( 'ical', 'single' ));
	}

	/**
	 * Returns a link to the events URL
	 *
	 * @return string
	 */
	function sp_get_events_link() {
		global $sp_ecp;
		return esc_html($sp_ecp->getLink('home'));
	}
	
	function sp_get_gridview_link() {
		global $sp_ecp;
		return esc_html($sp_ecp->getLink('month'));
	}
		
	function sp_get_listview_link() {
		global $sp_ecp;
		return esc_html($sp_ecp->getLink('upcoming'));
	}
	
	function sp_get_listview_past_link() {
		global $sp_ecp;
		return esc_html($sp_ecp->getLink('past'));
	}
	
	function sp_get_dropdown_link_prefix() {
		global $sp_ecp;
		return esc_html($sp_ecp->getLink('dropdown'));
	}
	function sp_get_ical_link() {
		global $sp_ecp;
		return esc_html($sp_ecp->getLink('ical'));
	}

	/**
	 * Returns a textual description of the previous month
	 *
	 * @return string
	 */
	function sp_get_previous_month_text() {
		global $sp_ecp;
		return $sp_ecp->getDateString( $sp_ecp->previousMonth( $sp_ecp->date ) );
	}
	/**
	 * Returns a textual description of the current month
	 *
	 * @return string
	 */
	function sp_get_current_month_text( ){
		global $sp_ecp; 
		return date( 'F', strtotime( $sp_ecp->date ) );
	}
	/**
	 * Returns a textual description of the next month
	 *
	 * @return string
	 */
	function sp_get_next_month_text() {
		global $sp_ecp;
		return $sp_ecp->getDateString( $sp_ecp->nextMonth( $sp_ecp->date ) );
	}
	/**
	 * Returns a formatted date string of the currently displayed month (in "jump to month" mode)
	 *
	 * @return string
	 */
	function sp_get_displayed_month() {
		global $sp_ecp;
		if ( $sp_ecp->displaying == 'month' ) {
			return $sp_ecp->getDateString( $sp_ecp->date );
		}
		return " ";
	}
	/**
	 * Returns a link to the currently displayed month (if in "jump to month" mode)
	 *
	 * @return string
	 */
	function sp_get_this_month_link() {
		global $sp_ecp;
		if ( $sp_ecp->displaying == 'month' ) {
			return esc_html($sp_ecp->getLink( 'month', $sp_ecp->date ));
		}
		return false;
	}
	/**
	 * Returns the state or province for US or non-US addresses
	 *
	 * @return string
	 */
	function sp_get_region() {
		global $sp_ecp;
		if(getEventMeta(sp_has_venue(), '_VenueStateProvince', true )){
			return getEventMeta(sp_has_venue(), '_VenueStateProvince', true );
		}else
		if ( sp_get_country() == __('United States', $sp_ecp->pluginDomain ) ) {
			return sp_get_state();
		} else {
			return sp_get_province(); 
		}
	}
	/**
	 * Returns true if the event is an all day event
	 *
	 * @return bool
	 */
	function sp_get_all_day( $postId = null ) {
		$postId = sp_post_id_helper( $postId );
		return !! getEventMeta( $postId, '_EventAllDay', true );
	}
	
	/**
	 * echos an events title, with pseudo-breadcrumb if on a category
	*/ 
	function sp_events_title() {
		global $sp_ecp;
		$title = __('Calendar of Events', $sp_ecp->pluginDomain);
		if ( is_tax( $sp_ecp->get_event_taxonomy() ) ) {
			$cat = get_term_by( 'slug', get_query_var('term'), $sp_ecp->get_event_taxonomy() );
			$title = '<a href="'.sp_get_events_link().'">'.$title.'</a>';
			$title .= ' &#8250; ' . $cat->name;
		}
		echo $title;
	}

	function sp_meta_event_cats() {
		global $sp_ecp;
		the_terms( get_the_ID(), $sp_ecp->get_event_taxonomy(), '<dt>'.__('Category:',$sp_ecp->pluginDomain ).'</dt><dd>', ', ', '</dd>' );
	}

	/** Just a global function alias of the class function by the same name. **/
	function getEventMeta( $id, $meta, $single = true ){
			global $sp_ecp;
			return $sp_ecp->getEventMeta( $id, $meta, $single );
	}
	
	/**
	 * r the current event category name
	*/ 
	function sp_meta_event_category_name(){
		global $sp_ecp;
		$current_cat = get_query_var('sp_events_cat');
		if($current_cat){
			$term_info = get_term_by('slug',$current_cat,$sp_ecp->get_event_taxonomy());
			return $term_info->name;
		}
	}

	/**
	 * Returns an add to Google Calendar link. Must be used in the loop
	 * @author Julien Cornic [www.juxy.fr]
	 * @author Matt Wiebe
	*/
	function sp_get_add_to_gcal_link() {
		$post_id = get_the_ID();
		$start_date = strtotime(get_post_meta( $post_id, '_EventStartDate', true ));
		$end_date = strtotime(get_post_meta( $post_id, '_EventEndDate', true ));
		$dates = ( sp_get_all_day() ) ? date('Ymd', $start_date) . '/' . date('Ymd', $end_date) : date('Ymd', $start_date) . 'T' . date('Hi00', $start_date) . '/' . date('Ymd', $end_date) . 'T' . date('Hi00', $end_date);
		$location = trim( sp_get_venue() . ' ' . sp_get_phone() );
		
		$base_url = 'http://www.google.com/calendar/event';
		$params = array(
			'action' => 'TEMPLATE',
			'text' => strip_tags(get_the_title()),
			'dates' => $dates,
			'details' => strip_tags( get_the_excerpt() ),
			'location' => $location,
			'sprop' => get_option('blogname'),
			'trp' => 'false',
			'sprop' => 'website:' . home_url()
		);
		$url = add_query_arg( $params, $base_url );
		return esc_html($url);
	}
	
	include_once 'deprecated-template-tags.php';
	
} // end if class_exists('The-Events-Calendar')