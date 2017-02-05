[h2]get_photo[/h2]

Called when fetching the content of photos (except for profile photos) in mod_photo.


Hook arguments:

'imgscale'    => integer resolution requested
'resource_id' => resource_id of requested photo
'photo'       => array of matching photo table rows after querying for the photo
'allowed'     => whether or not access to this resource is allowed



