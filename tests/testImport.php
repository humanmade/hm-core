<?php

// Import the WP Thumb unit tests
foreach ( glob( dirname( dirname( __FILE__ ) ) . '/WPThumb/tests/*.php' ) as $filename )
	include ( $filename );
	
// Import the HM Accounts unit tests
foreach ( glob( dirname( dirname( __FILE__ ) ) . '/hm-accounts/tests/*.php' ) as $filename )
	include ( $filename );