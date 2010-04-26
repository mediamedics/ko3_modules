<!-- <p>this is the body</p> -->

{set $count = count($list['sort_fields'])}
{foreach $list['rows'] as $row}
	
	<div class="row f-l">
		
		{set $i = 1}
		
		{foreach $row as $field => $value}

			<div rel="{$field}" class="col c{$i}-{$count}">{$value}</div>
			{set $i ++}
		{/foreach}
	</div>
	
{/foreach}
