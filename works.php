<?php
//Load jQuery
wp_enqueue_script('jquery');

//The Javascript
function add_works_size_footer(){ ?>
<script>
	function ajax_fetch_works_size(){
		jQuery.ajax({
			  url: ajaxurl,
			  data: {
				  'action':'fetch_works_size',
				  'query' : query
			  },
			  success:function(data) {
				  var json = JSON.parse(data)
				  jQuery('.works_numbers').html(json._embedded.searchResult.page.totalElements)

			  },
			  error: function(errorThrown){
				  window.alert(errorThrown);
			  }
		  });
	}

	jQuery(document).ready(function($) {
	ajax_fetch_works_size();
	
	});
</script>
<?php }
add_action('wp_footer', 'add_works_size_footer');

function fetch_works_size() {
    if ( isset($_REQUEST) ) {
        $query = $_REQUEST['query'];
		$url_works = "https://cetapsrepository.letras.up.pt/server/api/discover/search/objects?scope=04efd651-8dba-4c42-a648-7e3cb24e6168";

		$arguments = array(
			'method' => 'GET',
		);
        
		$works = wp_remote_retrieve_body(wp_remote_get($url_works, $arguments));
		$response = $works;

		if( is_wp_error($response) ) {
			$error_message = $response->get_error_message();
			return "Something went wrong: $error_message";
		}

		echo($response); 
    }
   die();
}
add_action( 'wp_ajax_fetch_works_size', 'fetch_works_size' );
add_action( 'wp_ajax_nopriv_fetch_works_size', 'fetch_works_size' );