[h2]event_store_event[/h2]

Called from event_store_event() when an event record is being stored.

Hook info is an array

'event' => the passed event details, ready for storage
'existing_event' => If the event already exists, a copy of the original event record from the database
'cancel' => false - set to true to cancel the operation.


