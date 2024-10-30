<?php
/*
Plugin Name: IE CSS Definer
Plugin URI: http://hebeisenconsulting.com/wordpress-ie-css-and-script-definer/
Description: iE CSS and SCRIPT Definer allows you to easily and quickly enter internet explorer version specific SCRIPT or CSS definitions without separate css files.
Version: 1.3
Author: Hebeisen Consulting - R Bueno
Author URI: http://www.hebeisenconsulting.com
License: A "Slug" license name e.g. GPL2

   Copyright 2012 Hebeisen Consulting

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

add_action('wp_head', 'ie_css_head');
add_action('admin_menu', 'ie_css_menu');

function ie_css_install()
{
    global $wpdb;
    $table = $wpdb->prefix . "ie_css";
	if($wpdb->get_var("show tables like '$table'") != $table) {
	    $sql = "CREATE TABLE " . $table . " (
					  id int(11) NOT NULL AUTO_INCREMENT,
					  cond varchar(150) NOT NULL,
					  css text NOT NULL,
					  PRIMARY KEY (id)
					)";
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	    dbDelta($sql);
	}
}
register_activation_hook(__FILE__, 'ie_css_install');

function ie_css_uninstall()
{
    global $wpdb;
    $table = $wpdb->prefix . "ie_css";
    
    /**
     * V 1.3
    */
    $wpdb->query("select * into outfile '" . plugin_dir_path( __FILE__ ) . "test.txt" . "' from $table ") or die( mysql_error() );
        
    $wpdb->query("DROP TABLE $table");
}
//register_deactivation_hook(__FILE__, 'ie_css_uninstall');

function ie_css_head()
{
	global $wpdb;
	$table = $wpdb->prefix . "ie_css";
	
	$sql = $wpdb->get_results("select * from " . $table . " order by id desc"); // or die(mysql_error());
	
	if( $sql ):
	 echo "\n";
	 echo '<!-- START OF IE-CSS PLUGIN -->' . "\n\n";
	 for( $i=0; $i < count( $sql ); $i++ ):
		
		/**
		 * If <style> tag exist in css, dont
		 * print this
		*/
		$o_style = substr_count( stripcslashes( htmlspecialchars_decode( $sql[$i]->css ) ), '<style' );
		$c_style = substr_count( stripcslashes( htmlspecialchars_decode( $sql[$i]->css ) ), "</style>" );
		
		//var_dump( $o_style );
		//var_dump( $c_style );				
		echo '<!--' . $sql[$i]->cond . ">\n";
		
		/**
		 * IF:
		 *	Open style is exist AND close style is exist
		 * 	AND
		 *	Open style == close stlye
		 *	
		 * Print css from DB
		 * ELSE:
		 * Print <style type="text/css">
		 *
		 *
		*/
		if( ( !$o_style || !$c_style ) && ( $o_style != $c_style ) ){
		
		echo '<style type="text/css">' . "\n";
		
		}
		echo stripcslashes( htmlspecialchars_decode( $sql[$i]->css ) ) . "\n";
		
		if( ( !$o_style || !$c_style ) && ( $o_style != $c_style ) ){
		
		echo '</style>' . "\n";
		
		}
		echo '<![endif]-->' . "\n\n";
	 endfor;
	 echo '<!-- END OF IE-CSS PLUGIN-->' . "\n\n";
	endif;
}

function ie_css_admin_head(){

}

function ie_css_menu()
{
	//$page = add_options_page('IE CSS', 'IE CSS', 'manage_options', 'ie-css-slug', 'ie_css_option');
		
	$page = add_submenu_page( 'tools.php', 'iE CSS STYLE and SCRIPT Definer', 'iE CSS/SCRIPT Definer', 'manage_options', 'ie-css-slug', 'ie_css_option');
	add_action( 'admin_head-' . $page, 'ie_css_admin_head' );
}

function ie_jquery(){
	wp_enqueue_style( 'jquery' );
}

add_action( 'wp_enqueue_scripts', 'ie_jquery' );
add_action('admin_enqueue_scripts', 'ie_jquery');

function check_select( $items_to_check, $corr_value )
{	
	global $wpdb;
	$table = $wpdb->prefix . "ie_css";
	
	$sql = $wpdb->get_results( "SELECT * FROM $table" );
	
	$pattern = "/^$items_to_check/";
	
	$matches = preg_match(  $pattern, $sql[0]->cond );
	
	echo $pattern;
	
	//print_r( $sql );
	
	print_r($matches);
}

