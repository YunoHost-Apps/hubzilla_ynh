[h2]tagged[/h2]


This hook is called when a delivery is made which results in the recipient being tagged. 

The hook data is an array containing 

	array(
		'channel_id' => int,
		'item' => item structure of the delivered item from database,
		'body' => the body of the referenced item

	);

	Note: This hook is called before secondary delivery chains are invoked in the case of tagging a forum. This means that permissions and some item attributes will be those of the item before being re-packaged and before ownership of this item is given to the forum. 

