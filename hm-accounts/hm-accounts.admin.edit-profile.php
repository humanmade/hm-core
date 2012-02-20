<?php

function hma_add_sso_fields_to_edit_profile( $user ) {

	if ( $sso_providers = hma_get_sso_providers_for_user( $user->ID ) ) : ?>

		<h3>Single Sign-on Providers</h3>
		
		<?php foreach( $sso_providers as $sso_provider ) : ?>

		<table class="form-table">

		    <tr>
		    	<th><strong><?php echo $sso_provider->name ?></strong></th>
		    	<td>
		    		<ul>

			<?php foreach( $sso_provider->user_info() as $key => $value ) :
				if ( is_object( $value ) )
					continue; ?>

						<li><strong><?php echo $key; ?></strong>: <?php echo implode( ', ', (array) $value ); ?></li>

		    <?php endforeach; ?>

		    		</ul>
		    	</td>
		    </tr>

		</table>

		<?php endforeach; ?>

	<?php endif;

}
add_action( 'edit_user_profile', 'hma_add_sso_fields_to_edit_profile' );