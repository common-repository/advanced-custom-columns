<?php
class ACCSettings
{

    public function __construct()
    {
       add_action( 'admin_menu', array($this,'acc_add_admin_menu') );
       add_action( 'admin_init', array($this,'acc_settings_init' ));
    }

    /**
     * Add options page
     */

    function acc_add_admin_menu(  ) { 

            add_options_page( 'Custom columns', 'Custom columns', 'manage_options', 'custom_admin_columns_for_sorting_and_filtering', array($this,'acc_options_page') );

    }
    
    function acc_settings_init(  ) { 

            register_setting( 'pluginPage', 'acc_custom_filter_settings' );

            add_settings_section(
                    'acc_pluginPage_section', 
                    __( 'Add Custom Fields', 'test' ), 
                    array($this,'acc_settings_section_callback'), 
                    'pluginPage'
            );

            add_settings_field( 
                    'acc_text_field_0', 
                    __( '', 'test' ), 
                    array($this,'acc_text_field_0_render'), 
                    'pluginPage', 
                    'acc_pluginPage_section' 
            );


    }


    function acc_text_field_0_render(  ) { 

            $options = get_option( 'acc_custom_filter_settings' );
            
            echo "<table><tr><th>Post Type</th><th>Type</th><th>Parameters</th></tr>";
            $args=array('_builtin'=>FALSE);
            
            foreach ( get_post_types( $args, 'names' ) as $post_type ) {
                $post_type_lables=$post_type."_labels";
                  $post_type_removecols=$post_type."_removecols";
                echo "<tr>";
                echo "<td><label for='".$post_type."_field'><strong>".$post_type."</strong></label></td>";
                echo "<td>Fields</td>";
                echo "<td><input type='text' id='".$post_type."_field' name='acc_custom_filter_settings[".$post_type."]' size='50' value='".$options[$post_type]."'></td>";
                echo "</tr>";
                
                echo "<tr>";
                echo "<td><label for='".$post_type."_label'><strong>".$post_type."</strong></label></td>";
                echo "<td>Labels</td>";
                echo "<td><input type='text' id='".$post_type."_label' name='acc_custom_filter_settings[".$post_type."_labels]' size='50' value='".$options[$post_type_lables]."'></td>";
                echo "</tr>";
                
                 echo "<tr>";
                echo "<td><label for='".$post_type."_removecol'><strong>".$post_type."</strong></label></td>";
                echo "<td>Remove Columns</td>";
                echo "<td><input type='text' id='".$post_type."_removecol' name='acc_custom_filter_settings[".$post_type."_removecols]' size='50' value='".$options[$post_type_removecols]."'></td>";
                echo "</tr>";
                
                echo "<tr>";
                echo "<td colspan=3></td>";
                echo "</tr>";
            }
            echo "</table>";

    }
    
    
    function acc_settings_section_callback(  ) { 

	echo __( 'Add your custom fields and labels for sorting and filtering for the corresponding post type as comma separated values in the Parameters Box', 'test' );

    }


    function acc_options_page(  ) { 

            ?>
            <form action='options.php' method='post'>

                    <h2>Custom columns</h2>
					<p><a href="http://www.a2il.fr/nos-produits/advanced-custom-columns" target="_blank">Refer to the Documentation on a2il Website</a></p>
<h3>Quick doc</h3>
<p>Fields can be prefixed with following chars for a special meaning:</p>
<dl>
  <dt>%colname</dt><dd>Use the colname value to design a post_id, and return the post_title. Link to post edit</dd>
  <dt>!colname</dt><dd>Use the colname value to design a post_id, and return the post_title. Link to post admin list</dd>
  <dt>/colname</dt><dd>Use the colname value as an URL, so return a link to the value</dd>
  <dt>#type=column</dt><dd>Use type as a post_type, then return the count of post_type where column = this->ID</dd>
  <dt>@colID=colVAL</dt><dd>Return the colVAL of post where colID = this->ID</dd>
</dl>
					

                    <?php
                    settings_fields( 'pluginPage' );
                    do_settings_sections( 'pluginPage' );
                    submit_button();
                    ?>

            </form>
            <?php

    }
}
if( is_admin() )
    new ACCSettings();

