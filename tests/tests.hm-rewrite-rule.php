<?php

class HM_Rewrite_Rule_Test_Case extends WP_UnitTestCase {

	function testSetRegex() {

		$rule = new HM_Rewrite_Rule( '~foo~' );

		$this->assertEquals( $rule->get_regex(), '~foo~' );
	}

	function testSetRegexWithNoDelimeter() {

		$rule = new HM_Rewrite_Rule( '^foo$');

		$this->assertNotEquals( $rule->get_regex(), '^foo$');
	}

	function testSetWPQueryArgs() {

		$rule = new HM_Rewrite_Rule( '^foo$' );
		$rule->set_wp_query_args( 'posts_per_page=10' );

		$this->assertEquals( $rule->get_wp_query_args(), array( 'posts_per_page' => 10 ) );
	}

	function testRequestCallback() {

		$rule = new HM_Rewrite_Rule( '^foo$' );
		$called = false;

		$rule->add_request_callback( function() use ( &$called ) {
			$called = true;
		} );

		$rule->matched_rule();

		$this->assertTrue( $called );
	}

	function testParseQueryCallback() {

		$rule = new HM_Rewrite_Rule( '^foo$' );
		$called = false;

		$rule->add_parse_query_callback( function() use ( &$called ) {
			$called = true;
		} );

		$rule->matched_rule();

		new WP_Query( 'foo' );

		$this->assertTrue( $called );
	}

	function testQueryCallback() {
		$rule = new HM_Rewrite_Rule( '^foo$' );
		$called = false;

		$rule->add_query_callback( function() use ( &$called ) {
			$called = true;
		} );

		$rule->matched_rule();

		//supress notice
		$_SERVER['HTTP_HOST'] = '';
		do_action( 'template_redirect' );

		$this->assertTrue( $called );
	}

	function testTitleCallback() {
		$rule = new HM_Rewrite_Rule( '^foo$' );
		$called = false;

		$rule->add_title_callback( function() use ( &$called ) {
			$called = true;
		} );

		$rule->matched_rule();

		apply_filters( 'wp_title', 'Foo' );

		$this->assertTrue( $called );
	}

	function testAddBodyClass() {

		$rule = new HM_Rewrite_Rule( '^foo$' );
		$called = false;

		$rule->add_body_class( 'mybodyclass' );

		$rule->matched_rule();

		$classes = apply_filters( 'body_class', array() );

		$this->assertTrue( in_array( 'mybodyclass', $classes ) );

	}

	function testBodyClassCallback() {
		$rule = new HM_Rewrite_Rule( '^foo$' );
		$called = false;

		$rule->add_body_class_callback( function() use ( &$called ) {
			$called = true;
		} );

		$rule->matched_rule();

		apply_filters( 'body_class', array() );

		$this->assertTrue( $called );
	}
}