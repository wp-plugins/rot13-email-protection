<?php
/*
Plugin Name: Rot13 Email Protection
Plugin URI: http://techtrouts.com/rot13-email-protection-for-wordpress/
Description: <a href="http://techtrouts.com/rot13-email-protection-for-wordpress/" title="Rot13 Email Protection for Wordpress">Rot13 Email Protection</a> for Wordpress, protects all email addresses and mailto: links on your blog from email harvesters using rot13 encryption and/or text mode.
Version: 0.1
Author: 9tree
Author URI: http://9tree.net/

    Copyright (c) 2008, 9Tree (http://techtrouts.com/)
    Rot13 Email Protection for Wordpress is released under the GNU General Public License
    http://www.gnu.org/licenses/gpl.txt

    This is a WordPress plugin (http://wordpress.org).
*/


//plugin variables
$ROT13_DEFAULT_FILTERS=array(
	/* post and Page filters */
	'the_author_email'=>array('name'=>'Post Author\'s Email', 'default_mode'=>2), 
	'the_content'=>array('name'=>'Post Content', 'default_mode'=>2), 
	'the_excerpt'=>array('name'=>'Post Excerpts', 'default_mode'=>2), 
	/* comment filters */
	'comment_author_email'=>array('name'=>'Commenter\'s Email', 'default_mode'=>2), 
	'comment_text'=>array('name'=>'Comments\'s Content', 'default_mode'=>2), 
	'comment_excerpt'=>array('name'=>'Comment\'s Excerpts', 'default_mode'=>2), 
	/*rss filters*/
	'comment_text_rss'=>array('name'=>'RSS Post Author\'s Email', 'default_mode'=>2),
	'the_content_rss'=>array('name'=>'RSS Post Content', 'default_mode'=>2), 
	'the_excerpt_rss'=>array('name'=>'RSS Post Excerpts', 'default_mode'=>2) 
	);

$ROT13_EP_GEN_PATTERN='%(<a +.*?mailto:)?((?:[a-z0-9](?:[a-z0-9_-]*\.?[a-z0-9])*)(?:\+[a-z0-9]+)?@'.
	'(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])*\.)*(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9]+)*)\.[a-z]{2,6})(.*?>.*?</a>)?%i';


//actions
add_action('init', 'rot13_ep_init');






//init
function rot13_ep_init(){
	add_action('admin_menu', 'rot13_ep_config_page');
	apply_rot13_email_protection();
}



//administration
function rot13_ep_config_page() {
	if ( function_exists('add_submenu_page') )
		add_submenu_page('plugins.php', __('Rot13 Email Protection'), __('Rot13 Email Protection'), 'manage_options', 'rot13_ep_conf', 'rot13_ep_conf');

}
function rot13_ep_conf(){
	global $ROT13_DEFAULT_FILTERS;
	//POST actions
	if ( isset($_POST['submit']) ) {
		if ( function_exists('current_user_can') && !current_user_can('manage_options') )
			die(__('Cheatin&#8217; uh?'));
			
		//updates filters
		foreach($_POST as $filter=>$value)
			if(array_key_exists($filter, $ROT13_DEFAULT_FILTERS)) update_option( 'rot13_ep_'.$filter, intval($value) );
	}
	
	
	?>
	<?php if ( !empty($_POST ) ) : ?>
	<div id="message" class="updated fade"><p><strong><?php _e('Options saved.') ?></strong></p></div>
	<?php endif; ?>
	<div class="wrap">
	<h2><?php _e('Rot13 Email Protection'); ?></h2>
	<div class="narrow">
	<form action="" method="post" id="akismet-conf" style="margin: auto; width: 600px; ">
		<p><?php printf(__('<a href="%1$s">Rot13 Email Protection</a> blinds all email addresses and mailto: links on your blog from common email harvesters using ROT13 encryption and/or text mode.'), 'http://techtrouts.com/rot13-email-protection-for-wordpress/'); ?></p>
		<table id="rot13_table">
			<tr>
				<td style="width:100%"><h3>Filter</h3></td>
				<td><h3>ROT13 Protection</h3></td>
				<td><h3>Text Protection</h3></td>
				<td><h3>None</h3></td>
			</tr>
			<?php foreach($ROT13_DEFAULT_FILTERS as $filter=>$defaults){
				$value=rot13_filter_check($filter);
				?>
			<tr>
				<td><label><?php echo $defaults['name'];?></label></td>
				<td><input type="radio" name="<?php echo $filter;?>" value="2"<?php echo $value==2?' checked="checked"':'';?>/></td>
				<td><input type="radio" name="<?php echo $filter;?>" value="1"<?php echo $value==1?' checked="checked"':'';?>/></td>
				<td><input type="radio" name="<?php echo $filter;?>" value="0"<?php echo $value==0?' checked="checked"':'';?>/></td>
			</tr>
				<?php
			}?>
		</table>
		<input type="submit" name="submit" value="Change Settings" style="float:right;margin:15px;" />
		<p><?php printf(__('Plugin developed and maintained by <a href="%1$s" title="9tree, Natural Revolution for the Web">9tree</a>.'), 'http://9tree.net'); ?></p>
	</form>
	</div>
	</div>
	<style>
	#rot13_table{
		border:1px #ccc solid;
		border-spacing:10px;
	}
	</style>
	<?php
}




//rot13 apply filters
function apply_rot13_email_protection(){
	global $ROT13_DEFAULT_FILTERS;
		
	//check for filters at the database
	$filters = $ROT13_DEFAULT_FILTERS;
	
	//add filters
	foreach($filters as $filter=>$mode){
		switch(rot13_filter_check($filter)){
			case 1:
				add_filter($filter, 'text_email_protection');
			break;
			case 2:
				add_filter($filter, 'rot13_email_protection');
			break;
			default:
			break;
		}
	}
}


//utilities
function rot13_filter_check($filter){
	global $ROT13_DEFAULT_FILTERS;
	$user_mode=get_option('rot13_ep_'.$filter);
	return $user_mode===false?$ROT13_DEFAULT_FILTERS[$filter]['default_mode']:$user_mode;
}




//PARSERS

//rot13 protection parser
function rot13_email_protection($text) {
	global $ROT13_EP_GEN_PATTERN;
    return preg_replace_callback($ROT13_EP_GEN_PATTERN, "rot13_protection_callback", $text); 
}
//rot13 protection filter
function rot13_protection_callback($matches) {
	$encode=$matches[1].$matches[2].$matches[3];
	$encode=str_replace("/","\\057",str_replace('"', '\\"',str_replace(".","\\056", str_replace("@", "\\100",str_rot13($encode)))));
	$encode="
			<script type=\"text/javascript\">
			/* <![CDATA[ */
	    		document.write(\"".$encode."\".replace(/[a-zA-Z]/g, function(c){return String.fromCharCode((c<=\"Z\"?90:122)>=(c=c.charCodeAt(0)+13)?c:c-26);}));
			/* ]]-> */
	   		</script>";
    return 	$encode;
}




//text mode protection parser
function text_email_protection($text) {
	global $ROT13_EP_GEN_PATTERN;
    return preg_replace_callback($ROT13_EP_GEN_PATTERN, "text_protection_callback", $text);
}
//text mode protection filter
function text_protection_callback($matches) {		
	return str_replace(".", "[:dot:]", str_replace("@", "[:at:]", $matches[1].$matches[2].$matches[3]));
}
?>