function ie_css_trim( $css )
{
	$count=str_word_count( $css, 0 );
	
	if( $count > 200 ){
		return substr( $css, 200 );
	}else{
		return $css;
	}
	
}

function ie_css_check_bracket( $ie_css ){
	
	$counter=0;
	for( $x = 0; $x < strlen( $ie_css ); $x++ ){
		
		$char = $ie_css[ $x ];
		if( $char == '{' ){
			$counter++;
		}elseif( $char == '}' ){
			$counter--;
		}
		
		if( $counter < 0 ){
			return false;
		}		
	}
	return $counter == 0;
	
}

function ie_css_option()
{
	echo '<div class="wrap">';
	
	echo '<h1>iE CSS STYLE and SCRIPT Definer</h1>';
	//echo plugin_dir_path( __FILE__ ) . "test.sql";
	global $wpdb;
	$table = $wpdb->prefix . "ie_css";
	
	switch( $_GET['a'] ){
		case'add':		
			$o_curly = substr_count( $_POST['css'], '{' );
			$c_curly = substr_count( $_POST['css'], '}' );
			/*
			$o_style = substr_count( stripcslashes( $_POST['css'] ), '<style type="text/css">' );
			$c_style = substr_count( stripcslashes( $_POST['css'] ), "</style>" );
			$o_script = substr_count( stripcslashes( $_POST['css'] ), '<script language="javascript">' );
			$c_script = substr_count( stripcslashes( $_POST['css'] ), "</script>" );
			*/
			
			$o_style = strpos( stripcslashes( $_POST['css'] ), "<style" );
			$c_style = strpos( stripcslashes( $_POST['css'] ), "</style>" );
			$o_script = strpos( stripcslashes( $_POST['css'] ), "<script" );
			$c_script = strpos( stripcslashes( $_POST['css'] ), "</script>" );

			//var_dump ( $o_style );
			//var_dump ( $c_style );
			//var_dump ( $o_script );
			//var_dump ( $c_script );
						
						
			$ieerror = "";
			
			//var_dump( ie_css_check_bracket( stripcslashes( $_POST['css'] ) ) );
			///if( ( $o_curly % 2 != 0 ) || ( $c_curly % 2 != 0 ) ){
			if( !ie_css_check_bracket( stripcslashes( $_POST['css'] ) ) ){
				$ieerror .= '<div id="message" class="error"><p>Error: Make sure that the { } bracket are paired correctly. We detected an incorrect number of them.</p></div>';
			}
			
			/**
			 * <style type="text/css"> must strictly present
			*/				
			if( ( $o_style === false ) || ( $c_style === false )  ){			
				$ieerror .= '<div id="message" class="error"><p>Error: Make sure that your defenitions are enclosed in &#60;STYLE>&#60;/STYLE> tags.</p></div>';
			}									
						
			/**
			 * IF:
			 * open script is missing AND close script is exist
			 *	OR 
			 * open script is exist AND close script missing
			 
			 @ Print error message
				 
			 * ENDIF;
			*/									
			if( ( $o_script === false && $c_script !== false ) || ( $o_script !== false && $c_script === false ) ){
				$ieerror .= '<div id="message" class="error"><p>Error: Make sure that your defenitions are enclosed in &#60;SCRIPT>&#60;/SCRIPT> tags.</p></div>';
			}						
							
			if( $ieerror != "" ){
				echo $ieerror;					
				?>			
			
							<form id="ie-css-definer-form" method="post" action="tools.php?page=ie-css-slug&a=add">
					<p>
				  	When the Internet Explorer version being used is 
				  	 <select name= "compare">				  	 
				  	  <option value="=" <?php selected( $_POST['compare'], "="); ?>>=</option>
				  	  <option value="!=" <?php selected( $_POST['compare'], "!="); ?>>!=</option>
				  	  <option value=">" <?php selected( $_POST['compare'], ">"); ?>>></option>
				  	  <option value="<" <?php selected( $_POST['compare'], "<"); ?>><</option>
				  	  <option value=">=" <?php selected( $_POST['compare'], ">="); ?>>>=</option>
				  	  <option value="<=" <?php selected( $_POST['compare'], "<="); ?>><=</option>
				  	 </select>
				  	 
				  	 <select name = "ie_version">
				  	  <option value="IE 6" <?php selected( $_POST['ie_version'], "IE 6"); ?>>IE6</option>
				  	  <option value="IE 7" <?php selected( $_POST['ie_version'], "IE 7"); ?>>IE7</option>
				  	  <option value="IE 8" <?php selected( $_POST['ie_version'], "IE 8"); ?>>IE8</option>
				  	  <option value="IE 9" <?php selected( $_POST['ie_version'], "IE 9"); ?>>IE9</option>
				  	  <option value="IE 10" <?php selected( $_POST['ie_version'], "IE 10"); ?>>IE10</option>
				  	  <option value="IE" <?php selected( $_POST['ie_version'], "IE"); ?>>All Versions</option>
				  	 </select>
				  	 
				  	 <input type="button" id="ie-script" class="button-primary" value="<script>">
		
					<script type="text/javascript">
					jQuery(document).ready(function(){
					
					  jQuery("#ie-script").click(function(){
					 	var txt = "<script language=" + String.fromCharCode(34) + "javascript" + String.fromCharCode(34) + ">";
					     	jQuery("#css").val(jQuery("#css").val() + '\r\n' + txt + '\r\n\n' + '<\/script>');
					  });
					});
					        </script>
		
					<input type="button" id="ie-style" class="button-primary" value="<style>">
					
					<script type="text/javascript">
					jQuery(document).ready(function(){
					
					  jQuery("#ie-style").click(function(){
					  	var txt = "<style type=" + String.fromCharCode(34) + "text/css" + String.fromCharCode(34) + ">";
					   jQuery("#css").val(jQuery("#css").val() + '\r\n' + txt + '\r\n\n' + '</style>');
					  });
					});
					        </script>
		
					</p>
		
					<p>
					<textarea name="css" id="css" rows="20" cols="100"><?php echo stripcslashes( $_POST['css'] ); ?></textarea>
				    	</p>
		
					<p>
					<input type="submit" class="button-primary" value="Add conditional CSS statement">
				     </p>
		
				</form>

				<?php
			}else{
				//echo "No error";
			
				switch( $_POST['compare'] )
				{
					case'=':
						$cond = "[if ";
					break;
					
					case'!=':
						$cond = "[if !";
					break;
					
					case'>':
						$cond = "[if gt ";
					break;
					
					case'<':
						$cond = "[if lt ";
					break;
					
					case'>=':
						$cond = "[if gte ";
					break;
					
					case'<=':
						$cond = "[if lte ";
					break;
				}
				
				$wpdb->insert( 
					$table, 
					array( 			
						'cond' => $cond . $_POST['ie_version'] . " ]", 
						'css' => htmlspecialchars( $_POST['css'], ENT_QUOTES )
					)
				);// or die( mysql_error() );
				
				update_option( 'compare', $_POST['compare'] );
				update_option( 'ie_version', $_POST['ie_version'] );
				
				echo '<div id="message" class="updated"><p>New CSS style has been added.</p></div>';
				echo '<p><input type="button" onclass="button-secondary" onclick="location.href=\'tools.php?page=ie-css-slug\'" value="Back"></p>';
			}
		break;
		
		case'edit':
			
			/**
			 * Check if saving
			*/
			if( $_GET['save'] == "true" ):
			
				/**
				 * Error checking and validation
				*/
				
				$ieerror = "";
				
				if( !ie_css_check_bracket( stripcslashes( $_POST['css'] ) ) ){
					$ieerror .= '<div id="message" class="error"><p>Error: Make sure that the { } bracket are paired correctly. We detected an incorrect number of them.</p></div>';
				}
				
				$o_style = strpos( stripcslashes( $_POST['css'] ), "<style" );
				$c_style = strpos( stripcslashes( $_POST['css'] ), "</style>" );
				$o_script = strpos( stripcslashes( $_POST['css'] ), "<script" );
				$c_script = strpos( stripcslashes( $_POST['css'] ), "</script>" );
				
				//var_dump ( $o_style );
				//var_dump ( $c_style );
				//var_dump ( $o_script );
				//var_dump ( $c_script );
				
				/**
				 * <style type="text/css"> must strictly present
				*/				
				if( ( $o_style === false ) || ( $c_style === false )  ){			
					$ieerror .= '<div id="message" class="error"><p>Error: Make sure that your defenitions are enclosed in &#60;STYLE>&#60;/STYLE> tags.</p></div>';
				}			
				
				/**
				 * IF:
				 * open script is missing AND close script is exist
				 *	OR 
				 * open script is exist AND close script missing
				 
				 @ Print error message
				 
				 * ENDIF;
				*/									
				if( ( $o_script === false && $c_script !== false ) || ( $o_script !== false && $c_script === false ) ){
					$ieerror .= '<div id="message" class="error"><p>Error: Make sure that your defenitions are enclosed in &#60;SCRIPT>&#60;/SCRIPT> tags.</p></div>';
				}
				
				/**
				 * IF ieerror not empty, print and prevent saving
				*/
				if( $ieerror ){
					echo $ieerror;
					echo '<p><input type="button" onclass="button-secondary" onclick="history.back()" value="Back"></p>';
				}else{
					/**
					 * ELSE
					 * NO error found, do saving here
					*/
					switch( $_POST['compare'] )
					{
						case'=':
							$cond = "[if ";
						break;
						
						case'!=':
							$cond = "[if !";
						break;
						
						case'>':
							$cond = "[if gt ";
						break;
						
						case'<':
							$cond = "[if lt ";
						break;
						
						case'>=':
							$cond = "[if gte ";
						break;
						
						case'<=':
							$cond = "[if lte ";
						break;
					}
					
					$wpdb->update(
						$table,
						array( 
							'cond' => $cond . $_POST['ie_version'] . " ]", 
							'css' => stripcslashes( $_POST['css'] )
						),
						array(
							'id' => $_GET['id'],
						)
					);
					
					update_option( 'compare', $_POST['compare'] );
					update_option( 'ie_version', $_POST['ie_version'] );
					
					echo '<div id="message" class="updated"><p>Style has been updated.</p></div>';
					echo '<p><input type="button" onclass="button-secondary" onclick="location.href=\'tools.php?page=ie-css-slug\'" value="Back"></p>';
				 }
			else:
				/**
				 * Saving is not present in param means
				 * still in edit form
				*/
			
				$result=$wpdb->get_results( "select * from " . $table . " where id = " . $_GET['id'] );
				
				/**
				 * Show edit form only if the request is correct
				*/
				if( $result ):					
					echo '<form id="ie-css-definer-form" method="post" action="tools.php?page=ie-css-slug&a=edit&save=true&id=' . $_GET['id'] . '">';
					//echo get_option( 'compare' );
					?>
					<p>
					  	When the Internet Explorer version being used is 
					  	 <select name= "compare">
					  	  <option value="=" <?php selected( get_option( 'compare' ), "="); ?>>=</option>
					  	  <option value="!=" <?php selected( get_option( 'compare' ), "!="); ?>>!=</option>
					  	  <option value=">" <?php selected( get_option( 'compare' ), ">"); ?>>></option>
					  	  <option value="<" <?php selected( get_option( 'compare' ), "<"); ?>><</option>
					  	  <option value=">=" <?php selected( get_option( 'compare' ), ">="); ?>>>=</option>
					  	  <option value="<=" <?php selected( get_option( 'compare' ), "<="); ?>><=</option>
					  	 </select>			  	 
					  	 
					  	 <select name = "ie_version">
					  	  <option value="IE 6" <?php selected( get_option( 'ie_version' ), "IE 6"); ?>>IE6</option>
					  	  <option value="IE 7" <?php selected( get_option( 'ie_version' ), "IE 7"); ?>>IE7</option>
					  	  <option value="IE 8" <?php selected( get_option( 'ie_version' ), "IE 8"); ?>>IE8</option>
					  	  <option value="IE 9" <?php selected( get_option( 'ie_version' ), "IE 9"); ?>>IE9</option>
					  	  <option value="IE 9" <?php selected( get_option( 'ie_version' ), "IE 109"); ?>>IE10</option>
					  	  <option value="IE" <?php selected( get_option( 'ie_version' ), "IE"); ?>>All Versions</option>
					  	 </select>
					  	 
					  	 <input type="button" id="ie-script" class="button-primary" value="<script>">
		
						<script type="text/javascript">
						jQuery(document).ready(function(){
						
						  jQuery("#ie-script").click(function(){
						 	var txt = "<script language=" + String.fromCharCode(34) + "javascript" + String.fromCharCode(34) + ">";
						     	jQuery("#css").val(jQuery("#css").val() + '\r\n' + txt + '\r\n\n' + '<\/script>');
						  });
						});
						        </script>
			
						<input type="button" id="ie-style" class="button-primary" value="<style>">
						
						<script type="text/javascript">
						jQuery(document).ready(function(){
						
						  jQuery("#ie-style").click(function(){
						  	var txt = "<style type=" + String.fromCharCode(34) + "text/css" + String.fromCharCode(34) + ">";
						   jQuery("#css").val(jQuery("#css").val() + '\r\n' + txt + '\r\n\n' + '</style>');
						  });
						});
					        </script>
					  	 
					     </p>
					<?php
					
					echo '<p>
						<textarea name="css" id="css" rows="20" cols="100">' . stripcslashes( $result[0]->css ) . '</textarea>
					     </p>';
					echo '<p>
						<input type="submit" class="button-primary" value="Save"> <input type="button" onclass="button-secondary" onclick="location.href=\'tools.php?page=ie-css-slug\'" value="Back">
					     </p>';
					echo '</form>';
				else:
					echo '<div id="message" class="error"><p>Wrong supplied parameter. Please go back and try again!</p></div>';
					echo '<p><input type="button" onclass="button-secondary" onclick="location.href=\'tools.php?page=ie-css-slug\'" value="Back"></p>';
				endif;
			
			endif;
		break;
		
		case'delete':			
			$delete = $wpdb->query( "DELETE FROM " . $table . " WHERE id = " . $_GET['id'] );
				
			if( $delete ):
				echo '<div id="message" class="updated"><p>Record deleted.</p></div>';					
				//wp_redirect( site_url() . '/wp-admin/tools.php?page=ie-css-slug&deleted=true' );
			else:
				echo '<div id="message" class="error"><p>Error: Wrong parameter supplied. </p></div>';
			endif;			
			
			echo '<p><input type="button" onclass="button-secondary" onclick="location.href=\'tools.php?page=ie-css-slug\'" value="Back"></p>';
		break;
		
		default:
			
			//if( $_GET['delete'] = "true" ){
			//	echo '<div id="message" class="updated"><p>Record has been deleted. </p></div>';
			//}
		?>
		<form id="ie-css-definer-form" method="post" action="tools.php?page=ie-css-slug&a=add">
			<p>
		  	When the Internet Explorer version being used is 
		  	 <select name= "compare">
		  	  <option value="=">=</option>
		  	  <option value="!=">!=</option>
		  	  <option value=">">></option>
		  	  <option value="<"><</option>
		  	  <option value=">=">>=</option>
		  	  <option value="<="><=</option>
		  	 </select>
		  	 
		  	 <select name = "ie_version">
		  	  <option value="IE 6">IE6</option>
		  	  <option value="IE 7">IE7</option>
		  	  <option value="IE 8">IE8</option>
		  	  <option value="IE 9">IE9</option>
		  	  <option value="IE 10">IE10</option>
		  	  <option value="IE">All Versions</option>
		  	 </select>
		  	 
		  	 <input type="button" id="ie-script" class="button-primary" value="<script>">

			<script type="text/javascript">
			jQuery(document).ready(function(){
			
			  jQuery("#ie-script").click(function(){
			 	var txt = "<script language=" + String.fromCharCode(34) + "javascript" + String.fromCharCode(34) + ">";
			     	jQuery("#css").val(jQuery("#css").val() + '\r\n' + txt + '\r\n\n' + '<\/script>');
			  });
			});
			        </script>

			<input type="button" id="ie-style" class="button-primary" value="<style>">
			
			<script type="text/javascript">
			jQuery(document).ready(function(){
			
			  jQuery("#ie-style").click(function(){
			  	var txt = "<style type=" + String.fromCharCode(34) + "text/css" + String.fromCharCode(34) + ">";
			   jQuery("#css").val(jQuery("#css").val() + '\r\n' + txt + '\r\n\n' + '</style>');
			  });
			});
			        </script>

			</p>

			<p>
			<textarea name="css" id="css" rows="20" cols="100">
<style type="text/css">
			
</style>
			</textarea>
		    	</p>

			<p>
			<input type="submit" class="button-primary" value="Add conditional CSS statement">
		     </p>

		</form>
		
		<form method="post" action="tools.php?page=ie-css-slug">
		<table class="widefat">
		<thead>
		    <tr>
		        <th>IE Version</th>       
		        <th>CSS Condition</th>
		        <th>Action</th>
		    </tr>
		</thead>
		<tfoot>
		    <tr>
			<th>IE Version</th>       
		        <th>CSS Condition</th>
		        <th>Action</th>
		    </tr>
		</tfoot>
		<tbody>	   
			<?php
				$dataFromDB = $wpdb->get_results( "SELECT * FROM " . $table . " order by id desc" );// or die( mysql_error());
				
				if( $dataFromDB ):
					for( $i=0; $i< count( $dataFromDB ); $i++ ):
						echo '<tr><td width="15%">' . $dataFromDB[$i]->cond . '</td><td width="50%"><p style="font-family: courier-new; font-size: 12px;">' . ie_css_trim( htmlspecialchars( unserialize( $dataFromDB[$i]->css ) ) ) . '</p></td><td width="15%" ><input type="button" id="ie-edit" onClick="location.href=\'tools.php?page=ie-css-slug&a=edit&id=' . $dataFromDB[$i]->id . '\';" class="button-primary" value="Edit"><input type="button" id="ie-delete" onClick="location.href=\'tools.php?page=ie-css-slug&a=delete&id=' . $dataFromDB[$i]->id . '\';" class="button-primary" value="Delete"></td></tr>';
					endfor;
				else:
					echo '<td colspan="3">No iE CSS rules defined.</td>';
				endif;
			?>	  
		</tbody>
		</table>
		</form>
	<?php	
		
	}

	
	/*
	if( $_GET['edit'] == "true" ):
		
		if( $_GET['save'] == "true" ):			
			
		else:
			$result=$wpdb->get_results( "select * from " . $table . " where id = " . $_GET['id'] );
									
			echo '<form id="ie-css-definer-form" method="post" action="tools.php?page=ie-css-slug&edit=true&save=true&id=' . $_GET['id'] . '">';
			//echo get_option( 'compare' );
			?>
			<p>
			  	When the Internet Explorer version being used is 
			  	 <select name= "compare">
			  	  <option value="=" <?php selected( get_option( 'compare' ), "="); ?>>=</option>
			  	  <option value="!=" <?php selected( get_option( 'compare' ), "!="); ?>>!=</option>
			  	  <option value=">" <?php selected( get_option( 'compare' ), ">"); ?>>></option>
			  	  <option value="<" <?php selected( get_option( 'compare' ), "<"); ?>><</option>
			  	  <option value=">=" <?php selected( get_option( 'compare' ), ">="); ?>>>=</option>
			  	  <option value="<=" <?php selected( get_option( 'compare' ), "<="); ?>><=</option>
			  	 </select>			  	 
			  	 
			  	 <select name = "ie_version">
			  	  <option value="IE 6" <?php selected( get_option( 'ie_version' ), "IE 6"); ?>>IE6</option>
			  	  <option value="IE 7" <?php selected( get_option( 'ie_version' ), "IE 7"); ?>>IE7</option>
			  	  <option value="IE 8" <?php selected( get_option( 'ie_version' ), "IE 8"); ?>>IE8</option>
			  	  <option value="IE 9" <?php selected( get_option( 'ie_version' ), "IE 9"); ?>>IE9</option>
			  	  <option value="IE 9" <?php selected( get_option( 'ie_version' ), "IE 109"); ?>>IE10</option>
			  	  <option value="IE" <?php selected( get_option( 'ie_version' ), "IE"); ?>>All Versions</option>
			  	 </select>
			     </p>
			<?php
			echo '<p>
				<textarea name="css" rows="20" cols="100">' . unserialize( $result[0]->css ) . '</textarea>
			     </p>';
			echo '<p>
				<input type="submit" class="button-primary" value="Save"> <input type="button" onclass="button-secondary" onclick="location.href=\'tools.php?page=ie-css-slug\'" value="Back">
			     </p>';
			echo '</form>';
		endif;
	endif;
	*/
	echo '</div>';
}

?>