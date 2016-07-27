<?php

/*
Plugin Name: Visitor Data
Description: A test plugin, storing visitor's IP, Page count, Browser name and language into the DB
Version: 1.0.0
Author: zaytka
*/

class visitorData {
	
	// cookie name
	public $idVarName = 'visitor_data_id';
	
	// DB table name (without WP prefix)
	public $tableName = 'visitor_data';
	
	// constructor
	function visitorData() {
		
		// store the data
		add_action( 'wp', array( &$this, 'storeVisitorData' ) );
		
		// create the DB table on activation
		register_activation_hook( __FILE__, array( &$this, 'createTable' ) );
	}
	
	/*
		creates the DB table if necessary
	*/
	function createTable()
	{
		global $wpdb;
		
		$charset_collate = $wpdb->get_charset_collate();
		
		$table_name = $wpdb->prefix . $this->tableName;
		
		//User IP, page view count and used browser name and language.
		$sql = "CREATE TABLE $table_name (
			id MEDIUMINT(9) NOT NULL AUTO_INCREMENT,
			page_count INT DEFAULT 0 NOT NULL,
			visitor_ip VARCHAR(80) DEFAULT '' NOT NULL,
			browser_name VARCHAR(255) DEFAULT '' NOT NULL,
			browser_lng VARCHAR(255) DEFAULT '' NOT NULL,
			UNIQUE KEY id (id)
		) $charset_collate;";
		
		// make sure the table does not exist
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
	}
	
	/*
		stores the visitor's id in a cookie
	*/
	function setVisitorId($id)
	{
		setcookie( $this->idVarName, $id, time()+3600, '/' );
	}
	
	/*
		attempts to read the visitor's id from a cookie
	*/
	function getVisitorId()
	{
		$visitorId = 0;
		if ( isset( $_COOKIE[ $this->idVarName ] ) ) {
			$visitorId = (int)$_COOKIE[ $this->idVarName ];
		}
		return $visitorId;
	}
	
	/*
		stores the visitor's data into the DB
	*/
	function storeVisitorData()
	{
		global $wpdb;
		
		$table_name = $wpdb->prefix . $this->tableName;
		
		// get the visitor's id, or 0, if its the first page
		$visitorId = $this->getVisitorId();
		
		if ( $visitorId == 0 ) {
			
			// if its the first page, insert a row
			$data = array(
				'page_count' => 1,
				'visitor_ip' => sanitize_text_field( $_SERVER['REMOTE_ADDR'] ),
				'browser_name' => sanitize_text_field( $_SERVER['HTTP_USER_AGENT'] ),
				'browser_lng' => sanitize_text_field( $_SERVER['HTTP_ACCEPT_LANGUAGE'] )
			);
			
			$wpdb->insert( $table_name, $data );
			
			$visitorId = $wpdb->insert_id;
			
		} else {
			
			// if its not the first page, update the pages count
			$query = "UPDATE $table_name SET page_count=page_count+1 WHERE id=$visitorId";
			
			$wpdb->query( $query );
		}
		
		// store the visitor's id
		$this->setVisitorId( $visitorId );
	
	}

} // end class

$visitorData_instance = new visitorData;