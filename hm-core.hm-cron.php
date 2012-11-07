<?php

class HM_Cron {

	private $DB;
	private static $instance;
	private static $table;

	function __construct() {

		global $wpdb;

		$this->DB = $wpdb;
		$this->table = "{$this->DB->prefix}hm_cron";
		$this->createDatabaseTable();
	}

	public function getInstance() {

		if ( empty( self::$instance ) )
			self::$instance = new HM_Cron();

		return self::$instance;

	}

	public function add( $handle, $start, $interval, $args = array() ) {

		$this->DB->insert( $this->table, array( 'handle' => $handle, 'next' => $start, 'interval' => $interval, 'args' => json_encode( $args ) ) );

	}

	public function delete( $handle, $args = array() ) {
		return (bool) $this->DB->get_var( $this->DB->prepare( "DELETE FROM $this->table WHERE handle = %s AND args = %s", $handle, json_encode( $args ) ) );
	}

	public function get( $handle, $args = array() ) {
		return $this->DB->get_var( $this->DB->prepare( "SELECT next FROM $this->table WHERE handle = %s AND args = %s", $handle, json_encode( $args ) ) );
	}

	public function checkForCron() {

		$crons = $this->DB->get_results( "SELECT * FROM $this->table WHERE `next` <= " . time() . " AND running = 0 LIMIT 1" );

		foreach ( $crons as $cron )
			$this->runCron( $cron );

	}

	public function checkForCronAsynchronous() {

		if ( ! $this->hasCronWaiting() )
			return;

		$cron_url = add_query_arg( array( 'hm-action' => 'process-cron', 't' => time() ), get_bloginfo( 'url' ) );

		wp_remote_post( $cron_url, array('timeout' => 0.01, 'blocking' => false, 'sslverify' => false ) );
	}

	private function hasCronWaiting() {
		return (bool) $this->DB->get_results( "SELECT * FROM $this->table WHERE `next` <= " . time() . " AND running = 0 LIMIT 1" );
	}

	private function createDatabaseTable() {

		if ( get_option( 'hm_created_cron_table' ) )
			return;

		$this->DB->query( "CREATE TABLE `{$this->DB->prefix}hm_cron` (
			  `cron_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			  `handle` varchar(255) DEFAULT NULL,
			  `interval` varchar(255) DEFAULT NULL,
			  `args` longtext,
			  PRIMARY KEY (`cron_id`)
			) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;" );

		update_option( 'hm_created_cron_table', true );

	}

	private function getIntervals() {
		return array(
			'hourly'	=> 60*60,
			'daily' 	=> 60*60*24
		 );
	}

	private function runCron( $item ) {

		$this->DB->update( $this->table, array( 'running' => 1 ), array( 'cron_id' => $item->cron_id ) );

		$result = null;

		$result = apply_filters( $item->handle, $result, (array) json_decode( $item->args ) );

		if ( is_wp_error( $result ) ) {

			error_log( $this->DB->update( $this->table, array( 'next' => time() + 1, 'last_message' => json_encode( $result ), 'running' => 0 ), array( 'cron_id' => $item->cron_id ) ) );

		} else {

			$intervals = $this->getIntervals();

			$this->DB->update( $this->table, array( 'next' => time() + $intervals[$item->interval], 'last_message' => json_encode( $result ), 'running' => 0 ), array( 'cron_id' => $item->cron_id ) );

		}
	}

}

add_action( 'init', function() {

	if ( isset( $_GET['hm-action'] ) && $_GET['hm-action'] == 'process-cron' ) {
		ignore_user_abort(1);
		set_time_limit(0);
		HM_Cron::getInstance()->checkForCron();
	}

} );