[h2]logger[/h2]

Called when making an entry to the application logfile

Hook data:

	array(
		'filename' => name of logfile relative to application basedir. String.
		'loglevel' => the log level of this log entry, if this is higher than the configured maximum loglevel
				this hook will not be called. Integer.
		'message'  => The formatted log message, ready for logging. String.
		'logged'   => boolean, default is false. Set to true to prevent the normal logfile entry to be made 
				(e.g. if the plugin is configured to handle this aspect of the function, or if it is determined 
				that this log entry should not be made)
	)

