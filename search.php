<?php
//Load jQuery
wp_enqueue_script('jquery');

//Define AJAX URL
function myplugin_ajaxurl() {

   echo '<script type="text/javascript">
           var ajaxurl = "' . admin_url('admin-ajax.php') . '";
         </script>';
}
add_action('wp_head', 'myplugin_ajaxurl');

//The Javascript
function add_this_script_footer(){ ?>
<script>

	var filters = {authors: "", dateIssued: ""};
	var query = '';
	var urls = {
		url_obras: 'https://cetapsrepository.letras.up.pt/server/api/discover/search/objects?sort=dc.date.accessioned,DESC&scope=04efd651-8dba-4c42-a648-7e3cb24e6168&size=10&embed=thumbnail',
		url_authors: 'https://cetapsrepository.letras.up.pt/server/api/discover/facets/author?scope=04efd651-8dba-4c42-a648-7e3cb24e6168&size=5',
		url_subjects: 'https://cetapsrepository.letras.up.pt/server/api/discover/facets/subject?scope=04efd651-8dba-4c42-a648-7e3cb24e6168&size=5',
		url_has_content_in_original_bundle: 'https://cetapsrepository.letras.up.pt/server/api/discover/facets/has_content_in_original_bundle?scope=04efd651-8dba-4c42-a648-7e3cb24e6168&size=5',
		url_dateIssued: 'https://cetapsrepository.letras.up.pt/server/api/discover/facets/dateIssued?scope=04efd651-8dba-4c42-a648-7e3cb24e6168&size=5',
		url_itemtype: 'https://cetapsrepository.letras.up.pt/server/api/discover/facets/itemtype?scope=04efd651-8dba-4c42-a648-7e3cb24e6168&size=5'
		
	};
	
	var filter_buttons = {
		authors: [],
		subjects: [],
		has_content_in_original_bundle: [],
		itemtype: []
	};
	
	function ajax_fetch_search_result(query, url, filters) {
		jQuery.ajax({
			  url: ajaxurl,
			  data: {
				  'action':'fetch_search_result',
				  'query' : {query: query, filters: filters, url: url}
			  },
			  success:function(data) {
				  var json = JSON.parse(data)
				  build_objects(json);
			  },
			  error: function(errorThrown){
				  window.alert(errorThrown);
			  }
		  });
	} //Gets what was written in the search bar, the url with pagination and the filters and calls build_objects with the http request result
	
	function build_objects(json) {
		var objects = json._embedded.searchResult._embedded.objects;
		var current_page = json._embedded.searchResult.page.number;
		var totalpages = json._embedded.searchResult.page.totalPages;
		var totalElements = json._embedded.searchResult.page.totalElements;
		var url = json._links.self.href;
		var resultHTML = 
			`<style>
				@media only screen and (max-width: 768px){
					.item-content-title{
						font-size: 1.25em !important;
					}
					.item-content-name-date{
						font-size: 1em !important;
					}
				}
			</style>`;
		resultHTML += `<p style="margin:0 0 0.5rem 1rem; color: gray;">Showing ${(current_page*10)+1}-${(current_page+1)*10} of ${totalElements}</p>`;
		objects.map((object)=>{
			var metadata = object._embedded.indexableObject.metadata;
			var thumb = object._embedded.indexableObject._embedded.thumbnail;
			resultHTML += "<div style='display:flex; column-gap:1em; position:relative; align-items: center;' class='item'>";
				resultHTML += '<div style="display:flex; flex-direction:column;" class="item-content">';
			
					if(object.hitHighlights){ //Elementos que tem algo a ver com a pesquisa no titulo, tem uma tag <em> envolta do elemento pesquisado
						resultHTML += `<h2 class="item-content-title">${object.hitHighlights["dc.title"][0]}</h2>`; }
					else{ //Elementos da pesquisa que tem o termo em outra parte
						resultHTML += `<h2 class="item-content-title">${object._embedded.indexableObject.name}</h2>`; }

					resultHTML += '<p style="margin-bottom:20px" class="item-content-name-date">';
					if(metadata["dc.date.issued"]){ resultHTML += `${metadata["dc.date.issued"][0].value} - `}
					if(metadata["dc.contributor.author"].length != 0) {resultHTML += `${metadata["dc.contributor.author"][0].value}`}
					resultHTML += '</p>';
			
					if(metadata["dc.identifier.uri"].length != 0) {resultHTML += `<div style="position:absolute; bottom:20px;" class='wp-block-button secondary-button external-icon'><a class='wp-block-button__link wp-element-button' href="${metadata["dc.identifier.uri"].pop().value}">Access</a></div>`}

				resultHTML += '</div>';
				
				if(thumb){ resultHTML += `<div style="width:30% !important; height:100%; display:flex; justify-content:center; align-items:center;"><img style="max-width:100%; max-height:100%; box-shadow: 0 4px 8px 0 rgba(0, 0, 0, 0.2), 0 6px 20px 0 rgba(0, 0, 0, 0.19);" src="${thumb._links.content.href}"></div>` }

			resultHTML +='</div>';
		})
		
		if(Object.keys(objects).length == 0){
			resultHTML += "<h4>No results found.</h4>"
		}

		jQuery(".publications-list").html(resultHTML);
		
		build_pagination(current_page, totalpages,url,filters);

  } //builds the html object with the works

	function build_pagination (current_page, totalpages,url,filters) { //this looks terrible but you can only get so far with vanilla js
		var paginationHTML = "";
		if(totalpages>1) {
			paginationHTML = '';
			if(current_page > 1){
				paginationHTML += `<button style="padding:.1em; margin:.5em; width:2em;"; class="pages wp-block-button__link wp-element-button" value="0">1</button>`;
				paginationHTML += `<span>...</span>`;
			}
			for (i=0; i<totalpages; i++){
				if((current_page < 2 && i < 3) || (current_page > totalpages - 2 && i >= totalpages-3 && i<totalpages) || (i>=current_page-1 && i<=current_page+1)){
					if(i == current_page){
						paginationHTML += `<button style="padding:.1em; margin:.5em; width:2em; background-color: #2D1E16; color: #FFCF47; border: #FFCF47 3px solid;"; class="pages wp-block-button__link wp-element-button" value="${i}">${i+1}</button>`;
					}
					else {
						paginationHTML += `<button style="padding:.1em; margin:.5em; width:2em;"; class="pages wp-block-button__link wp-element-button" value="${i}">${i+1}</button>`;
					}
				   }
			}
			if(current_page < totalpages-2){
				paginationHTML += `<span>...</span>`;
				paginationHTML += `<button style="padding:.1em; margin:.5em; width:2em;"; class="pages wp-block-button__link wp-element-button" value="${totalpages-1}">${totalpages}</button>`;
			}
		}
		
		jQuery(".publications-pagination").html(paginationHTML);
		
		//add the click event to change the pages
		jQuery(".pages").click(function() {
			page = jQuery(this).attr("value");
			url += `&size=10&page=${page}&embed=thumbnail`;
			ajax_fetch_search_result("",url,filters);
		})
		
	} //builds the pagination of works
	
	function ajax_fetch_filters(urls) {
	jQuery.ajax({
          url: ajaxurl, // Since WP 2.8 ajaxurl is always defined and points to admin-ajax.php
          data: {
              'action':'fetch_filters', // This is our PHP function below
              'query' : {query: query, urls: urls}
          },
          success:function(data) {
			  var json = JSON.parse(data)
			  for (key in json) {
				  build_radio_button_filters(json[key], key);
			  }
			  

          },
          error: function(errorThrown){
              window.alert(errorThrown);
          }
      });
	}
	
	function build_radio_button_filters(json, key) {
		filter_buttons[key] = filter_buttons[key].concat(json._embedded.values);
		var next;
		if(json._links.next){
			next = json._links.next.href;
		}
		
		var hide = "hide";
		if(json._links.prev){hide = "";}
		
		var name;
		
		switch (key){
			case 'has_content_in_original_bundle':
				name = 'Has Files';
				break;
			case 'authors':
				name = 'Authors';
				break;
			case 'subjects':
				name = 'Subjects';
				break;
			case 'itemtype':
				name = 'Item Type';
				break;
			default:
				break;
		}
		
		var resultHTML = `
                <div class="label-wrap ${key}"><label class="filter-label" for="filter1">${name}</label>
                        <span></span><i class='dropdownArrow'></i>
                    </div>
                    <div class="filter-options ${key} ${hide}">
                        
                    `;
		
		filter_buttons[key].map((object, index)=>{
			var label;
			label = object.label;
			//var count = object.count;
			resultHTML += `<label class="option-container">${label}
							  <input style="opacity:0; cursor:pointer;" type="radio" name="${key}" value="${label}">
							  <span class="checkmark"></span>
						 </label>`;
		})
				
		if(next){resultHTML += `<button style="padding: calc(.1em + 2px)" class="wp-block-button__link wp-element-button" type="button" id="see_more_${key}">More</button>`};
		resultHTML += `</div>
                </div> `;
		
		jQuery(`#${key}`).html(resultHTML);
		
		jQuery(`input[type='radio'][name='${key}']`)
		.change(function(){
			if( jQuery(this).is(":checked") ){
				var val = jQuery(this).val();
				filters[key] = val;
				ajax_fetch_search_result(query, urls.url_obras, filters);
			}
		});
		
		if(next){
			jQuery(`#see_more_${key}`).click(function(){
				var aux_obj = {};
				aux_obj[`url_${key}`] = next;
				ajax_fetch_filters(aux_obj);
			})
		}
		
		jQuery(`.label-wrap.${key}`).click(function(){
			jQuery(`.filter-options.${key}`).toggleClass("hide");
			jQuery(`.label-wrap.${key} i`).toggleClass(" dropdownArrow");
			jQuery(`.label-wrap.${key} i`).toggleClass("dropdownArrowRotated");
			   })
		
		

	}
	
	function publications_search_overwrite() {
		var HTML = `
		<style>
			@media only screen and (max-width:768px) {
				.sidebar, .publications {
					width:100% !important;
				}
			}
		</style>
		<div style="width:30%;" class="sidebar">
			<div>
					<label for="search">Search Publications</label>
					<input type="text" id="search_bar" name="search">

					<label for="date">Years</label>
					<div class="date-container" style="flex-wrap: nowrap; gap:10px;">
						<input type="number" min="1900" max="2099" step="1" id="start_date" name="start_date"> <span>—</span>
						<input type="number" id="end_date" name="end_date">
					</div>

					<div class="wp-block-buttons" id="search_button">
						<div class="wp-block-button search-icon">
							<a class="wp-block-button__link wp-element-button">Search</a>
						</div>
					</div>

				<div class="wp-block-buttons">
					<div class="wp-block-button secondary-button external-icon"><a
							class="wp-block-button__link wp-element-button"
							href="https://cetapsrepository.letras.up.pt/collections/04efd651-8dba-4c42-a648-7e3cb24e6168">Repository
							Advanced Search</a></div>
				</div>

			</div>

			<div>
			<form>
					<div class="filter-group"id="authors">
					 authors
					</div>

					<div class="filter-group"id="subjects">
					 subjects
					</div>

					<div class="filter-group"id="itemtype">
					 itemtype
					</div>

					<div class="filter-group"id="has_content_in_original_bundle">
					 has_content_in_original_bundle
					</div>

					<div class="wp-block-buttons" id="remove_filters">
						<div class="wp-block-button secondary-button"><a
								class="wp-block-button__link wp-element-button">Remove Filters</a></div>
					</div>
				</form>
		</div>
	</div>
	<div class="publications" style="display:flex; flex-direction:column; justify-content:center; width:70%;">
		<div style="width:100%" class="publications-list"> Loading... </div>
		<div style="display:flex; align-items:center; justify-content:center; flex-wrap: wrap;" class="publications-pagination"> Pagination </div>"
	</div>`
		
	jQuery('.publications-search').html(HTML);
	}

jQuery(document).ready(function($) {
	publications_search_overwrite();
	ajax_fetch_search_result(query, urls.url_obras);
	ajax_fetch_filters(urls);
	
    $( "#search_button" ).click(function() {
	  query = $('#search_bar').val();
	  var start_date = jQuery('#start_date').val()
	  var end_date = jQuery('#end_date').val()
	  filters = {};
	  for (key in filter_buttons){
		  filter_buttons[key] = [];
	  }
	  if(start_date!="" && end_date!=""){
	  	filters['dateIssued'] = `%5B${start_date} TO ${end_date}%5D`
	  }
      ajax_fetch_search_result(query, urls.url_obras, filters);
	  ajax_fetch_filters(urls);
    });
	
	$("#remove_filters").click(function(){
		filters = {};
		for (key in filter_buttons){
		  filter_buttons[key] = [];
	  	}
		ajax_fetch_search_result(query, urls.url_obras, filters);
	  	ajax_fetch_filters(urls);
	})
	
});
</script>
<?php }
add_action('wp_footer', 'add_this_script_footer');

