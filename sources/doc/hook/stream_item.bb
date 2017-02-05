[h2]stream_item[/h2]


Called for each item processed for viewing by conversation(); 

The hook data consists of an array

	array(
		'mode' => current mode of conversation()
		'item' => item being processed
	);

	Set item['blocked'] to block the item from viewing. This action will not affect comment or sub-thread counts, so if there are three comments in a conversation and you block one, three comments will still be reported even though only two are visible. 
