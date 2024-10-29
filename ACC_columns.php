<?php

class ACC {

  var $fields;           // Les champs (meta ou pas) du post-type
  var $field_headings;   // Le nom de ces champs
  var $unset_cols;       // Les colonnes à supprimer
  var $post_type;        // Le type de post

  // Les caractères spéciaux donnant lieu à des traitements particuliers
  public static $SpecialFunctions = array(
				"%" => 'SFIndirectionEdit',
				"!" => 'SFIndirection',
				"/" => 'SFWebsite',
				"#" => 'SFCount',
				"@" => 'SFAt',
				);
  public static $SpecialFieldFunctions = array(
				"%" => 'SFSkipChar',
				"/" => 'SFSkipChar',
				"!" => 'SFSkipChar',
				"#" => 'SFNullField',
				"@" => 'SFNullField',
				);
  public static $SpecialValueFunctions = array(
				"%" => 'SFIndirectionValue',
				"!" => 'SFIndirectionValue',
				// "/" => 'SFWebsiteValue',
				);

  static function SFSkipChar($col) { return substr($col,1);}
  static function SFNullField($col) { return "";}

  // La fonction d'indirection de post ...
  // @col1 = col2 Find a post_type where col1=this->ID, then return the col2 value of the post
  static function SFAt ($column, $post_id) {
    global $wpdb;
    
    $cols=explode("=",substr($column,1));
    $IDcol = $cols[0]; // The col (meta_key) with id
    $VALcol = $cols[1]; // The meta_key to return

    $pid = get_post_meta($post_id, $IDcol, true);
    $pval = get_post_meta($pid, $VALcol, true);

    // return "Find:".$pval." on Post=".$post_id." giving pid=".$pid;
    return $pval;
  }
  
  // La fonction de comptage de référence aux de posts
  //  <dt>#col1=col2</dt><dd>Use col1 as a post_type, then return the count of post_type where col2 column = this->ID</dd>
  // ... Et éventuellement on va là: http://www.a2il.fr/wp-admin/edit.php?s&post_status=all&post_type=compagny&action=-1&m=0&city&phone&progress&potentialite&contact=societe&website=www.stgo.fr&filter_action=Filtrer&paged=1&action2=-1
  static function SFCount($column, $post_id) {
    global $wpdb;

    $cols = explode("=",substr($column,1));
    // The post_type to check
    $ptype = $cols[0];
    $column = $cols[1];

    // On fabrique la requête
    $dbq = "SELECT count(*) FROM " . $wpdb->prefix . "postmeta WHERE post_id in (select ID from ".$wpdb->prefix ."posts WHERE post_type = '".$ptype."') AND meta_key='" . $column . "' AND meta_value = '".$post_id."'";
    $result = $wpdb->get_row($dbq, ARRAY_N);
    return "<a href='/wp-admin/edit.php?s&post_status=all&post_type=".esc_html($ptype)."&action=-1&m=0&".esc_html($column)."=".esc_html($post_id)."&filter_action=Filtrer&paged=1&action2=-1'>".$result[0]."</a>";

  }

  // La fonction de récupération indirection
  static function SFIndirectionValue($column, $value) {
    return get_the_title($value);
  }

  // La fonction de rendu d'indirection de post
  static function SFIndirectionEdit($column, $post_id) {
    // La valeur est une indirection sur un numéro de post
    $column = substr($column,1);
    $pid = get_post_meta($post_id, $column, true);
    return "<a href='/wp-admin/post.php?post=".$pid."&action=edit'>".get_the_title($pid)."</a>";
  }

  // La fonction de rendu d'indirection avec Edit
  static function SFIndirection($column, $post_id) {
    // La valeur est une indirection sur un numéro de post
    $column = substr($column,1);
    $pid = get_post_meta($post_id, $column, true);
    // return "<a href='/wp-admin/post.php?post=".$pid."&action=edit'>".get_the_title($pid)."</a>";
    return "<a href='/wp-admin/edit.php?s&post_status=all&post_type=".esc_html(get_post_type($pid))."&action=-1&m=0&post_id=".esc_html($pid)."&filter_action=Filtrer&paged=1&action2=-1'>".get_the_title($pid)."</a>";
  }

  // Retourne le nom du site
  static function SFWebsiteValue($column, $value) {
    return $value;
  }
  
  // Retourne un lien vers un site
  static function SFWebsite($column, $post_id) {
    $column = substr($column,1);
    $site = get_post_meta($post_id, $column, true);
    return "<a href='http://".$site."'>".$site."</a>";
  }
  
