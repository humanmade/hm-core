<?php

class HMCronTestCase extends WP_UnitTestCase {

	function testTableExists() {
		
		$cron = HM_Cron::getInstance();
		
		global $wpdb;
		
		$this->assertNotNull( $wpdb->get_var( "show tables like 'wp_hm_cron'" ) );
	}
	
	function testAddingCron() {
		
		$cron = HM_Cron::getInstance();
		$cron->add( 'testCron', time(), 'daily', array( 'foo' => 'bar' ) );
		
		$this->assertTrue( $cron->get( 'testCron', array( 'foo' => 'bar' ) ) > 0 );
		
		$cron->delete( 'testCron', array( 'foo' => 'bar' ) );
	}
	
	function testDeletingCron() {
		
		$cron = HM_Cron::getInstance();
		$cron->add( 'testDeleteCron', time(), 'daily', array( 'foo' => 'bar' ) );
		
		$this->assertTrue( $cron->get( 'testDeleteCron', array( 'foo' => 'bar' ) ) > 0 );
		
		$cron->delete( 'testDeleteCron', array( 'foo' => 'bar' ) );
		
		$this->assertFalse( $cron->get( 'testDeleteCron', array( 'foo' => 'bar' ) ) > 0 );
	}
	
	function testCronHookFires() {
		
		$cron = HM_Cron::getInstance();
		$cron->add( 'testHookCron', time(), 'daily', array( 'foo' => 'bar' ) );
		
		$passed = false;
		
		add_action( 'testHookCron', function() use ( &$passed ) {
		
			$passed = true;

		} );
		
		$cron->checkForCron();
		
		$cron->delete( 'testHookCron', array( 'foo' => 'bar' ) );
		
		$this->assertTrue( $passed );
	}
}