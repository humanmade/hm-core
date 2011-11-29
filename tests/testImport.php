<?php

// Import the WP Thumb unit tests
foreach ( glob( dirname( dirname( __FILE__ ) ) . '/WPThumb/tests/*.php' ) as $filename )
	include ( $filename );