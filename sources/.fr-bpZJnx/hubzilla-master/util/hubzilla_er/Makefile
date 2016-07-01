#makefile for schemaspy


#NOTE: This makefile sucks. It needs to have all run the script, and check that schemaspy and mysqlconn are present.

schemaspy::
	wget "http://downloads.sourceforge.net/project/schemaspy/schemaspy/SchemaSpy%205.0.0/schemaSpy_5.0.0.jar?r=http%3A%2F%2Fsourceforge.net%2Fprojects%2Fschemaspy%2F&ts=1447051240&use_mirror=iweb"

mysqlconn::
	wget http://central.maven.org/maven2/mysql/mysql-connector-java/5.1.17/mysql-connector-java-5.1.17.jar

#TODO: install java and graphviz or check it is there

install: schemaspy mysqlconn

run:	
	../util/schemaspy
