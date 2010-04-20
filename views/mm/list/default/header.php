<!-- <p>this is the header</p> -->

{set $count = count($list['sort_fields'])}

{set $i = 1}

<div class="row f-l">
	{foreach $list['sort_fields'] as $sort_field}
		<div class="col c{$i}-{$count} {$list['css_classes'][$sort_field]}">{$sort_field}</div>
	
		{set $i ++}
	{/foreach}
</div>