//The PHP

function fetch_search_result() {
    if ( isset($_REQUEST) ) {
        $query = $_REQUEST['query']['query'];
		$filters = $_REQUEST['query']['filters'];
		$url_obras = $_REQUEST['query']['url'];
		
		if($query != ''){
			$url_obras .= '&query='. $query;
		}

		if($filters['itemtype'] != ''){
			$url_obras .= '&f.itemtype=' . $filters['itemtype']. ',equals';
		}
		
		if($filters['authors'] != ''){
			$url_obras .= '&f.author=' . $filters['authors'] . ',equals';
		}
		
		if($filters['subjects'] != ''){
			$url_obras .= '&f.subject=' . $filters['subjects'] . ',equals';
		}
		
		
		if($filters['has_content_in_original_bundle'] != ''){
			$url_obras .= '&f.has_content_in_original_bundle=' . $filters['has_content_in_original_bundle'] . ',equals';
		}
		
		if($filters['dateIssued'] != ''){
			$url_obras .= '&f.dateIssued=' . $filters['dateIssued'] . ',equals';
		}

		$arguments = array(
			'method' => 'GET',
		);
        
		$obras = wp_remote_retrieve_body(wp_remote_get($url_obras, $arguments));
		$response = $obras;

		if( is_wp_error($response) ) {
			$error_message = $response->get_error_message();
			return "Something went wrong: $error_message";
		}

		echo($response); 
    }
   die();
}
add_action( 'wp_ajax_fetch_search_result', 'fetch_search_result' );
add_action( 'wp_ajax_nopriv_fetch_search_result', 'fetch_search_result' );

