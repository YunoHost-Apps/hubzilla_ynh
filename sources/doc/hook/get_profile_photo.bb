[h2]get_profile_photo[/h2]

Called when fetching the content of the default profile photo for a local channel in mod_photo.


Hook arguments:

'imgscale'   => integer resolution requested (4, 5, or 6)
'channel_id' => channel_id of requested profile photo
'default'    => filename of default profile photo of this imgscale
'data'       => empty string
'mimetype'   => empty string


If 'data' is set, this data will be used instead of the data obtained from the database search for the profile photo.
If 'mimetype' is set, this mimetype will be used instead of the mimetype obtained from the database or the default profile photo mimetype.


