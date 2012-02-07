<?php

class HM_Accounts_SSO_Facebook_Test_Case extends WP_UnitTestCase {
	
	function setUp() {
	
		$this->fresh_user = new WP_User( wp_insert_user( array( 'user_login' => rand(0,1000) ) ) );
	
	}
	
	function tearDown() {
		
		wp_delete_user( $this->fresh_user->ID );
		
	}
	
	function setupDefines() {
		
		if ( ! defined( 'HM_ENABLE_ACCOUNTS' ) )
			define( 'HM_ENABLE_ACCOUNTS', true );
		
		// dont continue if ther are set up already
		if ( ! defined( 'HMA_SSO_FACEBOOK_APP_ID' ) ) {
			define( 'HMA_SSO_FACEBOOK_APP_ID', '146234202162352' );
			define( 'HMA_SSO_FACEBOOK_APPLICATION_SECRET', 'f0cecd618f9f3ebccff207a50d873f8c' );
		}
		
	}

	function testThrowsErrorIfNotDefines() {
	
		if ( ! defined( 'HM_ENABLE_ACCOUNTS' ) )
			define( 'HM_ENABLE_ACCOUNTS', true );
		
		// dont continue if ther are set up already
		if ( defined( 'HMA_SSO_FACEBOOK_APP_ID' ) ||  defined( 'HMA_SSO_FACEBOOK_APP_ID' ) )
			$this->markTestSkipped( 'Remove defines for Facebook' );
		
		try { 
			$facebook = HMA_SSO_Facebook::instance();
		}
		catch( Exception $e ) {
		
		}
		
		$this->assertNotNull( $e, 'Facebook did not throw error' );
	
	}
	
	function testDoesNotThrowsErrorIfDefines() {
	
		$this->setupDefines();
		
		try { 
			$facebook = HMA_SSO_Facebook::instance();
		}
		catch( Exception $e ) {
		
		}
		
		$this->assertNull( $e, 'Facebook threw error' );
		
		$this->assertNotNull( $facebook->client );
	
	}
	
	function testFreshUserIsNotAuthenticated() {
	
		$this->setupDefines();
		
		$facebook = HMA_SSO_Facebook::instance();
		
		$facebook->set_user( $this->fresh_user );
		
		$this->assertFalse( $facebook->is_authenticated() );
	}
	
	function testFreshUserCanNotLogin() {
		
		$this->setupDefines();
		
		$facebook = HMA_SSO_Facebook::instance();
		
		$facebook->set_user( $this->fresh_user );
		
		$this->assertWPError( $facebook->login() );
		$this->assertEquals( $facebook->login()->get_error_code(), 'no-logged-in-to-facebook' );
	}
	
	function testCanNotLinkWithNoFacebook() {
		
		$this->setupDefines();
		
		$facebook = HMA_SSO_Facebook::instance();
		
		$facebook->set_user( $this->fresh_user );
		
		wp_set_current_user( $this->fresh_user->ID );
		
		$this->assertWPError( $facebook->link() );
	}

}