function fetch_filters() {
    if ( isset($_REQUEST) ) {
        $query = $_REQUEST['query']['query'];
		$url_authors = $_REQUEST['query']['urls']['url_authors'];
		$url_subjects = $_REQUEST['query']['urls']['url_subjects'];
		$url_itemtype = $_REQUEST['query']['urls']['url_itemtype'];
		$url_has_content_in_original_bundle = $_REQUEST['query']['urls']['url_has_content_in_original_bundle'];
		if($query != ''){
			$url_authors = $url_authors . '&query=' . $query;
			$url_subjects = $url_subjects . '&query=' . $query;
			$url_itemtype = $url_itemtype . '&query=' . $query;
			$url_has_content_in_original_bundle = $url_has_content_in_original_bundle . '&query=' . $query;
		}

		$arguments = array(
			'method' => 'GET',
		);
        
		$authors = wp_remote_retrieve_body(wp_remote_get($url_authors, $arguments));
		$subjects = wp_remote_retrieve_body(wp_remote_get($url_subjects, $arguments));
		$itemtype = wp_remote_retrieve_body(wp_remote_get($url_itemtype, $arguments));
		$has_content_in_original_bundle = wp_remote_retrieve_body(wp_remote_get($url_has_content_in_original_bundle, $arguments));
		$response = '{';
		if($authors){$response .= '"authors":'.$authors;}
		if($subjects){
			if($response !== '{'){$response .= ',';}
			$response .='"subjects": ' . $subjects;
		}
		if($itemtype){
			if($response !== '{'){$response .= ',';}
			$response .='"itemtype": ' . $itemtype;
		}
		if($has_content_in_original_bundle){
			if($response !== '{'){$response .= ',';}
			$response .='"has_content_in_original_bundle": ' . $has_content_in_original_bundle;
		}
		$response .= '}';

		if( is_wp_error($response) ) {
			$error_message = $response->get_error_message();
			return "Something went wrong: $error_message";
		}

		echo($response); 
    }
   die();
}
// This bit is a special action hook that works with the WordPress AJAX functionality.
add_action( 'wp_ajax_fetch_filters', 'fetch_filters' );
add_action( 'wp_ajax_nopriv_fetch_filters', 'fetch_filters' );