  /**
   * Constructeur
   */
  public function __construct() {

    // Récupération de la configuration
    $acc_admin_custom_filter_options = get_option('acc_custom_filter_settings');

    // et le type de post en cours
    $this->post_type = esc_attr(array_key_exists('post_type',$_GET)?$_GET['post_type']:(array_key_exists('post_type',$_POST)?$_POST['post_type']:""));

    // Les champs concernés dans le post
    $acc_cf_fields = esc_attr($acc_admin_custom_filter_options[$this->post_type]);
    $this->acc_cf_fields_comma_separated = $acc_cf_fields;
    // Leur label
    $acc_cf_labels = esc_attr($acc_admin_custom_filter_options[$this->post_type . '_labels']);
    // Et les champs à enlever
    $acc_cf_removecols = esc_attr($acc_admin_custom_filter_options[$this->post_type . '_removecols']);

    // Si il n'y a pas de labels, on prends les noms des champs
    if ($acc_cf_labels == NULL)
      $acc_cf_labels = $acc_cf_fields;

    // Et on crée un tableau pour chaque élément
    $this->acc_cf_fields = explode(',', $acc_cf_fields);
    $this->acc_cf_labels = explode(',', $acc_cf_labels);

    // On crée un tableau associatif pour le label des champs
    $count=0;
    foreach($this->acc_cf_fields as $field) {
      $firstChar = substr($field,0,1);
      if ($firstChar && array_key_exists($firstChar, ACC::$SpecialFieldFunctions)) {
	$sfunc = ACC::$SpecialFieldFunctions[$firstChar];
	$field = ACC::$sfunc($field);
      }
			   
      $this->acc_cf_fieldlabels[$field] = $this->acc_cf_labels[$count];
      $count++;
    }
    
    $this->acc_cf_removecols = explode(',', $acc_cf_removecols);

    // Puis on enregistre les fonctions qui vont faire les manips

    // Fonction qui rajoute les noms des colonnes
    add_filter('manage_edit-' . $this->post_type . '_columns', array($this, 'acc_table_head'));
    // Fonction qui récupère la valeur des colonnes
    add_action('manage_' . $this->post_type . '_posts_custom_column', array($this, 'acc_table_content'), 10, 2);
    // Fonction qui rajoute les colonnes triables
    add_filter('manage_edit-' . $this->post_type . '_sortable_columns', array($this, 'acc_table_sorting'));
    // Fonction qui traite les requêtes de tri
    add_filter('request', array($this, 'acc_column_orderby'));

    // Fonction de traitement des filtres
    add_filter('parse_query', array($this, 'acc_admin_posts_filter'));

    // Rajoute les éléments de filtrage
    add_action('restrict_manage_posts', array($this, 'acc_admin_posts_filter_restrict_manage_posts'));

    $this->fields = $this->acc_cf_fields;
    $this->field_headings = $this->acc_cf_labels;

    $this->unset_cols = $this->acc_cf_removecols;
  }

  /* function acc_table_head($defaults) {
     $i=0;
     foreach($this->fields as $field){
     $defaults[$field] = $this->field_headings[$i];
     $i++;
     }

     return $defaults;
     } */

  /*
   * Fabrication des colonnes
   */
  function acc_table_head($defaults) {
    // On rajoute les colonnes supplémentaires
    $i = 0;
    foreach ($this->fields as $field) {
      $defaults[$field] = $this->field_headings[$i];
      $i++;
    }

    // Et là on supprime les colonnes dont on ne veut pas
    foreach ($this->unset_cols as $ucols) {
      unset($defaults[$ucols]);
    }
    return $defaults;
  }

  function acc_table_content($column, $post_id) {
    if ($this->acc_cf_fields_comma_separated != NULL) {
      foreach ($this->fields as $field) {
	if ($column == $field) {
	  $firstChar = substr($field,0,1);
	  if (array_key_exists($firstChar, ACC::$SpecialFunctions)) {
	    $sfunc = ACC::$SpecialFunctions[$firstChar];
	    echo ACC::$sfunc($column, $post_id);
	    // echo ACC::{$SpecialFunctions[$firstChar]}($column, $post_id);
	  } else {
	    echo get_post_meta($post_id, $field, true);
	  }
	}
      }
    }
  }

  /**
   * Rajout des colonnes triables
   */
  function acc_table_sorting($columns) {
    if ($this->acc_cf_fields_comma_separated != NULL) {
      foreach ($this->fields as $field) {
	$firstChar = substr($field,0,1);
	if (array_key_exists($firstChar, ACC::$SpecialFieldFunctions)) {
	  $sfunc = ACC::$SpecialFieldFunctions[$firstChar];
	  $field = ACC::$sfunc($field);
	}
	$columns[$field] = $field;
      }
    }
    return $columns;
  }

