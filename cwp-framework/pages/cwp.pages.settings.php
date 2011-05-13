
<div class="wrap">
<?php screen_icon(); ?>
<h2><?php echo esc_html( $page->name ); ?> Settings</h2>

<form method="post" action="options.php">
<?php settings_fields($page->get_page_id()); ?>

<?php do_settings_sections($page->get_page_id()); ?>

<p class="submit">
	<input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e('Save Changes') ?>" />
</p>

</form>

<?php do_action( 'cwp_settings_page_below_form', $page ); ?>
<?php do_action( 'cwp_settings_page_below_form_' . $page->get_page_id(), $page ); ?>

</div>