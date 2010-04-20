<!-- <p> this is the pagination</p> -->


{if($list['prev_page'])}
	
	<a href="{@list.previous_url}">Prev</a> 
	
{/if}


{foreach $list['pages'] as $value}

	{if($value !== NULL)}

		{if($value === $list['page_nr'])}
			<strong>{$value}</strong>
		{else}
			<a href="{@list.clean_url}/{$value}">{$value}</a> 
		{/if}
	{else}
		..
	{/if}
{/foreach}


{if($list['next_page'])}

 	<a href="{@list.next_url}">Next</a>
	
{/if}
			
		
		
	