  /**
   * Rajout du paramètre de tri sur une méta-valeur
   */
  function acc_column_orderby($vars) {
    if ($this->acc_cf_fields_comma_separated != NULL) {
      foreach ($this->fields as $field) {
	$firstChar = substr($field,0,1);
	if (array_key_exists($firstChar, ACC::$SpecialFieldFunctions)) {
	  $sfunc = ACC::$SpecialFieldFunctions[$firstChar];
	  $field = ACC::$sfunc($field);
	}
	if (isset($vars['orderby']) && $field == $vars['orderby']) {
	  $vars = array_merge($vars, array(
					   'meta_key' => $field,
					   'orderby' => 'meta_value'
					   ));
	}
      }
    }
    return $vars;
  }

  /**
   * Traitement du filtrage des posts
   *
   * 
   */
  function acc_admin_posts_filter($query) {
    global $pagenow;

    // Si on est dans la liste des posts, et qu'il y a des colonnes supplémentaires...
    if (is_admin() && $pagenow == 'edit.php' && $this->acc_cf_fields_comma_separated != NULL) {
      $getFilterAction = esc_attr($_GET['filter_action']?$_GET['filter_action']:$_POST['filter_action']);
      // Et qu'on demande un filtre... Sur le type de post qu'on sait gérer
      if ($getFilterAction && $query->get('post_type') == $this->post_type) {
	$meta_filter = array();
	// On regarde dans l'URL si il y a des champs à nous... Et si il n'y en a qu'un
	$c = 0;
	foreach ($this->fields as $field) {
	  $firstChar = substr($field,0,1);
	  // Si il y a un champ spécial, on rétablit la valeur de meta_key
	  if (array_key_exists($firstChar, ACC::$SpecialFieldFunctions)) {
	    $sfunc = ACC::$SpecialFieldFunctions[$firstChar];
	    $field = ACC::$sfunc($field);
	  }

	  // Puis on récupère la valeur du champ de l'URL
	  $fieldValue = esc_attr($_GET[$field]?$_GET[$field]:$_POST[$field]);
	  if ($fieldValue != NULL) {
	    $meta_filter[] = array(
				   'key' => $field,
				   'value' => $fieldValue
				   );
	    $c++;
	  }
	}

	// And special treatment for specific ID's
	$pids = esc_attr($_GET['post_id']?$_GET['post_id']:$_POST['post_id']);
	if ($pids)
	  $query->set('post__in', explode(",",$pids));
	
	$query->set('meta_query', $meta_filter);
      }
    } /* if (is_admin()) */

    // Et quoi qu'il arrive, on retourne le query
    return $query;
  }

  /**
   * Affichage des éléments de filtrage sur les champs.
   */
  function acc_admin_posts_filter_restrict_manage_posts() {
    global $wpdb;
    global $pagenow;

    // Si on est dans une liste et que le type de post a des valeurs de colonne custom
    if (is_admin() && $pagenow == 'edit.php' && $this->acc_cf_fields_comma_separated != NULL) {

      // Alors on va regarder pour l'ensemble des custom si il est présent.
      foreach ($this->fields as $field) {
	$redir=false;
	$firstChar = substr($field,0,1);

	// Si le champ custom est de type particulier ...
	if (array_key_exists($firstChar, ACC::$SpecialFieldFunctions)) {
	  // On recalcule la valeur de la vraie meta_key
	  $sfunc = ACC::$SpecialFieldFunctions[$firstChar];
	  $field = ACC::$sfunc($field); $redir=true;
	}

	// Skip empty fields
	if ($field == "") continue;

	// Et on fabrique la requête.
	$dbq = "SELECT DISTINCT meta_value FROM " . $wpdb->prefix . "postmeta WHERE meta_key='" . $field . "' and meta_value<>''";
	// en allant chercher dans le post_type
	$dbq .= " and post_id in (select ID from ".$wpdb->prefix."posts where post_type = '".$this->post_type."') order by meta_value";
	$result = $wpdb->get_results($dbq);
	
	echo '<select name="' . $field . '">';
	echo '<option value="">- '.$this->acc_cf_fieldlabels[$field].' -</option>';

	// Pour chacune des valeurs possibles du champ...
	foreach ($result as $print) {
	  if ($redir && array_key_exists($firstChar, ACC::$SpecialValueFunctions)) {
	    $sfunc = ACC::$SpecialValueFunctions[$firstChar];
	    $pname =  ACC::$sfunc($firstChar . $field, $print->meta_value);
	  }
	  ?>
	    <option value="<?php echo $print->meta_value; ?>" <?php
          // Si le paramètre $field fait partie de la requête...
          if (isset($_GET[$field]) || isset($_POST[$field])) {
	    // Alors on regarde la valeur qu'il a
	    $getFields = esc_attr($_GET[$field]?$_GET[$field]:$_POST[$field]);
	    if ($getFields && ($getFields == $print->meta_value)) {
	      echo 'selected="selected"';
	    }
	  }
	  ?>><?php echo ($redir && array_key_exists($firstChar, ACC::$SpecialValueFunctions))?$pname:($print->meta_value); ?></option>	
	  <?php
	}
	echo '</select>';
      }
    }
  }

}
?>
