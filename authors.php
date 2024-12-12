<?php
//Load jQuery
wp_enqueue_script('jquery');

//The Javascript
function add_authors_size_footer(){ ?>
<script>
	function ajax_fetch_authors_size(){
		jQuery.ajax({
			  url: ajaxurl,
			  data: {
				  'action':'fetch_authors_size',
				  'query' : query
			  },
			  success:function(data) {
				  var json = JSON.parse(data)
				  jQuery('.authors_numbers').html(json.page.totalElements)

			  },
			  error: function(errorThrown){
				  window.alert(errorThrown);
			  }
		  });
	}

	jQuery(document).ready(function($) {
	ajax_fetch_authors_size();
	
	});
</script>
<?php }
add_action('wp_footer', 'add_authors_size_footer');

function fetch_authors_size() {
    if ( isset($_REQUEST) ) {
        $query = $_REQUEST['query'];
		$url_authors = "https://cetapsrepository.letras.up.pt/server/api/discover/browses/author/entries?scope=04efd651-8dba-4c42-a648-7e3cb24e6168";

		$arguments = array(
			'method' => 'GET',
		);
        
		$authors = wp_remote_retrieve_body(wp_remote_get($url_authors, $arguments));
		$response = $authors;

		if( is_wp_error($response) ) {
			$error_message = $response->get_error_message();
			return "Something went wrong: $error_message";
		}

		echo($response); 
    }
   die();
}
add_action( 'wp_ajax_fetch_authors_size', 'fetch_authors_size' );
add_action( 'wp_ajax_nopriv_fetch_authors_size', 'fetch_authors_size' );